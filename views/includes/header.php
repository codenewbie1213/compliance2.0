<?php
// Only start session if one hasn't been started already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize page and action variables
$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo $pageTitle ?? 'Action Plan Management'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/action_plan/assets/css/style.css" rel="stylesheet">
    
    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
</head>
<body>
    <!-- Header -->
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="index.php">Action Plan Management</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>" href="index.php?page=dashboard">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $page === 'action_plans' ? 'active' : ''; ?>" href="index.php?page=action_plans">
                                    <i class="fas fa-tasks"></i> Action Plans
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $page === 'feedback' ? 'active' : ''; ?>" href="index.php?page=feedback">
                                    <i class="fas fa-comments"></i> Feedback
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $page === 'analytics' ? 'active' : ''; ?>" href="index.php?page=analytics">
                                    <i class="fas fa-chart-line"></i> Analytics
                                </a>
                            </li>
                            <?php if ($page === 'action_plans' && $action !== 'create'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="index.php?page=action_plans&action=create">
                                    <i class="fas fa-plus"></i> Create Action Plan
                                </a>
                            </li>
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>
                    <ul class="navbar-nav">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user"></i> <?php echo isset($_SESSION['user']['email']) ? htmlspecialchars($_SESSION['user']['email']) : 'User'; ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <li><a class="dropdown-item" href="index.php?page=logout&csrf_token=<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $page === 'login' ? 'active' : ''; ?>" href="index.php?page=login">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $page === 'register' ? 'active' : ''; ?>" href="index.php?page=register">
                                    <i class="fas fa-user-plus"></i> Register
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    
    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Flash Messages -->
        <?php if (isset($_SESSION['flash_message'])): ?>
            <?php 
            $validTypes = ['success', 'danger', 'warning', 'info'];
            $messageType = in_array($_SESSION['flash_message']['type'], $validTypes) ? $_SESSION['flash_message']['type'] : 'info';
            ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['flash_message']['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?> 