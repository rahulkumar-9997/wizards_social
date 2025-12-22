$(document).ready(function () {
    function instaProfileVisit(id, startDate, endDate) {
        $("#profile-visit-component").addClass("loading"); 
        $.ajax({
            url: window.insta_profile_visit_pdf_url,
            method: "GET",
            data: {
                start_date: startDate,
                end_date: endDate,
            },
            beforeSend: function () {
                $("#profile-visit-component").append(
                    '<div class="loading-overlay"><div class="spinner-border text-primary"></div><span class="ms-2">Loading profile visits data...</span></div>'
                );
            },
            success: function (response) {
                console.log('Profile Visits Response:', response);
                
                if (response.status === "success" || response.status === "error") {
                    if (response.profileVisitsContent) {
                        $("#profile-visit-component").html(response.profileVisitsContent);
                        let tooltipElements = $("#profile-visit-component [data-bs-toggle='tooltip']");
                        tooltipElements.each(function () {
                            let tooltipEl = this;
                            let instance = bootstrap.Tooltip.getInstance(tooltipEl);
                            if (instance) {
                                instance.dispose();
                            }
                            if (response.data && response.data.profile_visits) {
                                let description = response.data.profile_visits.description || 'No description available';
                                tooltipEl.setAttribute("data-bs-title", description);
                            }
                            
                            new bootstrap.Tooltip(tooltipEl);
                        });
                    } else {
                        console.error('profileVisitsContent not found in response');
                    }
                    if (response.status === "error") {
                        Toastify({
                            text: response.message || "Failed to load profile visits data",
                            duration: 10000,
                            gravity: "top",
                            position: "right",
                            className: "bg-danger",
                            close: true,
                        }).showToast();
                    }
                } else {
                    Toastify({
                        text: response.message || "Failed to load profile visits data",
                        duration: 10000,
                        gravity: "top",
                        position: "right",
                        className: "bg-danger",
                        close: true,
                    }).showToast();
                }
            },
            error: function (xhr) {
                console.error('AJAX Error:', xhr);
                let errorMessage = "Error loading profile visits data. Please try again";
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
                $("#profile-visit-component").removeClass("loading");
                $(".loading-overlay").remove();
            },
        });
    }
    window.instaProfileVisit = instaProfileVisit;
});