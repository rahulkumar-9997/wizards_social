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
                $('#viewDaysContainer').html('<div id="viewChart"></div>');
                var colors = ["#4ecac2"];
                var options = {
                    chart: {
                        height: 380,
                        type: 'bar',
                        toolbar: {
                            show: false
                        }
                    },
                    plotOptions: {
                        bar: {
                            borderRadius: 5,
                            dataLabels: {
                                position: 'top',
                            },
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function (val) {
                            return val.toLocaleString();
                        },
                        offsetY: -25,
                        style: {
                            fontSize: '12px',
                            colors: ["#304758"]
                        }
                    },
                    colors: colors,
                    legend: {
                        show: true,
                        horizontalAlign: "center",
                        offsetX: 0,
                        offsetY: -5,
                    },
                    series: [{
                        name: 'Views',
                        data: seriesData
                    }],
                    xaxis: {
                        categories: categories,
                        position: 'bottom',
                        labels: {
                            offsetY: 0,
                        },
                        axisBorder: {
                            show: false
                        },
                        axisTicks: {
                            show: false
                        },
                        crosshairs: {
                            fill: {
                                type: 'gradient',
                                gradient: {
                                    colorFrom: '#D8E3F0',
                                    colorTo: '#BED1E6',
                                    stops: [0, 100],
                                    opacityFrom: 0.6,
                                    opacityTo: 0.5,
                                }
                            }
                        },
                        tooltip: {
                            enabled: true,
                            offsetY: -10,
                        }
                    },
                    fill: {
                        gradient: {
                            enabled: false,
                            shade: 'light',
                            type: "horizontal",
                            shadeIntensity: 0.25,
                            gradientToColors: undefined,
                            inverseColors: true,
                            opacityFrom: 1,
                            opacityTo: 1,
                            stops: [50, 0, 100, 100]
                        },
                    },
                    yaxis: {
                        axisBorder: {
                            show: false
                        },
                        axisTicks: {
                            show: false,
                        },
                        labels: {
                            show: false,
                            formatter: function (val) {
                                return val;
                            }
                        }
                    },
                    title: {
                        text: `Total Views: ${totalViews.toLocaleString()} (${startText} to ${endText})`,
                        align: 'center',
                        margin: 20,
                        offsetY: 10,
                        style: {
                            color: '#099901ff',
                            fontSize: '16px',
                            fontWeight: 'bold'
                        }
                    },
                    grid: {
                        row: {
                            colors: ['transparent', 'transparent'],
                            opacity: 0.2
                        },
                        borderColor: '#f1f3fa'
                    }
                }
                if (chart) {
                    chart.destroy();
                }
                chart = new ApexCharts(
                    document.querySelector("#viewChart"),
                    options
                );
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