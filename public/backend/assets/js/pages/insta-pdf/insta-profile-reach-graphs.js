$(document).ready(function () {
    let reachDaysChart;
    function profileReachGraph(id, start, end) {
        const colors = ["#4ecac2"];
        const textColor = "#101010";
        const startText = moment(start).format("DD MMM YYYY");
        const endText = moment(end).format("DD MMM YYYY");
        $("#reachDaysContainer").html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary"></div>
                <p class="mt-2 mb-0">Loading reach data from ${startText} to ${endText}...</p>
            </div>
        `);
        $.ajax({
            url: window.insta_profile_reach_graphs_pdf_url,
            data: {
                start_date: start,
                end_date: end,
            },
            headers: { "X-Requested-With": "XMLHttpRequest" },
            type: "GET",
            success: function (res) {
                if (!res.success) {
                    $("#reachDaysContainer").html(`
                        <div class="alert alert-warning mb-0">
                            <i class="bx bx-error"></i> ${
                                res.message || "Failed to load reach data."
                            }
                        </div>
                    `);
                    return;
                }

                const chartData = res.data || [];
                const description = res.api_description || "";
                initTooltipReach(description);
                if (chartData.length === 0) {
                    $("#reachDaysContainer").html(`
                        <div class="alert alert-info mb-0">
                            <i class="bx bx-info-circle"></i> No reach data available for ${startText} to ${endText}.
                        </div>
                    `);
                    return;
                }
                $("#reachDaysContainer").html(
                    '<canvas id="reachDaysChart" height="350"></canvas>'
                );
                const ctx = document
                    .getElementById("reachDaysChart")
                    .getContext("2d");
                const labels = chartData.map((item) =>
                    moment(item.date).format("MMM DD")
                );
                const values = chartData.map((item) => item.value);

                if (reachDaysChart) {
                    reachDaysChart.destroy();
                }
                reachDaysChart = new Chart(ctx, {
                    type: "line",
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: "Reach",
                                data: values,
                                fill: true,
                                borderColor: colors[0],
                                backgroundColor: "rgba(78, 202, 194, 0.2)",
                                tension: 0.3,
                                pointRadius: 3,
                                pointBackgroundColor: colors[0],
                                pointBorderColor: "#ffffff",
                                pointBorderWidth: 2,
                                pointHoverRadius: 5,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: "top",
                                labels: {
                                    usePointStyle: true,
                                    color: textColor,
                                    font: {
                                        size: 14,
                                    },
                                },
                            },
                            tooltip: {
                                enabled: true,
                                mode: "index",
                                intersect: false,
                                backgroundColor: "rgba(0, 0, 0, 0.8)",
                                titleColor: "#ffffff",
                                bodyColor: "#ffffff",
                                callbacks: {
                                    title: function (tooltipItems) {
                                        const dataIndex =
                                            tooltipItems[0].dataIndex;
                                        return moment(
                                            chartData[dataIndex].date
                                        ).format("MMMM DD, YYYY");
                                    },
                                    label: function (context) {
                                        let label = context.dataset.label || "";
                                        if (label) {
                                            label += ": ";
                                        }
                                        if (context.parsed.y !== null) {
                                            label +=
                                                new Intl.NumberFormat().format(
                                                    context.parsed.y
                                                );
                                        }
                                        return label;
                                    },
                                },
                            },
                            datalabels: {
                                display: false,
                                color: "black",
                            },
                        },
                        scales: {
                            x: {
                                title: {
                                    display: true,
                                    text: "Date",
                                    color: textColor,
                                    font: {
                                        size: 14,
                                        weight: "bold",
                                    },
                                },
                                ticks: {
                                    color: textColor,
                                    maxRotation: 45,
                                    minRotation: 45,
                                },
                                grid: {
                                    display: false,
                                },
                            },
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: "Reach Count",
                                    color: textColor,
                                    font: {
                                        size: 14,
                                        weight: "bold",
                                    },
                                },
                                ticks: {
                                    color: textColor,
                                    callback: function (value) {
                                        if (value >= 1000) {
                                            return (
                                                (value / 1000).toFixed(0) + "K"
                                            );
                                        }
                                        return value;
                                    },
                                },
                                grid: {
                                    color: "rgba(0, 0, 0, 0.05)",
                                },
                            },
                        },
                        interaction: {
                            mode: "nearest",
                            axis: "x",
                            intersect: false,
                        },
                    },
                    plugins: [
                        {
                            id: "datalabels",
                            beforeDraw: function (chart) {
                                chart.data.datasets.forEach(function (dataset) {
                                    if (dataset.datalabels) {
                                        dataset.datalabels.display = false;
                                    }
                                });
                            },
                        },
                    ],
                });
            },
            error: function (xhr) {
                console.error("Reach graph error:", xhr);
                const errorMessage =
                    xhr.responseJSON?.message ||
                    xhr.statusText ||
                    "Error fetching reach data. Please try again later.";

                $("#reachDaysContainer").html(`
                    <div class="alert alert-danger mb-0">
                        <i class="bx bx-error-circle"></i> ${errorMessage}
                    </div>
                `);

                Toastify({
                    text: "Failed to load reach graph data",
                    duration: 5000,
                    gravity: "top",
                    position: "right",
                    className: "bg-danger",
                    close: true,
                }).showToast();
            },
        });
    }

    function initTooltipReach(description) {
        const icon = $("#profileReachTitle");
        if (icon.length === 0) return;

        const safeDescription =
            description && description.trim() !== ""
                ? description
                : "No description available";
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
    window.profileReachGraph = profileReachGraph;
});
