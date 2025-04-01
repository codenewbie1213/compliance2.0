<?php
declare(strict_types=1);

/**
 * Audit view template
 * Displays a single audit with its details, sections, questions, and responses
 */
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        <?= htmlspecialchars($audit['title']) ?>
        <?php if ($audit['is_template']): ?>
            <span class="badge bg-info ms-2">Template</span>
        <?php endif; ?>
    </h1>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php?page=dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="index.php?controller=audit">Audits</a></li>
        <li class="breadcrumb-item active">View Audit</li>
    </ol>
    
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-clipboard-list me-1"></i>
                        Audit Information
                    </div>
                    <div>
                        <?php 
                        $statusBadge = 'secondary';
                        switch($audit['status']) {
                            case 'draft': $statusBadge = 'secondary'; break;
                            case 'in_progress': $statusBadge = 'warning'; break;
                            case 'completed': $statusBadge = 'success'; break;
                            case 'archived': $statusBadge = 'dark'; break;
                        }
                        ?>
                        <span class="badge bg-<?= $statusBadge ?>"><?= ucfirst(str_replace('_', ' ', $audit['status'])) ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <dl class="row">
                                <dt class="col-sm-3">Description</dt>
                                <dd class="col-sm-9">
                                    <?= !empty($audit['description']) ? nl2br(htmlspecialchars($audit['description'])) : '<em class="text-muted">No description provided</em>' ?>
                                </dd>
                                
                                <dt class="col-sm-3">Created By</dt>
                                <dd class="col-sm-9"><?= htmlspecialchars($audit['created_by_name'] ?? 'Unknown') ?></dd>
                                
                                <dt class="col-sm-3">Assigned To</dt>
                                <dd class="col-sm-9">
                                    <?= !empty($audit['assigned_to_name']) ? htmlspecialchars($audit['assigned_to_name']) : '<em class="text-muted">Unassigned</em>' ?>
                                </dd>
                                
                                <dt class="col-sm-3">Due Date</dt>
                                <dd class="col-sm-9">
                                    <?php if (!empty($audit['due_date'])): ?>
                                        <?= date('F j, Y', strtotime($audit['due_date'])) ?>
                                        <?php 
                                        $now = new DateTime();
                                        $dueDate = new DateTime($audit['due_date']);
                                        if ($now > $dueDate && $audit['status'] !== 'completed' && $audit['status'] !== 'archived') {
                                            echo '<span class="badge bg-danger ms-1">Overdue</span>';
                                        }
                                        ?>
                                    <?php else: ?>
                                        <em class="text-muted">No due date</em>
                                    <?php endif; ?>
                                </dd>
                                
                                <dt class="col-sm-3">Created On</dt>
                                <dd class="col-sm-9"><?= date('F j, Y \a\t g:i a', strtotime($audit['created_at'])) ?></dd>
                                
                                <?php if (!empty($audit['updated_at'])): ?>
                                    <dt class="col-sm-3">Last Updated</dt>
                                    <dd class="col-sm-9"><?= date('F j, Y \a\t g:i a', strtotime($audit['updated_at'])) ?></dd>
                                <?php endif; ?>
                            </dl>
                        </div>
                        
                        <div class="col-md-4 text-center">
                            <?php if (isset($completionStats['score_percentage'])): ?>
                                <div class="mb-2">Overall Score</div>
                                <div class="display-4 fw-bold mb-2">
                                    <?= round((float)$completionStats['score_percentage'], 1) ?>%
                                </div>
                                <div class="progress mb-3" style="height: 25px;">
                                    <?php 
                                    $color = 'bg-danger';
                                    if ($completionStats['score_percentage'] >= 80) {
                                        $color = 'bg-success';
                                    } elseif ($completionStats['score_percentage'] >= 60) {
                                        $color = 'bg-info';
                                    }
                                    ?>
                                    <div class="progress-bar <?= $color ?>" 
                                         role="progressbar" 
                                         style="width: <?= $completionStats['score_percentage'] ?>%" 
                                         aria-valuenow="<?= $completionStats['score_percentage'] ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        <?= round((float)$completionStats['score_percentage'], 1) ?>%
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="mb-2">Overall Score</div>
                                <div class="display-4 fw-bold mb-2">N/A</div>
                            <?php endif; ?>
                            
                            <div class="mt-4">
                                <?php if ($audit['status'] !== 'completed' && $audit['status'] !== 'archived'): ?>
                                    <a href="index.php?controller=audit&action=respond&id=<?= $audit['audit_id'] ?>" class="btn btn-primary">
                                        <i class="fas fa-pen"></i> Complete Audit
                                    </a>
                                <?php endif; ?>
                                
                                <a href="index.php?controller=audit&action=edit&id=<?= $audit['audit_id'] ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (empty($sections)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            This audit doesn't have any sections or questions yet. 
            <a href="index.php?controller=audit&action=edit&id=<?= $audit['audit_id'] ?>">Edit this audit</a> to add sections and questions.
        </div>
    <?php else: ?>
        <?php foreach ($sections as $section): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-clipboard me-1"></i>
                    <?= htmlspecialchars($section['title']) ?>
                    <?php if ($section['weight'] > 0): ?>
                        <span class="badge bg-secondary ms-2">Weight: <?= $section['weight'] ?></span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (!empty($section['description'])): ?>
                        <div class="mb-4">
                            <?= nl2br(htmlspecialchars($section['description'])) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($section['questions'])): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No questions in this section.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 60%">Question</th>
                                        <th style="width: 15%">Response</th>
                                        <th style="width: 25%">Comments</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($section['questions'] as $question): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($question['text']) ?></div>
                                                <?php if (!empty($question['help_text'])): ?>
                                                    <div class="text-muted small mt-1">
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        <?= htmlspecialchars($question['help_text']) ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($question['weight']) && $question['weight'] > 0): ?>
                                                    <div class="badge bg-secondary mt-1">Points: <?= $question['weight'] ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($question['response'])): ?>
                                                    <?php if ($question['type'] === 'yes_no'): ?>
                                                        <?php if ($question['response'] === 'yes'): ?>
                                                            <span class="badge bg-success">Yes</span>
                                                        <?php elseif ($question['response'] === 'no'): ?>
                                                            <span class="badge bg-danger">No</span>
                                                        <?php elseif ($question['response'] === 'n/a'): ?>
                                                            <span class="badge bg-secondary">N/A</span>
                                                        <?php endif; ?>
                                                    <?php elseif ($question['type'] === 'likert'): ?>
                                                        <?php 
                                                        $likertBadge = 'secondary';
                                                        if ($question['response'] >= 4) {
                                                            $likertBadge = 'success';
                                                        } elseif ($question['response'] >= 3) {
                                                            $likertBadge = 'info';
                                                        } elseif ($question['response'] >= 2) {
                                                            $likertBadge = 'warning';
                                                        } else {
                                                            $likertBadge = 'danger';
                                                        }
                                                        ?>
                                                        <span class="badge bg-<?= $likertBadge ?>"><?= $question['response'] ?> / 5</span>
                                                    <?php elseif ($question['type'] === 'text'): ?>
                                                        <?= nl2br(htmlspecialchars($question['response'])) ?>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">No response</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($question['comments'])): ?>
                                                    <?= nl2br(htmlspecialchars($question['comments'])) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">No comments</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (!empty($audit['comments'])): ?>
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-comments me-1"></i>
                Overall Comments
            </div>
            <div class="card-body">
                <?= nl2br(htmlspecialchars($audit['comments'])) ?>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="mb-4 text-center">
        <a href="index.php?controller=audit" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Audit List
        </a>
        
        <?php if ($audit['status'] !== 'archived'): ?>
            <div class="btn-group ms-2">
                <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-file-export me-1"></i> Export
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="index.php?controller=audit&action=export&id=<?= $audit['audit_id'] ?>&format=pdf">Export as PDF</a></li>
                    <li><a class="dropdown-item" href="index.php?controller=audit&action=export&id=<?= $audit['audit_id'] ?>&format=excel">Export as Excel</a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <!-- Attachments Section -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Attachments</h5>
            <?php if (in_array('audits.edit', $_SESSION['permissions'] ?? [])): ?>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadModal">
                <i class="fas fa-upload"></i> Upload File
            </button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div id="attachmentsList">
                <?php if (empty($attachments)): ?>
                    <p class="text-muted mb-0">No attachments uploaded yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>File Name</th>
                                    <th>Type</th>
                                    <th>Size</th>
                                    <th>Uploaded By</th>
                                    <th>Date</th>
                                    <th>Comments</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attachments as $attachment): ?>
                                <tr>
                                    <td>
                                        <a href="uploads/audits/<?= htmlspecialchars($attachment['file_path']) ?>" 
                                           target="_blank" 
                                           class="text-decoration-none">
                                            <i class="fas fa-file me-2"></i>
                                            <?= htmlspecialchars($attachment['file_name']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($attachment['file_type']) ?></td>
                                    <td><?= number_format($attachment['file_size'] / 1024, 2) ?> KB</td>
                                    <td><?= htmlspecialchars($attachment['uploaded_by_name']) ?></td>
                                    <td><?= date('M j, Y g:i A', strtotime($attachment['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($attachment['comments']) ?></td>
                                    <td>
                                        <?php if (in_array('audits.edit', $_SESSION['permissions'] ?? [])): ?>
                                        <button type="button" 
                                                class="btn btn-danger btn-sm delete-attachment" 
                                                data-attachment-id="<?= $attachment['attachment_id'] ?>"
                                                data-file-name="<?= htmlspecialchars($attachment['file_name']) ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
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

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="uploadForm" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="audit_id" value="<?= $audit['audit_id'] ?>">
                        
                        <div class="mb-3">
                            <label for="file" class="form-label">File</label>
                            <input type="file" 
                                   class="form-control" 
                                   id="file" 
                                   name="file" 
                                   required>
                            <div class="form-text">
                                Allowed types: PDF, JPEG, PNG, DOC, DOCX (max 10MB)
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="comments" class="form-label">Comments</label>
                            <textarea class="form-control" 
                                      id="comments" 
                                      name="comments" 
                                      rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="uploadButton">Upload</button>
                </div>
            </div>
        </div>
    </div>

    <script>
</div> 