/**
 * AkademiX Chart.js Integration
 * Fetches data from API and renders dashboard charts
 */

document.addEventListener('DOMContentLoaded', () => {
    // Only run if we are on a page with charts
    const pieCanvas = document.getElementById('taskStatusChart');
    if (!pieCanvas) return;

    // Common Chart.js Defaults for Dark Theme
    Chart.defaults.color = '#94a3b8';
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(17, 24, 39, 0.9)';
    Chart.defaults.plugins.tooltip.titleColor = '#f1f5f9';
    Chart.defaults.plugins.tooltip.bodyColor = '#f1f5f9';
    Chart.defaults.plugins.tooltip.borderColor = 'rgba(255,255,255,0.1)';
    Chart.defaults.plugins.tooltip.borderWidth = 1;
    Chart.defaults.plugins.tooltip.padding = 10;
    Chart.defaults.plugins.tooltip.cornerRadius = 8;

    // Fetch data
    fetch(getBaseUrl() + 'api/dashboard_stats.php')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.charts) {
                initPieChart(data.charts.pie);
                initBarChart(data.charts.bar);
                initLineChart(data.charts.line);
            }
        })
        .catch(err => console.error('Error loading chart data:', err));
});

function initPieChart(data) {
    const ctx = document.getElementById('taskStatusChart');
    if (!ctx) return;

    // Map statuses to specific colors
    const colors = data.labels.map(label => {
        if (label === 'Completed') return '#10b981'; // success
        if (label === 'In Progress') return '#f59e0b'; // warning
        if (label === 'Not Started') return '#64748b'; // secondary
        return '#667eea';
    });

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.data,
                backgroundColor: colors,
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                }
            }
        }
    });
}

function initBarChart(data) {
    const ctx = document.getElementById('productivityChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Tugas Selesai',
                data: data.data,
                backgroundColor: 'rgba(102, 126, 234, 0.8)',
                borderColor: '#667eea',
                borderWidth: 1,
                borderRadius: 4,
                barThickness: 30
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 },
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    border: { display: false }
                },
                x: {
                    grid: { display: false },
                    border: { display: false }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
}

function initLineChart(data) {
    const ctx = document.getElementById('completionTrendChart');
    if (!ctx) return;

    // Create gradient
    const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(118, 75, 162, 0.5)');
    gradient.addColorStop(1, 'rgba(118, 75, 162, 0.0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Penyelesaian Tugas',
                data: data.data,
                borderColor: '#764ba2',
                backgroundColor: gradient,
                borderWidth: 3,
                pointBackgroundColor: '#764ba2',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.4 // Smooth curve
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 },
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    border: { display: false }
                },
                x: {
                    grid: { display: false },
                    border: { display: false }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
}
