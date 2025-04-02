<?php
$page_title = "Edit " . ($entry['type'] ?? 'Feedback');
include __DIR__ . '/../layouts/header.php';

// Determine if this is a compliment or complaint
$isCompliment = isset($entry['type']) && $entry['type'] === 'Compliment';
?>

<div class="container-fluid px-4">
    <div class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="index.php?page=complaints">Complaints & Compliments</a></li>
            <?php 
                $viewUrl = "index.php?page=complaints&action=view&id=" . ($entry['id'] ?? 0);
                if ($isCompliment) {
                    $viewUrl .= "&type=compliment";
                }
            ?>
            <li class="breadcrumb-item"><a href="<?= $viewUrl ?>">View <?= $entry['type'] ?? 'Feedback' ?></a></li>
            <li class="breadcrumb-item active"><?= $page_title ?></li>
        </ol>
        <h1 class="mt-4"><?= $page_title ?></h1>
    </div>

    <?php include_once __DIR__ . '/../layouts/flash_messages.php'; ?>

    <div class="card mb-4">
        <div class="card-body">
            <?php 
                $formUrl = "index.php?page=complaints&action=update";
                if ($isCompliment) {
                    $formUrl .= "&compliment_id=" . ($entry['id'] ?? 0) . "&type=compliment";
                } else {
                    $formUrl .= "&id=" . ($entry['id'] ?? 0);
                }
            ?>
            <form action="<?= $formUrl ?>" method="post" class="needs-validation" novalidate>
                <?php if ($isCompliment): ?>
                    <input type="hidden" name="compliment_id" value="<?= $entry['id'] ?? 0 ?>">
                <?php else: ?>
                    <input type="hidden" name="id" value="<?= $entry['id'] ?? 0 ?>">
                <?php endif; ?>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="type" class="form-label">Type of Feedback <span class="text-danger">*</span></label>
                        <select name="type" id="type" class="form-select" required <?= $isCompliment ? 'readonly disabled' : '' ?>>
                            <option value="">Select Type</option>
                            <option value="Complaint" <?= isset($entry['type']) && $entry['type'] === 'Complaint' ? 'selected' : '' ?>>Complaint</option>
                            <option value="Compliment" <?= isset($entry['type']) && $entry['type'] === 'Compliment' ? 'selected' : '' ?>>Compliment</option>
                        </select>
                        <?php if ($isCompliment): ?>
                            <input type="hidden" name="type" value="Compliment">
                        <?php endif; ?>
                        <div class="invalid-feedback">Please select the type of feedback.</div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="category" id="category" class="form-select" required <?= $isCompliment ? 'readonly disabled' : '' ?>>
                            <option value="">Select Category</option>
                            <?php if(isset($categories) && is_array($categories)): ?>
                                <?php foreach($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category['name'] ?? '') ?>" 
                                            data-applies-to="<?= htmlspecialchars($category['applies_to'] ?? 'Both') ?>"
                                            <?= isset($entry['category']) && $entry['category'] === ($category['name'] ?? '') ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name'] ?? '') ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <?php if ($isCompliment): ?>
                            <input type="hidden" name="category" value="Compliment">
                        <?php endif; ?>
                        <div class="invalid-feedback">Please select a category.</div>
                    </div>
                </div>

                <?php if (!$isCompliment): ?>
                <div class="mb-3">
                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" 
                           value="<?= htmlspecialchars($entry['title'] ?? '') ?>" required>
                    <div class="invalid-feedback">Please provide a title.</div>
                </div>
                <?php else: ?>
                    <input type="hidden" name="title" value="<?= htmlspecialchars($entry['title'] ?? '') ?>">
                <?php endif; ?>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="description" name="description" rows="5" required><?= htmlspecialchars($entry['description'] ?? '') ?></textarea>
                    <div class="invalid-feedback">Please provide a description.</div>
                </div>

                <?php if (!$isCompliment): ?>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="">Select Status</option>
                            <option value="New" <?= isset($entry['status']) && $entry['status'] === 'New' ? 'selected' : '' ?>>New</option>
                            <option value="In Progress" <?= isset($entry['status']) && $entry['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="Resolved" <?= isset($entry['status']) && $entry['status'] === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                            <option value="Dismissed" <?= isset($entry['status']) && $entry['status'] === 'Dismissed' ? 'selected' : '' ?>>Dismissed</option>
                        </select>
                        <div class="invalid-feedback">Please select a status.</div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="assigned_to" class="form-label">Assign To</label>
                        <select name="assigned_to" id="assigned_to" class="form-select">
                            <option value="">Select User</option>
                            <?php if(isset($users) && is_array($users)): ?>
                                <?php foreach($users as $user): ?>
                                    <option value="<?= $user['user_id'] ?? 0 ?>" 
                                            <?= isset($entry['assigned_to']) && ($entry['assigned_to'] == ($user['user_id'] ?? 0)) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <?php else: ?>
                    <input type="hidden" name="status" value="Completed">
                <?php endif; ?>
                
                <div class="mb-3">
                    <label for="submitted_by" class="form-label">Submitted By</label>
                    <input type="text" class="form-control" id="submitted_by" name="submitted_by" 
                           value="<?= htmlspecialchars($entry['submitted_by'] ?? '') ?>">
                </div>
                
                <?php if (!$isCompliment): ?>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="contact_email" class="form-label">Contact Email</label>
                        <input type="email" class="form-control" id="contact_email" name="contact_email" 
                               value="<?= htmlspecialchars($entry['contact_email'] ?? '') ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label for="contact_phone" class="form-label">Contact Phone</label>
                        <input type="tel" class="form-control" id="contact_phone" name="contact_phone" 
                               value="<?= htmlspecialchars($entry['contact_phone'] ?? '') ?>">
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="is_anonymous" name="is_anonymous" value="1" 
                           <?= isset($entry['is_anonymous']) && $entry['is_anonymous'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_anonymous">Anonymous Submission</label>
                    <div class="form-text">The submitter's identity will be hidden in reports and public displays.</div>
                </div>
                
                <?php if(!$isCompliment && isset($entry['status']) && ($entry['status'] === 'Resolved' || $entry['status'] === 'Dismissed')): ?>
                <div class="mb-3" id="resolution_notes_container">
                    <label for="resolution_notes" class="form-label">Resolution Notes</label>
                    <textarea class="form-control" id="resolution_notes" name="resolution_notes" rows="4"><?= htmlspecialchars($entry['resolution_notes'] ?? '') ?></textarea>
                    <div class="form-text">Provide details on how this <?= strtolower($entry['type'] ?? 'feedback') ?> was resolved or why it was dismissed.</div>
                </div>
                <?php endif; ?>
                
                <hr class="my-4">
                
                <div class="d-flex justify-content-between">
                    <a href="<?= $viewUrl ?>" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Update <?= $entry['type'] ?? 'Feedback' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.querySelector('.needs-validation');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });
    
    // Status change handler for resolution notes
    const statusSelect = document.getElementById('status');
    const typeSelect = document.getElementById('type');
    const categorySelect = document.getElementById('category');
    
    // Only set up these handlers if the elements exist (not for compliments)
    if (statusSelect) {
        // Check if we need to add resolution notes section
        statusSelect.addEventListener('change', function() {
            const status = this.value;
            const resolutionNotesContainer = document.getElementById('resolution_notes_container');
            
            // If the container doesn't exist yet, create it
            if (!resolutionNotesContainer && (status === 'Resolved' || status === 'Dismissed')) {
                const container = document.createElement('div');
                container.id = 'resolution_notes_container';
                container.className = 'mb-3';
                
                container.innerHTML = `
                    <label for="resolution_notes" class="form-label">Resolution Notes</label>
                    <textarea class="form-control" id="resolution_notes" name="resolution_notes" rows="4"><?= htmlspecialchars($entry['resolution_notes'] ?? '') ?></textarea>
                    <div class="form-text">Provide details on how this ${typeSelect.value.toLowerCase() || 'feedback'} was resolved or why it was dismissed.</div>
                `;
                
                // Insert before the hr element
                const hr = document.querySelector('hr');
                hr.parentNode.insertBefore(container, hr);
            } else if (resolutionNotesContainer && !(status === 'Resolved' || status === 'Dismissed')) {
                // Remove the container if status is not Resolved or Dismissed
                resolutionNotesContainer.remove();
            }
        });
    }
    
    // Type and category dependency handling
    if (typeSelect && categorySelect) {
        typeSelect.addEventListener('change', function() {
            const selectedType = this.value;
            
            // Reset category select
            for (let i = 0; i < categorySelect.options.length; i++) {
                const option = categorySelect.options[i];
                const appliesTo = option.getAttribute('data-applies-to') || 'Both';
                
                if (!appliesTo || i === 0) {
                    option.style.display = '';
                    continue;
                }
                
                if (selectedType === 'Complaint' && (appliesTo === 'Both' || appliesTo === 'Complaints')) {
                    option.style.display = '';
                } else if (selectedType === 'Compliment' && (appliesTo === 'Both' || appliesTo === 'Compliments')) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            }
            
            // Reset selection if current category doesn't apply to selected type
            const currentOption = categorySelect.options[categorySelect.selectedIndex];
            if (currentOption && currentOption.style.display === 'none') {
                categorySelect.selectedIndex = 0;
            }
        });
        
        // Initialize the category dropdown based on selected type
        if (typeSelect.value) {
            typeSelect.dispatchEvent(new Event('change'));
        }
    }
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?> 