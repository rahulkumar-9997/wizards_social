$(document).ready(function() {
    const id = window.instagram_id;
    let currentSortField = '';
    let currentSortOrder = '';
    let currentMediaType = '';
    let currentSearch = '';    
    function loadInstagramPostData(id, start, end, pageUrl = null) {
        $('#instagram_post').html(`
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Loading posts...</p>
            </div>
        `);
        let url = pageUrl || window.insta_post_data_pdf_url;
        const params = new URLSearchParams();
        params.append('start_date', start);
        params.append('end_date', end);
        if (currentSortField && currentSortOrder) {
            params.append('sort', currentSortField);
            params.append('order', currentSortOrder);
        }
        if (currentMediaType) {
            params.append('media_type', currentMediaType);
        }
        if (currentSearch) {
            params.append('search', currentSearch);
        }
        if (pageUrl) {
            const urlObj = new URL(pageUrl, window.location.origin);
            const existingParams = new URLSearchParams(urlObj.search);
            
            for (let [key, value] of existingParams) {
                if (!params.has(key)) {
                    params.append(key, value);
                }
            }
            
            url = `${urlObj.pathname}?${params.toString()}`;
        } else {
            url = `${url}?${params.toString()}`;
        }

        $.ajax({
            url: url,
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(res) {
                if (res.success) {
                    $('#instagram_post').html(res.html);
                    initializeSorting();
                    initializeFilters();
                } else {
                    $('#instagram_post').html(`<div class="alert alert-danger">${res.error}</div>`);
                }
            },
            error: function(xhr) {
                $('#instagram_post').html(`<div class="alert alert-danger">Failed to fetch posts.</div>`);
            }
        });
    }

    function initializeSorting() {
        $('th[data-sort]').off('click').on('click', function() {
            const sortField = $(this).data('sort');
            const currentOrder = $(this).data('order');
            let newOrder;
            if (currentOrder === 'none' || currentOrder === 'desc') {
                newOrder = 'asc';
            } else {
                newOrder = 'desc';
            }
            currentSortField = sortField;
            currentSortOrder = newOrder;
            const currentRange = $('.daterange').val();
            let start, end;            
            if (currentRange) {
                [start, end] = currentRange.split(' - ');
            } else {
                start = moment().subtract(30, 'days').format('YYYY-MM-DD');
                end = moment().format('YYYY-MM-DD');
            }
            loadInstagramPostData(id, start, end);
        });
    }

    function initializeFilters() {
        $('#media-type-filter').off('change').on('change', function() {
            currentMediaType = $(this).val();            
            const currentRange = $('.daterange').val();
            let start, end;
            
            if (currentRange) {
                [start, end] = currentRange.split(' - ');
            } else {
                start = moment().subtract(30, 'days').format('YYYY-MM-DD');
                end = moment().format('YYYY-MM-DD');
            }
            
            loadInstagramPostData(id, start, end);
        });
        let searchTimeout;
        $('#post-search').off('input').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentSearch = $(this).val();
                
                const currentRange = $('.daterange').val();
                let start, end;
                
                if (currentRange) {
                    [start, end] = currentRange.split(' - ');
                } else {
                    start = moment().subtract(30, 'days').format('YYYY-MM-DD');
                    end = moment().format('YYYY-MM-DD');
                }
                
                loadInstagramPostData(id, start, end);
            }, 500);
        });
        $('#reset-filters').off('click').on('click', function() {
            $('#media-type-filter').val('');
            $('#post-search').val('');            
            currentMediaType = '';
            currentSearch = '';
            currentSortField = '';
            currentSortOrder = '';
            
            const currentRange = $('.daterange').val();
            let start, end;
            
            if (currentRange) {
                [start, end] = currentRange.split(' - ');
            } else {
                start = moment().subtract(30, 'days').format('YYYY-MM-DD');
                end = moment().format('YYYY-MM-DD');
            }
            
            loadInstagramPostData(id, start, end);
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