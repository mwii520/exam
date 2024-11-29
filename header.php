<!-- header.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* Your styles here */
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">ZOi</a>
            <div class="navbar-nav">
                <!-- Notification Link -->
                <a class="nav-item nav-link" href="notifications.php">
                    <i class="fas fa-bell"></i>
                    <?php
                    // Fetch unread notification count
                    $notification_count_sql = "SELECT COUNT(*) AS unread_count FROM Notifications WHERE user_id = ? AND is_read = 0";
                    $stmt = $conn->prepare($notification_count_sql);
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $unread_count = $result->fetch_assoc()['unread_count'];

                    // Display badge if there are unread notifications
                    if ($unread_count > 0) {
                        echo "<span class='badge bg-danger'>" . $unread_count . "</span>";
                    }
                    ?>
                </a>
            </div>
        </div>
    </nav>
</body>
</html>
