<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$user_id = getCurrentUserId();

// Get date range from URL parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-t'); // Last day of current month
$report_type = $_GET['report_type'] ?? 'monthly';

// Validate dates
if (!strtotime($start_date) || !strtotime($end_date)) {
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-t');
}

// Set predefined date ranges based on report type
switch ($report_type) {
    case 'weekly':
        $start_date = date('Y-m-d', strtotime('last monday'));
        $end_date = date('Y-m-d', strtotime('next sunday'));
        break;
    case 'monthly':
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
        break;
    case 'yearly':
        $start_date = date('Y-01-01');
        $end_date = date('Y-12-31');
        break;
    case 'custom':
        // Use provided dates
        break;
}

// Get summary statistics
$stmt = $db->prepare("
    SELECT 
        SUM(CASE WHEN c.type = 'income' THEN t.amount ELSE 0 END) as total_income,
        SUM(CASE WHEN c.type = 'expense' THEN t.amount ELSE 0 END) as total_expenses,
        COUNT(*) as total_transactions,
        AVG(CASE WHEN c.type = 'expense' THEN t.amount END) as avg_expense
    FROM transactions t 
    JOIN categories c ON t.category_id = c.id 
    WHERE t.user_id = ? AND t.transaction_date BETWEEN ? AND ?
");
$stmt->execute([$user_id, $start_date, $end_date]);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

$total_income = $summary['total_income'] ?? 0;
$total_expenses = $summary['total_expenses'] ?? 0;
$net_income = $total_income - $total_expenses;
$total_transactions = $summary['total_transactions'] ?? 0;
$avg_expense = $summary['avg_expense'] ?? 0;

// Get income by category
$stmt = $db->prepare("
    SELECT c.name, c.color, SUM(t.amount) as total, COUNT(t.id) as count
    FROM transactions t 
    JOIN categories c ON t.category_id = c.id 
    WHERE t.user_id = ? AND c.type = 'income' AND t.transaction_date BETWEEN ? AND ?
    GROUP BY c.id, c.name, c.color
    ORDER BY total DESC
");
$stmt->execute([$user_id, $start_date, $end_date]);
$income_by_category = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get expenses by category
$stmt = $db->prepare("
    SELECT c.name, c.color, SUM(t.amount) as total, COUNT(t.id) as count
    FROM transactions t 
    JOIN categories c ON t.category_id = c.id 
    WHERE t.user_id = ? AND c.type = 'expense' AND t.transaction_date BETWEEN ? AND ?
    GROUP BY c.id, c.name, c.color
    ORDER BY total DESC
");
$stmt->execute([$user_id, $start_date, $end_date]);
$expenses_by_category = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get daily spending trend
$stmt = $db->prepare("
    SELECT 
        t.transaction_date,
        SUM(CASE WHEN c.type = 'income' THEN t.amount ELSE 0 END) as daily_income,
        SUM(CASE WHEN c.type = 'expense' THEN t.amount ELSE 0 END) as daily_expenses
    FROM transactions t 
    JOIN categories c ON t.category_id = c.id 
    WHERE t.user_id = ? AND t.transaction_date BETWEEN ? AND ?
    GROUP BY t.transaction_date
    ORDER BY t.transaction_date
");
$stmt->execute([$user_id, $start_date, $end_date]);
$daily_trends = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get monthly comparison (last 6 months)
$stmt = $db->prepare("
    SELECT 
        DATE_FORMAT(t.transaction_date, '%Y-%m') as month,
        DATE_FORMAT(t.transaction_date, '%M %Y') as month_name,
        SUM(CASE WHEN c.type = 'income' THEN t.amount ELSE 0 END) as income,
        SUM(CASE WHEN c.type = 'expense' THEN t.amount ELSE 0 END) as expenses
    FROM transactions t 
    JOIN categories c ON t.category_id = c.id 
    WHERE t.user_id = ? AND t.transaction_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(t.transaction_date, '%Y-%m')
    ORDER BY month DESC
    LIMIT 6
");
$stmt->execute([$user_id]);
$monthly_comparison = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

$pageTitle = 'Reports - Personal Finance Tracker';
include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Financial Reports</h2>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-secondary">
            <i class="fas fa-print me-1"></i>Print Report
        </button>
        <button onclick="exportToPDF()" class="btn btn-outline-primary">
            <i class="fas fa-file-pdf me-1"></i>Export PDF
        </button>
    </div>
</div>

<!-- Date Range Selector -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="report_type" class="form-label">Report Type</label>
                <select class="form-select" id="report_type" name="report_type" onchange="this.form.submit()">
                    <option value="weekly" <?php echo $report_type == 'weekly' ? 'selected' : ''; ?>>This Week</option>
                    <option value="monthly" <?php echo $report_type == 'monthly' ? 'selected' : ''; ?>>This Month</option>
                    <option value="yearly" <?php echo $report_type == 'yearly' ? 'selected' : ''; ?>>This Year</option>
                    <option value="custom" <?php echo $report_type == 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" 
                       value="<?php echo $start_date; ?>" <?php echo $report_type != 'custom' ? 'readonly' : ''; ?>>
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" 
                       value="<?php echo $end_date; ?>" <?php echo $report_type != 'custom' ? 'readonly' : ''; ?>>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">Generate Report</button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6>Total Income</h6>
                <h3>$<?php echo number_format($total_income, 2); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <h6>Total Expenses</h6>
                <h3>$<?php echo number_format($total_expenses, 2); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-<?php echo $net_income >= 0 ? 'info' : 'warning'; ?> text-white">
            <div class="card-body">
                <h6>Net Income</h6>
                <h3>$<?php echo number_format($net_income, 2); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-secondary text-white">
            <div class="card-body">
                <h6>Avg. Expense</h6>
                <h3>$<?php echo number_format($avg_expense, 2); ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Income vs Expenses</h5>
            </div>
            <div class="card-body">
                <canvas id="incomeExpenseChart" height="300"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Expense Breakdown</h5>
            </div>
            <div class="card-body">
                <?php if ($expenses_by_category): ?>
                    <canvas id="expenseBreakdownChart" height="300"></canvas>
                <?php else: ?>
                    <p class="text-muted text-center py-4">No expense data available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Trend Chart -->
<?php if ($daily_trends): ?>
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Daily Spending Trend</h5>
    </div>
    <div class="card-body">
        <canvas id="trendChart" height="100"></canvas>
    </div>
</div>
<?php endif; ?>

<!-- Monthly Comparison -->
<?php if ($monthly_comparison): ?>
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">6-Month Comparison</h5>
    </div>
    <div class="card-body">
        <canvas id="monthlyChart" height="100"></canvas>
    </div>
</div>
<?php endif; ?>

<!-- Category Tables -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Income by Category</h5>
            </div>
            <div class="card-body">
                <?php if ($income_by_category): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($income_by_category as $income): ?>
                                    <tr>
                                        <td>
                                            <span class="color-indicator me-2" style="background-color: <?php echo $income['color']; ?>"></span>
                                            <?php echo htmlspecialchars($income['name']); ?>
                                        </td>
                                        <td class="text-end">$<?php echo number_format($income['total'], 2); ?></td>
                                        <td class="text-end"><?php echo $total_income > 0 ? round(($income['total'] / $total_income) * 100, 1) : 0; ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-3">No income data available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Expenses by Category</h5>
            </div>
            <div class="card-body">
                <?php if ($expenses_by_category): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expenses_by_category as $expense): ?>
                                    <tr>
                                        <td>
                                            <span class="color-indicator me-2" style="background-color: <?php echo $expense['color']; ?>"></span>
                                            <?php echo htmlspecialchars($expense['name']); ?>
                                        </td>
                                        <td class="text-end">$<?php echo number_format($expense['total'], 2); ?></td>
                                        <td class="text-end"><?php echo $total_expenses > 0 ? round(($expense['total'] / $total_expenses) * 100, 1) : 0; ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-3">No expense data available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Wait for Chart.js to be fully loaded
function waitForChartJS() {
    if (typeof Chart !== 'undefined') {
        console.log('✅ Chart.js is ready, initializing charts...');
        initializeCharts();
    } else {
        console.log('⏳ Waiting for Chart.js to load...');
        setTimeout(waitForChartJS, 100);
    }
}

// Listen for Chart.js loaded event
window.addEventListener('chartjs-loaded', function() {
    console.log('📊 Chart.js loaded event received');
    initializeCharts();
});

// Start checking for Chart.js
document.addEventListener('DOMContentLoaded', function() {
    waitForChartJS();
});

function initializeCharts() {
    // Prevent multiple initializations
    if (window.chartsInitialized) {
        console.log('Charts already initialized, skipping...');
        return;
    }
    
    console.log('🚀 Starting chart initialization...');
    
    // Chart Data from PHP
    const incomeData = <?php echo json_encode($income_by_category); ?>;
    const expenseData = <?php echo json_encode($expenses_by_category); ?>;
    const dailyTrends = <?php echo json_encode($daily_trends); ?>;
    const monthlyData = <?php echo json_encode($monthly_comparison); ?>;

    console.log('📊 Chart data loaded:', { 
        incomeCategories: incomeData.length, 
        expenseCategories: expenseData.length,
        dailyData: dailyTrends.length,
        monthlyData: monthlyData.length
    });

    // Set Chart.js defaults
    Chart.defaults.responsive = true;
    Chart.defaults.maintainAspectRatio = false;

    // 1. Income vs Expense Chart
    const incomeExpenseCanvas = document.getElementById('incomeExpenseChart');
    if (incomeExpenseCanvas) {
        try {
            const ctx1 = incomeExpenseCanvas.getContext('2d');
            new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: ['Income', 'Expenses', 'Net'],
                    datasets: [{
                        label: 'Amount ($)',
                        data: [<?php echo $total_income; ?>, <?php echo $total_expenses; ?>, <?php echo abs($net_income); ?>],
                        backgroundColor: [
                            '#28a745', 
                            '#dc3545', 
                            '<?php echo $net_income >= 0 ? "#17a2b8" : "#ffc107"; ?>'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return '$' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
            console.log('✅ Income vs Expense chart created');
        } catch (error) {
            console.error('❌ Error creating Income vs Expense chart:', error);
        }
    }

    // 2. Expense Breakdown Chart
    if (expenseData.length > 0) {
        const expenseCanvas = document.getElementById('expenseBreakdownChart');
        if (expenseCanvas) {
            try {
                const ctx2 = expenseCanvas.getContext('2d');
                new Chart(ctx2, {
                    type: 'doughnut',
                    data: {
                        labels: expenseData.map(item => item.name),
                        datasets: [{
                            data: expenseData.map(item => parseFloat(item.total)),
                            backgroundColor: expenseData.map(item => item.color),
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 15,
                                    usePointStyle: true
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = '$' + context.parsed.toLocaleString();
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((context.parsed / total) * 100).toFixed(1);
                                        return `${label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
                console.log('✅ Expense breakdown chart created');
            } catch (error) {
                console.error('❌ Error creating Expense breakdown chart:', error);
            }
        }
    } else {
        console.log('ℹ️ No expense data available for breakdown chart');
    }

    // 3. Daily Trend Chart
    if (dailyTrends.length > 0) {
        const trendCanvas = document.getElementById('trendChart');
        if (trendCanvas) {
            try {
                const ctx3 = trendCanvas.getContext('2d');
                new Chart(ctx3, {
                    type: 'line',
                    data: {
                        labels: dailyTrends.map(item => {
                            const date = new Date(item.transaction_date);
                            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                        }),
                        datasets: [{
                            label: 'Income',
                            data: dailyTrends.map(item => parseFloat(item.daily_income)),
                            borderColor: '#28a745',
                            backgroundColor: 'rgba(40, 167, 69, 0.1)',
                            tension: 0.4,
                            fill: false,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }, {
                            label: 'Expenses',
                            data: dailyTrends.map(item => parseFloat(item.daily_expenses)),
                            borderColor: '#dc3545',
                            backgroundColor: 'rgba(220, 53, 69, 0.1)',
                            tension: 0.4,
                            fill: false,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': $' + context.parsed.y.toLocaleString();
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
                console.log('✅ Daily trend chart created');
            } catch (error) {
                console.error('❌ Error creating Daily trend chart:', error);
            }
        }
    } else {
        console.log('ℹ️ No daily trend data available');
    }

    // 4. Monthly Comparison Chart
    if (monthlyData.length > 0) {
        const monthlyCanvas = document.getElementById('monthlyChart');
        if (monthlyCanvas) {
            try {
                const ctx4 = monthlyCanvas.getContext('2d');
                new Chart(ctx4, {
                    type: 'bar',
                    data: {
                        labels: monthlyData.map(item => item.month_name),
                        datasets: [{
                            label: 'Income',
                            data: monthlyData.map(item => parseFloat(item.income)),
                            backgroundColor: '#28a745'
                        }, {
                            label: 'Expenses',
                            data: monthlyData.map(item => parseFloat(item.expenses)),
                            backgroundColor: '#dc3545'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': $' + context.parsed.y.toLocaleString();
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
                console.log('✅ Monthly comparison chart created');
            } catch (error) {
                console.error('❌ Error creating Monthly comparison chart:', error);
            }
        }
    } else {
        console.log('ℹ️ No monthly comparison data available');
    }

    // Mark charts as initialized
    window.chartsInitialized = true;
    console.log('🎉 All charts initialized successfully!');
}

// Export to PDF function
function exportToPDF() {
    if (typeof jsPDF !== 'undefined') {
        // If jsPDF is available, create actual PDF
        const pdf = new jsPDF();
        pdf.text('Financial Report', 20, 20);
        pdf.save('financial-report.pdf');
    } else {
        // Fallback message
        alert('PDF export feature requires jsPDF library. This would be implemented with server-side PDF generation or by including jsPDF library.');
    }
}
</script>

<?php include 'includes/footer.php'; ?>