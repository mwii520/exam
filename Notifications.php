<?php  
session_start();
require 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch notifications for the logged-in user
$notifications_query = "SELECT * FROM Notifications WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($notifications_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications_result = $stmt->get_result();
$stmt->close();

// Fetch unread notifications count
$unread_notifications_query = "SELECT COUNT(*) AS unread_count FROM Notifications WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($unread_notifications_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$unread_count_result = $stmt->get_result()->fetch_assoc();
$unread_count = $unread_count_result['unread_count'];
$stmt->close();

// Fetch budget data for the logged-in user
$budget_query = "SELECT category, budget FROM budgets WHERE user_id = ?";
$stmt = $conn->prepare($budget_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$budget_result = $stmt->get_result();
$stmt->close();

// Mark a notification as read
if (isset($_GET['mark_read'])) {
    $notification_id = $_GET['mark_read'];

    $mark_read_query = "UPDATE Notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?";
    $stmt = $conn->prepare($mark_read_query);
    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: notifications.php");
    exit();
}

// Delete a notification
if (isset($_GET['delete_notification'])) {
    $notification_id = $_GET['delete_notification'];

    $delete_query = "DELETE FROM Notifications WHERE notification_id = ? AND user_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: notifications.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #F4F6F9;
            font-family: 'Arial', sans-serif;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100%;
            background-color: #AEC6D2;
            padding-top: 20px;
        }

        .sidebar a {
            display: block;
            padding: 12px;
            color: white;
            text-decoration: none;
            font-size: 18px;
            margin-bottom: 20px;
        }

        .sidebar a:hover {
            background-color: #C1E2DB;
            border-radius: 5px;
        }

        .sidebar i {
            margin-right: 10px;
        }

        .content {
            margin-left: 270px;
            padding: 40px;
        }

        .card {
            border-radius: 12px;
            background-color: #FFFFFF;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            padding: 20px;
        }

        .btn-mark-read {
            background-color: #B2C5CE;
            border-color: #B2C5CE;
            color: white;
        }

        .btn-mark-read:hover {
            background-color: #C8E6CF;
            border-color: #C8E6CF;
        }

        .btn-delete {
            background-color: #FF6F61;
            border-color: #FF6F61;
            color: white;
        }

        .btn-delete:hover {
            background-color: #FF4F3B;
            border-color: #FF4F3B;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 14px;
            color: #AEC6D2;
        }

        .notification-badge {
            background-color: #FF5733;
            color: white;
            padding: 2px 5px;
            border-radius: 50%;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2 class="text-center text-white">Notifications</h2>
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
        <a href="add_expense.php"><i class="fas fa-plus-circle"></i> Add Expense</a>
        <a href="set_budget.php"><i class="fas fa-wallet"></i> Set Budget</a>
        <a href="reports.php"><i class="fas fa-file-alt"></i> Reports</a>
        <a href="notifications.php" class="active"><i class="fas fa-bell"></i> Notifications
            <?php if ($unread_count > 0) { ?>
                <span class="notification-badge"><?php echo $unread_count; ?></span>
            <?php } ?>
        </a>
        <a href="login.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Content -->
    <div class="content">
        <h2 class="text-center mb-4">Your Notifications</h2>

        <?php if ($notifications_result->num_rows > 0) { ?>
            <div class="card">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Budget Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Fetch and display notifications along with corresponding budget
                        while ($notification = $notifications_result->fetch_assoc()) { 
                            $notification_message = htmlspecialchars($notification['message']);
                            $is_read = $notification['is_read'] ? 'Read' : 'Unread';
                            $notification_id = $notification['notification_id'];
                            
                            // Assuming the message might contain information about the budget category
                            $budget_amount = ''; // Default value

                            // Check if the notification relates to a specific budget category and amount
                            $category_name = ''; // Placeholder for category name if applicable
                            if (strpos($notification_message, 'budget for') !== false) {
                                // Find the related category and budget amount (this can be adjusted based on your notification system)
                                while ($budget = $budget_result->fetch_assoc()) {
                                    if (strpos($notification_message, $budget['category']) !== false) {
                                        $category_name = $budget['category'];
                                        $budget_amount = number_format($budget['budget'], 2);
                                        break;
                                    }
                                }
                            }
                        ?>
                            <tr>
                                <td><?php echo $notification_message; ?></td>
                                <td><?php echo $is_read; ?></td>
                                <td><?php echo $budget_amount ? "$budget_amount UGX" : 'N/A'; ?></td>
                                <td>
                                    <?php if (!$notification['is_read']) { ?>
                                        <a href="notifications.php?mark_read=<?php echo $notification_id; ?>" class="btn btn-mark-read">Mark as Read</a>
                                    <?php } ?>
                                    <a href="notifications.php?delete_notification=<?php echo $notification_id; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this notification?');">Delete</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } else { ?>
            <div class="alert alert-info">No notifications found.</div>
        <?php } ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
