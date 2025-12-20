$(document).ready(function () {
    const id = window.instagram_id;
    let chart;

    function instaViewGraph(id, start, end) {
        const startText = moment(start).format("DD MMM YYYY");
        const endText = moment(end).format("DD MMM YYYY");
        $("#viewDaysContainer").html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2 mb-0">Loading views data from ${startText} to ${endText}...</p>
            </div>
        `);

        $.ajax({
            url: window.insta_view_graphs_media_type_pdf_url,
            data: {
                start_date: start,
                end_date: end,
            },
            headers: {
                "X-Requested-With": "XMLHttpRequest",
            },
            type: "GET",
            success: function (res) {
                console.log("API Response:", res);
                if (res.api_description && res.api_description.trim() !== "") {
                    initTooltip(res.api_description);
                } else {
                    initTooltip("No description available");
                }
                if (!res.success) {
                    $("#viewDaysContainer").html(
                        `<div class="alert alert-danger mb-0">${res.message}</div>`
                    );
                    return;
                }
                if (
                    !res.categories ||
                    !res.values ||
                    res.categories.length === 0
                ) {
                    $("#viewDaysContainer").html(
                        `<div class="alert alert-warning mb-0">No views data available for ${startText} to ${endText}.</div>`
                    );
                    return;
                }

                const categories = res.categories;
                const seriesData = res.values;
                const totalViews =
                    res.total_views || seriesData.reduce((a, b) => a + b, 0);

                function formatNumber(num) {
                    if (num >= 1000000) {
                        return (num / 1000000).toFixed(1) + "M";
                    } else if (num >= 1000) {
                        return (num / 1000).toFixed(1) + "K";
                    }
                    return num;
                }

                const colors = [
                    "#4ecac2", // Teal
                    "#36a2eb", // Blue
                    "#ffce56", // Yellow
                    "#ff6384", // Pink
                    "#9966ff", // Purple
                    "#00cc99", // Green
                    "#ff9933", // Orange
                ];

                // First, create an array with original indices
                const originalData = categories.map((category, index) => {
                    const value = seriesData[index];
                    const percentage = ((value / totalViews) * 100).toFixed(1);
                    return {
                        category,
                        value,
                        formattedValue: formatNumber(value),
                        percentage,
                        originalIndex: index, // Keep the original index for color mapping
                    };
                });

                // Sort by value descending for display
                const sortedData = [...originalData].sort((a, b) => b.value - a.value);

                // Assign colors to original indices
                const colorMap = {};
                originalData.forEach((item, index) => {
                    colorMap[item.originalIndex] = colors[index % colors.length];
                });

                $("#viewDaysContainer").html(`
                    <div class="total-views-summary mb-3">
                        <h4 class="text-center mb-0">
                            Total Views: <span class="text-primary fw-bold">${formatNumber(
                                totalViews
                            )}</span>
                            <small class="text-muted">(${startText} to ${endText})</small>
                        </h4>
                    </div>
                    
                    <div class="row">
                        <!-- Pie Chart Column -->
                        <div class="col-md-7 mb-4 mb-md-0">
                            <div class="position-relative" style="min-height: 450px;">
                                <div id="viewChart"></div>
                            </div>
                        </div>
                        
                        <!-- Table Column -->
                        <div class="col-md-5">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="bx bx-table me-2"></i>Views Breakdown
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light sticky-top" style="top: 0;">
                                                <tr>
                                                    <th scope="col" class="ps-3">Content Type</th>
                                                    <th scope="col" class="text-end pe-3">Views</th>
                                                    <th scope="col" class="text-end pe-3">%</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${sortedData
                                                    .map(
                                                        (item) => `
                                                    <tr class="chart-highlight" data-original-index="${item.originalIndex}">
                                                        <td class="ps-3 fw-medium">
                                                            <span class="color-indicator me-2" style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background-color: ${colorMap[item.originalIndex]}"></span>
                                                            ${item.category}
                                                        </td>
                                                        <td class="text-end pe-3">${item.formattedValue}</td>
                                                        <td class="text-end pe-3 fw-bold ${
                                                            parseFloat(
                                                                item.percentage
                                                            ) > 10
                                                                ? "text-primary"
                                                                : "text-secondary"
                                                        }">
                                                            ${item.percentage}%
                                                        </td>
                                                    </tr>
                                                `
                                                    )
                                                    .join("")}
                                            </tbody>
                                            <tfoot class="table-primary">
                                                <tr>
                                                    <td class="ps-3 fw-bold">Total</td>
                                                    <td class="text-end pe-3 fw-bold">${formatNumber(
                                                        totalViews
                                                    )}</td>
                                                    <td class="text-end pe-3 fw-bold">100%</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                                <div class="card-footer bg-light text-muted small">
                                    <div class="d-flex justify-content-between">
                                        <span>${categories.length} content types</span>
                                        <span>Updated: ${moment().format(
                                            "HH:mm"
                                        )}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `);

                // UPDATED CHART OPTIONS - Using ApexCharts built-in dataLabels
                const options = {
                    chart: {
                        type: "pie",
                        height: 450,
                        width: "100%",
                        toolbar: { show: false },
                        foreColor: "#000000",
                        animations: {
                            enabled: true,
                            speed: 500,
                        },
                        events: {
                            dataPointSelection: function(event, chartContext, config) {
                                const originalIndex = config.dataPointIndex;
                                // Highlight corresponding table row
                                $(`tr[data-original-index="${originalIndex}"]`).addClass('active').siblings().removeClass('active');
                            },
                            updated: function(chartContext, config) {
                                // Reset table highlights when chart is updated
                                $('tr.chart-highlight').removeClass('active');
                            }
                        }
                    },
                    series: seriesData,
                    labels: categories,
                    colors: colors, // This uses the colors in original order
                    dataLabels: {
                        enabled: true,
                        formatter: function (val, opts) {
                            const label = opts.w.config.labels[opts.seriesIndex];
                            const value = formatNumber(seriesData[opts.seriesIndex]);
                            const percent = parseFloat(val).toFixed(1);
                            return `${label}\n${value} (${percent}%)`;
                        },
                        style: {
                            fontSize: '13px',
                            fontWeight: 'normal',
                            colors: ["#000000"],
                            fontFamily: 'Arial, sans-serif'
                        },
                        dropShadow: {
                            enabled: false
                        },
                        textAnchor: 'middle',
                        offset: 20,
                        background: {
                            enabled: true,
                            foreColor: '#fff',
                            padding: 6,
                            borderRadius: 4,
                            borderWidth: 1,
                            borderColor: '#ddd',
                            opacity: 0.9,
                            dropShadow: {
                                enabled: true,
                                top: 1,
                                left: 1,
                                blur: 1,
                                color: '#000',
                                opacity: 0.45
                            }
                        }
                    },
                    legend: {
                        show: false,
                    },
                    tooltip: {
                        enabled: false,
                    },
                    plotOptions: {
                        pie: {
                            startAngle: 0,
                            endAngle: 360,
                            donut: {
                                size: "55%",
                                labels: {
                                    show: false,
                                },
                            },
                            expandOnClick: true,
                            dataLabels: {
                                offset: 40,
                                minAngleToShowLabel: 1
                            }
                        },
                    },
                    stroke: {
                        width: 2,
                        colors: ["#fff"],
                    },
                    responsive: [
                        {
                            breakpoint: 768,
                            options: {
                                chart: {
                                    height: 400,
                                },
                                dataLabels: {
                                    style: {
                                        fontSize: '12px'
                                    },
                                    offset: 15
                                }
                            },
                        },
                        {
                            breakpoint: 480,
                            options: {
                                chart: {
                                    height: 350,
                                },
                                dataLabels: {
                                    style: {
                                        fontSize: '11px'
                                    },
                                    offset: 10
                                }
                            },
                        },
                    ],
                };

                if (chart) {
                    chart.destroy();
                }

                chart = new ApexCharts(
                    document.querySelector("#viewChart"),
                    options
                );
                chart.render();

                // Table hover interaction
                $('table tbody tr.chart-highlight').hover(
                    function() {
                        const originalIndex = parseInt($(this).data('original-index'));
                        chart.highlightSeries(originalIndex);
                        $(this).addClass('active');
                    },
                    function() {
                        const originalIndex = parseInt($(this).data('original-index'));
                        chart.resetSeries();
                        $(this).removeClass('active');
                    }
                );

                Toastify({
                    text: `Views data loaded`,
                    duration: 2000,
                    gravity: "top",
                    position: "right",
                    className: "bg-success",
                }).showToast();

                // Simplified resize handler
                let resizeTimer;
                $(window).on("resize", function () {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(() => {
                        chart.updateSeries(seriesData);
                    }, 200);
                });
            },
            error: function (xhr, status, error) {
                console.error("API Error:", xhr.responseText);
                let errorMessage = "Error fetching views data.";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                $("#viewDaysContainer").html(
                    `<div class="alert alert-danger mb-0">${errorMessage}</div>`
                );
            },
        });
    }

    window.instaViewGraph = instaViewGraph;
});

function initTooltip(description) {
    const icon = $("#viewDateRangeTitle");
    if (icon.length === 0) return;

    const safeDescription = description || "No description available";

    const existingTooltip = bootstrap.Tooltip.getInstance(icon[0]);
    if (existingTooltip) {
        existingTooltip.dispose();
    }

    icon.attr("data-bs-title", safeDescription);
    icon.attr("title", safeDescription);

    new bootstrap.Tooltip(icon[0], {
        placement: "top",
        customClass: "success-tooltip",
    });
}

// Add CSS for better label styling
$(document).ready(function() {
    $('head').append(`
        <style>
            /* Active row styling */
            table tbody tr.chart-highlight.active {
                background-color: rgba(54, 162, 235, 0.1);
            }
            
            /* Make sure chart container has proper positioning */
            #viewChart {
                position: relative;
            }
            
            /* Style for ApexCharts dataLabels */
            .apexcharts-datalabels text {
                font-family: Arial, sans-serif !important;
                pointer-events: none;
            }
            
            .apexcharts-datalabels rect {
                stroke-width: 1;
                stroke: #ddd;
            }
        </style>
    `);
});