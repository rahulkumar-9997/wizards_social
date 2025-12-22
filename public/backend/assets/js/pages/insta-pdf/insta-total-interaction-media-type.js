$(document).ready(function () {
    function instaTotalInteractionMediaType(id, startDate, endDate) {
        $("#total-interaction-media-type").addClass("loading");
        let url = window.insta_total_interactions_media_type_pdf_url.replace(":id", id);
        $.ajax({
            url: url,
            method: "GET",
            data: {
                start_date: startDate,
                end_date: endDate,
            },
            beforeSend: function () {
                $("#total-interaction-media-type").append(
                    '<div class="loading-overlay"><div class="spinner-border text-primary"></div><span class="ms-2">Loading media type interactions data...</span></div>'
                );
            },
            success: function (response) {
                console.log(
                    "Total Interactions by Media Type Response:",
                    response
                );

                if (
                    response.status === "success" ||
                    response.status === "error"
                ) {
                    if (response.totalInteractionMediaTypeContent) {
                        $("#total-interaction-media-type").html(
                            response.totalInteractionMediaTypeContent
                        );
                        let tooltipElements = $(
                            "#total-interaction-media-type [data-bs-toggle='tooltip']"
                        );
                        if (tooltipElements.length > 0) {
                            tooltipElements.each(function () {
                                let tooltipEl = this;
                                let instance =
                                    bootstrap.Tooltip.getInstance(tooltipEl);
                                if (instance) {
                                    instance.dispose();
                                }
                                new bootstrap.Tooltip(tooltipEl);
                            });
                        }
                    } else {
                        console.error(
                            "totalInteractionMediaTypeContent not found in response"
                        );
                        Toastify({
                            text: "No content received from server",
                            duration: 5000,
                            gravity: "top",
                            position: "right",
                            className: "bg-warning",
                            close: true,
                        }).showToast();
                    }
                    if (response.status === "error") {
                        Toastify({
                            text:
                                response.message ||
                                "Failed to load media type interactions data",
                            duration: 10000,
                            gravity: "top",
                            position: "right",
                            className: "bg-danger",
                            close: true,
                        }).showToast();
                    }
                } else {
                    console.error("API Error:", response);
                    Toastify({
                        text:
                            response.message ||
                            "Failed to load media type interactions data",
                        duration: 10000,
                        gravity: "top",
                        position: "right",
                        className: "bg-danger",
                        close: true,
                    }).showToast();
                }
            },
            error: function (xhr) {
                console.error("AJAX Error:", xhr);
                let errorMessage =
                    "Error loading media type interactions data. Please try again";
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
                $("#total-interaction-media-type").removeClass("loading");
                $(".loading-overlay").remove();
            },
        });
    }

    window.instaTotalInteractionMediaType = instaTotalInteractionMediaType;
});
