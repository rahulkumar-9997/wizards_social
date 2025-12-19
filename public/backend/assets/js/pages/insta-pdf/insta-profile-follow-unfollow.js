$(document).ready(function () {
    function instaProfileFollowUnfollowPDF(id, startDate, endDate) {
        $("#profile-followers-component").addClass("loading");
        $.ajax({
            url: window.insta_profile_follow_unfollow_pdf_url,
            method: "GET",
            data: {
                start_date: startDate,
                end_date: endDate,
            },
            beforeSend: function () {
                $("#profile-followers-component").append(
                    '<div class="loading-overlay"><div class="spinner-border text-primary"></div><span class="ms-2">Loading followers data...</span></div>'
                );
            },
            success: function (response) {
                if (response.status === "success") {
                    $("#profile-followers-component").html(
                        response.followersContent
                    );
                    let tooltipElements = $(
                        "#profile-followers-component [data-bs-toggle='tooltip']"
                    );
                    tooltipElements.each(function () {
                        let tooltipEl = this;
                        let instance = bootstrap.Tooltip.getInstance(tooltipEl);
                        if (instance) {
                            instance.dispose();
                        }
                        let title = tooltipEl.getAttribute("data-bs-title");
                        if (!title || title.trim() === "") {
                            title = "No description available";
                            tooltipEl.setAttribute("data-bs-title", title);
                        }
                        new bootstrap.Tooltip(tooltipEl);
                    });
                } else {
                    Toastify({
                        text:response.message || "Failed to load followers data",
                        duration: 10000,
                        gravity: "top",
                        position: "right",
                        className: "bg-danger",
                        close: true,
                    }).showToast();
                }
            },
            error: function (xhr) {
                let errorMessage =
                    "Error loading followers data. Please try again";
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
                $("#profile-followers-component").removeClass("loading");
                $(".loading-overlay").remove();
            },
        });
    }
    window.instaProfileFollowUnfollowPDF = instaProfileFollowUnfollowPDF;
});
