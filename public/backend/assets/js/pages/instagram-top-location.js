$(document).ready(function () {
    let chart;

    function loadTopLocations(timeframe = 'this_month') {
        const container = $('#topLocationsContainer');
        container.html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2">Loading top locations...</p>
            </div>
        `);

        $.ajax({
            url: window.instagramTopLocationUrl,
            data: { timeframe },
            success: function (res) {
                if (!res.success) {
                    container.html(`<div class="alert alert-danger">${res.message}</div>`);
                    return;
                }

                container.html(`<canvas id="topLocationsChart" height="450"></canvas>`);
                const ctx = document.getElementById('topLocationsChart');
                if (!ctx) return;

                const context = ctx.getContext('2d');
                if (chart) chart.destroy();

                chart = new Chart(context, {
                    type: 'bar',
                    data: {
                        labels: res.labels,
                        datasets: [{
                            label: 'Audience %',
                            data: res.values,
                            backgroundColor: '#ff9900',
                            borderRadius: 5,
                            barThickness: 14
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            datalabels: {
                                color: '#000',
                                anchor: 'end',
                                align: 'right',
                                formatter: (value) => value + '%',
                                font: { weight: 'bold', size: 12 }
                            },
                            tooltip: {
                                callbacks: {
                                    label: (ctx) => ctx.parsed.x + '%'
                                }
                            },
                            title: {
                                display: false,
                                text: 'Top Locations',
                                font: { size: 16, weight: 'bold' },
                                padding: { top: 10, bottom: 20 }
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    callback: (value) => value + '%'
                                },
                                grid: { display: false }
                            },
                            y: {
                                grid: { display: false },
                                ticks: { font: { size: 12 } }
                            }
                        }
                    },
                    plugins: [ChartDataLabels]
                });
            },
            error: function () {
                container.html(`<div class="alert alert-danger">Error loading data</div>`);
            }
        });
    }

    loadTopLocations();

    $('#timeframe').on('change', function () {
        loadTopLocations($(this).val());
    });
});
