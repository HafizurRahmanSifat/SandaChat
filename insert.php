<?php
session_start();
require("config.php");

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit(json_encode(['success' => false, 'error' => 'Access denied']));
}

try {
    $user = new USER();
    $con = $user->getConnection();
    $con->beginTransaction();

    // Check or create chat
    $stmt = $con->prepare("SELECT id FROM chat LIMIT 1");
    $stmt->execute();
    $chat = $stmt->fetch();

    if (!$chat) {
        $stmt = $con->prepare("INSERT INTO chat (title, created_by) VALUES ('General Chat', :user_id)");
        $stmt->execute([':user_id' => $_SESSION['user']]);
        $chatId = $con->lastInsertId();
    } else {
        $chatId = $chat['id'];
    }

    // Insert message
    $stmt = $con->prepare("INSERT INTO message (chat_id, user_id, content) 
                          VALUES (:chat_id, :user, :content)");
    $stmt->execute([
        ':chat_id' => $chatId,
        ':user' => $_SESSION['user'],
        ':content' => ($_POST['message'] ?? '')
    ]);
    
    $messageId = $con->lastInsertId();
    $uploadedFiles = [];

    // Process file uploads
    if (!empty($_FILES['files'])) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        foreach ($_FILES['files']['tmp_name'] as $key => $tmpName) {
            $fileType = mime_content_type($tmpName);

            if ($_FILES['files']['size'][$key] > 10 * 1024 * 1024) {
                throw new Exception('File size exceeds 10MB limit');
            }

            $extension = pathinfo($_FILES['files']['name'][$key], PATHINFO_EXTENSION);
            $storedName = sprintf('%s_%s.%s', 
                uniqid(), 
                bin2hex(random_bytes(8)), 
                $extension
            );
            $destination = $uploadDir . $storedName;

            if (move_uploaded_file($tmpName, $destination)) {
                $stmt = $con->prepare("INSERT INTO uploads 
                                      (user_id, message_id, file_name, stored_name, deleted_at) 
                                      VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_SESSION['user'],
                    $messageId,
                    $_FILES['files']['name'][$key],
                    $storedName,
                    date('Y-m-d H:i:s')
                ]);
                $uploadedFiles[] = $storedName;
            }
        }
    }

    $con->commit();
    echo json_encode(['success' => true, 'files' => $uploadedFiles]);

} catch (PDOException $e) {
    $con->rollBack();
    error_log('Database Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
} catch (Exception $e) {
    $con->rollBack();
    error_log('Upload Error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>