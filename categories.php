<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

requireLogin();

$database = new Database();
$db = $database->getConnection();
$user_id = getCurrentUserId();

$errors = [];
$success = '';
$action = $_GET['action'] ?? 'list';
$category_id = $_GET['id'] ?? null;

// Handle form submissions
if ($_POST) {
    $name = trim($_POST['name']);
    $type = $_POST['type'];
    $color = $_POST['color'];
    
    // Validation
    if (empty($name)) $errors[] = 'Category name is required';
    if (!in_array($type, ['income', 'expense'])) $errors[] = 'Invalid category type';
    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) $errors[] = 'Invalid color format';
    
    // Check for duplicate names
    if (!empty($name)) {
        $stmt = $db->prepare("SELECT id FROM categories WHERE name = ? AND user_id = ? AND id != ?");
        $stmt->execute([$name, $user_id, $category_id ?? 0]);
        if ($stmt->fetch()) {
            $errors[] = 'Category name already exists';
        }
    }
    
    if (empty($errors)) {
        if ($action == 'add') {
            $stmt = $db->prepare("INSERT INTO categories (user_id, name, type, color) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$user_id, $name, $type, $color])) {
                $success = 'Category added successfully!';
                $action = 'list';
            } else {
                $errors[] = 'Failed to add category';
            }
        } elseif ($action == 'edit' && $category_id) {
            // Check if category has transactions before allowing type change
            $stmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE category_id = ?");
            $stmt->execute([$category_id]);
            $transaction_count = $stmt->fetchColumn();
            
            if ($transaction_count > 0) {
                // Get original category type
                $stmt = $db->prepare("SELECT type FROM categories WHERE id = ? AND user_id = ?");
                $stmt->execute([$category_id, $user_id]);
                $original_category = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($original_category && $original_category['type'] != $type) {
                    $errors[] = 'Cannot change category type when transactions exist. Delete transactions first.';
                }
            }
            
            if (empty($errors)) {
                $stmt = $db->prepare("UPDATE categories SET name = ?, type = ?, color = ? WHERE id = ? AND user_id = ?");
                if ($stmt->execute([$name, $type, $color, $category_id, $user_id])) {
                    $success = 'Category updated successfully!';
                    $action = 'list';
                } else {
                    $errors[] = 'Failed to update category';
                }
            }
        }
    }
}

// Handle delete action
if ($action == 'delete' && $category_id) {
    // Check if category has transactions
    $stmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE category_id = ?");
    $stmt->execute([$category_id]);
    $transaction_count = $stmt->fetchColumn();
    
    if ($transaction_count > 0) {
        $errors[] = "Cannot delete category with $transaction_count transaction(s). Delete transactions first.";
    } else {
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$category_id, $user_id])) {
            $success = 'Category deleted successfully!';
        } else {
            $errors[] = 'Failed to delete category';
        }
    }
    $action = 'list';
}

// Get category for editing
$category = null;
if ($action == 'edit' && $category_id) {
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ? AND user_id = ?");
    $stmt->execute([$category_id, $user_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$category) {
        $errors[] = 'Category not found';
        $action = 'list';
    }
}

// Get all categories with transaction counts
$stmt = $db->prepare("
    SELECT c.*, 
           COUNT(t.id) as transaction_count,
           COALESCE(SUM(t.amount), 0) as total_amount
    FROM categories c 
    LEFT JOIN transactions t ON c.id = t.category_id 
    WHERE c.user_id = ? 
    GROUP BY c.id, c.name, c.type, c.color, c.created_at
    ORDER BY c.type, c.name
");
$stmt->execute([$user_id]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Categories - Personal Finance Tracker';
include 'includes/header.php';
?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($action == 'add' || $action == 'edit'): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><?php echo $action == 'add' ? 'Add New Category' : 'Edit Category'; ?></h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo htmlspecialchars($category['name'] ?? $_POST['name'] ?? ''); ?>" 
                               placeholder="e.g., Groceries, Salary" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="type" class="form-label">Type</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="">Select Type</option>
                            <option value="income" <?php echo (($category['type'] ?? $_POST['type'] ?? '') == 'income') ? 'selected' : ''; ?>>Income</option>
                            <option value="expense" <?php echo (($category['type'] ?? $_POST['type'] ?? '') == 'expense') ? 'selected' : ''; ?>>Expense</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="color" class="form-label">Color</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="color" name="color" 
                                   value="<?php echo $category['color'] ?? $_POST['color'] ?? '#007bff'; ?>">
                            <input type="text" class="form-control" id="colorHex" 
                                   value="<?php echo $category['color'] ?? $_POST['color'] ?? '#007bff'; ?>" readonly>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $action == 'add' ? 'Add Category' : 'Update Category'; ?>
                    </button>
                    <a href="categories.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <!-- Category List View -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Categories</h2>
        <a href="categories.php?action=add" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Add Category
        </a>
    </div>

    <div class="row">
        <!-- Income Categories -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-arrow-up me-2"></i>Income Categories</h5>
                </div>
                <div class="card-body">
                    <?php 
                    $income_categories = array_filter($categories, function($cat) { return $cat['type'] == 'income'; });
                    if ($income_categories): 
                    ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($income_categories as $cat): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div class="d-flex align-items-center">
                                        <span class="color-indicator me-3" style="background-color: <?php echo $cat['color']; ?>"></span>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($cat['name']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo $cat['transaction_count']; ?> transactions • 
                                                $<?php echo number_format($cat['total_amount'], 2); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <a href="categories.php?action=edit&id=<?php echo $cat['id']; ?>" 
                                           class="btn btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="categories.php?action=delete&id=<?php echo $cat['id']; ?>" 
                                           class="btn btn-outline-danger"
                                           onclick="return confirm('Are you sure you want to delete this category?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">No income categories yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Expense Categories -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-arrow-down me-2"></i>Expense Categories</h5>
                </div>
                <div class="card-body">
                    <?php 
                    $expense_categories = array_filter($categories, function($cat) { return $cat['type'] == 'expense'; });
                    if ($expense_categories): 
                    ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($expense_categories as $cat): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div class="d-flex align-items-center">
                                        <span class="color-indicator me-3" style="background-color: <?php echo $cat['color']; ?>"></span>
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($cat['name']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo $cat['transaction_count']; ?> transactions • 
                                                $<?php echo number_format($cat['total_amount'], 2); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <a href="categories.php?action=edit&id=<?php echo $cat['id']; ?>" 
                                           class="btn btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="categories.php?action=delete&id=<?php echo $cat['id']; ?>" 
                                           class="btn btn-outline-danger"
                                           onclick="return confirm('Are you sure you want to delete this category?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">No expense categories yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Statistics -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Category Statistics</h5>
        </div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="stat-item">
                        <h4 class="text-success"><?php echo count($income_categories); ?></h4>
                        <p class="text-muted">Income Categories</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <h4 class="text-danger"><?php echo count($expense_categories); ?></h4>
                        <p class="text-muted">Expense Categories</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <h4 class="text-info"><?php echo array_sum(array_column($categories, 'transaction_count')); ?></h4>
                        <p class="text-muted">Total Transactions</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <h4 class="text-primary">
                            $<?php echo number_format(array_sum(array_column($categories, 'total_amount')), 2); ?>
                        </h4>
                        <p class="text-muted">Total Amount</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
// Sync color picker with hex input
document.getElementById('color').addEventListener('input', function() {
    document.getElementById('colorHex').value = this.value;
});
</script>

<?php include 'includes/footer.php'; ?>