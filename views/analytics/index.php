<!-- Add DataTables CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <div class="row">
        <div class="col">
            <h1 class="h3 mb-4">Performance Analytics Dashboard</h1>
        </div>
    </div>

    <!-- Overall Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Tasks</h5>
                    <h2 class="card-text"><?= number_format($overallStats['total_tasks'] ?? 0) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Completed Tasks</h5>
                    <h2 class="card-text"><?= number_format($overallStats['total_completed'] ?? 0) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Overall Completion Rate</h5>
                    <h2 class="card-text"><?= number_format($overallStats['completion_rate'] ?? 0, 1) ?>%</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">On-Time Completion Rate</h5>
                    <h2 class="card-text"><?= number_format($overallStats['on_time_rate'] ?? 0, 1) ?>%</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Trends Chart -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title">Performance Trends</h5>
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

    <!-- User Performance Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">User Performance Metrics</h5>
                    <div class="table-responsive">
                        <table class="table table-hover" id="performanceTable">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Total Tasks</th>
                                    <th>Completed</th>
                                    <th>In Progress</th>
                                    <th>Pending</th>
                                    <th>Completion Rate</th>
                                    <th>On-Time Rate</th>
                                    <th>Avg. Days to Complete</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($metrics as $metric): ?>
                                <tr>
                                    <td><?= htmlspecialchars($metric['full_name']) ?></td>
                                    <td><?= number_format($metric['total_assigned'] ?? 0) ?></td>
                                    <td><?= number_format($metric['completed_tasks'] ?? 0) ?></td>
                                    <td><?= number_format($metric['in_progress_tasks'] ?? 0) ?></td>
                                    <td><?= number_format($metric['pending_tasks'] ?? 0) ?></td>
                                    <td><?= number_format($metric['completion_rate'] ?? 0, 1) ?>%</td>
                                    <td><?= number_format($metric['on_time_completion_rate'] ?? 0, 1) ?>%</td>
                                    <td><?= number_format($metric['avg_completion_days'] ?? 0, 1) ?></td>
                                    <td>
                                        <a href="index.php?page=analytics&action=user_details&id=<?= $metric['user_id'] ?>" class="btn btn-sm btn-outline-primary">
                                            View Details
                                        </a>
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

<!-- JavaScript Dependencies -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable with jQuery
    $(document).ready(function() {
        $('#performanceTable').DataTable({
            order: [[5, 'desc']], // Sort by completion rate by default
            pageLength: 25,
            responsive: true,
            language: {
                search: "Search users:",
                lengthMenu: "Show _MENU_ users per page",
                info: "Showing _START_ to _END_ of _TOTAL_ users",
                infoEmpty: "No users found",
                emptyTable: "No user data available"
            }
        });
    });

    // Initialize the trends chart
    let trendsChart = null;
    const trendsCtx = document.getElementById('trendsChart').getContext('2d');

    function updateChart(period = 'weekly') {
        fetch(`index.php?page=analytics&action=get_trends&period=${period}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (!Array.isArray(data) || data.length === 0) {
                    throw new Error('No data available');
                }

                const labels = data.map(item => {
                    const date = new Date(item.period);
                    if (period === 'monthly') {
                        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short' });
                    } else if (period === 'weekly') {
                        return `Week of ${date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}`;
                    }
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                });

                const completedTasks = data.map(item => parseInt(item.completed_tasks) || 0);
                const totalTasks = data.map(item => parseInt(item.total_tasks) || 0);

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
                            backgroundColor: 'rgba(75, 192, 192, 0.1)',
                            tension: 0.1,
                            fill: true
                        }, {
                            label: 'Total Tasks',
                            data: totalTasks,
                            borderColor: 'rgb(54, 162, 235)',
                            backgroundColor: 'rgba(54, 162, 235, 0.1)',
                            tension: 0.1,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                    callback: function(value) {
                                        return value.toLocaleString();
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `${context.dataset.label}: ${context.parsed.y.toLocaleString()}`;
                                    }
                                }
                            }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        layout: {
                            padding: {
                                bottom: 20
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Error fetching trends data:', error);
                const container = document.getElementById('trendsChart').parentElement;
                container.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Error loading trends data. Please try again later.
                    </div>`;
            });
    }

    // Initialize chart with weekly data
    updateChart();

    // Handle period selector clicks
    document.querySelectorAll('.period-selector').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('.period-selector').forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            updateChart(this.dataset.period);
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
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 