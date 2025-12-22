$(document).ready(function () {
    function instaTotalInteraction(id, startDate, endDate) {
        $("#total-interactions-component").addClass("loading");
        let url = window.insta_total_interactions_pdf_url.replace(":id", id);

        $.ajax({
            url: url,
            method: "GET",
            data: {
                start_date: startDate,
                end_date: endDate,
            },
            beforeSend: function () {
                $("#total-interactions-component").append(
                    '<div class="loading-overlay"><div class="spinner-border text-primary"></div><span class="ms-2">Loading total interactions data...</span></div>'
                );
            },
            success: function (response) {
                console.log("Total Interactions Response:", response);

                if (response.status === "success") {
                    if (response.totalInteractionContent) {
                        $("#total-interactions-component").html(
                            response.totalInteractionContent
                        );
                        let tooltipElement = $(
                            "#total-interactions-component [data-bs-toggle='tooltip']"
                        );
                        if (
                            tooltipElement.length > 0 &&
                            response.data &&
                            response.data.total_interactions
                        ) {
                            let description =
                                response.data.total_interactions.description ||
                                "No description available";
                            tooltipElement.attr("data-bs-title", description);
                            let instance = bootstrap.Tooltip.getInstance(
                                tooltipElement[0]
                            );
                            if (instance) {
                                instance.dispose();
                            }
                            new bootstrap.Tooltip(tooltipElement[0]);
                        }
                    } else {
                        console.error(
                            "totalInteractionContent not found in response"
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
                } else {
                    console.error("API Error:", response);
                    Toastify({
                        text:
                            response.message ||
                            "Failed to load total interactions data",
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
                    "Error loading total interactions data. Please try again";
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
                $("#total-interactions-component").removeClass("loading");
                $(".loading-overlay").remove();
            },
        });
    }

    window.instaTotalInteraction = instaTotalInteraction;
});
