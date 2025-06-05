<h1>Edit Task</h1>

<?php if (isset($errors) && !empty($errors)): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($errors as $field => $message): ?>
                <li><?= $field ?>: <?= $message ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="/tasks/<?= $task->id ?>" method="POST">
    <input type="hidden" name="_method" value="PUT">
    
    <div class="mb-3">
        <label for="title" class="form-label">Title</label>
        <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($task->title) ?>" required>
    </div>
    
    <div class="mb-3">
        <label for="description" class="form-label">Description</label>
        <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($task->description) ?></textarea>
    </div>
    
    <div class="mb-3">
        <label for="status" class="form-label">Status</label>
        <select class="form-select" id="status" name="status">
            <option value="pending" <?= $task->status === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="in_progress" <?= $task->status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
            <option value="completed" <?= $task->status === 'completed' ? 'selected' : '' ?>>Completed</option>
        </select>
    </div>
    
    <div class="mb-3">
        <label for="due_date" class="form-label">Due Date</label>
        <input type="date" class="form-control" id="due_date" name="due_date" value="<?= date('Y-m-d', strtotime($task->due_date)) ?>">
    </div>
    
    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
        <a href="/tasks" class="btn btn-secondary me-md-2">Cancel</a>
        <button type="submit" class="btn btn-primary">Update Task</button>
    </div>
</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?> 