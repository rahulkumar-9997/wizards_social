$(document).ready(function () {
    function instaEngagement(id, startDate, endDate) {
        $("#engagement-component").addClass("loading");
        let url = window.insta_engagement_pdf_url.replace(":id", id);
        $.ajax({
            url: url,
            method: "GET",
            data: {
                start_date: startDate,
                end_date: endDate,
            },
            beforeSend: function () {
                $("#engagement-component").append(
                    '<div class="loading-overlay"><div class="spinner-border text-primary"></div><span class="ms-2">Loading engagement data...</span></div>'
                );
            },
            success: function (response) {
                console.log("Engagement Response:", response);

                if (
                    response.status === "success" ||
                    response.status === "error"
                ) {
                    if (response.totalAccountsEngagedContent) {
                        $("#engagement-component").html(
                            response.totalAccountsEngagedContent
                        );
                        let tooltipElement = $(
                            "#engagement-component [data-bs-toggle='tooltip']"
                        );
                        if (
                            tooltipElement.length > 0 &&
                            response.data &&
                            response.data.accounts_engaged
                        ) {
                            let description =
                                response.data.accounts_engaged.description ||
                                "The number of accounts that have interacted with your content, including in ads.";
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
                            "totalAccountsEngagedContent not found in response"
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
                                "Failed to load engagement data",
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
                            "Failed to load engagement data",
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
                    "Error loading engagement data. Please try again";
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
                $("#engagement-component").removeClass("loading");
                $(".loading-overlay").remove();
            },
        });
    }

    window.instaEngagement = instaEngagement;
});
