<?php
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$pageTitle = 'Welcome - Personal Finance Tracker';
include 'includes/header.php';
?>

<div class="hero-section text-center py-5">
    <div class="container">
        <h1 class="display-4 mb-4">Take Control of Your Finances</h1>
        <p class="lead mb-4">Track expenses, manage budgets, and achieve your financial goals with our comprehensive finance tracker.</p>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <a href="register.php" class="btn btn-primary btn-lg me-3">Get Started</a>
                <a href="login.php" class="btn btn-outline-primary btn-lg">Login</a>
            </div>
        </div>
    </div>
</div>

<div class="features-section py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-md-4 text-center mb-4">
                <i class="fas fa-chart-line fa-3x text-primary mb-3"></i>
                <h4>Track Expenses</h4>
                <p>Monitor your spending patterns and identify areas for improvement.</p>
            </div>
            <div class="col-md-4 text-center mb-4">
                <i class="fas fa-piggy-bank fa-3x text-primary mb-3"></i>
                <h4>Budget Management</h4>
                <p>Set budgets and track your progress towards financial goals.</p>
            </div>
            <div class="col-md-4 text-center mb-4">
                <i class="fas fa-file-alt fa-3x text-primary mb-3"></i>
                <h4>Detailed Reports</h4>
                <p>Generate comprehensive reports to understand your financial health.</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>