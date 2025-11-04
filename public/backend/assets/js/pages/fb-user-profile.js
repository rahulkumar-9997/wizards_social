document.addEventListener("DOMContentLoaded", function () {
    const loader = document.getElementById('fb-dashboard-loader');
    const content = document.getElementById('fb-dashboard-content');

    if (!FB_DASHBOARD_URL) {
        console.error("Facebook dashboard route not defined.");
        return;
    }

    loader.style.display = 'block';
    content.style.display = 'none';

    fetch(FB_DASHBOARD_URL, {
        headers: {
            "X-Requested-With": "XMLHttpRequest",
        },
    })
    .then((response) => response.json())
    .then((data) => {
        loader.style.display = 'none';
        if (data.status === 'success') {
            content.innerHTML = data.html;
            content.style.display = 'block';
        } else {
            content.innerHTML =
                data.html ||
                '<div class="alert alert-danger">Failed to load data.</div>';
            content.style.display = 'block';
        }
    })
    .catch((error) => {
        loader.style.display = 'none';
        content.innerHTML =
            '<div class="alert alert-danger">Error loading Facebook data.</div>';
        content.style.display = 'block';
        console.error(error);
    });
});
