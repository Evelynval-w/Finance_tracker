<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$user_id = getCurrentUserId();

// Get current month statistics
$current_month = date('Y-m');
$stmt = $db->prepare("
    SELECT 
        SUM(CASE WHEN c.type = 'income' THEN t.amount ELSE 0 END) as total_income,
        SUM(CASE WHEN c.type = 'expense' THEN t.amount ELSE 0 END) as total_expenses,
        COUNT(*) as total_transactions
    FROM transactions t 
    JOIN categories c ON t.category_id = c.id 
    WHERE t.user_id = ? AND DATE_FORMAT(t.transaction_date, '%Y-%m') = ?
");
$stmt->execute([$user_id, $current_month]);
$monthly_stats = $stmt->fetch(PDO::FETCH_ASSOC);

$total_income = $monthly_stats['total_income'] ?? 0;
$total_expenses = $monthly_stats['total_expenses'] ?? 0;
$net_income = $total_income - $total_expenses;
$total_transactions = $monthly_stats['total_transactions'] ?? 0;

// Get recent transactions
$stmt = $db->prepare("
    SELECT t.*, c.name as category_name, c.type as category_type, c.color 
    FROM transactions t 
    JOIN categories c ON t.category_id = c.id 
    WHERE t.user_id = ? 
    ORDER BY t.transaction_date DESC, t.created_at DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get expense breakdown for chart
$stmt = $db->prepare("
    SELECT c.name, c.color, SUM(t.amount) as total
    FROM transactions t 
    JOIN categories c ON t.category_id = c.id 
    WHERE t.user_id = ? AND c.type = 'expense' AND DATE_FORMAT(t.transaction_date, '%Y-%m') = ?
    GROUP BY c.id, c.name, c.color
    ORDER BY total DESC
    LIMIT 6
");
$stmt->execute([$user_id, $current_month]);
$expense_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Dashboard - Personal Finance Tracker';
include 'includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <h2>Welcome back, <?php echo getCurrentUsername(); ?>!</h2>
        <p class="text-muted">Here's your financial overview for <?php echo date('F Y'); ?></p>
    </div>
</div>

<!-- Financial Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Income</h6>
                        <h4 class="mb-0">$<?php echo number_format($total_income, 2); ?></h4>
                    </div>
                    <i class="fas fa-arrow-up fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Expenses</h6>
                        <h4 class="mb-0">$<?php echo number_format($total_expenses, 2); ?></h4>
                    </div>
                    <i class="fas fa-arrow-down fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card text-white <?php echo $net_income >= 0 ? 'bg-info' : 'bg-warning'; ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Net Income</h6>
                        <h4 class="mb-0">$<?php echo number_format($net_income, 2); ?></h4>
                    </div>
                    <i class="fas fa-balance-scale fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-secondary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Transactions</h6>
                        <h4 class="mb-0"><?php echo $total_transactions; ?></h4>
                    </div>
                    <i class="fas fa-receipt fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts and Recent Transactions -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Expense Breakdown</h5>
            </div>
            <div class="card-body">
                <?php if ($expense_breakdown): ?>
                    <canvas id="expenseChart" style="height: 300px;"></canvas>
                    <div id="chartLoading" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading chart...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading chart...</p>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No expenses recorded this month</p>
                        <a href="transactions.php?action=add" class="btn btn-primary btn-sm">Add Your First Expense</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Transactions</h5>
                <a href="transactions.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if ($recent_transactions): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_transactions as $transaction): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <div class="d-flex align-items-center">
                                        <span class="badge me-2" style="background-color: <?php echo $transaction['color']; ?>">
                                            <?php echo $transaction['category_name']; ?>
                                        </span>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($transaction['description']); ?></h6>
                                            <small class="text-muted"><?php echo date('M j, Y', strtotime($transaction['transaction_date'])); ?></small>
                                        </div>
                                    </div>
                                </div>
                                <span class="text-<?php echo $transaction['category_type'] == 'income' ? 'success' : 'danger'; ?>">
                                    <?php echo $transaction['category_type'] == 'income' ? '+' : '-'; ?>$<?php echo number_format($transaction['amount'], 2); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No transactions yet</p>
                        <a href="transactions.php?action=add" class="btn btn-primary btn-sm">Add Your First Transaction</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3 mb-3">
                        <a href="transactions.php?action=add" class="btn btn-success btn-lg w-100">
                            <i class="fas fa-plus-circle d-block mb-2"></i>
                            Add Transaction
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="transactions.php" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-list d-block mb-2"></i>
                            View Transactions
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="categories.php" class="btn btn-info btn-lg w-100">
                            <i class="fas fa-tags d-block mb-2"></i>
                            Manage Categories
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="reports.php" class="btn btn-warning btn-lg w-100">
                            <i class="fas fa-chart-bar d-block mb-2"></i>
                            View Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Dashboard chart initialization
function initializeDashboardChart() {
    // Chart data from PHP
    const expenseData = <?php echo json_encode($expense_breakdown); ?>;
    
    console.log('📊 Dashboard expense data:', expenseData);
    
    // Check if we have data and Chart.js is loaded
    if (expenseData.length > 0 && typeof Chart !== 'undefined') {
        const canvas = document.getElementById('expenseChart');
        const loading = document.getElementById('chartLoading');
        
        if (canvas) {
            try {
                // Hide loading indicator
                if (loading) loading.style.display = 'none';
                
                const ctx = canvas.getContext('2d');
                new Chart(ctx, {
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
                console.log('✅ Dashboard expense chart created successfully');
            } catch (error) {
                console.error('❌ Error creating dashboard chart:', error);
                if (loading) {
                    loading.innerHTML = '<div class="alert alert-warning">Chart failed to load</div>';
                }
            }
        }
    } else if (expenseData.length === 0) {
        console.log('ℹ️ No expense data available for dashboard chart');
        const loading = document.getElementById('chartLoading');
        if (loading) loading.style.display = 'none';
    } else {
        console.log('⏳ Waiting for Chart.js to load...');
        // Try again in a moment
        setTimeout(initializeDashboardChart, 100);
    }
}

// Wait for Chart.js to load, then initialize dashboard chart
function waitForChartJSOnDashboard() {
    if (typeof Chart !== 'undefined') {
        console.log('✅ Chart.js ready for dashboard');
        initializeDashboardChart();
    } else {
        console.log('⏳ Dashboard waiting for Chart.js...');
        setTimeout(waitForChartJSOnDashboard, 100);
    }
}

// Listen for Chart.js loaded event from footer
window.addEventListener('chartjs-loaded', function() {
    console.log('📊 Chart.js loaded event received on dashboard');
    initializeDashboardChart();
});

// Start checking when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('🏠 Dashboard loaded, waiting for Chart.js...');
    waitForChartJSOnDashboard();
});

// Backup: Try again after a delay if everything else fails
setTimeout(function() {
    if (typeof Chart !== 'undefined' && !window.dashboardChartInitialized) {
        console.log('🔄 Backup initialization attempt');
        initializeDashboardChart();
    }
}, 2000);
</script>

<?php include 'includes/footer.php'; ?>