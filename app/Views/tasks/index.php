<?php
?>
<div class="row mb-4">
    <div class="col">
        <h1>Tasks</h1>
    </div>
    <div class="col text-end">
        <a href="/tasks/create" class="btn btn-primary">New Task</a>
    </div>
</div>

<?php if (empty($tasks)): ?>
    <div class="alert alert-info">
        No tasks found. <a href="/tasks/create">Create a new task</a>.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Due Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td><?= $task->id ?></td>
                        <td><?= htmlspecialchars($task->title) ?></td>
                        <td>
                            <?php if ($task->status == 'pending'): ?>
                                <span class="badge bg-warning">Pending</span>
                            <?php elseif ($task->status == 'in_progress'): ?>
                                <span class="badge bg-primary">In Progress</span>
                            <?php else: ?>
                                <span class="badge bg-success">Completed</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $task->due_date ? ($task->due_date instanceof \DateTime ? $task->due_date->format('Y-m-d') : date('Y-m-d', strtotime($task->due_date))) : 'N/A' ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="/tasks/<?= $task->id ?>" class="btn btn-sm btn-info">View</a>
                                <a href="/tasks/<?= $task->id ?>/edit" class="btn btn-sm btn-primary">Edit</a>
                                <form action="/tasks/<?= $task->id ?>" method="POST" style="display: inline;">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?> 