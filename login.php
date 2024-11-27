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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #F4F6F9; /* Profile page background color */
            font-family: 'Arial', sans-serif;
        }

        .card {
            border-radius: 12px;
            background-color: #FFFFFF; /* White background for card */
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            padding: 30px;
            max-width: 500px;
            margin: 60px auto;
        }

        h2 {
            color: #364C84; /* Deep blue */
            text-align: center;
            margin-bottom: 30px;
        }

        .btn-primary {
            background-color: #B2C5CE;
            border-color: #B2C5CE;
        }

        .btn-primary:hover {
            background-color: #C8E6CF;
            border-color: #C8E6CF;
        }

        .form-label {
            color: #364C84;
            font-weight: bold;
        }

        .form-control:focus {
            border-color: #364C84;
            box-shadow: 0 0 5px rgba(54, 76, 132, 0.5);
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #AEC6D2;
        }

        .footer a {
            color: #5D6D7E;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="card">
            <h2><i class="fas fa-user-circle"></i> Login</h2>

            <!-- Display error message -->
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
