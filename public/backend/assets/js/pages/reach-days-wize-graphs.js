$(document).ready(function () {
    const id = window.instagram_id;
    let reachDaysChart;

    function loadReachGraph(id, start, end) {
        var colors = ["#4ecac2"];
        const startText = moment(start).format("DD MMM YYYY");
        const endText = moment(end).format("DD MMM YYYY");
        $('#reachDateRange').html(`(${startText} → ${endText})`);
        $('#reachDaysContainer').html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2 mb-0">Loading reach data...</p>
            </div>
        `);
        
        $.ajax({
            url: window.instagramFetchReachDaysWise,
            data: { start_date: start, end_date: end },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            type: "GET",
            success: function (res) {
                if (!res || !Array.isArray(res) || res.length === 0) {
                    $('#reachDaysContainer').html(
                        `<div class="alert alert-warning mb-0">No reach data available for this date range.</div>`
                    );
                    return;
                }

                $('#reachDaysContainer').html('<canvas id="reachDaysChart" height="400"></canvas>');
                const ctx = document.getElementById('reachDaysChart').getContext('2d');
                const labels = res.map(item => moment(item.date).format("MMM DD"));
                const values = res.map(item => item.value);
                if (reachDaysChart) reachDaysChart.destroy();                
                reachDaysChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Reach',
                            data: values,
                            fill: true,
                            borderColor: colors,
                            backgroundColor: 'rgba(0, 123, 255, 0.2)',
                            tension: 0.3,
                            pointRadius: 3,
                            pointBackgroundColor: colors,
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointHoverRadius: 5,
                        }]
                    },
                    
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { 
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                }
                            },
                            tooltip: { 
                                enabled: true,
                                mode: 'index',
                                intersect: false,
                                callbacks: {
                                    title: function(tooltipItems) {
                                        const dataIndex = tooltipItems[0].dataIndex;
                                        return moment(res[dataIndex].date).format("MMMM DD, YYYY");
                                    }
                                }
                            },
                            datalabels: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                title: {
                                    display: true,
                                    text: 'Date',
                                    font: { weight: 'bold' }
                                },
                                grid: { display: false }
                            },
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Reach Count',
                                    font: { weight: 'bold' }
                                },
                                grid: { color: '#f0f0f0' }
                            }
                        },
                        interaction: {
                            mode: 'nearest',
                            axis: 'x',
                            intersect: false
                        }
                    }
                });
            },
            error: function (xhr, status, error) {
                let errorMessage = 'Error fetching reach data. Please try again later.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                $('#reachDaysContainer').html(
                    `<div class="alert alert-danger mb-0">${errorMessage}</div>`
                );
            }
        });
    }
    
    window.loadReachGraph = loadReachGraph;
});