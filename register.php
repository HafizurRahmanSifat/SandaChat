<?php
session_start();
require("config.php");

if(isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$user = new User();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');

$email = $_POST['email'] ?? '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $email = 'someone@gmail.com';
}

$password = $_POST['password'] ?? '';
    
    try {
        // Validate inputs
        if(empty($name) || empty($password)) {
            throw new Exception("Username and password are required");
        }
        
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Check if email exists
        $conn = $user->getConnection();
        $check = $conn->prepare("SELECT id FROM user WHERE user_name = ?");
        $check->execute([$name]);
        
        if($check->rowCount() > 0) {
            $error = "Username already registered. Try diffrent one.";
        } else {
            $pass_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Start transaction
            $conn->beginTransaction();
            
            try {
                // Insert user
                $stmt = $conn->prepare("INSERT INTO user (user_name, email, pass_hash) VALUES (?, ?, ?)");
                $stmt->execute([$name, $email, $pass_hash]);
                $user_id = $conn->lastInsertId();

                // Insert default profile picture
                $stmt = $conn->prepare("INSERT INTO profile_pic (user_id, old_name, stored_name) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $name, "user.jpeg"]);

                $conn->commit();
                
                $_SESSION['user'] = $user_id;
                header("Location: index.php");
                exit();
            } catch(PDOException $e) {
                $conn->rollBack();
                throw $e;
            }
        }
    } catch(PDOException $e) {
        $error = "Registration failed: " . $e->getMessage();
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Chat Application</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container { margin-top: 100px; }
        .card { border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    </style>    
    <link rel="icon" href="user.ico" type="image/x-icon">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="text-center mb-0">Create Account</h3>
                    </div>
                    <div class="card-body p-4">
                        <?php if($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" autocomplete="off">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       required pattern=".{2,50}" title="2-50 characters">
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address(Optional)</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="you can leave blank">
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       required minlength="4" title="At least 4 characters">
                            </div>
                            <button type="submit" class="btn btn-primary w-100 btn-lg">
                                Register Now
                            </button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p class="mb-0">Already have an account? 
                                <a href="login.php" class="text-decoration-none">Sign In</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>