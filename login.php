<?php
session_start();
require 'connection.php';

$error_message = '';

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

<!-- HTML form for the Login page -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #FFFEF6; /* Soft light background */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            background-color: #AEC6D2; /* Soft Teal */
            color: #364C84; /* Deep Blue */
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 50px auto;
        }

        h2 {
            color: #364C84; /* Deep Blue */
            text-align: center;
            margin-bottom: 30px;
            font-weight: bold;
        }

        .btn-primary {
            background-color: #5D6D7E; /* Soft Teal */
            border: none;
            color: #FFFFFF;
        }

        .btn-primary:hover {
            background-color: #3B4A58; /* Darker Teal on hover */
            color: #FFFFFF;
        }

        .form-label {
            color: #364C84;
            font-weight: bold;
        }

        .form-container input,
        .form-container select,
        .form-container textarea {
            border: 1px solid #CBCEEA; /* Soft Lavender */
            background-color: #FFFFFF;
            color: #364C84;
        }

        .form-container input:focus,
        .form-container select:focus,
        .form-container textarea:focus {
            border-color: #364C84; /* Deep Blue */
            box-shadow: 0 0 5px rgba(54, 76, 132, 0.5);
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #364C84;
        }

        .footer a {
            color: #5D6D7E; /* Soft Teal */
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container card">
    <h2>Login</h2>

    <!-- Display error message -->
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?= $error_message ?></div>
    <?php endif; ?>

    <!-- Login Form -->
    <form method="POST" action="login.php">
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>

    <!-- Link to Signup Page -->
    <div class="footer mt-4">
        <p>Don't have an account? <a href="signup.php">Create one</a></p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
