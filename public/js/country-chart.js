document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('countryLikesChart').getContext('2d');
    
    // Get the data from the data attribute
    const chartData = JSON.parse(document.getElementById('countryLikesChart').dataset.chartData);
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.map(item => item.name),
            datasets: [{
                label: 'Likes',
                data: chartData.map(item => item.likes),
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Countries Ranked by Number of Likes',
                    font: {
                        size: 16
                    }
                },
                legend: {
                    display: false
                }
            }
        }
    });
});
