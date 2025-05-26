// Personal Finance Tracker - Main JavaScript File

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initializeFormValidation();
    initializeTooltips();
    initializeModals();
    initializeSearchFilters();
    initializeDatePickers();
    initializeAnimations();
    console.log('Finance Tracker JavaScript loaded successfully');
});

// Form Validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    
    // Real-time validation for specific fields
    const amountInputs = document.querySelectorAll('input[name="amount"]');
    amountInputs.forEach(input => {
        input.addEventListener('input', function() {
            validateAmount(this);
        });
    });
    
    // Category form validation
    const categoryForms = document.querySelectorAll('form[action*="categories"]');
    categoryForms.forEach(form => {
        const nameInput = form.querySelector('input[name="name"]');
        const colorInput = form.querySelector('input[name="color"]');
        
        if (nameInput) {
            nameInput.addEventListener('input', function() {
                validateCategoryName(this);
            });
        }
        
        if (colorInput) {
            colorInput.addEventListener('change', function() {
                updateColorPreview(this);
            });
        }
    });
}

// Amount validation
function validateAmount(input) {
    const value = parseFloat(input.value);
    const feedback = input.nextElementSibling;
    
    if (isNaN(value) || value <= 0) {
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.textContent = 'Please enter a valid amount greater than 0';
        }
        return false;
    } else {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        return true;
    }
}

// Category name validation
function validateCategoryName(input) {
    const value = input.value.trim();
    const feedback = input.nextElementSibling;
    
    if (value.length < 2) {
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.textContent = 'Category name must be at least 2 characters';
        }
        return false;
    } else {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        return true;
    }
}

// Color preview update
function updateColorPreview(input) {
    const hexInput = document.getElementById('colorHex');
    if (hexInput) {
        hexInput.value = input.value;
    }
    
    // Update any preview elements
    const previews = document.querySelectorAll('.color-preview');
    previews.forEach(preview => {
        preview.style.backgroundColor = input.value;
    });
}

