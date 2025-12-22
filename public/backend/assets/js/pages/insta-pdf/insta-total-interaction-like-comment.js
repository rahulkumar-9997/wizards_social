$(document).ready(function () {
    function instaTotalInteractionLikeComments(id, startDate, endDate) {
        $("#total-interaction-like-comment").addClass("loading");
        let url = window.insta_total_interactions_like_comment_pdf_url.replace(":id", id);
        $.ajax({
            url: url,
            method: "GET",
            data: {
                start_date: startDate,
                end_date: endDate,
            },
            beforeSend: function () {
                $("#total-interaction-like-comment").append(
                    '<div class="loading-overlay"><div class="spinner-border text-primary"></div><span class="ms-2">Loading interactions data...</span></div>'
                );
            },
            success: function (response) {
                console.log(
                    "Total Interactions Like/Comments Response:",
                    response
                );
                if (
                    response.status === "success" ||
                    response.status === "error"
                ) {
                    if (response.totalInteractionLikeContent) {
                        $("#total-interaction-like-comment").html(
                            response.totalInteractionLikeContent
                        );
                        let tooltipElements = $(
                            "#total-interaction-like-comment [data-bs-toggle='tooltip']"
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
                            "totalInteractionLikeContent not found in response"
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
                                "Failed to load interactions data",
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
                            "Failed to load interactions data",
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
                    "Error loading interactions data. Please try again";
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
                $("#total-interaction-like-comment").removeClass("loading");
                $(".loading-overlay").remove();
            },
        });
    }

    window.instaTotalInteractionLikeComments =
        instaTotalInteractionLikeComments;
});
