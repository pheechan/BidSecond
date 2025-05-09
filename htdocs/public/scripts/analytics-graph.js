document.addEventListener('DOMContentLoaded', function () {
    const dropdown = document.getElementById('analytics-dropdown');
    const canvas = document.getElementById('analytics-graph');
    if (!dropdown || !canvas) return;

    const ctx = canvas.getContext('2d');
    let chart;

    window.showGraph = function(type) {
        if (chart) chart.destroy();

        let labels = [];
        let data = [];
        let label = '';

        // Use real data from PHP
        switch (type) {
            case 'highest-bids':
                labels = window.analyticsData.highestBids.labels;
                data = window.analyticsData.highestBids.data;
                label = 'Highest Bids';
                break;
            case 'lowest-bids':
                labels = window.analyticsData.lowestBids.labels;
                data = window.analyticsData.lowestBids.data;
                label = 'Lowest Bids';
                break;
            case 'most-bids':
                labels = window.analyticsData.mostBids.labels;
                data = window.analyticsData.mostBids.data;
                label = 'Most Bids';
                break;
            case 'category-ranking':
                labels = window.analyticsData.categoryRanking.labels;
                data = window.analyticsData.categoryRanking.data;
                label = 'Highest Bid by Category';
                break;
            case 'average-start-price':
                labels = window.analyticsData.averageStartPrice.labels;
                data = window.analyticsData.averageStartPrice.data;
                label = 'Average Starting Price';
                break;
            case 'average-bid-price':
                labels = window.analyticsData.averageBidPrice.labels;
                data = window.analyticsData.averageBidPrice.data;
                label = 'Average Bid Price';
                break;
            case 'total-revenue':
                labels = window.analyticsData.totalRevenue.labels;
                data = window.analyticsData.totalRevenue.data;
                label = 'Total Revenue';
                break;
            default:
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                return;
        }

        chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: label,
                    data: data,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    };
});