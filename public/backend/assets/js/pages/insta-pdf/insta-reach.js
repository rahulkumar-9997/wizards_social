$(document).ready(function () {
    function generateInstaReachPDF(id, startDate, endDate) {
        $("#reach-component").addClass("loading");
        $.ajax({
            url: window.insta_reach_pdf_url,
            method: "GET",
            data: {
                start_date: startDate,
                end_date: endDate,
            },
            beforeSend: function () {
                $("#reach-component").append(
                    '<div class="loading-overlay"><div class="spinner-border text-primary"></div></div>'
                );
            },
            success: function (response) {
                if (response.status === "success") {
                    $("#reach-component").html(response.reachContent);
                    let tooltipEl = document.querySelector(
                        '#reach-component [data-bs-toggle="tooltip"]'
                    );
                    if (tooltipEl) {
                        let instance = bootstrap.Tooltip.getInstance(tooltipEl);
                        if (instance) {
                            instance.dispose();
                        }
                        let title = tooltipEl.getAttribute("data-bs-title");
                        if (!title || title.trim() === "") {
                            tooltipEl.setAttribute(
                                "data-bs-title",
                                "No description available"
                            );
                        }
                        new bootstrap.Tooltip(tooltipEl);
                    }
                } else {
                    Toastify({
                        text: response.message || "Failed to load reach data",
                        duration: 10000,
                        gravity: "top",
                        position: "right",
                        className: "bg-danger",
                        close: true,
                    }).showToast();
                }
            },
            error: function (xhr) {
                let errorMessage = "Error loading reach data. Please try again";
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
                $("#reach-component").removeClass("loading");
                $(".loading-overlay").remove();
            },
        });
    }
    window.generateInstaReachPDF = generateInstaReachPDF;
});
