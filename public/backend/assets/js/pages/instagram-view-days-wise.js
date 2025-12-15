$(document).ready(function () {
    const id = window.instagram_id;
    let chart;

    function loadViewGraph(id, start, end) {
        const startText = moment(start).format("DD MMM YYYY");
        const endText = moment(end).format("DD MMM YYYY");
        $('#viewDateRange').html(`(${startText} → ${endText})`);
        $('#viewDaysContainer').html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2 mb-0">Loading views data...</p>
            </div>
        `);

        $.ajax({
            url: window.instagramFetchViewDaysWise,
            data: { 
                start_date: start, 
                end_date: end 
            },
            headers: { 
                'X-Requested-With': 'XMLHttpRequest' 
            },
            type: "GET",
            success: function (res) {
                console.log('API Response:', res);
                initTooltip(res.api_description || '');
                if (!res.success) {
                    $('#viewDaysContainer').html(
                        `<div class="alert alert-danger mb-0">${res.message}</div>`
                    );
                    return;
                }
                if (!res.categories || !res.values || res.categories.length === 0) {
                    $('#viewDaysContainer').html(
                        `<div class="alert alert-warning mb-0">No views data available for this date range.</div>`
                    );
                    return;
                }
                const categories = res.categories;
                const seriesData = res.values;
                const totalViews = res.total_views;
                const startText = moment(res.start_date).format("DD MMM YYYY");
                const endText = moment(res.end_date).format("DD MMM YYYY");
                $('#viewDaysContainer').html('<div id="viewChart"></div>');
                const colors = [
                    '#4ecac2', '#36a2eb', '#ffce56',
                    '#ff6384', '#9966ff', '#00cc99', '#ff9933'
                ];
                const options = {
                    chart: {
                        type: 'pie',
                        height: 420,
                        toolbar: { show: false }
                    },
                    series: seriesData,
                    labels: categories,
                    colors: [
                        '#4ecac2', '#36a2eb', '#ffce56',
                        '#ff6384', '#9966ff', '#00cc99', '#ff9933'
                    ],
                    dataLabels: {
                        enabled: true,
                        formatter: function (val, opts) {
                            const label = categories[opts.seriesIndex];
                            const value = seriesData[opts.seriesIndex];
                            const total = seriesData.reduce((a, b) => a + b, 0);
                            const percent = ((value / total) * 100).toFixed(1);
                            if (percent < 6) {
                                return `${label} (${percent}%)`;
                            } else {
                                return `${label}\n${value.toLocaleString()}`;
                            }
                        },
                        style: {
                            fontSize: '15px',
                            fontWeight: '600',
                            colors: ['#000000ff']
                        },
                        background: {
                            enabled: false
                        },
                        dropShadow: { enabled: false },
                        offset: 0,
                        connector: {
                            enabled: true,
                            length: 15,
                            strokeWidth: 1.2,
                            color: '#888'
                        }
                    },
                    legend: {
                        show: true,
                        position: 'bottom',
                        horizontalAlign: 'center',
                        floating: false,
                        fontSize: '15px',
                        labels: { colors: '#000000ff' },
                        itemMargin: { horizontal: 10, vertical: 4 },
                        formatter: function (seriesName, opts) {
                            const val = seriesData[opts.seriesIndex];
                            const total = seriesData.reduce((a, b) => a + b, 0);
                            const perc = ((val / total) * 100).toFixed(1);
                            return `${seriesName}: ${val.toLocaleString()} (${perc}%)`;
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: function (val, opts) {
                                const label = categories[opts.seriesIndex];
                                return `${label}: ${val.toLocaleString()} views`;
                            }
                        }
                    },
                    title: {
                        text: `Total Views: ${totalViews.toLocaleString()}`,
                        align: 'center',
                        style: {
                            fontSize: '15px',
                            fontWeight: 'bold',
                            color: '#099901ff'
                        }
                    },
                    plotOptions: {
                        pie: {
                            startAngle: -90,
                            endAngle: 270,
                            dataLabels: {
                                offset: -10,
                                minAngleToShowLabel: 10
                            },
                            expandOnClick: false
                        }
                    },
                    responsive: [{
                        breakpoint: 600,
                        options: {
                            chart: { height: 320 },
                            dataLabels: {
                                style: { fontSize: '11px' }
                            },
                            legend: { fontSize: '11px' }
                        }
                    }]
                };



                if (chart) chart.destroy();
                chart = new ApexCharts(document.querySelector("#viewChart"), options);
                chart.render();
            },
            error: function (xhr, status, error) {
                let errorMessage = 'Error fetching views data. Please try again later.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }                
                $('#viewDaysContainer').html(
                    `<div class="alert alert-danger mb-0">${errorMessage}</div>`
                );
                console.error('API Error:', error);
            }
        });
    }
    window.loadViewGraph = loadViewGraph;
});

function initTooltip(description) {
    const icon = $('#viewDateRangeTitle');
    if (description && description.trim() !== '') {
        icon.attr('data-bs-title', description);
        new bootstrap.Tooltip(icon[0]);
    } else {
        icon.attr('data-bs-title', 'No description available');
        new bootstrap.Tooltip(icon[0]);
    }
}