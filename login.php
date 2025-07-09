<?php
session_start();
require("config.php");

if(isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$user = new USER();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim(strtolower($_POST['email'])) ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $stmt = $user->getConnection()->prepare("SELECT id, pass_hash FROM user WHERE email = ? OR user_name = ?");
        $stmt->execute([$username, $username]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if($userData && password_verify($password, $userData['pass_hash'])) {
            $_SESSION['user'] = $userData['id'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid email or password";
        }
    } catch(PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Chat Application</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="user.ico" type="image/x-icon">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Login</h3>
                    </div>
                    <div class="card-body">
                        <?php if($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <!-- Updated label -->
                                <label for="email" class="form-label">Email or Username</label>
                                <input type="text" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                        <div class="mt-3 text-center">
                            Don't have an account? <a href="register.php">Register here</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>