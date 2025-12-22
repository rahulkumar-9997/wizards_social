$(document).ready(function () {
    function instaPostReel(id, startDate, endDate) {
        $("#post-reels-component").addClass("loading");
        $.ajax({
            url: window.insta_post_reel_pdf_url,
            method: "GET",
            data: {
                start_date: startDate,
                end_date: endDate,
            },
            beforeSend: function () {
                $("#post-reels-component").append(
                    '<div class="loading-overlay"><div class="spinner-border text-primary"></div><span class="ms-2">Loading posts & reels data...</span></div>'
                );
            },
            success: function (response) {
                console.log("Response:", response); 
                if (response.status === "success") {
                    if (response.postReelsContent) {
                        $("#post-reels-component").html(
                            response.postReelsContent
                        );
                        let tooltipElements = $(
                            "#post-reels-component [data-bs-toggle='tooltip']"
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
                        console.error("postReelsContent not found in response");
                    }
                } else {
                    console.error("API Error:", response);
                    Toastify({
                        text:
                            response.message ||
                            "Failed to load posts & reels data",
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
                    "Error loading posts & reels data. Please try again";
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
                $("#post-reels-component").removeClass("loading");
                $(".loading-overlay").remove();
            },
        });
    }

    window.instaPostReel = instaPostReel;
});
