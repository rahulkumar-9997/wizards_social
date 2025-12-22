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
                if (res.api_description && res.api_description.trim() !== "") {
                    initTooltip(res.api_description);
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
                    "#4ecac2",
                    "#36a2eb",
                    "#ffce56",
                    "#ff6384",
                    "#9966ff",
                    "#00cc99",
                    "#ff9933",
                ];

                const originalData = categories.map((category, index) => {
                    const value = seriesData[index];
                    const percentage = ((value / totalViews) * 100).toFixed(1);
                    return {
                        category,
                        value,
                        formattedValue: formatNumber(value),
                        percentage,
                        originalIndex: index,
                    };
                });

                const sortedData = [...originalData].sort(
                    (a, b) => b.value - a.value
                );
                const colorMap = {};
                originalData.forEach((item, index) => {
                    colorMap[item.originalIndex] =
                        colors[index % colors.length];
                });

                $("#viewDaysContainer").html(`
                    <div class="total-views-summary mb-1">
                        <h4 class="text-center">
                            Total Views: <span class="text-bold fw-bold"><strong>${formatNumber(
                                totalViews
                            )}</strong></span>
                        </h4>
                    </div>
                    
                    <div class="row align-items-center">
                        <div class="col-md-6 mb-4 mb-md-0">
                            <div class="position-relative" style="min-height: 300px;">
                                <div id="viewChart"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0">
                                    <thead class="table-light sticky-top" style="top: 0;">
                                        <tr>
                                            <th scope="col">Content Type</th>
                                            <th scope="col">Views</th>
                                            <th scope="col">%</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${sortedData
                                            .map(
                                                (item) => `
                                            <tr class="chart-highlight" data-original-index="${
                                                item.originalIndex
                                            }">
                                                <td>
                                                    <h4 class="mb-0">
                                                        <span class="color-indicator me-2" style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background-color: ${
                                                        colorMap[
                                                            item.originalIndex
                                                        ]
                                                        }">
                                                        </span>
                                                         ${item.category}
                                                    </h4>
                                                </td>
                                                <td>
                                                    <h4 class="mb-0">${item.formattedValue}</h4>
                                                </td>
                                                <td class="${
                                                    parseFloat(
                                                        item.percentage
                                                    ) > 10
                                                        ? "text-primary"
                                                        : "text-secondary"
                                                }">
                                                   <h4 class="mb-0"> ${item.percentage}% </h4>
                                                </td>
                                            </tr>
                                        `
                                            )
                                            .join("")}
                                    </tbody>
                                    <tfoot class="table-primary">
                                        <tr>
                                            <td>
                                                <h4 class="mb-0">
                                                    Total
                                                </h4>
                                            </td>
                                            <td>
                                                <h4 class="mb-0"> ${formatNumber(totalViews)}</h4>
                                            </td>
                                            <td>
                                                <h4 class="mb-0">
                                                    100%
                                                </h4>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                `);

                const options = {
                    chart: {
                        type: "pie",
                        height: 300,
                        toolbar: { show: false },
                        animations: {
                            enabled: true,
                            speed: 500,
                        },
                        events: {
                            dataPointSelection: function (
                                event,
                                chartContext,
                                config
                            ) {
                                const originalIndex = config.dataPointIndex;
                                $(`tr[data-original-index="${originalIndex}"]`)
                                    .addClass("active")
                                    .siblings()
                                    .removeClass("active");
                            },
                            updated: function (chartContext, config) {
                                $("tr.chart-highlight").removeClass("active");
                            },
                        },
                    },
                    series: seriesData,
                    labels: categories,
                    colors: colors,
                    dataLabels: {
                        enabled: false,
                    },
                    legend: {
                        show: false,
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: "55%",
                            },
                            expandOnClick: true,
                        },
                    },
                    stroke: {
                        width: 2,
                        colors: ["#fff"],
                    },
                };

                if (chart) {
                    chart.destroy();
                }

                chart = new ApexCharts(
                    document.querySelector("#viewChart"),
                    options
                );
                chart.render();

                let resizeTimer;
                $(window).on("resize", function () {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(() => {
                        chart.updateSeries(seriesData);
                    }, 200);
                });
            },
            error: function (xhr, status, error) {
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

    new bootstrap.Tooltip(icon[0], {
        title: safeDescription,
        placement: "top",
    });
}
