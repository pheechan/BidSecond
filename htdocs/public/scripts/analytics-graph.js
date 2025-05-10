document.addEventListener('DOMContentLoaded', function () {
    const dropdown = document.getElementById('analytics-dropdown');
    const canvas = document.getElementById('analytics-graph');
    const tableContainer = document.getElementById('analytics-table-container');
    if (!dropdown || !canvas || !tableContainer) return;

    const ctx = canvas.getContext('2d');
    let chart;

    window.showGraph = function(type) {
        if (chart) chart.destroy();

        let labels = [];
        let data = [];
        let label = '';
        let tableHtml = '';

        switch (type) {
            case 'highest-bids':
                labels = window.analyticsData.highestBids.labels;
                data = window.analyticsData.highestBids.data;
                label = 'Highest Bids';
                tableHtml = `<table class="styled-table"><thead><tr><th>Auction ID</th><th>Highest Bid</th></tr></thead><tbody>` +
                    labels.map((id, i) => `<tr><td>${id}</td><td>฿${data[i].toLocaleString(undefined, {minimumFractionDigits:2})}</td></tr>`).join('') +
                    `</tbody></table>`;
                break;
            case 'lowest-bids':
                // Filter out negative values
                const rawLabels = window.analyticsData.lowestBids.labels;
                const rawData = window.analyticsData.lowestBids.data;
                // Only keep values >= 0
                labels = [];
                data = [];
                rawLabels.forEach((label, i) => {
                    if (rawData[i] >= 0) {
                        labels.push(label);
                        data.push(rawData[i]);
                    }
                });
                label = 'Lowest Bids';
                tableHtml = `<table class="styled-table"><thead><tr><th>Auction Title</th><th>Lowest Bid</th></tr></thead><tbody>` +
                    labels.map((title, i) => `<tr><td>${title}</td><td>฿${data[i].toLocaleString(undefined, {minimumFractionDigits:2})}</td></tr>`).join('') +
                    `</tbody></table>`;
                break;
            case 'most-bids':
                labels = window.analyticsData.mostBids.labels;
                data = window.analyticsData.mostBids.data;
                label = 'Most Bids';
                tableHtml = `<table class="styled-table"><thead><tr><th>Auction ID</th><th>Bid Count</th></tr></thead><tbody>` +
                    labels.map((id, i) => `<tr><td>${id}</td><td>${data[i]}</td></tr>`).join('') +
                    `</tbody></table>`;
                break;
            case 'average-start-price':
                labels = window.analyticsData.averageStartPrice.labels;
                data = window.analyticsData.averageStartPrice.data;
                label = 'Average Starting Price';
                tableHtml = `<table class="styled-table"><thead><tr><th>Category</th><th>Avg Start Price</th></tr></thead><tbody>` +
                    labels.map((cat, i) => `<tr><td>${cat}</td><td>฿${data[i].toLocaleString(undefined, {minimumFractionDigits:2})}</td></tr>`).join('') +
                    `</tbody></table>`;
                break;
            case 'average-bid-price':
                labels = window.analyticsData.averageBidPrice.labels;
                data = window.analyticsData.averageBidPrice.data;
                label = 'Average Bid Price';
                tableHtml = `<table class="styled-table"><thead><tr><th>Category</th><th>Avg Bid Price</th></tr></thead><tbody>` +
                    labels.map((cat, i) => `<tr><td>${cat}</td><td>฿${data[i].toLocaleString(undefined, {minimumFractionDigits:2})}</td></tr>`).join('') +
                    `</tbody></table>`;
                break;
            case 'total-revenue':
                labels = window.analyticsData.totalRevenue.labels;
                data = window.analyticsData.totalRevenue.data;
                label = 'Total Revenue';
                tableHtml = `<table class="styled-table"><thead><tr><th>Category</th><th>Total Revenue</th></tr></thead><tbody>` +
                    labels.map((cat, i) => `<tr><td>${cat}</td><td>฿${data[i].toLocaleString(undefined, {minimumFractionDigits:2})}</td></tr>`).join('') +
                    `</tbody></table>`;
                break;
            default:
                tableHtml = '';
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                tableContainer.innerHTML = '';
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
                maintainAspectRatio: false,
                scales: {
                    x: {
                        ticks: {
                            autoSkip: false,
                            maxRotation: 45,
                            minRotation: 0
                        },
                        grid: {
                            display: false // Hide vertical grid lines
                        }
                    },
                    y: {
                        type: 'logarithmic',
                        min: 1,
                        ticks: {
                            display: false,
                            callback: function(value) {
                                return value === 1 ? '0' : value;
                            }
                        },
                        grid: {
                            display: false // Hide horizontal grid lines
                        }
                    }
                },
                plugins: {
                    zoom: false
                }
            }
        });

        tableContainer.innerHTML = tableHtml;
    };
});