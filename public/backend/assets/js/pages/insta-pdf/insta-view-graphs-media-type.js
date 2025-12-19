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
                const totalViews = res.total_views || seriesData.reduce((a, b) => a + b, 0);
                
                function formatNumber(num) {
                    if (num >= 1000000) {
                        return (num / 1000000).toFixed(1) + "M";
                    } else if (num >= 1000) {
                        return (num / 1000).toFixed(1) + "K";
                    }
                    return num;
                }
                
                // Calculate percentages
                const viewData = categories.map((category, index) => {
                    const value = seriesData[index];
                    const percentage = ((value / totalViews) * 100).toFixed(1);
                    return {
                        category,
                        value,
                        formattedValue: formatNumber(value),
                        percentage,
                        index: index
                    };
                });
                
                // Sort by value descending
                viewData.sort((a, b) => b.value - a.value);

                $("#viewDaysContainer").html(`
                    <div class="total-views-summary mb-3">
                        <h4 class="text-center mb-0">
                            Total Views: <span class="text-primary fw-bold">${formatNumber(totalViews)}</span>
                            <small class="text-muted">(${startText} to ${endText})</small>
                        </h4>
                    </div>
                    
                    <div class="row">
                        <!-- Pie Chart Column -->
                        <div class="col-md-7 mb-4 mb-md-0">
                            <div class="position-relative" style="min-height: 400px;">
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
                                                ${viewData.map(item => `
                                                    <tr class="chart-highlight" data-index="${item.index}">
                                                        <td class="ps-3 fw-medium">
                                                            <span class="color-indicator me-2" style="display: inline-block; width: 12px; height: 12px; border-radius: 50%;"></span>
                                                            ${item.category}
                                                        </td>
                                                        <td class="text-end pe-3">${item.formattedValue}</td>
                                                        <td class="text-end pe-3 fw-bold ${parseFloat(item.percentage) > 10 ? 'text-primary' : 'text-secondary'}">
                                                            ${item.percentage}%
                                                        </td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                            <tfoot class="table-primary">
                                                <tr>
                                                    <td class="ps-3 fw-bold">Total</td>
                                                    <td class="text-end pe-3 fw-bold">${formatNumber(totalViews)}</td>
                                                    <td class="text-end pe-3 fw-bold">100%</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                                <div class="card-footer bg-light text-muted small">
                                    <div class="d-flex justify-content-between">
                                        <span>${categories.length} content types</span>
                                        <span>Updated: ${moment().format('HH:mm')}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `);

                const colors = [
                    "#4ecac2", // Teal
                    "#36a2eb", // Blue
                    "#ffce56", // Yellow
                    "#ff6384", // Pink
                    "#9966ff", // Purple
                    "#00cc99", // Green
                    "#ff9933", // Orange
                ];

                const options = {
                    chart: {
                        type: "pie",
                        height: 380,
                        width: '100%',
                        toolbar: { show: false },
                        foreColor: "#000000",
                        animations: {
                            enabled: true,
                            speed: 500,
                        }
                    },
                    series: seriesData,
                    labels: categories,
                    colors: colors,
                    dataLabels: {
                        enabled: false, // Circle ke andar koi label nahi
                    },
                    legend: {
                        show: false,
                    },
                    tooltip: {
                        enabled: true,
                        y: {
                            formatter: function (val, opts) {
                                const label = categories[opts.seriesIndex];
                                const percent = ((val / totalViews) * 100).toFixed(1);
                                return `${label}: ${formatNumber(val)} (${percent}%)`;
                            },
                        },
                    },
                    title: {
                        text: `Views Distribution`,
                        align: "center",
                        style: {
                            fontSize: "18px",
                            fontWeight: "bold",
                            color: "#000000",
                        },
                    },
                    plotOptions: {
                        pie: {
                            startAngle: 0,
                            endAngle: 360,
                            donut: {
                                size: '65%',
                                labels: {
                                    show: true,
                                    value: {
                                        show: true,
                                        fontSize: '26px',
                                        fontWeight: 'bold',
                                        color: '#000',
                                        formatter: function (val) {
                                            return formatNumber(totalViews);
                                        }
                                    },
                                    total: {
                                        show: true,
                                        label: 'Total Views',
                                        color: '#666',
                                        fontSize: '13px',
                                    }
                                }
                            },
                            expandOnClick: true,
                        },
                    },
                    stroke: {
                        width: 1,
                        colors: ['#fff']
                    },
                    responsive: [
                        {
                            breakpoint: 768,
                            options: {
                                chart: { 
                                    height: 350,
                                },
                                plotOptions: {
                                    pie: {
                                        donut: {
                                            size: '60%'
                                        }
                                    }
                                }
                            },
                        },
                        {
                            breakpoint: 480,
                            options: {
                                chart: { 
                                    height: 300,
                                },
                                plotOptions: {
                                    pie: {
                                        donut: {
                                            size: '55%'
                                        }
                                    }
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

                // Circle ke bahar labels add karo
                setTimeout(() => {
                    addLabelsOutsideCircle();
                }, 1000);

                function addLabelsOutsideCircle() {
                    // Chart element
                    const chartEl = document.querySelector('#viewChart');
                    if (!chartEl) return;
                    
                    const chartRect = chartEl.getBoundingClientRect();
                    const centerX = chartRect.width / 2;
                    const centerY = chartRect.height / 2;
                    const radius = Math.min(centerX, centerY) * 0.45; // Circle ke bahar
                    
                    const total = seriesData.reduce((a, b) => a + b, 0);
                    let currentAngle = -90; // Top se start
                    
                    // Sab labels ka array
                    const allLabels = [];
                    
                    seriesData.forEach((value, index) => {
                        const percentage = (value / total) * 100;
                        if (percentage < 1) return; // Bahut chhote slices skip
                        
                        const sliceAngle = (value / total) * 360;
                        const midAngle = currentAngle + (sliceAngle / 2);
                        currentAngle += sliceAngle;
                        
                        // Convert to radians
                        const angleRad = (midAngle * Math.PI) / 180;
                        
                        // Circle ke bahar position
                        const labelX = centerX + Math.cos(angleRad) * (radius + 30);
                        const labelY = centerY + Math.sin(angleRad) * (radius + 30);
                        
                        // Label create karo
                        const label = document.createElement('div');
                        label.className = 'outside-label';
                        label.style.position = 'absolute';
                        label.style.left = `${labelX}px`;
                        label.style.top = `${labelY}px`;
                        label.style.transform = 'translate(-50%, -50%)';
                        label.style.zIndex = '10';
                        label.style.pointerEvents = 'auto';
                        label.style.cursor = 'default';
                        
                        // Simple HTML aapke format me
                        label.innerHTML = `
                            <span style="
                                color: rgb(0, 0, 0);
                                font-size: 14px;
                                font-weight: normal;
                                font-family: Arial, sans-serif;
                                line-height: 1.4;
                                display: inline-block;
                                vertical-align: middle;
                                text-shadow: white 1px 1px 2px;
                            ">
                                <strong>${categories[index]}</strong><br>
                                <small>${formatNumber(value)} (${percentage.toFixed(1)}%)</small>
                            </span>
                        `;
                        
                        // Chart container me add karo
                        chartEl.parentElement.appendChild(label);
                        allLabels.push({ element: label, x: labelX, y: labelY, index: index });
                        
                        // Hover effect
                        label.addEventListener('mouseenter', () => {
                            chart.highlightSeries(index);
                        });
                        
                        label.addEventListener('mouseleave', () => {
                            chart.resetSeries();
                        });
                    });
                    
                    // Overlap fix - simple adjustment
                    fixLabelOverlap(allLabels);
                }
                
                function fixLabelOverlap(labels) {
                    // Simple overlap check
                    for (let i = 0; i < labels.length; i++) {
                        for (let j = i + 1; j < labels.length; j++) {
                            const label1 = labels[i];
                            const label2 = labels[j];
                            
                            // Check distance
                            const distanceX = Math.abs(label1.x - label2.x);
                            const distanceY = Math.abs(label1.y - label2.y);
                            
                            // Agar overlap ho raha hai (very close)
                            if (distanceX < 60 && distanceY < 20) {
                                // Adjust position
                                if (label1.y < label2.y) {
                                    label1.element.style.top = `${parseFloat(label1.element.style.top) - 15}px`;
                                    label2.element.style.top = `${parseFloat(label2.element.style.top) + 15}px`;
                                } else {
                                    label1.element.style.top = `${parseFloat(label1.element.style.top) + 15}px`;
                                    label2.element.style.top = `${parseFloat(label2.element.style.top) - 15}px`;
                                }
                            }
                        }
                    }
                }

                // Table me color indicators
                $('.color-indicator').each(function(index) {
                    $(this).css('background-color', colors[index % colors.length]);
                });

                // Table hover interaction
                $('table tbody tr.chart-highlight').hover(
                    function() {
                        const index = parseInt($(this).data('index'));
                        chart.highlightSeries(index);
                        $(this).addClass('active');
                    },
                    function() {
                        const index = parseInt($(this).data('index'));
                        chart.resetSeries();
                        $(this).removeClass('active');
                    }
                );

                // Success message
                Toastify({
                    text: `Views data loaded`,
                    duration: 2000,
                    gravity: "top",
                    position: "right",
                    className: "bg-success",
                }).showToast();
                
                // Resize handle
                let resizeTimer;
                $(window).on('resize', function() {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(() => {
                        // Remove old labels
                        $('.outside-label').remove();
                        // Add new labels
                        setTimeout(addLabelsOutsideCircle, 300);
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