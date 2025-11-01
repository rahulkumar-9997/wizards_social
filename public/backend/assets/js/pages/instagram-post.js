$(document).ready(function() {
    const id = window.instagram_id;
    function loadInstagramPostData(id, start, end, pageUrl = null) {
        $('#instagram_post').html(`
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Loading posts...</p>
            </div>
        `);

        $.ajax({
            url: pageUrl || window.instagramFetchPostUrl,
            method: 'GET',
            data: { start_date: start, end_date: end },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if (res.success) {
                    $('#instagram_post').html(res.html);
                } else {
                    $('#instagram_post').html(`<div class="alert alert-danger">${res.error}</div>`);
                }
            },
            error: function(xhr) {
                $('#instagram_post').html(`<div class="alert alert-danger">Failed to fetch posts.</div>`);
            }
        });
    }
    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        const currentRange = $('.daterange').val();
        
        if (currentRange) {
            const [start, end] = currentRange.split(' - ');
            loadInstagramPostData(id, start, end, url);
        } else {
            const defaultStart = moment().subtract(30, 'days').format('YYYY-MM-DD');
            const defaultEnd = moment().format('YYYY-MM-DD');
            loadInstagramPostData(id, defaultStart, defaultEnd, url);
        }
    });
    window.loadInstagramPostData = loadInstagramPostData;
});