<h1>Create Task</h1>

<?php if (isset($errors) && !empty($errors)): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($errors as $field => $message): ?>
                <li><?= $field ?>: <?= $message ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="/tasks" method="POST">
    <div class="mb-3">
        <label for="title" class="form-label">Title</label>
        <input type="text" class="form-control" id="title" name="title" value="<?= $data['title'] ?? '' ?>" required>
    </div>
    
    <div class="mb-3">
        <label for="description" class="form-label">Description</label>
        <textarea class="form-control" id="description" name="description" rows="3"><?= $data['description'] ?? '' ?></textarea>
    </div>
    
    <div class="mb-3">
        <label for="status" class="form-label">Status</label>
        <select class="form-select" id="status" name="status">
            <option value="pending" <?= isset($data['status']) && $data['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="in_progress" <?= isset($data['status']) && $data['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
            <option value="completed" <?= isset($data['status']) && $data['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
        </select>
    </div>
    
    <div class="mb-3">
        <label for="due_date" class="form-label">Due Date</label>
        <input type="date" class="form-control" id="due_date" name="due_date" value="<?= $data['due_date'] ?? '' ?>">
    </div>
    
    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
        <a href="/tasks" class="btn btn-secondary me-md-2">Cancel</a>
        <button type="submit" class="btn btn-primary">Create Task</button>
    </div>
</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?> 