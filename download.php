<?php
session_start();

// --- OPTIONAL: enforce login/auth here ---
// if (!isset($_SESSION['user_id'])) {
//     http_response_code(403);
//     exit('Unauthorized');
// }

if (!isset($_GET['file'])) {
    http_response_code(400);
    exit('No file specified');
}

$filePath = urldecode($_GET['file']);
$fileName = $_GET['name'];
echo $filePath;
// 2. Check file exists
if (!file_exists($filePath)) {
    http_response_code(404);
    exit('File not found');
}

// 3. Send download headers
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

// 4. Read the file and exit
readfile($filePath);
exit;
?>
