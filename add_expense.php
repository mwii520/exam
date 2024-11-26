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
    $amount = $_POST['amount'];
    $description = $_POST['description'];

    // Check if budget is set for the category
    $budget_sql = "SELECT budget FROM budgets WHERE user_id = ? AND category = ?";
    $stmt = $conn->prepare($budget_sql);
    $stmt->bind_param("is", $user_id, $category);
    $stmt->execute();
    $budget_result = $stmt->get_result();
    $stmt->close();

    if ($budget_result->num_rows > 0) {
        // Budget found, check if the expense is within the budget
        $budget = $budget_result->fetch_assoc()['budget'];
        if ($amount > $budget) {
            $error_msg = "Expense amount exceeds the set budget for this category.";
        } else {
            // Insert expense if it's within the budget
            $sql = "INSERT INTO expenses (user_id, category, amount, description) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isss", $user_id, $category, $amount, $description);

            if ($stmt->execute()) {
                $success_msg = "Expense added successfully.";
            } else {
                $error_msg = "Failed to add expense. Please try again.";
            }
            $stmt->close();
        }
    } else {
        // No budget found for the selected category
        $error_msg = "Please set a budget for this category before adding an expense.";
    }
}

// Fetch expenses for this user
$sql = "SELECT * FROM expenses WHERE user_id = ?";
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

        .form-control {
            border-radius: 8px;
        }

        .navbar {
            display: none;
        }

        .table {
            border-radius: 8px;
            overflow: hidden;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2 class="text-center text-white">Dashboard</h2>
        <a href="dashboard.php">Dashboard</a>
        <a href="profile.php">Profile</a>
        <a href="add_expense.php" class="active">Add Expense</a>
        
        <a href="set_budget.php">Set Budget</a>
        
        <a href="reports.php">Reports</a>
        <a href="login.php" class="text-danger">Logout</a>
    </div>

    <!-- Content -->
    <div class="content">
        <div class="card">
            <h2 class="text-center">Add Expense</h2>
            
            <!-- Display success or error messages -->
            <?php if (isset($success_msg)) echo "<div class='alert alert-success'>$success_msg</div>"; ?>
            <?php if (isset($error_msg)) echo "<div class='alert alert-danger'>$error_msg</div>"; ?>

            <form method="POST">
                <!-- Category Field -->
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

                <!-- Amount Field -->
                <div class="mb-3">
                    <label for="amount" class="form-label">Amount</label>
                    <input type="number" class="form-control" id="amount" name="amount" required>
                </div>

                <!-- Description Field -->
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <input type="text" class="form-control" id="description" name="description" required>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary w-100">Add Expense</button>
            </form>
        </div>

        <!-- View Expenses -->
        <div class="card">
            <h2 class="text-center">Your Expenses</h2>
            <?php if ($result->num_rows > 0) { ?>
                <table class="table table-striped table-hover">
                    <thead class="table-light">
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
                                    <a href="add_expense.php?delete_expense_id=<?php echo $row['expense_id']; ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Are you sure you want to delete this expense?');">
                                       Delete
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php } else { ?>
                <p class="text-center text-muted">No expenses found.</p>
            <?php } ?>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
