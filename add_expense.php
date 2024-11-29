<?php  
session_start();
require 'connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle deleting an expense
if (isset($_GET['delete_expense_id'])) {
    $expense_id = $_GET['delete_expense_id'];

    $delete_sql = "DELETE FROM expenses WHERE expense_id = ? AND user_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("ii", $expense_id, $user_id);

    if ($stmt->execute()) {
        $delete_msg = "Expense deleted successfully.";
    } else {
        $delete_msg = "Failed to delete the expense. Please try again.";
    }
    $stmt->close();
}

// Handle adding an expense
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category = $_POST['category'];
    $amount = floatval($_POST['amount']);
    $description = $_POST['description'];

    // Fetch the budget and total expenses for this category
    $budget_sql = "
        SELECT 
            budgets.budget, 
            IFNULL(SUM(expenses.amount), 0) AS total_expense 
        FROM budgets 
        LEFT JOIN expenses 
        ON budgets.user_id = expenses.user_id AND budgets.category = expenses.category 
        WHERE budgets.user_id = ? AND budgets.category = ?
    ";
    $stmt = $conn->prepare($budget_sql);
    $stmt->bind_param("is", $user_id, $category);
    $stmt->execute();
    $budget_result = $stmt->get_result();
    $stmt->close();

    if ($budget_result->num_rows > 0) {
        $row = $budget_result->fetch_assoc();
        $budget = floatval($row['budget']);
        $total_expense = floatval($row['total_expense']);
        $remaining_budget = $budget - $total_expense;

        if ($amount > $remaining_budget) {
            $error_msg = "The expense amount exceeds the remaining budget for the $category category.";
        } else {
            // Insert expense if it's within the remaining budget
            $sql = "INSERT INTO expenses (user_id, category, amount, description) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issd", $user_id, $category, $amount, $description);

            if ($stmt->execute()) {
                $success_msg = "Expense added successfully.";
            } else {
                $error_msg = "Failed to add expense. Please try again.";
            }
            $stmt->close();
        }
    } else {
        $error_msg = "Please set a budget for this category before adding an expense.";
    }
}

// Fetch expenses for this user
$sql = "SELECT * FROM expenses WHERE user_id = ? ORDER BY date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Expenses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #F4F6F9;
            font-family: Arial, sans-serif;
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
        .content {
            margin-left: 270px;
            padding: 30px;
        }
        .card {
            padding: 30px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        .btn-update {
            width: 100%;
            background-color: #D3D3D3;
        }
        .btn-update:hover {
            background-color: #C8E6CF;
        }
        #expensesTable {
            display: none;
        }
    </style>
    <script>
        function toggleTable() {
            const table = document.getElementById('expensesTable');
            table.style.display = table.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</head>
<body>
    <div class="sidebar">
        <h2 class="text-center text-white">ZOi</h2>
        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="add_expense.php"><i class="fas fa-plus-circle"></i> Add Expense</a>
        <a href="set_budget.php"><i class="fas fa-wallet"></i> Set Budget</a>
        <a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
        <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="content">
        <div class="card">
            <h2>Add Expense</h2>
            <?php if (isset($success_msg)) echo "<div class='alert alert-success'>$success_msg</div>"; ?>
            <?php if (isset($error_msg)) echo "<div class='alert alert-danger'>$error_msg</div>"; ?>
            <?php if (isset($delete_msg)) echo "<div class='alert alert-info'>$delete_msg</div>"; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-control" id="category" name="category" required>
                        <option value="Food">Food</option>
                        <option value="Transportation">Transportation</option>
                        <option value="Entertainment">Entertainment</option>
                        <option value="Tuition">Tuition</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="amount" class="form-label">Amount</label>
                    <input type="number" class="form-control" id="amount" name="amount" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <input type="text" class="form-control" id="description" name="description" required>
                </div>
                <button type="submit" class="btn btn-update">Add Expense</button>
            </form>
        </div>

        <div class="card">
            <h2>Expenses</h2>
            <button class="btn btn-primary" onclick="toggleTable()">Show Expenses</button>
            <div id="expensesTable">
                <?php if ($result->num_rows > 0) { ?>
                    <table class="table table-striped mt-3">
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
                            <?php while ($row = $result->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                                    <td><?php echo htmlspecialchars($row['amount']); ?></td>
                                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                                    <td><?php echo htmlspecialchars($row['date']); ?></td>
                                    <td>
                                        <a href="add_expense.php?delete_expense_id=<?php echo $row['expense_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?');">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } else { ?>
                    <p class="text-center text-muted mt-3">No expenses found.</p>
                <?php } ?>
            </div>
        </div>
    </div>
</body>
</html>

