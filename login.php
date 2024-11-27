<?php
session_start();
require 'connection.php';

$error_message = ''; // Ensure the variable is always initialized

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Fetch user data from the database
    $query = "SELECT * FROM Users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verify the password using password_verify
        if (password_verify($password, $user['password_hash'])) {
            // Store user data in session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];

            // Redirect to profile page
            header("Location: profile.php");
            exit();
        } else {
            $error_message = "Incorrect password.";
        }
    } else {
        $error_message = "No account found with this email.";
    }
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Students Finance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #F4F6F9;
            font-family: 'Arial', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            display: flex;
            width: 90%;
            max-width: 1000px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .login-form {
            padding: 50px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-form h2 {
            color: #364C84;
            font-size: 28px;
            margin-bottom: 30px;
            text-align: center;
        }

        .login-form .form-label {
            color: #364C84;
            font-weight: bold;
        }

        .login-form .btn-primary {
            background-color: #B2C5CE;
            border-color: #B2C5CE;
            font-weight: bold;
            padding: 12px;
        }

        .login-form .btn-primary:hover {
            background-color: #C8E6CF;
            border-color: #C8E6CF;
        }

        .login-form .form-control:focus {
            border-color: #364C84;
            box-shadow: 0 0 5px rgba(54, 76, 132, 0.5);
        }

        .welcome-section {
            flex: 1;
            background: linear-gradient(135deg, #AEC6D2, #364C84);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 50px;
        }

        .welcome-section h3 {
            font-size: 30px;
            font-weight: bold;
            margin-bottom: 20px;
            text-align: center;
        }

        .welcome-section p {
            font-size: 16px;
            margin-bottom: 30px;
            text-align: center;
            line-height: 1.6;
        }

        .welcome-section a {
            text-decoration: none;
            font-weight: bold;
            padding: 10px 20px;
            background-color: #B2C5CE;
            color: #fff;
            border-radius: 6px;
            transition: background 0.3s ease;
        }

        .welcome-section a:hover {
            background-color: #C8E6CF;
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }

            .welcome-section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<div class="login-container">
    <!-- Welcome Section -->
    <div class="welcome-section">
        <h3>Welcome Back!</h3>
        <p>Log in to manage your finances with ease. Track your expenses, set budgets, and gain control of your financial journey.</p>
        
    </div>

    <!-- Login Form Section -->
    <div class="login-form">
        <h2><i class="fas fa-user-circle"></i> Login</h2>

        <?php if ($error_message): ?>
            <div class="alert alert-danger text-center"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="mb-4">
                <label for="email" class="form-label">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                </div>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>

        <div class="text-center mt-3">
            <a href="forgot_password.php" class="text-decoration-none" style="color: #364C84;"><i class="fas fa-question-circle"></i> Forgot your password?</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
