<?php
$pageTitle = 'Action Plans';
$contentView = __FILE__;
?>

<?php include_once __DIR__ . '/../layouts/main.php'; ?> 

<h1 class="mb-4"><i class="fas fa-tasks"></i> Action Plans</h1>

<!-- Search and Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form action="index.php" method="get" class="row g-3" id="search-form">
            <input type="hidden" name="page" value="action_plans">
            <?php if (isset($_GET['view']) && $_GET['view'] === 'all'): ?>
            <input type="hidden" name="view" value="all">
            <?php endif; ?>
            
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" id="search-input" placeholder="Search by name..." value="<?php echo htmlspecialchars($searchTerm ?? ''); ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>
            
            <div class="col-md-3">
                <select name="status" id="status-filter" class="form-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="Pending" <?php echo ($statusFilter ?? '') === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="In Progress" <?php echo ($statusFilter ?? '') === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="Completed" <?php echo ($statusFilter ?? '') === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <div class="d-flex gap-2">
                    <a href="index.php?page=action_plans<?php echo isset($_GET['view']) && $_GET['view'] === 'all' ? '&view=all' : ''; ?>" class="btn btn-outline-secondary flex-grow-1">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                    <?php if (isset($_GET['view']) && $_GET['view'] === 'all'): ?>
                    <button type="button" class="btn btn-outline-primary" id="print-btn">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?php echo !isset($_GET['view']) || $_GET['view'] !== 'all' ? 'active' : ''; ?>" href="index.php?page=action_plans">
            <i class="fas fa-user"></i> My Action Plans
        </a>
    </li>
    <?php if (isset($_SESSION['user']) && $_SESSION['user']['is_management_staff']): ?>
    <li class="nav-item">
        <a class="nav-link <?php echo isset($_GET['view']) && $_GET['view'] === 'all' ? 'active' : ''; ?>" href="index.php?page=action_plans&view=all">
            <i class="fas fa-globe"></i> All Action Plans
        </a>
    </li>
    <?php endif; ?>
</ul>

<?php if (isset($_GET['view']) && $_GET['view'] === 'all' && isset($_SESSION['user']) && $_SESSION['user']['is_management_staff']): ?>
    <!-- All Action Plans (Management View) -->
    <div class="card mb-4" id="printable-area">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-list"></i> All Action Plans</h5>
        </div>
        <div class="card-body">
            <?php if (empty($allActionPlans)): ?>
                <p id="no-results" class="d-none">No action plans found matching your search.</p>
                <p id="no-plans">No action plans found.</p>
            <?php else: ?>
                <p id="no-results" class="d-none">No action plans found matching your search.</p>
                <div class="table-responsive">
                    <table class="table table-hover" id="action-plans-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Creator</th>
                                <th>Assignee</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th class="no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allActionPlans as $plan): ?>
                                <tr class="action-plan-row">
                                    <td><?php echo htmlspecialchars($plan['name']); ?></td>
                                    <td><?php echo htmlspecialchars($plan['creator_name']); ?></td>
                                    <td><?php echo htmlspecialchars($plan['assignee_name'] ?? 'Not Assigned'); ?></td>
                                    <td><?php echo $plan['due_date'] ? date('Y-m-d', strtotime($plan['due_date'])) : 'No due date'; ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $plan['status'] === 'Completed' ? 'success' : 
                                                ($plan['status'] === 'In Progress' ? 'warning' : 'secondary'); 
                                        ?>">
                                            <?php echo htmlspecialchars($plan['status']); ?>
                                        </span>
                                    </td>
                                    <td class="no-print">
                                        <a href="index.php?page=action_plans&action=view&id=<?php echo $plan['action_plan_id']; ?>" 
                                           class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i>
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

    <!-- Print-specific styles -->
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            #printable-area, #printable-area * {
                visibility: visible;
            }
            #printable-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
            .card {
                border: none !important;
            }
            .card-header {
                background-color: #f8f9fa !important;
                color: #000 !important;
                border-bottom: 2px solid #dee2e6 !important;
            }
            .badge {
                border: 1px solid #000 !important;
                color: #000 !important;
                background-color: transparent !important;
            }
        }
    </style>

    <!-- Live search script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Live search functionality
            const searchInput = document.getElementById('search-input');
            const rows = document.querySelectorAll('.action-plan-row');
            const noResults = document.getElementById('no-results');
            const table = document.getElementById('action-plans-table');
            const noPlans = document.getElementById('no-plans');
            
            if (searchInput && rows.length > 0) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    let visibleCount = 0;
                    
                    rows.forEach(row => {
                        const name = row.cells[0].textContent.toLowerCase();
                        const creator = row.cells[1].textContent.toLowerCase();
                        const assignee = row.cells[2].textContent.toLowerCase();
                        const status = row.cells[4].textContent.toLowerCase();
                        
                        if (name.includes(searchTerm) || 
                            creator.includes(searchTerm) || 
                            assignee.includes(searchTerm) || 
                            status.includes(searchTerm)) {
                            row.style.display = '';
                            visibleCount++;
                        } else {
                            row.style.display = 'none';
                        }
                    });
                    
                    // Show/hide no results message
                    if (visibleCount === 0 && rows.length > 0) {
                        if (noResults) noResults.classList.remove('d-none');
                        if (table) table.classList.add('d-none');
                    } else {
                        if (noResults) noResults.classList.add('d-none');
                        if (table) table.classList.remove('d-none');
                    }
                });
            }
            
            // Print functionality
            const printBtn = document.getElementById('print-btn');
            if (printBtn) {
                printBtn.addEventListener('click', function() {
                    window.print();
                });
            }
        });
    </script>
<?php else: ?>
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
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Creator</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignedActionPlans as $plan): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($plan['name']); ?></td>
                                    <td><?php echo htmlspecialchars($plan['creator_name']); ?></td>
                                    <td><?php echo $plan['due_date'] ? date('Y-m-d', strtotime($plan['due_date'])) : 'No due date'; ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $plan['status'] === 'Completed' ? 'success' : 
                                                ($plan['status'] === 'In Progress' ? 'warning' : 'secondary'); 
                                        ?>">
                                            <?php echo htmlspecialchars($plan['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="index.php?page=action_plans&action=view&id=<?php echo $plan['action_plan_id']; ?>" 
                                           class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i>
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

    <!-- Action Plans Created by Me -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-list"></i> Action Plans Created by Me</h5>
        </div>
        <div class="card-body">
            <?php if (empty($createdActionPlans)): ?>
                <p>No action plans created by you.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Assignee</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($createdActionPlans as $plan): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($plan['name']); ?></td>
                                    <td><?php echo htmlspecialchars($plan['assignee_name'] ?? 'Not Assigned'); ?></td>
                                    <td><?php echo $plan['due_date'] ? date('Y-m-d', strtotime($plan['due_date'])) : 'No due date'; ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $plan['status'] === 'Completed' ? 'success' : 
                                                ($plan['status'] === 'In Progress' ? 'warning' : 'secondary'); 
                                        ?>">
                                            <?php echo htmlspecialchars($plan['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="index.php?page=action_plans&action=view&id=<?php echo $plan['action_plan_id']; ?>" 
                                           class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i>
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
<?php endif; ?>

