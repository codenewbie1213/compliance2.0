<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="container mt-4">
    <h1>Feedback System</h1>
    
    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="feedbackTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="compliments-tab" data-bs-toggle="tab" href="#compliments" role="tab">
                Compliments
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="complaints-tab" data-bs-toggle="tab" href="#complaints" role="tab">
                Complaints
            </a>
        </li>
    </ul>
    
    <!-- Tab Content -->
    <div class="tab-content" id="feedbackTabContent">
        <!-- Compliments Tab -->
        <div class="tab-pane fade show active" id="compliments" role="tabpanel">
            <div class="row mb-4">
                <div class="col">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addComplimentModal">
                        Add Compliment
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>From</th>
                            <th>To</th>
                            <th>Description</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($compliments as $compliment): ?>
                        <tr>
                            <td><?= htmlspecialchars($compliment['from_user_name']) ?></td>
                            <td><?= htmlspecialchars($compliment['about_user_name']) ?></td>
                            <td><?= strlen($compliment['description']) > 50 ? htmlspecialchars(substr($compliment['description'], 0, 50)) . '...' : htmlspecialchars($compliment['description']) ?></td>
                            <td><?= date('Y-m-d', strtotime($compliment['created_at'])) ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#viewComplimentModal"
                                        data-from="<?= htmlspecialchars($compliment['from_user_name']) ?>"
                                        data-to="<?= htmlspecialchars($compliment['about_user_name']) ?>"
                                        data-description="<?= htmlspecialchars($compliment['description']) ?>"
                                        data-date="<?= date('F j, Y g:i A', strtotime($compliment['created_at'])) ?>">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Complaints Tab -->
        <div class="tab-pane fade" id="complaints" role="tabpanel">
            <div class="row mb-4">
                <div class="col">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addComplaintModal">
                        Submit Complaint
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>From</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Action Plans</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($complaints as $complaint): ?>
                        <tr>
                            <td><?= htmlspecialchars($complaint['from_user_name']) ?></td>
                            <td><?= strlen($complaint['description']) > 50 ? htmlspecialchars(substr($complaint['description'], 0, 50)) . '...' : htmlspecialchars($complaint['description']) ?></td>
                            <td>
                                <span class="badge bg-<?= $complaint['status'] === 'Pending' ? 'warning' : ($complaint['status'] === 'In Progress' ? 'info' : 'success') ?>">
                                    <?= htmlspecialchars($complaint['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($complaint['action_plan_names'])): ?>
                                    <?= htmlspecialchars($complaint['action_plan_names']) ?>
                                <?php endif; ?>
                                <?php if ($complaint['status'] !== 'Resolved'): ?>
                                    <button type="button" class="btn btn-sm btn-primary mt-1" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#createActionPlanModal"
                                            data-complaint-id="<?= $complaint['complaint_id'] ?>">
                                        Add Action Plan
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td><?= date('Y-m-d', strtotime($complaint['created_at'])) ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-info" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#viewComplaintModal"
                                        data-from="<?= htmlspecialchars($complaint['from_user_name']) ?>"
                                        data-description="<?= htmlspecialchars($complaint['description']) ?>"
                                        data-status="<?= htmlspecialchars($complaint['status']) ?>"
                                        data-action-plans="<?= htmlspecialchars($complaint['action_plan_names'] ?? 'No Action Plans') ?>"
                                        data-date="<?= date('F j, Y g:i A', strtotime($complaint['created_at'])) ?>">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Compliment Modal -->
<div class="modal fade" id="addComplimentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Compliment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="index.php?page=feedback&action=add_compliment" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="from_name" class="form-label">Compliment From</label>
                        <input type="text" class="form-control" id="from_name" name="from_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="compliment_description" class="form-label">Description</label>
                        <textarea class="form-control" id="compliment_description" name="description" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Submit Compliment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Complaint Modal -->
<div class="modal fade" id="addComplaintModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Submit Complaint</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="index.php?page=feedback&action=add_complaint" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="from_name" class="form-label">Complaint From</label>
                        <input type="text" class="form-control" id="from_name" name="from_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="complaint_description" class="form-label">Description</label>
                        <textarea class="form-control" id="complaint_description" name="description" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Submit Complaint</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Action Plan Modal -->
<div class="modal fade" id="createActionPlanModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Action Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="index.php?page=feedback&action=create_action_plan" method="POST">
                <input type="hidden" name="complaint_id" id="complaint_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="action_plan_name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="action_plan_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="action_plan_description" class="form-label">Description</label>
                        <textarea class="form-control" id="action_plan_description" name="description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="assignee_id" class="form-label">Assign To</label>
                        <select class="form-select" id="assignee_id" name="assignee_id">
                            <option value="">Select User</option>
                            <option value="0">Not Applicable</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= htmlspecialchars($user['user_id']) ?>"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="due_date" class="form-label">Due Date</label>
                        <input type="date" class="form-control" id="due_date" name="due_date" >
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Create Action Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Compliment Modal -->
<div class="modal fade" id="viewComplimentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">View Compliment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <dl class="row">
                    <dt class="col-sm-3">From:</dt>
                    <dd class="col-sm-9" id="complimentFrom"></dd>
                    
                    <dt class="col-sm-3">To:</dt>
                    <dd class="col-sm-9" id="complimentTo"></dd>
                    
                    <dt class="col-sm-3">Date:</dt>
                    <dd class="col-sm-9" id="complimentDate"></dd>
                    
                    <dt class="col-sm-3">Description:</dt>
                    <dd class="col-sm-9" id="complimentDescription"></dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- View Complaint Modal -->
<div class="modal fade" id="viewComplaintModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">View Complaint</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <dl class="row">
                    <dt class="col-sm-3">From:</dt>
                    <dd class="col-sm-9" id="complaintFrom"></dd>
                    
                    <dt class="col-sm-3">Status:</dt>
                    <dd class="col-sm-9" id="complaintStatus"></dd>
                    
                    <dt class="col-sm-3">Action Plans:</dt>
                    <dd class="col-sm-9" id="complaintActionPlans"></dd>
                    
                    <dt class="col-sm-3">Date:</dt>
                    <dd class="col-sm-9" id="complaintDate"></dd>
                    
                    <dt class="col-sm-3">Description:</dt>
                    <dd class="col-sm-9" id="complaintDescription"></dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle complaint ID for action plan creation
    var createActionPlanModal = document.getElementById('createActionPlanModal');
    if (createActionPlanModal) {
        createActionPlanModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var complaintId = button.getAttribute('data-complaint-id');
            var complaintIdInput = document.getElementById('complaint_id');
            complaintIdInput.value = complaintId;
        });
    }
    
    // Set minimum date for due date input
    var dueDateInput = document.getElementById('due_date');
    if (dueDateInput) {
        var today = new Date().toISOString().split('T')[0];
        dueDateInput.setAttribute('min', today);
    }
    
    // Handle view compliment modal
    var viewComplimentModal = document.getElementById('viewComplimentModal');
    if (viewComplimentModal) {
        viewComplimentModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            document.getElementById('complimentFrom').textContent = button.getAttribute('data-from');
            document.getElementById('complimentTo').textContent = button.getAttribute('data-to');
            document.getElementById('complimentDescription').textContent = button.getAttribute('data-description');
            document.getElementById('complimentDate').textContent = button.getAttribute('data-date');
        });
    }
    
    // Handle view complaint modal
    var viewComplaintModal = document.getElementById('viewComplaintModal');
    if (viewComplaintModal) {
        viewComplaintModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            document.getElementById('complaintFrom').textContent = button.getAttribute('data-from');
            document.getElementById('complaintStatus').textContent = button.getAttribute('data-status');
            document.getElementById('complaintActionPlans').textContent = button.getAttribute('data-action-plans');
            document.getElementById('complaintDescription').textContent = button.getAttribute('data-description');
            document.getElementById('complaintDate').textContent = button.getAttribute('data-date');
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 