var site_url = $('meta[name="base-url"]').attr("content");
$(document).ready(function () {
    $(document).on("change", "#instagram_accounts", function () {
        const selectedId = $(this).val();
        if (selectedId) {
            const baseUrl = window.INSTAGRAM_BASE_URL || "/instagram";
            window.location.href = `${baseUrl}/${selectedId}`;
        }
    });
    /*Facebook select */
    $(document).on("change", "#facebook_pages", function () {
        const selectedId = $(this).val();
        if (selectedId) {
            const baseUrlFb = window.facebook_base_url;
            window.location.href = `${baseUrlFb}/${selectedId}`;
        }
    });
});
$(document).ready(function () {
    $("#toggleSidebar").on("click", function () {
        const $sidebar = $("#leftSidebar");
        const $content = $("#mainContent");
        if ($sidebar.hasClass("col-xxl-3")) {
            $sidebar
                .removeClass("col-xxl-3 col-xl-3 big-sidebar-big")
                .addClass("col-xxl-1 col-xl-1 small-sidebar-small");

            $content.removeClass("col-xl-9").addClass("col-xl-11");
        } else {
            $sidebar
                .removeClass("col-xxl-1 col-xl-1 small-sidebar-small")
                .addClass("col-xxl-3 col-xl-3 big-sidebar-big");
            $content.removeClass("col-xl-11").addClass("col-xl-9");
        }
    });
});
