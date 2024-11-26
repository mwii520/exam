<?php
session_start();
require 'connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$selected_category = null;

// Fetch available categories for selection
$categories_query = "SELECT DISTINCT category FROM expenses WHERE user_id = ?";
$categories_stmt = $conn->prepare($categories_query);
$categories_stmt->bind_param("i", $user_id);
$categories_stmt->execute();
$categories_result = $categories_stmt->get_result();

// Handle category selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_category'])) {
    $selected_category = $_POST['selected_category'];

    // Fetch expenses for the selected category
    $expenses_query = "
        SELECT expense_id, category, amount, description, date 
        FROM expenses 
        WHERE user_id = ? AND category = ?
        ORDER BY date DESC";
    $expenses_stmt = $conn->prepare($expenses_query);
    $expenses_stmt->bind_param("is", $user_id, $selected_category);
    $expenses_stmt->execute();
    $expenses_result = $expenses_stmt->get_result();
}

// Handle delete request for an expense
if (isset($_GET['delete_expense_id'])) {
    $expense_id = $_GET['delete_expense_id'];

    $delete_query = "DELETE FROM expenses WHERE expense_id = ? AND user_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("ii", $expense_id, $user_id);
    if ($delete_stmt->execute()) {
        $delete_msg = "Expense deleted successfully.";
    } else {
        $delete_msg = "Failed to delete the expense. Please try again.";
    }
    $delete_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #FFFEF6; /* Light beige background */
            font-family: 'Arial', sans-serif;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100%;
            background-color: #AEC6D2; /* Soft Teal */
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
            background-color: #C1E2DB; /* Soft Light Blue */
            border-radius: 5px;
        }

        .content {
            margin-left: 250px;
            padding: 30px;
        }

        .card {
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background-color: #FFFFFF;
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

        .table th {
            background-color: #C1E2DB;
        }

        .table tbody tr:hover {
            background-color: #f5f5f5;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2 class="text-center text-white">Student Finance</h2>
        <a href="dashboard.php">Dashboard</a>
        <a href="profile.php">Profile</a>
        <a href="add_expense.php">Add Expense</a>
        <a href="set_budget.php">Set Budget</a>
        <a href="reports.php" class="active">Reports</a>
        <a href="login.php" class="text-danger">Logout</a>
    </div>

    <!-- Content -->
    <div class="content">
        <h2 class="text-center mt-3">Expense History</h2>

        <?php if (isset($delete_msg)) { ?>
            <div class="alert alert-success"><?php echo $delete_msg; ?></div>
        <?php } ?>

        <!-- Category Selection Form -->
        <div class="card mb-4">
            <h5>Select a Category</h5>
            <form method="POST" action="reports.php">
                <div class="mb-3">
                    <label for="selected_category" class="form-label">Category</label>
                    <select name="selected_category" id="selected_category" class="form-control" required>
                        <option value="">-- Select Category --</option>
                        <?php while ($category_row = $categories_result->fetch_assoc()) { ?>
                            <option value="<?php echo htmlspecialchars($category_row['category']); ?>" 
                                <?php echo ($selected_category === $category_row['category']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category_row['category']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100">View History</button>
            </form>
        </div>

        <!-- Expense Table -->
        <?php if (isset($expenses_result)) { ?>
            <div class="card">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($expenses_result->num_rows > 0) { 
                            while ($row = $expenses_result->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['category']); ?></td>
                                <td><?php echo number_format($row['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($row['description']); ?></td>
                                <td><?php echo htmlspecialchars($row['date']); ?></td>
                                <td>
                                    <a href="reports.php?delete_expense_id=<?php echo $row['expense_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this expense?');">Delete</a>
                                </td>
                            </tr>
                        <?php } } else { ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No expenses found for this category.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
