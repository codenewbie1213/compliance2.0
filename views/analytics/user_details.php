<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php?page=analytics">Analytics Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">User Details</li>
                </ol>
            </nav>
            <h1 class="h3"><?= htmlspecialchars($metrics['full_name']) ?>'s Performance</h1>
        </div>
    </div>

    <!-- Performance Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Tasks</h5>
                    <h2 class="card-text"><?= number_format($metrics['total_assigned'] ?? 0) ?></h2>
                    <div class="small text-muted">
                        Assigned action plans
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Completion Rate</h5>
                    <h2 class="card-text"><?= number_format($metrics['completion_rate'] ?? 0, 1) ?>%</h2>
                    <div class="small text-muted">
                        <?= number_format($metrics['completed_tasks'] ?? 0) ?> completed of <?= number_format($metrics['total_assigned'] ?? 0) ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">On-Time Rate</h5>
                    <h2 class="card-text"><?= number_format($metrics['on_time_completion_rate'] ?? 0, 1) ?>%</h2>
                    <div class="small text-muted">
                        Tasks completed before due date
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Avg. Completion Time</h5>
                    <h2 class="card-text"><?= number_format($metrics['avg_completion_days'] ?? 0, 1) ?></h2>
                    <div class="small text-muted">
                        Days to complete a task
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Status Distribution -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Task Status Distribution</h5>
                    <div class="chart-container" style="position: relative; height: 300px; width: 100%;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Performance Trends</h5>
                    <div class="d-flex justify-content-end mb-3">
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-secondary btn-sm period-selector active" data-period="weekly">Weekly</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm period-selector" data-period="monthly">Monthly</button>
                        </div>
                    </div>
                    <div class="chart-container" style="position: relative; height: 300px; width: 100%;">
                        <canvas id="trendsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Recent Activity</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Action Plan</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($metrics['recent_activity'] as $activity): ?>
                                <tr>
                                    <td><?= date('M j, Y', strtotime($activity['date'])) ?></td>
                                    <td>
                                        <?php if ($activity['type'] === 'action_plan'): ?>
                                            <span class="badge bg-primary">Action Plan Update</span>
                                        <?php elseif ($activity['type'] === 'comment'): ?>
                                            <span class="badge bg-info">Comment</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Attachment</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($activity['title']) ?></td>
                                    <td>
                                        <?php if ($activity['status']): ?>
                                            <span class="badge bg-<?= $activity['status'] === 'Completed' ? 'success' : 
                                                               ($activity['status'] === 'In Progress' ? 'warning' : 'secondary') ?>">
                                                <?= htmlspecialchars($activity['status']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize status distribution chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'In Progress', 'Pending'],
            datasets: [{
                data: [
                    <?= $metrics['completed_tasks'] ?? 0 ?>,
                    <?= $metrics['in_progress_tasks'] ?? 0 ?>,
                    <?= $metrics['pending_tasks'] ?? 0 ?>
                ],
                backgroundColor: [
                    'rgb(75, 192, 192)',
                    'rgb(255, 205, 86)',
                    'rgb(201, 203, 207)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20
                    }
                }
            }
        }
    });

    // Initialize trends chart
    let trendsChart = null;
    const trendsCtx = document.getElementById('trendsChart').getContext('2d');

    function updateTrendsChart(period = 'weekly') {
        fetch(`index.php?page=analytics&action=get_trends&user_id=<?= $metrics['user_id'] ?>&period=${period}`)
            .then(response => response.json())
            .then(data => {
                const labels = data.map(item => item.period);
                const completedTasks = data.map(item => item.completed_tasks);
                const totalTasks = data.map(item => item.total_tasks);

                if (trendsChart) {
                    trendsChart.destroy();
                }

                trendsChart = new Chart(trendsCtx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Completed Tasks',
                            data: completedTasks,
                            borderColor: 'rgb(75, 192, 192)',
                            tension: 0.1,
                            fill: false
                        }, {
                            label: 'Total Tasks',
                            data: totalTasks,
                            borderColor: 'rgb(54, 162, 235)',
                            tension: 0.1,
                            fill: false
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20
                                }
                            }
                        },
                        layout: {
                            padding: {
                                bottom: 20
                            }
                        }
                    }
                });
            })
            .catch(error => console.error('Error fetching trends data:', error));
    }

    // Initialize trends chart with weekly data
    updateTrendsChart();

    // Handle period selector clicks
    document.querySelectorAll('.period-selector').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('.period-selector').forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            updateTrendsChart(this.dataset.period);
        });
    });
});
</script>

<style>
.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
    margin-bottom: 20px;
}
.card.h-100 {
    height: 100% !important;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 