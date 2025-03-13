<?php
$pageTitle = 'Dashboard';
$contentView = __FILE__;
?>

<?php include_once __DIR__ . '/../layouts/main.php'; ?> 

<h1 class="mb-4"><i class="fas fa-tachometer-alt"></i> Dashboard</h1>

<!-- Global Stats -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-tasks"></i> Total Action Plans</h5>
                <h2 class="card-text"><?php echo $globalStats['total']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-clock"></i> Pending</h5>
                <h2 class="card-text"><?php echo $globalStats['pending']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-dark">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-spinner"></i> In Progress</h5>
                <h2 class="card-text"><?php echo $globalStats['in_progress']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-check"></i> Completed</h5>
                <h2 class="card-text"><?php echo $globalStats['completed']; ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Feedback Stats -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-thumbs-up"></i> Compliments Overview</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Total Statistics</h6>
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Total Compliments
                                <span class="badge bg-success rounded-pill"><?php echo $complimentStats['total']; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Today
                                <span class="badge bg-success rounded-pill"><?php echo $complimentStats['today']; ?></span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Time Period</h6>
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Last 7 Days
                                <span class="badge bg-success rounded-pill"><?php echo $complimentStats['last_7_days']; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Last 30 Days
                                <span class="badge bg-success rounded-pill"><?php echo $complimentStats['last_30_days']; ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-exclamation-circle"></i> Complaints Overview</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Status</h6>
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Total Complaints
                                <span class="badge bg-warning text-dark rounded-pill"><?php echo $complaintStats['total']; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Pending
                                <span class="badge bg-warning text-dark rounded-pill"><?php echo $complaintStats['pending']; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                In Progress
                                <span class="badge bg-info rounded-pill"><?php echo $complaintStats['in_progress']; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Resolved
                                <span class="badge bg-success rounded-pill"><?php echo $complaintStats['resolved']; ?></span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Time Period</h6>
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Today
                                <span class="badge bg-warning text-dark rounded-pill"><?php echo $complaintStats['today']; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Last 7 Days
                                <span class="badge bg-warning text-dark rounded-pill"><?php echo $complaintStats['last_7_days']; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Last 30 Days
                                <span class="badge bg-warning text-dark rounded-pill"><?php echo $complaintStats['last_30_days']; ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- User Stats -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Your Performance</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Assigned to You</h6>
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Total
                                <span class="badge bg-primary rounded-pill"><?php echo $userStats['assigned_total']; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Completed
                                <span class="badge bg-success rounded-pill"><?php echo $userStats['assigned_completed']; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Overdue
                                <span class="badge bg-danger rounded-pill"><?php echo $userStats['assigned_overdue']; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Completed On Time
                                <span class="badge bg-info rounded-pill"><?php echo $userStats['assigned_completed_on_time']; ?></span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Created by You</h6>
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Total
                                <span class="badge bg-primary rounded-pill"><?php echo $userStats['created_total']; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Completed
                                <span class="badge bg-success rounded-pill"><?php echo $userStats['created_completed']; ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Overdue Action Plans</h5>
            </div>
            <div class="card-body">
                <?php if (empty($overdueActionPlans)): ?>
                    <p class="text-success">No overdue action plans. Great job!</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($overdueActionPlans as $plan): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($plan['name'] ?? ''); ?></td>
                                        <td class="text-danger"><?php echo date('M d, Y', strtotime($plan['due_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($plan['status'] ?? ''); ?></td>
                                        <td>
                                            <a href="index.php?page=action_plans&action=view&id=<?php echo $plan['action_plan_id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View
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
    </div>
</div>

<!-- Due Soon -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-clock"></i> Action Plans Due Soon</h5>
            </div>
            <div class="card-body">
                <?php if (empty($dueSoonActionPlans)): ?>
                    <p>No action plans due soon.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dueSoonActionPlans as $plan): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($plan['name']); ?></td>
                                        <td class="text-warning"><?php echo date('M d, Y', strtotime($plan['due_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($plan['status']); ?></td>
                                        <td><?php echo htmlspecialchars($plan['creator_name']); ?></td>
                                        <td>
                                            <a href="index.php?page=action_plans&action=view&id=<?php echo $plan['action_plan_id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View
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
    </div>
</div>

<!-- Recent Action Plans -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-list"></i> My Action Plans</h5>
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
                                    <th>Status</th>
                                    <th>Due Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $count = 0;
                                foreach ($assignedActionPlans as $plan): 
                                    if ($count >= 5) break; // Show only 5 most recent
                                    $count++;
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($plan['name']); ?></td>
                                        <td><?php echo htmlspecialchars($plan['status']); ?></td>
                                        <td>
                                            <?php if ($plan['due_date']): ?>
                                                <?php echo date('M d, Y', strtotime($plan['due_date'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">No due date</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="index.php?page=action_plans&action=view&id=<?php echo $plan['action_plan_id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (count($assignedActionPlans) > 5): ?>
                        <div class="text-center mt-3">
                            <a href="index.php?page=action_plans" class="btn btn-outline-primary">View All</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-info text-dark">
                <h5 class="mb-0"><i class="fas fa-clipboard-list"></i> Action Plans I Created</h5>
            </div>
            <div class="card-body">
                <?php if (empty($createdActionPlans)): ?>
                    <p>You haven't created any action plans yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Assignee</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $count = 0;
                                foreach ($createdActionPlans as $plan): 
                                    if ($count >= 5) break; // Show only 5 most recent
                                    $count++;
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($plan['name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($plan['assignee_name'] ?? 'Not Assigned'); ?></td>
                                        <td><?php echo htmlspecialchars($plan['status'] ?? ''); ?></td>
                                        <td>
                                            <a href="index.php?page=action_plans&action=view&id=<?php echo $plan['action_plan_id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (count($createdActionPlans) > 5): ?>
                        <div class="text-center mt-3">
                            <a href="index.php?page=action_plans" class="btn btn-outline-primary">View All</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php';?>