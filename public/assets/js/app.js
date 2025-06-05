// Basic JavaScript for Portfolion

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips if Bootstrap is loaded
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Add confirm dialog to delete buttons
    document.querySelectorAll('.btn-danger[type="submit"]').forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
                return false;
            }
        });
    });
    
    // Add task form validation
    const taskForm = document.getElementById('task-form');
    if (taskForm) {
        taskForm.addEventListener('submit', function(e) {
            const titleInput = document.getElementById('title');
            if (titleInput && titleInput.value.trim() === '') {
                e.preventDefault();
                alert('Task title is required');
                titleInput.focus();
                return false;
            }
        });
    }
}); 