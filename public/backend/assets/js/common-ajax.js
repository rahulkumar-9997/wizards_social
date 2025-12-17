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
        if ($sidebar.hasClass("col-md-3")) {
            $sidebar
                .removeClass("col-md-3 big-sidebar-big")
                .addClass("col-md-1 small-sidebar-small");

            $content.removeClass("col-md-9").addClass("col-md-11");
        } else {
            $sidebar
                .removeClass("col-md-1 small-sidebar-small")
                .addClass("col-md-3 big-sidebar-big");
            $content.removeClass("col-md-11").addClass("col-md-9");
        }
    });
});

document.getElementById('search_post_smpa').addEventListener('keyup', function () {
    let value = this.value.toLowerCase();
    document.querySelectorAll('.account-item').forEach(function (item) {
        let text = item.innerText.toLowerCase();
        item.style.display = text.includes(value) ? '' : 'none';
    });
});
