<?php
$pageTitle = 'Action Plans';
$contentView = __FILE__;
?>

<?php include_once __DIR__ . '/../layouts/main.php'; ?> 

<h1 class="mb-4"><i class="fas fa-tasks"></i> Action Plans</h1>

<!-- Search and Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form action="index.php" method="get" class="row g-3">
            <input type="hidden" name="page" value="action_plans">
            
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Search by name..." value="<?php echo htmlspecialchars($searchTerm ?? ''); ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>
            
            <div class="col-md-4">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="Pending" <?php echo ($statusFilter ?? '') === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="In Progress" <?php echo ($statusFilter ?? '') === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="Completed" <?php echo ($statusFilter ?? '') === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <a href="index.php?page=action_plans" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-redo"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Action Plans Assigned to Me -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-list"></i> Action Plans Assigned to Me</h5>
    </div>
    <div class="card-body">
        <?php if (empty($assignedActionPlans)): ?>
            <p>No action plans assigned to you.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Created By</th>
                            <th>Status</th>
                            <th>Due Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignedActionPlans as $plan): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($plan['name']); ?></td>
                                <td><?php echo htmlspecialchars($plan['creator_name']); ?></td>
                                <td>
                                    <?php if ($plan['status'] === 'Pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php elseif ($plan['status'] === 'In Progress'): ?>
                                        <span class="badge bg-info text-dark">In Progress</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Completed</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($plan['due_date']): ?>
                                        <?php 
                                        $dueDate = strtotime($plan['due_date']);
                                        $today = strtotime('today');
                                        $tomorrow = strtotime('tomorrow');
                                        $isOverdue = $dueDate < $today && $plan['status'] !== 'Completed';
                                        $isDueSoon = $dueDate >= $today && $dueDate < $tomorrow && $plan['status'] !== 'Completed';
                                        ?>
                                        
                                        <?php if ($isOverdue): ?>
                                            <span class="text-danger">
                                                <i class="fas fa-exclamation-triangle"></i> 
                                                <?php echo date('M d, Y', $dueDate); ?> (Overdue)
                                            </span>
                                        <?php elseif ($isDueSoon): ?>
                                            <span class="text-warning">
                                                <i class="fas fa-clock"></i> 
                                                <?php echo date('M d, Y', $dueDate); ?> (Today)
                                            </span>
                                        <?php else: ?>
                                            <?php echo date('M d, Y', $dueDate); ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">No due date</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="index.php?page=action_plans&action=view&id=<?php echo $plan['action_plan_id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="index.php?page=action_plans&action=edit&id=<?php echo $plan['action_plan_id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Action Plans I Created -->
<div class="card">
    <div class="card-header bg-info text-dark">
        <h5 class="mb-0"><i class="fas fa-clipboard-list"></i> Action Plans I Created</h5>
    </div>
    <div class="card-body">
        <?php if (empty($createdActionPlans)): ?>
            <p>You haven't created any action plans yet.</p>
            <a href="index.php?page=action_plans&action=create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Action Plan
            </a>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Assignee</th>
                            <th>Status</th>
                            <th>Due Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($createdActionPlans as $plan): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($plan['name']); ?></td>
                                <td>
                                    <?php if (isset($plan['assignee_id']) && $plan['assignee_id']): ?>
                                        <?php echo htmlspecialchars($plan['assignee_name']); ?>
                                    <?php else: ?>
                                        <em>Not Applicable</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($plan['status'] === 'Pending'): ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php elseif ($plan['status'] === 'In Progress'): ?>
                                        <span class="badge bg-info text-dark">In Progress</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Completed</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($plan['due_date']): ?>
                                        <?php 
                                        $dueDate = strtotime($plan['due_date']);
                                        $today = strtotime('today');
                                        $isOverdue = $dueDate < $today && $plan['status'] !== 'Completed';
                                        ?>
                                        
                                        <?php if ($isOverdue): ?>
                                            <span class="text-danger">
                                                <i class="fas fa-exclamation-triangle"></i> 
                                                <?php echo date('M d, Y', $dueDate); ?> (Overdue)
                                            </span>
                                        <?php else: ?>
                                            <?php echo date('M d, Y', $dueDate); ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">No due date</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="index.php?page=action_plans&action=view&id=<?php echo $plan['action_plan_id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="index.php?page=action_plans&action=edit&id=<?php echo $plan['action_plan_id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="index.php?page=action_plans&action=delete&id=<?php echo $plan['action_plan_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this action plan?');">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