// Initialize Bootstrap tooltips
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    if (typeof bootstrap !== 'undefined') {
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

// Initialize Bootstrap modals
function initializeModals() {
    // Add confirmation dialogs for delete actions
    const deleteButtons = document.querySelectorAll('a[href*="delete"]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
    
    // Quick add transaction modal
    const quickAddBtn = document.getElementById('quickAddTransaction');
    if (quickAddBtn) {
        quickAddBtn.addEventListener('click', function() {
            showQuickAddModal();
        });
    }
}

// Quick add transaction modal
function showQuickAddModal() {
    const modalHtml = `
        <div class="modal fade" id="quickAddModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Quick Add Transaction</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="quickAddForm">
                            <div class="mb-3">
                                <label for="quickAmount" class="form-label">Amount</label>
                                <input type="number" class="form-control" id="quickAmount" step="0.01" required>
                            </div>
                            <div class="mb-3">
                                <label for="quickDescription" class="form-label">Description</label>
                                <input type="text" class="form-control" id="quickDescription" required>
                            </div>
                            <div class="mb-3">
                                <label for="quickType" class="form-label">Type</label>
                                <select class="form-select" id="quickType" required>
                                    <option value="">Select Type</option>
                                    <option value="income">Income</option>
                                    <option value="expense">Expense</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="submitQuickAdd()">Add Transaction</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    if (typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(document.getElementById('quickAddModal'));
        modal.show();
        
        // Clean up modal after hiding
        document.getElementById('quickAddModal').addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
    }
}

// Submit quick add form
function submitQuickAdd() {
    const form = document.getElementById('quickAddForm');
    const amount = document.getElementById('quickAmount').value;
    const description = document.getElementById('quickDescription').value;
    const type = document.getElementById('quickType').value;
    
    if (form.checkValidity()) {
        // In a real application, this would make an AJAX request
        showNotification('Transaction added successfully!', 'success');
        if (typeof bootstrap !== 'undefined') {
            bootstrap.Modal.getInstance(document.getElementById('quickAddModal')).hide();
        }
        
        // Redirect to transactions page
        setTimeout(() => {
            window.location.href = 'transactions.php';
        }, 1000);
    } else {
        form.classList.add('was-validated');
    }
}

// Search and filters
function initializeSearchFilters() {
    const searchInputs = document.querySelectorAll('input[name="search"]');
    searchInputs.forEach(input => {
        let timeout;
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                if (input.value.length >= 3 || input.value.length === 0) {
                    console.log('Searching for:', input.value);
                }
            }, 300);
        });
    });
    
    // Auto-submit filter forms on change
    const filterSelects = document.querySelectorAll('select[name="category"], select[name="month"]');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            if (this.closest('form')) {
                this.closest('form').submit();
            }
        });
    });
}

// Date pickers enhancement
function initializeDatePickers() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        // Set default date if empty
        if (!input.value && input.name === 'transaction_date') {
            input.value = new Date().toISOString().split('T')[0];
        }
        
        // Validate date range
        input.addEventListener('change', function() {
            validateDateRange(this);
        });
    });
}

// Date range validation
function validateDateRange(input) {
    const value = new Date(input.value);
    const today = new Date();
    const oneYearAgo = new Date(today.getFullYear() - 1, today.getMonth(), today.getDate());
    const oneYearAhead = new Date(today.getFullYear() + 1, today.getMonth(), today.getDate());
    
    if (value < oneYearAgo || value > oneYearAhead) {
        showNotification('Please select a date within the last year or next year', 'warning');
        input.value = today.toISOString().split('T')[0];
    }
}

// Animations
function initializeAnimations() {
    // Add fade-in animation to cards
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Notification system
function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after duration
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, duration);
}

// Dashboard specific functions
function refreshDashboard() {
    showNotification('Refreshing dashboard...', 'info', 2000);
    
    // Add loading animation
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.classList.add('loading');
    });
    
    // Simulate refresh delay
    setTimeout(() => {
        cards.forEach(card => {
            card.classList.remove('loading');
        });
        location.reload();
    }, 1500);
}

// Export functions
function exportData(format = 'csv') {
    showNotification(`Exporting data as ${format.toUpperCase()}...`, 'info');
    
    // In a real application, this would make an AJAX request to export data
    setTimeout(() => {
        showNotification(`Data exported successfully as ${format.toUpperCase()}!`, 'success');
    }, 2000);
}

// Export to PDF function for reports
function exportToPDF() {
    showNotification('PDF export feature would be implemented with a library like jsPDF or server-side PDF generation.', 'info');
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + N = New transaction
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        if (document.querySelector('a[href*="transactions.php?action=add"]')) {
            window.location.href = 'transactions.php?action=add';
        }
    }
    
    // Ctrl/Cmd + D = Dashboard
    if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
        e.preventDefault();
        window.location.href = 'dashboard.php';
    }
    
    // Ctrl/Cmd + R = Reports
    if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
        e.preventDefault();
        window.location.href = 'reports.php';
    }
});

// Mobile-specific enhancements
if (window.innerWidth <= 768) {
    // Add swipe gestures for mobile
    let touchStartX = 0;
    let touchEndX = 0;
    
    document.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
    });
    
    document.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipeGesture();
    });
    
    function handleSwipeGesture() {
        const swipeThreshold = 50;
        const diff = touchStartX - touchEndX;
        
        if (Math.abs(diff) > swipeThreshold) {
            if (diff > 0) {
                // Swipe left - next page or action
                console.log('Swipe left detected');
            } else {
                // Swipe right - previous page or action
                console.log('Swipe right detected');
            }
        }
    }
}

// Performance monitoring
function trackPerformance() {
    if ('performance' in window) {
        window.addEventListener('load', function() {
            const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
            console.log('Page load time:', loadTime + 'ms');
            
            if (loadTime > 3000) {
                showNotification('Page loaded slowly. Consider optimizing.', 'warning');
            }
        });
    }
}

// Initialize performance tracking
trackPerformance();

// Error handling
window.addEventListener('error', function(e) {
    console.error('JavaScript Error:', e.error);
    showNotification('An error occurred. Please refresh the page.', 'danger');
});