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
$transaction_id = $_GET['id'] ?? null;

// Handle form submissions
if ($_POST) {
    $category_id = $_POST['category_id'];
    $amount = floatval($_POST['amount']);
    $description = trim($_POST['description']);
    $transaction_date = $_POST['transaction_date'];
    
    // Validation
    if (empty($category_id)) $errors[] = 'Please select a category';
    if ($amount <= 0) $errors[] = 'Amount must be greater than zero';
    if (empty($transaction_date)) $errors[] = 'Transaction date is required';
    
    // Verify category belongs to user
    if (!empty($category_id)) {
        $stmt = $db->prepare("SELECT id FROM categories WHERE id = ? AND user_id = ?");
        $stmt->execute([$category_id, $user_id]);
        if (!$stmt->fetch()) {
            $errors[] = 'Invalid category selected';
        }
    }
    
    if (empty($errors)) {
        if ($action == 'add') {
            $stmt = $db->prepare("INSERT INTO transactions (user_id, category_id, amount, description, transaction_date) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$user_id, $category_id, $amount, $description, $transaction_date])) {
                $success = 'Transaction added successfully!';
                $action = 'list';
            } else {
                $errors[] = 'Failed to add transaction';
            }
        } elseif ($action == 'edit' && $transaction_id) {
            $stmt = $db->prepare("UPDATE transactions SET category_id = ?, amount = ?, description = ?, transaction_date = ? WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$category_id, $amount, $description, $transaction_date, $transaction_id, $user_id])) {
                $success = 'Transaction updated successfully!';
                $action = 'list';
            } else {
                $errors[] = 'Failed to update transaction';
            }
        }
    }
}

// Handle delete action
if ($action == 'delete' && $transaction_id) {
    $stmt = $db->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$transaction_id, $user_id])) {
        $success = 'Transaction deleted successfully!';
    } else {
        $errors[] = 'Failed to delete transaction';
    }
    $action = 'list';
}

// Get user's categories
$stmt = $db->prepare("SELECT * FROM categories WHERE user_id = ? ORDER BY type, name");
$stmt->execute([$user_id]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get transaction for editing
$transaction = null;
if ($action == 'edit' && $transaction_id) {
    $stmt = $db->prepare("SELECT * FROM transactions WHERE id = ? AND user_id = ?");
    $stmt->execute([$transaction_id, $user_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$transaction) {
        $errors[] = 'Transaction not found';
        $action = 'list';
    }
}

// Get transactions list with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 15;
$offset = ($page - 1) * $per_page;

$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$month_filter = $_GET['month'] ?? '';

// Build WHERE clause
$where_conditions = ["t.user_id = ?"];
$params = [$user_id];

if (!empty($search)) {
    $where_conditions[] = "t.description LIKE ?";
    $params[] = "%$search%";
}

if (!empty($category_filter)) {
    $where_conditions[] = "t.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($month_filter)) {
    $where_conditions[] = "DATE_FORMAT(t.transaction_date, '%Y-%m') = ?";
    $params[] = $month_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$stmt = $db->prepare("SELECT COUNT(*) FROM transactions t WHERE $where_clause");
$stmt->execute($params);
$total_transactions = $stmt->fetchColumn();
$total_pages = ceil($total_transactions / $per_page);

// Get transactions
$stmt = $db->prepare("
    SELECT t.*, c.name as category_name, c.type as category_type, c.color 
    FROM transactions t 
    JOIN categories c ON t.category_id = c.id 
    WHERE $where_clause
    ORDER BY t.transaction_date DESC, t.created_at DESC 
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Transactions - Personal Finance Tracker';
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
            <h5 class="mb-0"><?php echo $action == 'add' ? 'Add New Transaction' : 'Edit Transaction'; ?></h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php 
                            $current_type = '';
                            foreach ($categories as $category): 
                                if ($current_type != $category['type']):
                                    if ($current_type != '') echo '</optgroup>';
                                    echo '<optgroup label="' . ucfirst($category['type']) . '">';
                                    $current_type = $category['type'];
                                endif;
                            ?>
                                <option value="<?php echo $category['id']; ?>" 
                                        <?php echo (($transaction['category_id'] ?? $_POST['category_id'] ?? '') == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if ($current_type != '') echo '</optgroup>'; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="amount" class="form-label">Amount</label>
                        <input type="number" class="form-control" id="amount" name="amount" 
                               step="0.01" min="0" 
                               value="<?php echo $transaction['amount'] ?? $_POST['amount'] ?? ''; ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="transaction_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="transaction_date" name="transaction_date" 
                               value="<?php echo $transaction['transaction_date'] ?? $_POST['transaction_date'] ?? date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="description" class="form-label">Description</label>
                        <input type="text" class="form-control" id="description" name="description" 
                               value="<?php echo htmlspecialchars($transaction['description'] ?? $_POST['description'] ?? ''); ?>" 
                               placeholder="Enter transaction description">
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $action == 'add' ? 'Add Transaction' : 'Update Transaction'; ?>
                    </button>
                    <a href="transactions.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <!-- Transaction List View -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Transactions</h2>
        <a href="transactions.php?action=add" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Add Transaction
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" placeholder="Search description">
                </div>
                <div class="col-md-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                    <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="month" class="form-label">Month</label>
                    <input type="month" class="form-control" id="month" name="month" 
                           value="<?php echo $month_filter; ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-outline-primary me-2">Filter</button>
                    <a href="transactions.php" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="card">
        <div class="card-body">
            <?php if ($transactions): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th class="text-end">Amount</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $t): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($t['transaction_date'])); ?></td>
                                    <td>
                                        <span class="badge" style="background-color: <?php echo $t['color']; ?>">
                                            <?php echo htmlspecialchars($t['category_name']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($t['description']); ?></td>
                                    <td class="text-end">
                                        <span class="text-<?php echo $t['category_type'] == 'income' ? 'success' : 'danger'; ?>">
                                            <?php echo $t['category_type'] == 'income' ? '+' : '-'; ?>$<?php echo number_format($t['amount'], 2); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="transactions.php?action=edit&id=<?php echo $t['id']; ?>" 
                                               class="btn btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="transactions.php?action=delete&id=<?php echo $t['id']; ?>" 
                                               class="btn btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to delete this transaction?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&month=<?php echo $month_filter; ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&month=<?php echo $month_filter; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $category_filter; ?>&month=<?php echo $month_filter; ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                    <h5>No transactions found</h5>
                    <p class="text-muted">Start by adding your first transaction</p>
                    <a href="transactions.php?action=add" class="btn btn-primary">Add Transaction</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>