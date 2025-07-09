<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once("config.php");



if (!isset($_SESSION['user'])) {
    http_response_code(403);
    exit('Access denied');
}

try {
    $user = new USER();
    $con = $user->getConnection();




    $last_date  = $_GET['last_date']  ?? null;
    $since_date = $_GET['since']     ?? null;

    if ($since_date) {
        // load messages created *after* since_date
        $sql = "
    SELECT 
            m.id,
            u.id AS user_id,
            u.user_name, 
            p.stored_name AS profile_pic,
            m.content, 
            m.created_at,
            up.file_name,
            up.stored_name AS file_path
        FROM message m
        JOIN user u ON m.user_id = u.id
        LEFT JOIN profile_pic p ON u.id = p.user_id
        LEFT JOIN uploads up ON m.id = up.message_id
    WHERE m.created_at > :since_date
    ORDER BY m.created_at ASC
  ";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(':since_date', $since_date);
    } else {
        // your existing pagination for older messages
        $sql = "
    SELECT 
            m.id,
            u.id AS user_id,
            u.user_name, 
            p.stored_name AS profile_pic,
            m.content, 
            m.created_at,
            up.file_name,
            up.stored_name AS file_path
        FROM message m
        JOIN user u ON m.user_id = u.id
        LEFT JOIN profile_pic p ON u.id = p.user_id
        LEFT JOIN uploads up ON m.id = up.message_id
    WHERE (:last_date IS NULL OR m.created_at < :last_date)
    ORDER BY m.created_at DESC
    LIMIT 20
  ";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(':last_date', $last_date);
    }
    $stmt->execute();


    $messages = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $msgId = $row['id'];
        if (!isset($messages[$msgId])) {
            $messages[$msgId] = [
                'user_id' => $row['user_id'],
                'user_name' => $row['user_name'],
                'profile_pic' => $row['profile_pic'],
                'content' => $row['content'],
                'created_at' => $row['created_at'],
                'files' => []
            ];
        }
        if (!empty($row['file_path'])) {
            $messages[$msgId]['files'][] = [
                'name' => $row['file_name'],
                'path' => $row['file_path']
            ];
        }
    }

    foreach ($messages as $msg) {
        $myMsg = ($msg['user_id'] == $_SESSION['user']) ? 'my-message' : '';
        echo '<div class="message-card ' . $myMsg .
            '" data-created-at="' . htmlspecialchars($msg['created_at']) . '">';
        echo '  <div class="d-flex align-items-center gap-2 mb-2">';
        $profilePic = !empty($msg['profile_pic'])
            ? 'uploads/' . $msg['profile_pic']
            : 'uploads/user.jpeg';
        echo '    <img src="' . $profilePic . '" class="rounded-circle" width="40" height="40" alt="' . htmlspecialchars($msg['user_name']) . '">';
        echo '    <div>';
        echo '      <strong>' . htmlspecialchars($msg['user_name']) . '</strong>';
        echo '      <small class="text-muted ms-2">' . date('M j, Y g:i a', strtotime($msg['created_at'])) . '</small>';
        echo '    </div>';
        echo '  </div>';

        // Copy button
        echo '  <a href="#" class="message-card-copy">Copy</a>';

        echo '  <div class="mb-1 message-content"><pre><code>' . htmlspecialchars($msg['content']) . '</code></pre></div>';

        if (!empty($msg['files'])) {
            echo '  <div class="mt-2">';
            foreach ($msg['files'] as $file) {
                $filePath = 'uploads/' . $file['path'];
                if (file_exists($filePath)) {
                    $fileName = htmlspecialchars($file['name']);
                    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                    if (str_starts_with(mime_content_type($filePath), 'image/')) {
                        echo '<img src="' . $filePath . '" class="img-fluid rounded" alt="' . $fileName . '" style="max-height: 200px">';
                    } else {
                        if ($extension == "php") {
                            echo '<a href="download.php?file=' . urlencode($filePath) . '&name='.$fileName.'" class="btn btn-sm btn-outline-primary">📎 ' . htmlentities($fileName) . '</a>';
                        } else {
                            echo '<a href="' . $filePath . '" download="' . $fileName . '" class="btn btn-sm btn-outline-primary">📎 ' . $fileName . '</a>';
                        }
                    }
                } else {
                    echo "<div class='alert alert-danger'>[File deleted]</div>";
                }
            }
            echo '  </div>';
        }

        echo '</div>';
    }


    // Output load more button if there are messages
    if (!empty($messages)) {
        $oldestDate = end($messages)['created_at'];
        echo '<button class="btn btn-secondary w-100 load-more" 
                data-last-date="' . htmlspecialchars($oldestDate) . '">
                Load Older Messages
              </button>';
    }
} catch (PDOException $e) {
    error_log('Database Error: ' . $e->getMessage());
    echo '<div class="alert alert-danger">Error loading messages</div>';
}
