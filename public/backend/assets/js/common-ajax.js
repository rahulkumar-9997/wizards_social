
var site_url = $('meta[name="base-url"]').attr('content');
$(document).ready(function () {
    $(document).on("change", "#instagram_accounts", function () {
        const selectedId = $(this).val();
        if (selectedId) {
            const baseUrl = window.INSTAGRAM_BASE_URL || "/instagram";
            window.location.href = `${baseUrl}/${selectedId}`;
        }
    });    
    
});



