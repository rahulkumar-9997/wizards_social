$(document).ready(function () {
    let audienceAgeChart;

    function loadAudienceAgeGroup(timeframe = "this_month") {
        const ctx = document
            .getElementById("audienceAgeGroupChart")
            .getContext("2d");
        $("#audienceAgeGroupContainer").html(
            '<canvas id="audienceAgeGroupChart" height="450"></canvas>'
        );

        $.ajax({
            url: window.instagramAudienceAgeUrl,
            data: { timeframe },
            beforeSend: function () {
                $("#audienceAgeGroupContainer").html(`
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary"></div>
                        <p class="mt-2" style="color: #101010;">Loading audience age data...</p>
                    </div>
                `);
            },
            success: function (res) {
                const description = res.api_description || "";
                initTooltipAgeGroup(description);
                if (!res.success) {
                    $("#audienceAgeGroupContainer").html(
                        `<div class="alert alert-danger" style="color: #101010;">${
                            res.message || "Unable to load data."
                        }</div>`
                    );
                    return;
                }

                $("#audienceAgeGroupContainer").html(
                    '<canvas id="audienceAgeGroupChart" height="500"></canvas>'
                );
                const ctx = document
                    .getElementById("audienceAgeGroupChart")
                    .getContext("2d");

                if (audienceAgeChart) audienceAgeChart.destroy();
                if (
                    typeof Chart !== "undefined" &&
                    typeof ChartDataLabels !== "undefined"
                ) {
                    Chart.register(ChartDataLabels);
                }

                audienceAgeChart = new Chart(ctx, {
                    type: "bar",
                    data: {
                        labels: res.labels,
                        datasets: [
                            {
                                label: "Female",
                                data: res.female,
                                backgroundColor: "#e83e8c",
                            },
                            {
                                label: "Male",
                                data: res.male,
                                backgroundColor: "#003976ff",
                            },
                            {
                                label: "Unknown",
                                data: res.unknown,
                                backgroundColor: "#6c757d",
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
                                    boxWidth: 25,
                                    padding: 10,
                                    color: "#101010",
                                    font: {
                                        color: "#101010",
                                    },
                                },
                            },
                            title: {
                                display: false,
                            },
                            datalabels: {
                                anchor: "end",
                                align: "top",
                                formatter: (val) => (val > 0 ? val : ""),
                                font: {
                                    size: 15,
                                    weight: "bold",
                                    color: "#101010",
                                },
                            },
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                title: {
                                    display: true,
                                    text: "Age Group",
                                    font: {
                                        weight: "900",
                                        color: "#101010",
                                    },
                                    color: "#101010",
                                },
                                ticks: {
                                    color: "#101010",
                                },
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: "#f0f0f0" },
                                title: {
                                    display: true,
                                    text: "Audience Count",
                                    font: {
                                        weight: "bold",
                                        color: "#101010",
                                    },
                                    color: "#101010",
                                },
                                ticks: {
                                    color: "#101010",
                                },
                            },
                        },
                    },
                });
            },
            error: function () {
                $("#audienceAgeGroupContainer").html(
                    `<div class="alert alert-danger" style="color: #101010;">Error loading data. Please try again.</div>`
                );
            },
        });
    }
    loadAudienceAgeGroup();
    $("#ageTimeframe").on("change", function () {
        loadAudienceAgeGroup($(this).val());
    });
});

function initTooltipAgeGroup(description) {
    const icon = $("#audienceByAgeGroup");
    if (icon.length === 0) return;

    const safeDescription =
        description && description.trim() !== ""
            ? description
            : "No description available";

    icon.attr("data-bs-title", safeDescription);
    icon.attr("data-bs-toggle", "tooltip");
    new bootstrap.Tooltip(icon[0]);
}
