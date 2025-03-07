<?php
$pageTitle = 'Edit Action Plan';
$contentView = __FILE__;
?>

<?php include_once __DIR__ . '/../layouts/main.php'; ?> 

<h1 class="mb-4"><i class="fas fa-edit"></i> Edit Action Plan</h1>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Action Plan Details</h5>
    </div>
    <div class="card-body">
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form action="index.php?page=action_plans&action=update" method="post">
            <input type="hidden" name="id" value="<?php echo $actionPlan['action_plan_id']; ?>">
            
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($actionPlan['name']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($actionPlan['description']); ?></textarea>
            </div>
            
            <div class="mb-3">
                <label for="assignee_id" class="form-label">Assignee</label>
                <select class="form-select" id="assignee_id" name="assignee_id" required>
                    <option value="">Select Assignee</option>
                    <option value="0" <?php echo (!isset($actionPlan['assignee_id']) || $actionPlan['assignee_id'] === null) ? 'selected' : ''; ?>>Not Applicable</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['user_id']; ?>" <?php echo (isset($actionPlan['assignee_id']) && $actionPlan['assignee_id'] == $user['user_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['username']); ?>
                            <?php echo $user['is_management_staff'] ? ' (Management Staff)' : ''; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="due_date" class="form-label">Due Date (Optional)</label>
                <input type="date" class="form-control" id="due_date" name="due_date" value="<?php echo $actionPlan['due_date'] ?? ''; ?>">
            </div>
            
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="Pending" <?php echo $actionPlan['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="In Progress" <?php echo $actionPlan['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="Completed" <?php echo $actionPlan['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="index.php?page=action_plans&action=view&id=<?php echo $actionPlan['action_plan_id']; ?>" class="btn btn-outline-secondary me-md-2">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Action Plan
                </button>
            </div>
        </form>
    </div>
</div>

