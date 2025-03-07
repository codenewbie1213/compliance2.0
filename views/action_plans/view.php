<?php
$pageTitle = 'View Action Plan';
$contentView = __FILE__;
?>

<?php include_once __DIR__ . '/../layouts/main.php'; ?> 

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-clipboard-list"></i> Action Plan Details</h1>
    <div>
        <a href="index.php?page=action_plans" class="btn btn-outline-secondary me-2">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
        <?php if (isset($canEdit) && $canEdit): ?>
        <a href="index.php?page=action_plans&action=edit&id=<?php echo $actionPlan['action_plan_id']; ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Action Plan Information</h5>
            </div>
            <div class="card-body">
                <h2 class="card-title"><?php echo htmlspecialchars($actionPlan['name']); ?></h2>
                
                <div class="mb-4">
                    <h5>Description</h5>
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($actionPlan['description'])); ?></p>
                </div>
                
                <div class="mb-4">
                    <h5>Comments</h5>
                    <?php if (empty($comments)): ?>
                        <p class="text-muted">No comments yet.</p>
                    <?php else: ?>
                        <div class="comments-section">
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment card mb-2">
                                    <div class="card-body py-2">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-1">
                                                <?php echo htmlspecialchars($comment['full_name'] ?? ''); ?>
                                                <?php if (isset($comment['is_management_staff']) && $comment['is_management_staff']): ?>
                                                    <span class="badge bg-info">Management</span>
                                                <?php endif; ?>
                                            </h6>
                                            <small class="text-muted"><?php echo isset($comment['created_at']) ? date('M d, Y g:i A', strtotime($comment['created_at'])) : ''; ?></small>
                                        </div>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($comment['comment_text'] ?? '')); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="index.php?page=action_plans&action=add_comment" method="post" class="mt-3">
                        <input type="hidden" name="action_plan_id" value="<?php echo $actionPlan['action_plan_id']; ?>">
                        <div class="mb-3">
                            <label for="comment" class="form-label">Add Comment</label>
                            <textarea class="form-control" id="comment" name="comment_text" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-comment"></i> Post Comment
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Status Information</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Status
                        <?php
                        $statusClass = '';
                        switch ($actionPlan['status']) {
                            case 'Pending':
                                $statusClass = 'bg-warning text-dark';
                                break;
                            case 'In Progress':
                                $statusClass = 'bg-info text-white';
                                break;
                            case 'Completed':
                                $statusClass = 'bg-success text-white';
                                break;
                        }
                        ?>
                        <span class="badge <?php echo $statusClass; ?>"><?php echo $actionPlan['status']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Created By
                        <span><?php echo htmlspecialchars($actionPlan['creator_full_name'] ?? $actionPlan['creator_name']); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Assigned To
                        <span>
                            <?php if (isset($actionPlan['assignee_id']) && $actionPlan['assignee_id']): ?>
                                <?php echo htmlspecialchars($actionPlan['assignee_full_name'] ?? $actionPlan['assignee_name']); ?>
                            <?php else: ?>
                                <em>Not Applicable</em>
                            <?php endif; ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Created On
                        <span><?php echo date('M d, Y', strtotime($actionPlan['created_at'])); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Due Date
                        <?php if (!empty($actionPlan['due_date'])): ?>
                            <?php
                            $dueDate = new DateTime($actionPlan['due_date']);
                            $today = new DateTime();
                            $interval = $today->diff($dueDate);
                            $isPast = $dueDate < $today;
                            $isToday = $dueDate->format('Y-m-d') === $today->format('Y-m-d');
                            
                            $dueDateClass = '';
                            if ($actionPlan['status'] !== 'Completed') {
                                if ($isPast) {
                                    $dueDateClass = 'text-danger fw-bold';
                                } elseif ($isToday) {
                                    $dueDateClass = 'text-warning fw-bold';
                                }
                            }
                            ?>
                            <span class="<?php echo $dueDateClass; ?>">
                                <?php echo date('M d, Y', strtotime($actionPlan['due_date'])); ?>
                                <?php if ($actionPlan['status'] !== 'Completed'): ?>
                                    <?php if ($isPast): ?>
                                        <span class="badge bg-danger">Overdue</span>
                                    <?php elseif ($isToday): ?>
                                        <span class="badge bg-warning text-dark">Today</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted">No due date</span>
                        <?php endif; ?>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Last Updated
                        <span><?php echo date('M d, Y g:i A', strtotime($actionPlan['updated_at'])); ?></span>
                    </li>
                </ul>
                
                <?php 
                // Define canUpdateStatus if it's not set
                $canUpdateStatus = isset($canUpdateStatus) ? $canUpdateStatus : 
                    (isset($currentUserId) && isset($actionPlan['assignee_id']) && $currentUserId == $actionPlan['assignee_id']);
                
                if ($canUpdateStatus && $actionPlan['status'] !== 'Completed'): 
                ?>
                <div class="mt-3">
                    <form action="index.php?page=action_plans&action=update_status" method="post">
                        <input type="hidden" name="id" value="<?php echo $actionPlan['action_plan_id']; ?>">
                        <div class="d-grid">
                            <?php if ($actionPlan['status'] === 'Pending'): ?>
                                <input type="hidden" name="status" value="In Progress">
                                <button type="submit" class="btn btn-info text-white">
                                    <i class="fas fa-play"></i> Mark as In Progress
                                </button>
                            <?php elseif ($actionPlan['status'] === 'In Progress'): ?>
                                <input type="hidden" name="status" value="Completed">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check"></i> Mark as Completed
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

