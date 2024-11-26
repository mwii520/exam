<?php
session_start();
require 'connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch spending data by category
$spending_sql = "SELECT category, SUM(amount) AS total_spent FROM expenses WHERE user_id = ? GROUP BY category";
$stmt = $conn->prepare($spending_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$spending_result = $stmt->get_result();

$categories = [];
$spent_totals = [];
while ($row = $spending_result->fetch_assoc()) {
    $categories[] = $row['category'];
    $spent_totals[$row['category']] = $row['total_spent'];
}
$stmt->close();

// Fetch budget data for each category
$budget_sql = "SELECT category, budget FROM budgets WHERE user_id = ?";
$stmt = $conn->prepare($budget_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$budget_result = $stmt->get_result();

$budget_totals = [];
while ($row = $budget_result->fetch_assoc()) {
    $budget_totals[$row['category']] = $row['budget'];
}
$stmt->close();

// Prepare data for the bar chart (Budget vs Spent)
$chart_data = [];
foreach ($categories as $category) {
    $spent = isset($spent_totals[$category]) ? $spent_totals[$category] : 0;
    $budget = isset($budget_totals[$category]) ? $budget_totals[$category] : 0;
    $remaining = $budget - $spent;
    $chart_data[$category] = [
        'spent' => $spent,
        'budget' => $budget,
        'remaining' => $remaining
    ];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        #barChart {
            max-width: 600px;  /* Adjusted size for better appearance */
            height: 300px !important; /* Reduced height */
        }

        .welcome-message {
            font-size: 24px;
            font-weight: bold;
            color: #364C84;
            text-align: center;
            margin-bottom: 30px;
        }

        .category-container {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .category-box {
            background-color: #D1E0DC; /* Soft Mint Green */
            padding: 20px;
            margin: 10px;
            border-radius: 10px;
            width: 48%;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .category-box h5 {
            color: #364C84;
        }

        .category-box p {
            font-size: 16px;
        }

        .category-box .remaining {
            color: #FF5733; /* Red for remaining balance */
            font-weight: bold;
        }

        .category-box .spent {
            color: #36A2EB; /* Blue for spent amount */
        }

        .category-box .budget {
            color: #4BC0C0; /* Green for the budget */
        }

    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2 class="text-center text-white">Dashboard</h2>
        <a href="dashboard.php">Dashboard</a>
        <a href="profile.php">Profile</a>
        <a href="add_expense.php">Add Expense</a>
        
        <a href="set_budget.php">Set Budget</a>
        
        <a href="reports.php">Reports</a>
        <a href="login.php" class="text-danger">Logout</a>
    </div>

    <!-- Content -->
    <div class="content">
        <div class="welcome-message">
            Welcome, <?php echo $_SESSION['full_name']; ?>! <br>
            Here is an overview of your budget and spending.
        </div>

        <!-- Category Balance Containers -->
        <div class="category-container">
            <?php foreach ($categories as $category) { 
                $spent = $chart_data[$category]['spent'];
                $budget = $chart_data[$category]['budget'];
                $remaining = $chart_data[$category]['remaining'];
            ?>
                <div class="category-box">
                    <h5><?php echo $category; ?></h5>
                    <p class="spent">Spent: $<?php echo number_format($spent, 2); ?></p>
                    <p class="budget">Budget: $<?php echo number_format($budget, 2); ?></p>
                    <p class="remaining">Remaining: $<?php echo number_format($remaining, 2); ?></p>
                </div>
            <?php } ?>
        </div>

        <!-- Budget vs Spent Bar Chart -->
        <div class="card mt-3 p-4">
            <h5>Budget vs Spending Overview</h5>
            <canvas id="barChart"></canvas>  <!-- Bar Chart -->
        </div>
    </div>

    <script>
        // Prepare data for Bar Chart
        const budgetData = <?php echo json_encode($chart_data); ?>;
        const labels = Object.keys(budgetData);
        const budgetValues = labels.map(category => budgetData[category].budget);
        const spentValues = labels.map(category => budgetData[category].spent);
        const remainingValues = labels.map(category => budgetData[category].remaining);

        // Initialize Bar Chart using Chart.js
        const ctx = document.getElementById('barChart').getContext('2d');
        const barChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Budget',
                        data: budgetValues,
                        backgroundColor: '#36A2EB',
                        borderColor: '#36A2EB',
                        borderWidth: 1
                    },
                    {
                        label: 'Spent',
                        data: spentValues,
                        backgroundColor: '#FF6384',
                        borderColor: '#FF6384',
                        borderWidth: 1
                    },
                    {
                        label: 'Remaining',
                        data: remainingValues,
                        backgroundColor: '#4BC0C0',
                        borderColor: '#4BC0C0',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                },
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
