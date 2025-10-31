$(document).ready(function () {
    let audienceAgeChart;

    function loadAudienceAgeGroup(timeframe = 'this_month') {
        const ctx = document.getElementById('audienceAgeGroupChart').getContext('2d');
        $('#audienceAgeGroupContainer').html('<canvas id="audienceAgeGroupChart" height="450"></canvas>');

        $.ajax({
            url: window.instagramAudienceAgeUrl,
            data: { timeframe },
            beforeSend: function () {
                $('#audienceAgeGroupContainer').html(`
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary"></div>
                        <p class="mt-2">Loading audience age data...</p>
                    </div>
                `);
            },
            success: function (res) {
                if (!res.success) {
                    $('#audienceAgeGroupContainer').html(
                        `<div class="alert alert-danger">${res.message || 'Unable to load data.'}</div>`
                    );
                    return;
                }

                $('#audienceAgeGroupContainer').html('<canvas id="audienceAgeGroupChart" height="450"></canvas>');
                const ctx = document.getElementById('audienceAgeGroupChart').getContext('2d');

                if (audienceAgeChart) audienceAgeChart.destroy();

                // Register ChartDataLabels if available
                if (typeof Chart !== 'undefined' && typeof ChartDataLabels !== 'undefined') {
                    Chart.register(ChartDataLabels);
                }

                audienceAgeChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: res.labels,
                        datasets: [
                            {
                                label: 'Female',
                                data: res.female,
                                backgroundColor: '#e83e8c',
                            },
                            {
                                label: 'Male',
                                data: res.male,
                                backgroundColor: '#007bff',
                            },
                            {
                                label: 'Unknown',
                                data: res.unknown,
                                backgroundColor: '#6c757d',
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: { boxWidth: 20, padding: 10 },
                            },
                            title: {
                                display: false,
                            },
                            datalabels: {
                                anchor: 'end',
                                align: 'top',
                                formatter: (val) => (val > 0 ? val : ''),
                                font: { size: 11, weight: 'bold' },
                            },
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                title: { display: true, text: 'Age Group', font: { weight: 'bold' } },
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: '#f0f0f0' },
                                title: { display: true, text: 'Audience Count', font: { weight: 'bold' } },
                            },
                        },
                    },
                });
            },
            error: function () {
                $('#audienceAgeGroupContainer').html(
                    `<div class="alert alert-danger">Error loading data. Please try again.</div>`
                );
            },
        });
    }

    // Load default (this month)
    loadAudienceAgeGroup();

    // Reload when timeframe changes
    $('#ageTimeframe').on('change', function () {
        loadAudienceAgeGroup($(this).val());
    });
});
