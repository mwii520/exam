<?php
session_start();
require 'connection.php';
require 'TCPDF-main/tcpdf.php'; // Include TCPDF library

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$selected_category = null;
$expenses_result = null; // Initialize to prevent undefined variable issues

// Fetch available categories for selection
$categories_query = "SELECT DISTINCT category FROM expenses WHERE user_id = ?";
$categories_stmt = $conn->prepare($categories_query);
$categories_stmt->bind_param("i", $user_id);
$categories_stmt->execute();
$categories_result = $categories_stmt->get_result();

// Handle category selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_category'])) {
    $selected_category = $_POST['selected_category'];

    if ($selected_category && $selected_category !== 'All') {
        // Fetch expenses and budgets for the selected category
        $expenses_query = "
            SELECT expense_id, category, amount, description, date, 'Expense' AS type 
            FROM expenses 
            WHERE user_id = ? AND category = ? 
            UNION 
            SELECT NULL AS expense_id, category, budget AS amount, NULL AS description, NULL AS date, 'Budget' AS type 
            FROM budgets 
            WHERE user_id = ? AND category = ? 
            ORDER BY date DESC";
        $expenses_stmt = $conn->prepare($expenses_query);
        $expenses_stmt->bind_param("isis", $user_id, $selected_category, $user_id, $selected_category);
        $expenses_stmt->execute();
        $expenses_result = $expenses_stmt->get_result();
    } elseif ($selected_category === 'All') {
        // Fetch expenses and budgets for all categories
        $expenses_query = "
            SELECT expense_id, category, amount, description, date, 'Expense' AS type 
            FROM expenses 
            WHERE user_id = ? 
            UNION 
            SELECT NULL AS expense_id, category, budget AS amount, NULL AS description, NULL AS date, 'Budget' AS type 
            FROM budgets 
            WHERE user_id = ? 
            ORDER BY category, date DESC";
        $expenses_stmt = $conn->prepare($expenses_query);
        $expenses_stmt->bind_param("ii", $user_id, $user_id);
        $expenses_stmt->execute();
        $expenses_result = $expenses_stmt->get_result();
    }
}

// Generate PDF report
if (isset($_POST['generate_pdf'])) {
    $pdf = new TCPDF();
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Student Finance');
    $pdf->SetTitle('Expense Report');
    $pdf->SetHeaderData('', 0, 'Student Finance - Expense Report', 'Generated on: ' . date('Y-m-d'));
    $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
    $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);
    $pdf->SetMargins(15, 27, 15);
    $pdf->AddPage();

    $html = '<h2>Expense and Budget Report</h2>';
    $html .= '<p><strong>Category:</strong> ' . ($selected_category ?? 'All Categories') . '</p>';
    $html .= '<table border="1" cellpadding="5">';
    $html .= '<thead>
                <tr>
                    <th><strong>Category</strong></th>
                    <th><strong>Amount</strong></th>
                    <th><strong>Description</strong></th>
                    <th><strong>Date</strong></th>
                    <th><strong>Type</strong></th>
                </tr>
              </thead>';
    $html .= '<tbody>';

    if ($expenses_result->num_rows > 0) {
        while ($row = $expenses_result->fetch_assoc()) {
            $html .= '<tr>
                        <td>' . htmlspecialchars($row['category']) . '</td>
                        <td>' . number_format($row['amount'], 2) . '</td>
                        <td>' . htmlspecialchars($row['description'] ?? 'N/A') . '</td>
                        <td>' . htmlspecialchars($row['date'] ?? 'N/A') . '</td>
                        <td>' . htmlspecialchars($row['type']) . '</td>
                      </tr>';
        }
    } else {
        $html .= '<tr><td colspan="5">No records found.</td></tr>';
    }

    $html .= '</tbody></table>';
    $pdf->writeHTML($html, true, false, true, false, '');

    $pdf->Output('Expense_Report.pdf', 'D'); // Download PDF
    exit();
}

?>
<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #F4F6F9; /* Same background color as the budget page */
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
            transition: background-color 0.3s ease;
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
            color: #fff;
        }

        .table tbody tr:hover {
            background-color: #f5f5f5;
        }

        @media (max-width: 768px) {
            .content {
                margin-left: 0;
                padding: 15px;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2 class="text-center text-white">Students Finance</h2>
        <a href="profile.php"><i class="fas fa-user"></i>Profile</a>
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
        <a href="add_expense.php"><i class="fas fa-plus-circle"></i>Add Expense</a>
        <a href="set_budget.php"><i class="fas fa-dollar-sign"></i>Set Budget</a>
        <a href="reports.php" class="active"><i class="fas fa-chart-line"></i>Reports</a>
        <a href="login.php" class="text-danger"><i class="fas fa-sign-out-alt"></i>Logout</a>
    </div>

    <!-- Content -->
    <div class="content">
        <h2 class="text-center mt-3">Expense and Budget Reports</h2>

        <!-- Category Selection Form -->
        <div class="card mb-4">
            <form method="POST" action="reports.php">
                <div class="mb-3">
                    <label for="selected_category" class="form-label">Category</label>
                    <select name="selected_category" id="selected_category" class="form-control" required>
                        <option value="">-- Select Category --</option>
                        <option value="All" <?php echo ($selected_category === 'All') ? 'selected' : ''; ?>>All Categories</option>
                        <?php while ($category_row = $categories_result->fetch_assoc()) { ?>
                            <option value="<?php echo htmlspecialchars($category_row['category']); ?>" 
                                <?php echo ($selected_category === $category_row['category']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category_row['category']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100">View Report Overview</button>
            </form>
        </div>

        <!-- Expense and Budget Table -->
        <?php if ($expenses_result !== null) { ?>
            <div class="card">
                <h5>Report Overview for <?php echo htmlspecialchars($selected_category ?? 'All Categories'); ?></h5>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Date</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($expenses_result->num_rows > 0) {
                            while ($row = $expenses_result->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                                    <td><?php echo number_format($row['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['description'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['date'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['type']); ?></td>
                                </tr>
                        <?php }
                        } else { ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No records found.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <form method="POST" class="mt-3">
                    <input type="hidden" name="selected_category" value="<?php echo htmlspecialchars($selected_category); ?>">
                    <button type="submit" name="generate_pdf" class="btn btn-primary w-100">Download PDF Report</button>
                </form>
            </div>
        <?php } ?>
    </div>
</body>
</html>
