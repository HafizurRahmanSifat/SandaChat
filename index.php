<?php
session_start();
require("config.php");
date_default_timezone_set('Asia/Dhaka');

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Chat</title>
    <style>

    </style>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="user.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>

<body>
    <div class="p-2">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><a href="index.php" class="title-logo">SandaChat</a></h2>
            <a href="logout.php" class="btn btn-outline-danger">Logout</a>
        </div>

        <div class="border rounded p-3 mb-3" id="chatDiv"></div>

        <div class="message-form">
            <input type="file" id="fileInput" class="d-none" multiple>

            <div class="textarea-wrapper position-relative mb-2">
                <textarea id="messageBox" class="form-control pr-5 pl-5" placeholder="Type your message..." rows="3"></textarea>

                <label for="fileInput" class="attach-file-btn">
                    📎 
                </label>
            </div>

            <div id="filePreview" class="file-preview mb-2"></div>
            <button id="submitButton" class="btn btn-primary w-100">Send Message</button>
        </div>

    </div>

    <script>
        $(function() {
            let filesToUpload = [];
            const maxSize = 5 * 1024 * 1024; // 5MB
            let lastTimestamp = null;
            let isLoading = false;

            // Handle file selection and preview
            $('#fileInput').change(function() {
                filesToUpload = [];
                $('#filePreview').empty();

                [...this.files].forEach(file => {
                    if (file.size > maxSize) {
                        alert(`File ${file.name} exceeds the size limit (5MB)`);
                        return;
                    }

                    filesToUpload.push(file);
                    $('#filePreview').append(`
                <div class="upload-item">
                    <span>📄 ${file.name}</span>
                    <small>(${(file.size / 1024).toFixed(1)}KB)</small>
                </div>
            `);
                });
            });

            // Submit message and files
            $('#submitButton').click(() => {
                const message = $('#messageBox').val().trim();
                if (!message && filesToUpload.length === 0) return;

                const formData = new FormData();
                formData.append('message', message);
                filesToUpload.forEach(file => formData.append('files[]', file));

                $.ajax({
                    url: 'insert.php',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: () => {
                        $('#messageBox').val('');
                        $('#filePreview').empty();
                        filesToUpload = [];
                        loadNewMessages();
                    },
                    error: (xhr) => {
                        console.error('Error:', xhr.responseText);
                        alert('Error sending message. Please try again.');
                    }
                });
            });

            // Load initial messages
            function initialLoad() {
                if (isLoading) return;
                isLoading = true;
                $.get('get_messages.php')
                    .done(html => {
                        $('#chatDiv').html(html);
                        updateLastTimestamp();
                        scrollToBottom();
                    })
                    .always(() => isLoading = false);
            }

            // Load new messages since lastTimestamp
            function loadNewMessages() {
                if (!lastTimestamp || isLoading) return;
                isLoading = true;
                $.get('get_messages.php', {
                        since: lastTimestamp
                    })
                    .done(html => {
                        const $newCards = $('<div>').html(html).find('.message-card');
                        $newCards.each(function() {
                            const ts = $(this).data('created-at');
                            if (ts > lastTimestamp) {
                                $('#chatDiv').prepend(this);
                            }
                        });
                        updateLastTimestamp();
                    })
                    .always(() => isLoading = false);
            }

            // Update lastTimestamp to newest loaded message
            function updateLastTimestamp() {
                const firstCard = $('#chatDiv .message-card').first();
                if (firstCard.length) {
                    lastTimestamp = firstCard.data('created-at');
                }
            }

            // Scroll to bottom
            function scrollToBottom() {
                const c = $('#chatDiv')[0];
                c.scrollTop = c.scrollHeight;
            }

            // Scroll trigger for loading older messages
            $('#chatDiv').on('scroll', function() {
                if (this.scrollTop < 100 && !isLoading && this.scrollHeight > this.clientHeight) {
                    const loadMoreBtn = $('.load-more').last();
                    if (loadMoreBtn.length) {
                        loadMoreBtn.trigger('click');
                    }
                }
            });

            // Load older messages on demand
            $('#chatDiv').on('click', '.load-more', function() {
                const lastDate = $(this).data('last-date');
                $(this).remove();
                loadMessages(lastDate, false);
            });

            // Copy to clipboard
            $(document).on('click', '.message-card-copy', function(e) {
                e.preventDefault();
                const $btn = $(this);
                const text = $btn.closest('.message-card')
                    .find('.message-content code')
                    .text().trim();

                if (!text) return;

                const doCopied = () => {
                    $btn.addClass('copied').text('Copied!');
                    setTimeout(() => $btn.removeClass('copied').text('Copy'), 2000);
                };

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text)
                        .then(doCopied)
                        .catch(err => {
                            console.error('Clipboard API error:', err);
                            fallbackCopy(text, doCopied);
                        });
                } else {
                    fallbackCopy(text, doCopied);
                }
            });

            function fallbackCopy(text, onSuccess) {
                const ta = document.createElement('textarea');
                ta.value = text;
                document.body.appendChild(ta);
                ta.select();
                try {
                    document.execCommand('copy');
                    onSuccess();
                } catch (err) {
                    console.error('execCommand copy failed:', err);
                    alert('Failed to copy. Please copy manually.');
                }
                document.body.removeChild(ta);
            }

            // Start initial load and auto-refresh
            initialLoad();
            setInterval(loadNewMessages, 5000); // every 5s
        });
    </script>
</body>

</html>