<?php
$pageTitle = "Complaints & Compliments";

// Include the header
require_once __DIR__ . '/../layouts/header.php';
?>

<!-- Complaints & Compliments -->
<div class="container-fluid px-4 py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-comment-alt me-2"></i> <?= $pageTitle ?></h1>
        <div>
            <a href="<?= BASE_URL ?>/complaints/dashboard" class="btn btn-info me-2">
                <i class="fas fa-chart-bar"></i> Dashboard
            </a>
            <a href="index.php?page=complaints&action=create" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Entry
            </a>
            <a href="index.php?page=complaints&action=create&public=1" class="btn btn-outline-primary ms-2">
                <i class="fas fa-external-link-alt"></i> Public Form
            </a>
        </div>
    </div>

    <?php include_once __DIR__ . '/../layouts/flash_messages.php'; ?>

    <!-- Filter and Search Form -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Filter &amp; Search</h5>
        </div>
        <div class="card-body">
            <form action="<?= BASE_URL ?>/complaints" method="get" class="row g-3">
                <input type="hidden" name="page" value="complaints">
                
                <!-- Type Filter -->
                <div class="col-md-2">
                    <label for="type" class="form-label">Type</label>
                    <select name="type" id="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="Complaint" <?= isset($_GET['type']) && $_GET['type'] === 'Complaint' ? 'selected' : '' ?>>Complaints</option>
                        <option value="Compliment" <?= isset($_GET['type']) && $_GET['type'] === 'Compliment' ? 'selected' : '' ?>>Compliments</option>
                    </select>
                </div>
                
                <!-- Status Filter -->
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="New" <?= isset($_GET['status']) && $_GET['status'] === 'New' ? 'selected' : '' ?>>New</option>
                        <option value="In Progress" <?= isset($_GET['status']) && $_GET['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="Resolved" <?= isset($_GET['status']) && $_GET['status'] === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                        <option value="Dismissed" <?= isset($_GET['status']) && $_GET['status'] === 'Dismissed' ? 'selected' : '' ?>>Dismissed</option>
                    </select>
                </div>
                
                <!-- Category Filter -->
                <div class="col-md-2">
                    <label for="category" class="form-label">Category</label>
                    <select name="category" id="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php if(isset($categories) && is_array($categories)): ?>
                            <?php foreach($categories as $category): ?>
                                <option value="<?= htmlspecialchars($category['name']) ?>" <?= isset($_GET['category']) && $_GET['category'] === $category['name'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <!-- Assigned To Filter -->
                <div class="col-md-3">
                    <label for="assigned_to" class="form-label">Assigned To</label>
                    <select name="assigned_to" id="assigned_to" class="form-select">
                        <option value="">All Users</option>
                        <?php if(isset($users) && is_array($users)): ?>
                            <?php foreach($users as $user): ?>
                                <option value="<?= $user['user_id'] ?>" <?= isset($_GET['assigned_to']) && $_GET['assigned_to'] == $user['user_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <!-- Search Term -->
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="Search by title, description..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                </div>
                
                <!-- Submit/Reset Buttons -->
                <div class="col-md-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <a href="index.php?page=complaints" class="btn btn-outline-secondary">
                        <i class="fas fa-undo"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Complaints & Compliments Table -->
    <div class="card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Feedback List</h5>
            <div class="text-muted">
                <?php if (isset($entries) && is_array($entries) && count($entries) > 0): ?>
                    Showing <?= count($entries) ?> of <?= $total_count ?? count($entries) ?> entries
                <?php else: ?>
                    No entries found
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (isset($entries) && is_array($entries) && count($entries) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Submitted By</th>
                                <th>Date Submitted</th>
                                <th>Assigned To</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($entries as $entry): ?>
                                <tr>
                                    <td><?= $entry['id'] ?></td>
                                    <td>
                                        <?php 
                                            // Generate the correct URLs based on the entry type
                                            if ($entry['type'] === 'Compliment') {
                                                // For compliments, use compliment_id instead of id
                                                $viewUrl = "index.php?page=complaints&action=view&compliment_id=" . $entry['id'] . "&type=compliment";
                                                $editUrl = "index.php?page=complaints&action=edit&compliment_id=" . $entry['id'] . "&type=compliment";
                                                $deleteUrl = "index.php?page=complaints&action=delete&compliment_id=" . $entry['id'] . "&type=compliment";
                                            } else {
                                                // For complaints, keep using id
                                                $viewUrl = "index.php?page=complaints&action=view&id=" . $entry['id'];
                                                $editUrl = "index.php?page=complaints&action=edit&id=" . $entry['id'];
                                                $deleteUrl = "index.php?page=complaints&action=delete&id=" . $entry['id'];
                                            }
                                        ?>
                                        <a href="<?= $viewUrl ?>">
                                            <?= htmlspecialchars($entry['title']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if($entry['type'] === 'Complaint'): ?>
                                            <span class="badge bg-danger">Complaint</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Compliment</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($entry['category']) ?></td>
                                    <td>
                                        <?php
                                        $statusClass = 'bg-secondary';
                                        switch($entry['status']) {
                                            case 'New':
                                                $statusClass = 'bg-info';
                                                break;
                                            case 'In Progress':
                                                $statusClass = 'bg-warning';
                                                break;
                                            case 'Resolved':
                                            case 'Completed':
                                                $statusClass = 'bg-success';
                                                break;
                                            case 'Dismissed':
                                                $statusClass = 'bg-danger';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?= $statusClass ?>"><?= $entry['status'] ?></span>
                                    </td>
                                    <td>
                                        <?php if(isset($entry['is_anonymous']) && $entry['is_anonymous']): ?>
                                            <em>Anonymous</em>
                                        <?php else: ?>
                                            <?= htmlspecialchars($entry['submitted_by'] ?? ($entry['created_by_name'] ?? 'N/A')) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d M Y', strtotime($entry['date_submitted'])) ?></td>
                                    <td><?= htmlspecialchars($entry['assigned_to_name'] ?? 'Unassigned') ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            
                                            <a href="<?= $viewUrl ?>" class="btn btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?= $editUrl ?>" class="btn btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $entry['id'] ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                        
                                        <!-- Delete Modal -->
                                        <div class="modal fade" id="deleteModal<?= $entry['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $entry['id'] ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteModalLabel<?= $entry['id'] ?>">Confirm Delete</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Are you sure you want to delete this entry? This action cannot be undone.
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <a href="<?= $deleteUrl ?>" class="btn btn-danger">Delete</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info m-3">
                    <i class="fas fa-info-circle me-2"></i> No entries found. Create a new entry or adjust your filters.
                </div>
            <?php endif; ?>
        </div>

        <?php if (isset($total_pages) && $total_pages > 1): ?>
            <div class="card-footer bg-light">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mb-0">
                        <?php if ($current_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="index.php?page=complaints&p=1<?= isset($_GET['type']) ? '&type=' . $_GET['type'] : '' ?><?= isset($_GET['status']) ? '&status=' . $_GET['status'] : '' ?><?= isset($_GET['category']) ? '&category=' . $_GET['category'] : '' ?><?= isset($_GET['search']) ? '&search=' . $_GET['search'] : '' ?>" aria-label="First">
                                    <span aria-hidden="true">&laquo;&laquo;</span>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="index.php?page=complaints&p=<?= $current_page - 1 ?><?= isset($_GET['type']) ? '&type=' . $_GET['type'] : '' ?><?= isset($_GET['status']) ? '&status=' . $_GET['status'] : '' ?><?= isset($_GET['category']) ? '&category=' . $_GET['category'] : '' ?><?= isset($_GET['search']) ? '&search=' . $_GET['search'] : '' ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                            <li class="page-item <?= $i === $current_page ? 'active' : '' ?>">
                                <a class="page-link" href="index.php?page=complaints&p=<?= $i ?><?= isset($_GET['type']) ? '&type=' . $_GET['type'] : '' ?><?= isset($_GET['status']) ? '&status=' . $_GET['status'] : '' ?><?= isset($_GET['category']) ? '&category=' . $_GET['category'] : '' ?><?= isset($_GET['search']) ? '&search=' . $_GET['search'] : '' ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($current_page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="index.php?page=complaints&p=<?= $current_page + 1 ?><?= isset($_GET['type']) ? '&type=' . $_GET['type'] : '' ?><?= isset($_GET['status']) ? '&status=' . $_GET['status'] : '' ?><?= isset($_GET['category']) ? '&category=' . $_GET['category'] : '' ?><?= isset($_GET['search']) ? '&search=' . $_GET['search'] : '' ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="index.php?page=complaints&p=<?= $total_pages ?><?= isset($_GET['type']) ? '&type=' . $_GET['type'] : '' ?><?= isset($_GET['status']) ? '&status=' . $_GET['status'] : '' ?><?= isset($_GET['category']) ? '&category=' . $_GET['category'] : '' ?><?= isset($_GET['search']) ? '&search=' . $_GET['search'] : '' ?>" aria-label="Last">
                                    <span aria-hidden="true">&raquo;&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include the footer
require_once __DIR__ . '/../layouts/footer.php';
?> 