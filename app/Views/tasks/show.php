<div class="row mb-4">
    <div class="col">
        <h1><?= htmlspecialchars($task->title) ?></h1>
    </div>
    <div class="col text-end">
        <a href="/tasks" class="btn btn-secondary me-2">Back to List</a>
        <a href="/tasks/<?= $task->id ?>/edit" class="btn btn-primary">Edit</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3 fw-bold">Status:</div>
            <div class="col-md-9">
                <?php if ($task->status == 'pending'): ?>
                    <span class="badge bg-warning">Pending</span>
                <?php elseif ($task->status == 'in_progress'): ?>
                    <span class="badge bg-primary">In Progress</span>
                <?php else: ?>
                    <span class="badge bg-success">Completed</span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-md-3 fw-bold">Due Date:</div>
            <div class="col-md-9">
                <?= $task->due_date ? date('Y-m-d', strtotime($task->due_date)) : 'N/A' ?>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-md-3 fw-bold">Description:</div>
            <div class="col-md-9">
                <p class="text-pre-wrap"><?= nl2br(htmlspecialchars($task->description)) ?></p>
            </div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between">
        <form action="/tasks/<?= $task->id ?>" method="POST" style="display: inline;">
            <input type="hidden" name="_method" value="DELETE">
            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
        </form>
        <div>
            <small class="text-muted">Created: <?= date('Y-m-d H:i', strtotime($task->created_at)) ?></small><br>
            <small class="text-muted">Updated: <?= date('Y-m-d H:i', strtotime($task->updated_at)) ?></small>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?> 