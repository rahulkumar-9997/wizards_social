$(document).ready(function () {
    function generateInstaViewPDF(id, startDate, endDate) {
        $("#view-component").addClass("loading");
        $.ajax({
            url: window.insta_view_pdf_url,
            method: "GET",
            data: {
                start_date: startDate,
                end_date: endDate,
            },
            beforeSend: function () {
                $("#view-component").append(
                    '<div class="loading-overlay"><div class="spinner-border text-primary"></div><span class="ms-2">Loading views data...</span></div>'
                );
            },
            success: function (response) {
                if (response.status === "success") {
                    $("#view-component").html(response.viewContent);
                    var existingTooltips = bootstrap.Tooltip.getInstance(
                        $("#view-component [data-bs-toggle='tooltip']")[0]
                    );
                    if (existingTooltips) {
                        existingTooltips.dispose();
                    }
                    var tooltipElements = $(
                        "#view-component [data-bs-toggle='tooltip']"
                    );
                    tooltipElements.each(function () {
                        var tooltipTriggerEl = this;
                        var tooltipInstance =
                            bootstrap.Tooltip.getInstance(tooltipTriggerEl);
                        if (tooltipInstance) {
                            tooltipInstance.dispose();
                        }
                        var title =
                            $(tooltipTriggerEl).data("bs-title") ||
                            "No description available";
                        new bootstrap.Tooltip(tooltipTriggerEl, {
                            title: title,
                            placement: "top",
                            customClass: "success-tooltip",
                        });
                    });                    
                } else {
                    Toastify({
                        text: response.message || "Failed to load views data",
                        duration: 10000,
                        gravity: "top",
                        position: "right",
                        className: "bg-danger",
                        close: true,
                    }).showToast();
                }
            },
            error: function (xhr) {
                let errorMessage = "Error loading views data. Please try again";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                Toastify({
                    text: errorMessage,
                    duration: 10000,
                    gravity: "top",
                    position: "right",
                    className: "bg-danger",
                    close: true,
                }).showToast();
            },
            complete: function () {
                $("#view-component").removeClass("loading");
                $(".loading-overlay").remove();
            },
        });
    }
    window.generateInstaViewPDF = generateInstaViewPDF;
});
