<?php
$pageTitle = 'Create Action Plan';
$contentView = __FILE__;
?>
<?php include_once __DIR__ . '/../layouts/main.php'; ?> 

<h1 class="mb-4"><i class="fas fa-plus"></i> Create Action Plan</h1>

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
        
        <form action="index.php?page=action_plans&action=store" method="post">
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo $name ?? ''; ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4" required><?php echo $description ?? ''; ?></textarea>
            </div>
            
            <div class="mb-3">
                <label for="assignee_id" class="form-label">Assignee</label>
                <select class="form-select" id="assignee_id" name="assignee_id">
                    <option value="">Select Assignee</option>
                    <option value="0" <?php echo (isset($assignee_id) && $assignee_id === 0) ? 'selected' : ''; ?>>Not Applicable</option>
                    <?php if (isset($users) && is_array($users)): ?>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo htmlspecialchars($user['user_id']); ?>" 
                                <?php echo (isset($assignee_id) && $assignee_id == $user['user_id']) ? 'selected' : ''; ?>>
                                <?php 
                                $displayName = trim($user['first_name'] . ' ' . $user['last_name']);
                                echo htmlspecialchars($displayName ?: $user['email']); 
                                echo $user['is_management_staff'] ? ' (Management Staff)' : ''; 
                                ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="due_date" class="form-label">Due Date (Optional)</label>
                <input type="date" class="form-control" id="due_date" name="due_date" value="<?php echo $due_date ?? ''; ?>">
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="index.php?page=action_plans" class="btn btn-outline-secondary me-md-2">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Action Plan
                </button>
            </div>
        </form>
    </div>
</div>

