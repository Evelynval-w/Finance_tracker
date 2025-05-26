<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$errors = [];
$success = '';

if ($_POST) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($username)) $errors[] = 'Username is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (empty($password)) $errors[] = 'Password is required';
    if ($password !== $confirm_password) $errors[] = 'Passwords do not match';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
    
    if (empty($errors)) {
        $database = new Database();
        $db = $database->getConnection();
        
        // Check if username or email already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            $errors[] = 'Username or email already exists';
        } else {
            // Create user
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            
            if ($stmt->execute([$username, $email, $hashed_password])) {
                // Get the new user's ID
                $user_id = $db->lastInsertId();
                
                // Create default categories for the new user
                $default_categories = [
                    ['Salary', 'income', '#28a745'],
                    ['Freelance', 'income', '#17a2b8'],
                    ['Investment', 'income', '#20c997'],
                    ['Food & Dining', 'expense', '#dc3545'],
                    ['Transportation', 'expense', '#ffc107'],
                    ['Utilities', 'expense', '#6f42c1'],
                    ['Entertainment', 'expense', '#fd7e14'],
                    ['Healthcare', 'expense', '#e83e8c'],
                    ['Shopping', 'expense', '#6610f2']
                ];
                
                $category_stmt = $db->prepare("INSERT INTO categories (user_id, name, type, color) VALUES (?, ?, ?, ?)");
                foreach ($default_categories as $category) {
                    $category_stmt->execute([$user_id, $category[0], $category[1], $category[2]]);
                }
                
                $success = 'Registration successful! Default categories have been created for you. You can now login.';
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
}

$pageTitle = 'Register - Personal Finance Tracker';
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Create Account</h4>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                    <a href="login.php" class="btn btn-primary">Login Now</a>
                <?php else: ?>
                    <?php if ($errors): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo $_POST['username'] ?? ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo $_POST['email'] ?? ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p>Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>