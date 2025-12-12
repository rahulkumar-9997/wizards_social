<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instagram Report - {{ $instagram['name'] ?? '' }}</title>
    <style>
        @page {
            margin: 20px;
        }

        body {
            font-family: 'DejaVu Sans', 'Helvetica', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }

        .pdf-header {
            border-bottom: 3px solid #fd7e03;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .pdf-header h1 {
            font-size: 24px;
            color: #000;
            margin: 0;
            font-weight: bold;
        }

        .pdf-footer {
            border-top: 3px solid #fd7e03;
            padding-top: 15px;
            margin-top: 20px;
            font-size: 11px;
        }

        .page-break {
            page-break-before: always;
        }

        .section-title {
            background: #222;
            color: white;
            padding: 8px 12px;
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0 15px 0;
            border-radius: 4px;
        }

        .metric-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: #f9f9f9;
        }

        .metric-title {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .table th {
            background: #333;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 11px;
        }

        .table td {
            padding: 8px;
            border: 1px solid #ddd;
            font-size: 11px;
        }

        .table tr:nth-child(even) {
            background: #f8f8f8;
        }

        .chart-container {
            width: 100%;
            height: 300px;
            margin: 15px 0;
        }

        .profile-section {
            margin-bottom: 25px;
        }

        .profile-info {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .profile-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 3px solid #eee;
        }

        .profile-details h3 {
            margin: 0 0 5px 0;
            font-size: 18px;
        }

        .profile-stats {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }

        .stat-label {
            font-size: 11px;
            color: #666;
        }

        .positive {
            color: #28a745;
            font-weight: bold;
        }

        .negative {
            color: #dc3545;
            font-weight: bold;
        }

        .date-range {
            font-size: 13px;
            color: #666;
            margin: 10px 0;
            font-style: italic;
        }

        .post-media {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .mb-3 {
            margin-bottom: 15px;
        }

        .mt-3 {
            margin-top: 15px;
        }

        .p-2 {
            padding: 10px;
        }

        .bg-light {
            background: #f8f9fa;
        }
    </style>
</head>

<body>
    <div class="col-xl-9 export_pdf_report">
        <div class="pdf-header" style="display: none;">
            <div class="header-content" style="padding: 10px; border-bottom: 5px solid #fd7e03; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1 style="font-size: 30px; color: #000000; margin: 0; font-weight: bold;">Instagram Report</h1>
                        <p style="font-size: 18px; color: #000000; margin: 5px 0 0 0;">For the Date Range of:
                            <span id="report-date">
                                14-Nov-25 to 11-Dec-25
                            </span>
                        </p>
                    </div>
                    <div style="text-align: right;">
                        <img src="http://localhost:8000/backend/assets/logo.png" style="width:177px; height:45px;" class="logo-lg" alt="logo light" loading="lazy">
                    </div>
                </div>
            </div>
        </div>
        <div class="pdf-footer" style="display: none;">
            <div class="header-content" style="padding: 10px; border-top: 5px solid #fd7e03; margin-top: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="margin-right: 5px;">
                            <img src="http://localhost:8000/backend/assets/phone-icon.png">
                        </div>
                        <div>
                            <h4 style="font-size: 18px; color: #000000; margin: 0px 0 0 0;">
                                +91-7339474554
                            </h4>
                        </div>

                    </div>
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="margin-right: 5px;">
                                <img src="http://localhost:8000/backend/assets/location-icon.png">
                            </div>
                            <div>
                                <h4 style="font-size: 16px; color: #000000; margin: 0px 0 0 0; line-height: 20px;">
                                    Wizards Next LLP, Sigra Mahmoorganj Road,<br> Near
                                    Santoor Hotel, Varanasi, 221010
                                </h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-2 pdf-content">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center gap-1">
                        <h4 class="card-title mb-0">Instagram Integration - Connected</h4>
                        <button id="downloadPdf" class="btn btn-outline-primary pdf-download-btn no-print">
                            <i class="bx bx-download"></i> Download PDF Report
                        </button>
                    </div>
                    <div class="card-body d-flex align-items-center">
                        <img src="https://scontent.flko9-1.fna.fbcdn.net/v/t51.2885-15/101336411_597076511014406_8708244078963195904_n.jpg?_nc_cat=100&amp;ccb=1-7&amp;_nc_sid=7d201b&amp;_nc_ohc=vGmxziXB7aMQ7kNvwGhJ5hC&amp;_nc_oc=AdkFbR7G30tIATHQdjvji7G-_FkqsmmloMU7YrS0HMJ8vTLkNoFLKJ6-yS89k7Lv1pH4HlLmloLyzQQnI2BiXFRP&amp;_nc_zt=23&amp;_nc_ht=scontent.flko9-1.fna&amp;edm=AL-3X8kEAAAA&amp;oh=00_AfnGgyLVMShOMRuNohT4lwdvhv5MggoZsE2q67UTHs9RDg&amp;oe=6941AB7B" width="100" height="100" class="me-3" alt="Profile">
                        <div>
                            <h3 class="mb-1 fw-bold">Dr Aradhya Achuri</h3>
                            <p class="text-muted mb-1">draradhyaachuri</p>
                            <p class="mb-2">Guiding your journey to parenthood 🌱 | IVF &amp; Egg Freezing | Busting myths, sharing hope 🌟 | 📍 Hyderabad<br>
                                💌 DM for appointments &amp; queries</p>
                            <div class="d-flex gap-4">
                                <span><strong>962</strong> posts</span>
                                <span><strong>25,388</strong> followers</span>
                                <span><strong>408</strong> following</span>
                            </div>
                        </div>
                        <div class="ms-auto">
                            <a href="http://localhost:8000/facebook" class="btn btn-outline-primary">
                                <i class="fab fa-facebook"></i> Back to Facebook
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-xxl-12">
                <div id="insta_face_dashboard">
                    <div class="card">
                        <div class="card-header text-white">
                            <h4 class="card-title mb-0">
                                Performance (<span class="text-info">14 November 2025 - 11 December 2025</span>)
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="reach-section mb-4">
                                <div class="row g-4">
                                    <div class="col-md-4 reach col-sm-4 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-0 pe-xl-0 ps-xl-2">
                                        <div class="metric-card">
                                            <div class="metric-header">
                                                <h4>
                                                    Reach
                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of unique accounts that have seen your content, at least once, including in ads. Content includes posts, stories, reels, videos and live videos. Reach is different from impressions, which may include multiple views of your content by the same accounts. This metric is estimated and in development.">
                                                    </i>
                                                </h4>
                                            </div>
                                            <div class="metric-body">
                                                <table class="table table-sm mb-2 align-middle text-center">
                                                    <tbody>
                                                        <tr>
                                                            <th>
                                                                <h3 class="mb-0">804.6K</h3>
                                                            </th>
                                                            <th>
                                                                <h3 class="mb-0">734.9K</h3>
                                                            </th>
                                                        </tr>
                                                        <tr>
                                                            <td class="bg-black text-light">Previous Month</td>
                                                            <td class="bg-black text-light">Current Month</td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="2" class="negative">
                                                                <h4 class="mb-0">▼ 8.66%</h4>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="bg-black text-light">Paid Reach</td>
                                                            <td class="bg-black text-light">Organic Reach</td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <h4 class="mb-0">516.5K</h4>
                                                            </td>
                                                            <td>
                                                                <h4 class="mb-0">218.5K</h4>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="bg-black text-light">Followers</td>
                                                            <td class="bg-black text-light">Non-Followers</td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <h4 class="mb-0">13.4K</h4>
                                                            </td>
                                                            <td>
                                                                <h4 class="mb-0">721.5K</h4>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 followers">
                                        <div class="row">
                                            <div class="col-md-12 col-sm-12 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-1 pe-xl-0 ps-xl-2">
                                                <div class="metric-card">
                                                    <div class="metric-header">
                                                        <h4>
                                                            Followers
                                                            <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of accounts that followed you and the number of accounts that unfollowed you or left Instagram in the selected time period.">
                                                            </i>
                                                        </h4>
                                                    </div>
                                                    <div class="metric-body">
                                                        <table class="table table-sm mb-2 align-middle text-center">

                                                            <tbody>
                                                                <tr>
                                                                    <th>
                                                                        <h3 class="mb-0">
                                                                            1694
                                                                        </h3>
                                                                    </th>
                                                                    <th>
                                                                        <h3 class="mb-0">
                                                                            2033
                                                                        </h3>
                                                                    </th>
                                                                </tr>
                                                                <tr>
                                                                    <td class="bg-black text-light">Previous Month</td>
                                                                    <td class="bg-black text-light">Current Month</td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="2" class="positive">
                                                                        <h4 class="mb-0">▲ +20.01%</h4>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-12 col-sm-12 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-1 pe-xl-0 ps-xl-2">
                                                <div class="metric-card">
                                                    <div class="metric-header">
                                                        <h4>
                                                            Unfollowers
                                                            <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of accounts that followed you and the number of accounts that unfollowed you or left Instagram in the selected time period.">
                                                            </i>
                                                        </h4>
                                                    </div>
                                                    <div class="metric-body">
                                                        <table class="table table-sm mb-2 align-middle text-center">

                                                            <tbody>
                                                                <tr>
                                                                    <th>
                                                                        <h3 class="mb-0">
                                                                            322
                                                                        </h3>
                                                                    </th>
                                                                    <th>
                                                                        <h3 class="mb-0">
                                                                            368
                                                                        </h3>
                                                                    </th>
                                                                </tr>
                                                                <tr>
                                                                    <td class="bg-black text-light">Previous Month</td>
                                                                    <td class="bg-black text-light">Current Month</td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="2" class="positive">
                                                                        <h4 class="mb-0">▲ +14.29%</h4>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 view col-sm-4 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-1 pe-xl-0 ps-xl-2 mb-1">
                                        <div class="metric-card">
                                            <div class="metric-header">
                                                <h4>
                                                    View By Followers &amp; Non Followers
                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of times your content was played or displayed. Content includes reels, posts, stories.">
                                                    </i>
                                                </h4>
                                            </div>
                                            <div class="metric-body">
                                                <table class="table table-sm mb-2 align-middle text-center">
                                                    <tbody>
                                                        <tr>
                                                            <td colspan="2" class="bg-black text-light">Current Month</td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <h4 class="mb-0">154.7K</h4>
                                                            </td>
                                                            <td>
                                                                <h4 class="mb-0">1.2M</h4>
                                                            </td>
                                                        </tr>
                                                        <!--<tr>
                                            <td colspan="2" class="negative">
                                                <h4 class="mb-0">▼ 6.82%</h4>
                                            </td>
                                        </tr>-->
                                                        <tr>
                                                            <td class="bg-success text-light">Followers</td>
                                                            <td class="bg-success text-light">Non-Followers</td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="2" class="bg-black text-light">Previous Month</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="bg-success text-light">Followers</td>
                                                            <td class="bg-success text-light">Non-Followers</td>
                                                        </tr>

                                                        <tr>
                                                            <td>
                                                                <h4 class="mb-0">166.1K</h4>
                                                            </td>
                                                            <td>
                                                                <h4 class="mb-0">1.4M</h4>
                                                            </td>
                                                        </tr>
                                                        <!--<tr>
                                            <td colspan="2" class="negative">
                                                <h4 class="mb-0">▼ 14.46%</h4>
                                            </td>
                                        </tr>-->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xxl-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center gap-1">
                        <h5 class="card-title mb-0">
                            Total Views
                            <i id="viewDateRangeTitle" class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of times your content was played or displayed. Content includes reels, posts, stories.">
                            </i>
                        </h5>
                        <span id="viewDateRange" class="text-muted small">(14 Nov 2025 → 11 Dec 2025)</span>
                    </div>
                    <div class="card-body">
                        <div id="viewDaysContainer">
                            <div id="viewChart" style="min-height: 388.1px;">
                                <div id="apexchartsyp43pmiz" class="apexcharts-canvas apexchartsyp43pmiz apexcharts-theme-light" style="width: 1030px; height: 388.1px;"><svg id="SvgjsSvg1121" width="1030" height="388.1000015258789" xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:svgjs="http://svgjs.dev" class="apexcharts-svg" xmlns:data="ApexChartsNS" transform="translate(0, 0)" style="background: transparent;">
                                        <foreignObject x="0" y="0" width="1030" height="388.1000015258789">
                                            <div class="apexcharts-legend apexcharts-align-center apx-legend-position-bottom" xmlns="http://www.w3.org/1999/xhtml" style="inset: auto 0px 1px; position: absolute; max-height: 210px;">
                                                <div class="apexcharts-legend-series" rel="1" seriesname="Others" data:collapsed="false" style="margin: 4px 10px;"><span class="apexcharts-legend-marker" rel="1" data:collapsed="false" style="background: rgb(78, 202, 194) !important; color: rgb(78, 202, 194); height: 12px; width: 12px; left: 0px; top: 0px; border-width: 0px; border-color: rgb(255, 255, 255); border-radius: 12px;"></span><span class="apexcharts-legend-text" rel="1" i="0" data:default-text="Others%3A%201%20(0.0%25)" data:collapsed="false" style="color: rgb(51, 51, 51); font-size: 13px; font-weight: 400; font-family: Helvetica, Arial, sans-serif;">Others: 1 (0.0%)</span></div>
                                                <div class="apexcharts-legend-series" rel="2" seriesname="IGTV" data:collapsed="false" style="margin: 4px 10px;"><span class="apexcharts-legend-marker" rel="2" data:collapsed="false" style="background: rgb(54, 162, 235) !important; color: rgb(54, 162, 235); height: 12px; width: 12px; left: 0px; top: 0px; border-width: 0px; border-color: rgb(255, 255, 255); border-radius: 12px;"></span><span class="apexcharts-legend-text" rel="2" i="1" data:default-text="IGTV%3A%2078%20(0.0%25)" data:collapsed="false" style="color: rgb(51, 51, 51); font-size: 13px; font-weight: 400; font-family: Helvetica, Arial, sans-serif;">IGTV: 78 (0.0%)</span></div>
                                                <div class="apexcharts-legend-series" rel="3" seriesname="Carousels" data:collapsed="false" style="margin: 4px 10px;"><span class="apexcharts-legend-marker" rel="3" data:collapsed="false" style="background: rgb(255, 206, 86) !important; color: rgb(255, 206, 86); height: 12px; width: 12px; left: 0px; top: 0px; border-width: 0px; border-color: rgb(255, 255, 255); border-radius: 12px;"></span><span class="apexcharts-legend-text" rel="3" i="2" data:default-text="Carousels%3A%2058%2C878%20(4.4%25)" data:collapsed="false" style="color: rgb(51, 51, 51); font-size: 13px; font-weight: 400; font-family: Helvetica, Arial, sans-serif;">Carousels: 58,878 (4.4%)</span></div>
                                                <div class="apexcharts-legend-series" rel="4" seriesname="Reels" data:collapsed="false" style="margin: 4px 10px;"><span class="apexcharts-legend-marker" rel="4" data:collapsed="false" style="background: rgb(255, 99, 132) !important; color: rgb(255, 99, 132); height: 12px; width: 12px; left: 0px; top: 0px; border-width: 0px; border-color: rgb(255, 255, 255); border-radius: 12px;"></span><span class="apexcharts-legend-text" rel="4" i="3" data:default-text="Reels%3A%20414%2C506%20(30.7%25)" data:collapsed="false" style="color: rgb(51, 51, 51); font-size: 13px; font-weight: 400; font-family: Helvetica, Arial, sans-serif;">Reels: 414,506 (30.7%)</span></div>
                                                <div class="apexcharts-legend-series" rel="5" seriesname="Ads" data:collapsed="false" style="margin: 4px 10px;"><span class="apexcharts-legend-marker" rel="5" data:collapsed="false" style="background: rgb(153, 102, 255) !important; color: rgb(153, 102, 255); height: 12px; width: 12px; left: 0px; top: 0px; border-width: 0px; border-color: rgb(255, 255, 255); border-radius: 12px;"></span><span class="apexcharts-legend-text" rel="5" i="4" data:default-text="Ads%3A%20774%2C106%20(57.3%25)" data:collapsed="false" style="color: rgb(51, 51, 51); font-size: 13px; font-weight: 400; font-family: Helvetica, Arial, sans-serif;">Ads: 774,106 (57.3%)</span></div>
                                                <div class="apexcharts-legend-series" rel="6" seriesname="Stories" data:collapsed="false" style="margin: 4px 10px;"><span class="apexcharts-legend-marker" rel="6" data:collapsed="false" style="background: rgb(0, 204, 153) !important; color: rgb(0, 204, 153); height: 12px; width: 12px; left: 0px; top: 0px; border-width: 0px; border-color: rgb(255, 255, 255); border-radius: 12px;"></span><span class="apexcharts-legend-text" rel="6" i="5" data:default-text="Stories%3A%2087%2C585%20(6.5%25)" data:collapsed="false" style="color: rgb(51, 51, 51); font-size: 13px; font-weight: 400; font-family: Helvetica, Arial, sans-serif;">Stories: 87,585 (6.5%)</span></div>
                                                <div class="apexcharts-legend-series" rel="7" seriesname="Posts" data:collapsed="false" style="margin: 4px 10px;"><span class="apexcharts-legend-marker" rel="7" data:collapsed="false" style="background: rgb(255, 153, 51) !important; color: rgb(255, 153, 51); height: 12px; width: 12px; left: 0px; top: 0px; border-width: 0px; border-color: rgb(255, 255, 255); border-radius: 12px;"></span><span class="apexcharts-legend-text" rel="7" i="6" data:default-text="Posts%3A%2016%2C503%20(1.2%25)" data:collapsed="false" style="color: rgb(51, 51, 51); font-size: 13px; font-weight: 400; font-family: Helvetica, Arial, sans-serif;">Posts: 16,503 (1.2%)</span></div>
                                            </div>
                                            <style type="text/css">
                                                .apexcharts-legend {
                                                    display: flex;
                                                    overflow: auto;
                                                    padding: 0 10px;
                                                }

                                                .apexcharts-legend.apx-legend-position-bottom,
                                                .apexcharts-legend.apx-legend-position-top {
                                                    flex-wrap: wrap
                                                }

                                                .apexcharts-legend.apx-legend-position-right,
                                                .apexcharts-legend.apx-legend-position-left {
                                                    flex-direction: column;
                                                    bottom: 0;
                                                }

                                                .apexcharts-legend.apx-legend-position-bottom.apexcharts-align-left,
                                                .apexcharts-legend.apx-legend-position-top.apexcharts-align-left,
                                                .apexcharts-legend.apx-legend-position-right,
                                                .apexcharts-legend.apx-legend-position-left {
                                                    justify-content: flex-start;
                                                }

                                                .apexcharts-legend.apx-legend-position-bottom.apexcharts-align-center,
                                                .apexcharts-legend.apx-legend-position-top.apexcharts-align-center {
                                                    justify-content: center;
                                                }

                                                .apexcharts-legend.apx-legend-position-bottom.apexcharts-align-right,
                                                .apexcharts-legend.apx-legend-position-top.apexcharts-align-right {
                                                    justify-content: flex-end;
                                                }

                                                .apexcharts-legend-series {
                                                    cursor: pointer;
                                                    line-height: normal;
                                                }

                                                .apexcharts-legend.apx-legend-position-bottom .apexcharts-legend-series,
                                                .apexcharts-legend.apx-legend-position-top .apexcharts-legend-series {
                                                    display: flex;
                                                    align-items: center;
                                                }

                                                .apexcharts-legend-text {
                                                    position: relative;
                                                    font-size: 14px;
                                                }

                                                .apexcharts-legend-text *,
                                                .apexcharts-legend-marker * {
                                                    pointer-events: none;
                                                }

                                                .apexcharts-legend-marker {
                                                    position: relative;
                                                    display: inline-block;
                                                    cursor: pointer;
                                                    margin-right: 3px;
                                                    border-style: solid;
                                                }

                                                .apexcharts-legend.apexcharts-align-right .apexcharts-legend-series,
                                                .apexcharts-legend.apexcharts-align-left .apexcharts-legend-series {
                                                    display: inline-block;
                                                }

                                                .apexcharts-legend-series.apexcharts-no-click {
                                                    cursor: auto;
                                                }

                                                .apexcharts-legend .apexcharts-hidden-zero-series,
                                                .apexcharts-legend .apexcharts-hidden-null-series {
                                                    display: none !important;
                                                }

                                                .apexcharts-inactive-legend {
                                                    opacity: 0.45;
                                                }
                                            </style>
                                        </foreignObject>
                                        <g id="SvgjsG1123" class="apexcharts-inner apexcharts-graphical" transform="translate(12, 22.600000381469727)">
                                            <defs id="SvgjsDefs1122">
                                                <clipPath id="gridRectMaskyp43pmiz">
                                                    <rect id="SvgjsRect1125" width="1014" height="312.3999996185303" x="-3" y="-3" rx="0" ry="0" opacity="1" stroke-width="0" stroke="none" stroke-dasharray="0" fill="#fff"></rect>
                                                </clipPath>
                                                <clipPath id="forecastMaskyp43pmiz"></clipPath>
                                                <clipPath id="nonForecastMaskyp43pmiz"></clipPath>
                                                <clipPath id="gridRectMarkerMaskyp43pmiz">
                                                    <rect id="SvgjsRect1126" width="1012" height="310.3999996185303" x="-2" y="-2" rx="0" ry="0" opacity="1" stroke-width="0" stroke="none" stroke-dasharray="0" fill="#fff"></rect>
                                                </clipPath>
                                            </defs>
                                            <g id="SvgjsG1127" class="apexcharts-pie">
                                                <g id="SvgjsG1128" transform="translate(0, 0) scale(1)">
                                                    <g id="SvgjsG1129" class="apexcharts-slices">
                                                        <g id="SvgjsG1130" class="apexcharts-series apexcharts-pie-series" seriesName="Others" rel="1" data:realIndex="0">
                                                            <path id="SvgjsPath1131" d="M 360.53658555193647 153.1999998092651 A 143.46341444806356 143.46341444806356 0 0 1 360.5365855534865 153.19933291876126 L 500.0000000000432 153.19998121524222 C 502.0000000000216 153.1999905122537 502 153.19999980926514 500 153.19999980926514 L 360.53658555193647 153.1999998092651 " fill="rgba(78,202,194,1)" fill-opacity="1" stroke-opacity="1" stroke-linecap="butt" stroke-width="2" stroke-dasharray="0" class="apexcharts-pie-area apexcharts-pie-slice-0" index="0" j="0" data:angle="0.00026633975927836673" data:startAngle="-90" data:strokeWidth="2" data:value="1" data:pathOrig="M 360.53658555193647 153.1999998092651 A 143.46341444806356 143.46341444806356 0 0 1 360.5365855534865 153.19933291876126 L 500.0000000000432 153.19998121524222 C 502.0000000000216 153.1999905122537 502 153.19999980926514 500 153.19999980926514 L 360.53658555193647 153.1999998092651 " stroke="#ffffff"></path>
                                                        </g>
                                                        <g id="SvgjsG1132" class="apexcharts-series apexcharts-pie-series" seriesName="IGTV" rel="2" data:realIndex="1">
                                                            <path id="SvgjsPath1133" d="M 360.5365855534865 153.19933291876126 A 143.46341444806356 143.46341444806356 0 0 1 360.53659522562464 153.14731546064417 L 500.0000002697186 153.198530881488 C 502.0000001348593 153.19926534537657 502.0000000000216 153.1999905122537 500.0000000000432 153.19998121524222 L 360.5365855534865 153.19933291876126 " fill="rgba(54,162,235,1)" fill-opacity="1" stroke-opacity="1" stroke-linecap="butt" stroke-width="2" stroke-dasharray="0" class="apexcharts-pie-area apexcharts-pie-slice-1" index="0" j="1" data:angle="0.0207745012233147" data:startAngle="-89.99973366024072" data:strokeWidth="2" data:value="78" data:pathOrig="M 360.5365855534865 153.19933291876126 A 143.46341444806356 143.46341444806356 0 0 1 360.53659522562464 153.14731546064417 L 500.0000002697186 153.198530881488 C 502.0000001348593 153.19926534537657 502.0000000000216 153.1999905122537 500.0000000000432 153.19998121524222 L 360.5365855534865 153.19933291876126 " stroke="#ffffff"></path>
                                                        </g>
                                                        <g id="SvgjsG1134" class="apexcharts-series apexcharts-pie-series" seriesName="Carousels" rel="3" data:realIndex="2">
                                                            <path id="SvgjsPath1135" d="M 360.53659522562464 153.14731546064417 A 143.46341444806356 143.46341444806356 0 0 1 365.89071217533103 114.37248505928932 L 500.1492820073743 152.11742374206767 C 502.07464100368713 152.6587117756664 502.0000001348593 153.19926534537657 500.0000002697186 153.198530881488 L 360.53659522562464 153.14731546064417 " fill="rgba(255,206,86,1)" fill-opacity="1" stroke-opacity="1" stroke-linecap="butt" stroke-width="2" stroke-dasharray="0" class="apexcharts-pie-area apexcharts-pie-slice-2" index="0" j="2" data:angle="15.681552346490278" data:startAngle="-89.9789591590174" data:strokeWidth="2" data:value="58878" data:pathOrig="M 360.53659522562464 153.14731546064417 A 143.46341444806356 143.46341444806356 0 0 1 365.89071217533103 114.37248505928932 L 500.1492820073743 152.11742374206767 C 502.07464100368713 152.6587117756664 502.0000001348593 153.19926534537657 500.0000002697186 153.198530881488 L 360.53659522562464 153.14731546064417 " stroke="#ffffff"></path>
                                                        </g>
                                                        <g id="SvgjsG1138" class="apexcharts-series apexcharts-pie-series" seriesName="Reels" rel="4" data:realIndex="3">
                                                            <path id="SvgjsPath1139" d="M 365.89071217533103 114.37248505928932 A 143.46341444806356 143.46341444806356 0 0 1 588.5322108055115 37.28599452332301 L 506.3568994542818 149.96812342511828 C 505.1784497271409 151.5840616171917 502.07464100368713 152.6587117756664 500.1492820073743 152.11742374206767 L 365.89071217533103 114.37248505928932 " fill="rgba(255,99,132,1)" fill-opacity="1" stroke-opacity="1" stroke-linecap="butt" stroke-width="2" stroke-dasharray="0" class="apexcharts-pie-area apexcharts-pie-slice-3" index="0" j="3" data:angle="110.39942825731676" data:startAngle="-74.29740681252713" data:strokeWidth="2" data:value="414506" data:pathOrig="M 365.89071217533103 114.37248505928932 A 143.46341444806356 143.46341444806356 0 0 1 588.5322108055115 37.28599452332301 L 506.3568994542818 149.96812342511828 C 505.1784497271409 151.5840616171917 502.07464100368713 152.6587117756664 500.1492820073743 152.11742374206767 L 365.89071217533103 114.37248505928932 " stroke="#ffffff"></path>
                                                        </g>
                                                        <g id="SvgjsG1142" class="apexcharts-series apexcharts-pie-series" seriesName="Ads" rel="5" data:realIndex="4">
                                                            <path id="SvgjsPath1143" d="M 588.5322108055115 37.28599452332301 A 143.46341444806356 143.46341444806356 0 1 1 377.0049231911877 219.93830778467537 L 500.4591648038661 155.06077548457353 C 502.22958240193304 154.13038764691933 505.1784497271409 151.5840616171917 506.3568994542818 149.96812342511828 L 588.5322108055115 37.28599452332301 " fill="rgba(153,102,255,1)" fill-opacity="1" stroke-opacity="1" stroke-linecap="butt" stroke-width="2" stroke-dasharray="0" class="apexcharts-pie-area apexcharts-pie-slice-4" index="0" j="4" data:angle="206.1752056919766" data:startAngle="36.10202144478963" data:strokeWidth="2" data:value="774106" data:pathOrig="M 588.5322108055115 37.28599452332301 A 143.46341444806356 143.46341444806356 0 1 1 377.0049231911877 219.93830778467537 L 500.4591648038661 155.06077548457353 C 502.22958240193304 154.13038764691933 505.1784497271409 151.5840616171917 506.3568994542818 149.96812342511828 L 588.5322108055115 37.28599452332301 " stroke="#ffffff"></path>
                                                        </g>
                                                        <g id="SvgjsG1146" class="apexcharts-series apexcharts-pie-series" seriesName="Stories" rel="6" data:realIndex="5">
                                                            <path id="SvgjsPath1147" d="M 377.0049231911877 219.93830778467537 A 143.46341444806356 143.46341444806356 0 0 1 360.95852556192915 164.19490206802024 L 500.01176439335745 153.50655607801295 C 502.0058821966787 153.35327794363906 502.22958240193304 154.13038764691933 500.4591648038661 155.06077548457353 L 377.0049231911877 219.93830778467537 " fill="rgba(0,204,153,1)" fill-opacity="1" stroke-opacity="1" stroke-linecap="butt" stroke-width="2" stroke-dasharray="0" class="apexcharts-pie-area apexcharts-pie-slice-5" index="0" j="5" data:angle="23.32736781594741" data:startAngle="242.27722713676621" data:strokeWidth="2" data:value="87585" data:pathOrig="M 377.0049231911877 219.93830778467537 A 143.46341444806356 143.46341444806356 0 0 1 360.95852556192915 164.19490206802024 L 500.01176439335745 153.50655607801295 C 502.0058821966787 153.35327794363906 502.22958240193304 154.13038764691933 500.4591648038661 155.06077548457353 L 377.0049231911877 219.93830778467537 " stroke="#ffffff"></path>
                                                        </g>
                                                        <g id="SvgjsG1150" class="apexcharts-series apexcharts-pie-series" seriesName="Posts" rel="7" data:realIndex="6">
                                                            <path id="SvgjsPath1151" d="M 360.95852556192915 164.19490206802024 A 143.46341444806356 143.46341444806356 0 0 1 360.5365877370092 153.2250388985207 L 500.00000006092347 153.20069794096239 C 502.0000000304617 153.20034887511378 502.0058821966787 153.35327794363906 500.01176439335745 153.50655607801295 L 360.95852556192915 164.19490206802024 " fill="rgba(255,153,51,1)" fill-opacity="1" stroke-opacity="1" stroke-linecap="butt" stroke-width="2" stroke-dasharray="0" class="apexcharts-pie-area apexcharts-pie-slice-6" index="0" j="6" data:angle="4.395405047286431" data:startAngle="265.6045949527136" data:strokeWidth="2" data:value="16503" data:pathOrig="M 360.95852556192915 164.19490206802024 A 143.46341444806356 143.46341444806356 0 0 1 360.5365877370092 153.2250388985207 L 500.00000006092347 153.20069794096239 C 502.0000000304617 153.20034887511378 502.0058821966787 153.35327794363906 500.01176439335745 153.50655607801295 L 360.95852556192915 164.19490206802024 " stroke="#ffffff"></path>
                                                        </g>
                                                        <g id="SvgjsG1136" class="apexcharts-datalabels"><text id="SvgjsText1137" font-family="Helvetica, Arial, sans-serif" x="400.21402542455075" y="138.86899591152044" text-anchor="middle" dominant-baseline="auto" font-size="12px" font-weight="600" fill="#ffffff" class="apexcharts-text apexcharts-pie-label" style="font-family: Helvetica, Arial, sans-serif;">Carousels (4.4%)</text></g>
                                                        <g id="SvgjsG1140" class="apexcharts-datalabels"><text id="SvgjsText1141" font-family="Helvetica, Arial, sans-serif" x="469.72112824917144" y="54.1956304976877" text-anchor="middle" dominant-baseline="auto" font-size="12px" font-weight="600" fill="#ffffff" class="apexcharts-text apexcharts-pie-label" style="font-family: Helvetica, Arial, sans-serif;">Reels
                                                                414,506</text></g>
                                                        <g id="SvgjsG1144" class="apexcharts-datalabels"><text id="SvgjsText1145" font-family="Helvetica, Arial, sans-serif" x="572.473716011213" y="232.49852695137992" text-anchor="middle" dominant-baseline="auto" font-size="12px" font-weight="600" fill="#ffffff" class="apexcharts-text apexcharts-pie-label" style="font-family: Helvetica, Arial, sans-serif;">Ads
                                                                774,106</text></g>
                                                        <g id="SvgjsG1148" class="apexcharts-datalabels"><text id="SvgjsText1149" font-family="Helvetica, Arial, sans-serif" x="403.31774498925034" y="182.18257590669558" text-anchor="middle" dominant-baseline="auto" font-size="12px" font-weight="600" fill="#ffffff" class="apexcharts-text apexcharts-pie-label" style="font-family: Helvetica, Arial, sans-serif;">Stories
                                                                87,585</text></g>
                                                    </g>
                                                </g>
                                            </g>
                                            <line id="SvgjsLine1152" x1="0" y1="0" x2="1008" y2="0" stroke="#b6b6b6" stroke-dasharray="0" stroke-width="1" stroke-linecap="butt" class="apexcharts-ycrosshairs"></line>
                                            <line id="SvgjsLine1153" x1="0" y1="0" x2="1008" y2="0" stroke-dasharray="0" stroke-width="0" stroke-linecap="butt" class="apexcharts-ycrosshairs-hidden"></line>
                                        </g><text id="SvgjsText1124" font-family="Helvetica, Arial, sans-serif" x="515" y="18.5" text-anchor="middle" dominant-baseline="auto" font-size="16px" font-weight="bold" fill="#099901ff" class="apexcharts-title-text" style="font-family: Helvetica, Arial, sans-serif; opacity: 1;">Total Views: 1,351,657 (14 Nov 2025 → 11 Dec 2025)</text>
                                    </svg>
                                    <div class="apexcharts-tooltip apexcharts-theme-dark">
                                        <div class="apexcharts-tooltip-series-group" style="order: 1;"><span class="apexcharts-tooltip-marker" style="background-color: rgb(78, 202, 194);"></span>
                                            <div class="apexcharts-tooltip-text" style="font-family: Helvetica, Arial, sans-serif; font-size: 12px;">
                                                <div class="apexcharts-tooltip-y-group"><span class="apexcharts-tooltip-text-y-label"></span><span class="apexcharts-tooltip-text-y-value"></span></div>
                                                <div class="apexcharts-tooltip-goals-group"><span class="apexcharts-tooltip-text-goals-label"></span><span class="apexcharts-tooltip-text-goals-value"></span></div>
                                                <div class="apexcharts-tooltip-z-group"><span class="apexcharts-tooltip-text-z-label"></span><span class="apexcharts-tooltip-text-z-value"></span></div>
                                            </div>
                                        </div>
                                        <div class="apexcharts-tooltip-series-group" style="order: 2;"><span class="apexcharts-tooltip-marker" style="background-color: rgb(54, 162, 235);"></span>
                                            <div class="apexcharts-tooltip-text" style="font-family: Helvetica, Arial, sans-serif; font-size: 12px;">
                                                <div class="apexcharts-tooltip-y-group"><span class="apexcharts-tooltip-text-y-label"></span><span class="apexcharts-tooltip-text-y-value"></span></div>
                                                <div class="apexcharts-tooltip-goals-group"><span class="apexcharts-tooltip-text-goals-label"></span><span class="apexcharts-tooltip-text-goals-value"></span></div>
                                                <div class="apexcharts-tooltip-z-group"><span class="apexcharts-tooltip-text-z-label"></span><span class="apexcharts-tooltip-text-z-value"></span></div>
                                            </div>
                                        </div>
                                        <div class="apexcharts-tooltip-series-group" style="order: 3;"><span class="apexcharts-tooltip-marker" style="background-color: rgb(255, 206, 86);"></span>
                                            <div class="apexcharts-tooltip-text" style="font-family: Helvetica, Arial, sans-serif; font-size: 12px;">
                                                <div class="apexcharts-tooltip-y-group"><span class="apexcharts-tooltip-text-y-label"></span><span class="apexcharts-tooltip-text-y-value"></span></div>
                                                <div class="apexcharts-tooltip-goals-group"><span class="apexcharts-tooltip-text-goals-label"></span><span class="apexcharts-tooltip-text-goals-value"></span></div>
                                                <div class="apexcharts-tooltip-z-group"><span class="apexcharts-tooltip-text-z-label"></span><span class="apexcharts-tooltip-text-z-value"></span></div>
                                            </div>
                                        </div>
                                        <div class="apexcharts-tooltip-series-group" style="order: 4;"><span class="apexcharts-tooltip-marker" style="background-color: rgb(255, 99, 132);"></span>
                                            <div class="apexcharts-tooltip-text" style="font-family: Helvetica, Arial, sans-serif; font-size: 12px;">
                                                <div class="apexcharts-tooltip-y-group"><span class="apexcharts-tooltip-text-y-label"></span><span class="apexcharts-tooltip-text-y-value"></span></div>
                                                <div class="apexcharts-tooltip-goals-group"><span class="apexcharts-tooltip-text-goals-label"></span><span class="apexcharts-tooltip-text-goals-value"></span></div>
                                                <div class="apexcharts-tooltip-z-group"><span class="apexcharts-tooltip-text-z-label"></span><span class="apexcharts-tooltip-text-z-value"></span></div>
                                            </div>
                                        </div>
                                        <div class="apexcharts-tooltip-series-group" style="order: 5;"><span class="apexcharts-tooltip-marker" style="background-color: rgb(153, 102, 255);"></span>
                                            <div class="apexcharts-tooltip-text" style="font-family: Helvetica, Arial, sans-serif; font-size: 12px;">
                                                <div class="apexcharts-tooltip-y-group"><span class="apexcharts-tooltip-text-y-label"></span><span class="apexcharts-tooltip-text-y-value"></span></div>
                                                <div class="apexcharts-tooltip-goals-group"><span class="apexcharts-tooltip-text-goals-label"></span><span class="apexcharts-tooltip-text-goals-value"></span></div>
                                                <div class="apexcharts-tooltip-z-group"><span class="apexcharts-tooltip-text-z-label"></span><span class="apexcharts-tooltip-text-z-value"></span></div>
                                            </div>
                                        </div>
                                        <div class="apexcharts-tooltip-series-group" style="order: 6;"><span class="apexcharts-tooltip-marker" style="background-color: rgb(0, 204, 153);"></span>
                                            <div class="apexcharts-tooltip-text" style="font-family: Helvetica, Arial, sans-serif; font-size: 12px;">
                                                <div class="apexcharts-tooltip-y-group"><span class="apexcharts-tooltip-text-y-label"></span><span class="apexcharts-tooltip-text-y-value"></span></div>
                                                <div class="apexcharts-tooltip-goals-group"><span class="apexcharts-tooltip-text-goals-label"></span><span class="apexcharts-tooltip-text-goals-value"></span></div>
                                                <div class="apexcharts-tooltip-z-group"><span class="apexcharts-tooltip-text-z-label"></span><span class="apexcharts-tooltip-text-z-value"></span></div>
                                            </div>
                                        </div>
                                        <div class="apexcharts-tooltip-series-group" style="order: 7;"><span class="apexcharts-tooltip-marker" style="background-color: rgb(255, 153, 51);"></span>
                                            <div class="apexcharts-tooltip-text" style="font-family: Helvetica, Arial, sans-serif; font-size: 12px;">
                                                <div class="apexcharts-tooltip-y-group"><span class="apexcharts-tooltip-text-y-label"></span><span class="apexcharts-tooltip-text-y-value"></span></div>
                                                <div class="apexcharts-tooltip-goals-group"><span class="apexcharts-tooltip-text-goals-label"></span><span class="apexcharts-tooltip-text-goals-value"></span></div>
                                                <div class="apexcharts-tooltip-z-group"><span class="apexcharts-tooltip-text-z-label"></span><span class="apexcharts-tooltip-text-z-value"></span></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="page-break"></div>
        <!--Total interaction section-->
        <div class="row mb-2">
            <div class="col-xxl-12">
                <div id="total_interactions_dashboard">
                    <div class="row g-4">
                        <div class="col-md-12 col-sm-6 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-1 pe-xl-0 ps-xl-2 mb-1">
                            <div class="row">
                                <div class="col-md-12 col-sm-12 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-1 pe-xl-0 ps-xl-2 mb-1">
                                    <div class="metric-card">
                                        <div class="metric-header">
                                            <h4>
                                                Total Interactions
                                                <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.">
                                                </i>
                                            </h4>
                                        </div>
                                        <div class="metric-body">
                                            <table class="table table-sm mb-3 align-middle text-center">

                                                <tbody>
                                                    <tr>
                                                        <td>
                                                            <h4 class="mb-0">13.6K</h4>
                                                        </td>
                                                        <td>
                                                            <h4 class="mb-0">10K</h4>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="bg-black text-light">Previous Month</td>
                                                        <td class="bg-black text-light">Current Month</td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="2" class="negative">
                                                            <h4 class="mb-0">▼ 26.39%</h4>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="col-lg-12 mb-2">
                                                        <h5 class="card-title mb-0">Total Interactions by Likes, Comments, Saves, Shares, Reposts </h5>
                                                    </div>
                                                    <table class="table table-bordered table-sm mb-2 ">
                                                        <tbody>
                                                            <tr>
                                                                <td class="bg-black text-light">Previous Month</td>
                                                                <td class="bg-black text-light">Current Month</td>
                                                            </tr>
                                                            <tr>
                                                                <!-- Previous Month -->
                                                                <td>
                                                                    <table class="table table-sm mb-2 ">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td>
                                                                                    <h4 class="mb-0">
                                                                                        Likes
                                                                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of likes on your posts, reels and videos.">
                                                                                        </i>
                                                                                    </h4>
                                                                                </td>
                                                                                <td>
                                                                                    <h4 class="mb-0">9.3K</h4>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>
                                                                                    <h4 class="mb-0">
                                                                                        Comments
                                                                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of comments on your posts, reels, videos and live videos.">
                                                                                        </i>
                                                                                    </h4>
                                                                                </td>
                                                                                <td>
                                                                                    <h4 class="mb-0">102</h4>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>
                                                                                    <h4 class="mb-0">
                                                                                        Saves
                                                                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of saves of your posts, reels and videos.">
                                                                                        </i>
                                                                                    </h4>
                                                                                </td>
                                                                                <td>
                                                                                    <h4 class="mb-0">1.1K</h4>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>
                                                                                    <h4 class="mb-0">
                                                                                        Shares
                                                                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of shares of your posts, stories, reels, videos and live videos.">
                                                                                        </i>
                                                                                    </h4>
                                                                                </td>
                                                                                <td>
                                                                                    <h4 class="mb-0">1.5K</h4>
                                                                                </td>
                                                                            </tr>
                                                                            <!--<tr>
                                                            <td>
                                                                <h4 class="mb-0">
                                                                    Reposts
                                                                    <i class="bx bx-question-mark text-primary" 
                                                                        style="cursor: pointer; font-size: 18px;" 
                                                                        data-bs-toggle="tooltip" data-bs-placement="top" 
                                                                        data-bs-custom-class="success-tooltip" 
                                                                        data-bs-title="The total number of times that your content was reposted. A repost occurs when another account shares your content to their own profile.">
                                                                    </i>
                                                                </h4>
                                                            </td>
                                                            <td><h4 class="mb-0">30</h4></td>
                                                        </tr>-->
                                                                        </tbody>
                                                                    </table>
                                                                </td>

                                                                <!-- Current Month -->
                                                                <td>
                                                                    <table class="table  table-sm mb-2">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td>
                                                                                    <h4 class="mb-0">
                                                                                        Likes
                                                                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of likes on your posts, reels and videos.">
                                                                                        </i>
                                                                                    </h4>
                                                                                </td>
                                                                                <td>
                                                                                    <h4 class="mb-0">
                                                                                        7K
                                                                                        <small class="text-danger">
                                                                                            ▼ 25.17%</small>
                                                                                    </h4>

                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>
                                                                                    <h4 class="mb-0">
                                                                                        Comments
                                                                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of comments on your posts, reels, videos and live videos.">
                                                                                        </i>
                                                                                    </h4>
                                                                                </td>
                                                                                <td>
                                                                                    <h4 class="mb-0">
                                                                                        84
                                                                                        <small class="text-danger">
                                                                                            ▼ 17.65%</small>
                                                                                    </h4>

                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>
                                                                                    <h4 class="mb-0">
                                                                                        Saves
                                                                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of saves of your posts, reels and videos.">
                                                                                        </i>
                                                                                    </h4>
                                                                                </td>
                                                                                <td>
                                                                                    <h4 class="mb-0">
                                                                                        958
                                                                                        <small class="text-danger">
                                                                                            ▼ 14.16%</small>
                                                                                    </h4>

                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>
                                                                                    <h4 class="mb-0">
                                                                                        Shares
                                                                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of shares of your posts, stories, reels, videos and live videos.">
                                                                                        </i>
                                                                                    </h4>
                                                                                </td>
                                                                                <td>
                                                                                    <h4 class="mb-0">
                                                                                        920
                                                                                        <small class="text-danger">
                                                                                            ▼ 39.39%</small>
                                                                                    </h4>

                                                                                </td>
                                                                            </tr>
                                                                            <!--<tr>
                                                            <td>
                                                                <h4 class="mb-0">
                                                                    Reposts
                                                                    <i class="bx bx-question-mark text-primary" 
                                                                        style="cursor: pointer; font-size: 18px;" 
                                                                        data-bs-toggle="tooltip" data-bs-placement="top" 
                                                                        data-bs-custom-class="success-tooltip" 
                                                                        data-bs-title="The total number of times that your content was reposted. A repost occurs when another account shares your content to their own profile.">
                                                                    </i>
                                                                </h4>
                                                            </td>
                                                            <td>
                                                                <h4 class="mb-0">
                                                                30
                                                                <small class="text-muted">
                                                                    ➖ 0%</small>
                                                                </h4>
                                                                
                                                            </td>
                                                        </tr>-->
                                                                        </tbody>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="col-lg-12 mb-2">
                                                        <h5 class="card-title mb-0">Total Interactions by Media Type</h5>
                                                    </div>
                                                    <table class="table table-bordered table-sm mb-2">
                                                        <tbody>
                                                            <tr>
                                                                <td class="bg-black text-light">Previous Month</td>
                                                                <td class="bg-black text-light">Current Month</td>
                                                            </tr>
                                                            <tr>
                                                                <!-- Previous Month -->
                                                                <td>
                                                                    <table class="table table-sm mb-2">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td>
                                                                                    <h4 class="mb-0">Post
                                                                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.">
                                                                                        </i>
                                                                                    </h4>
                                                                                </td>
                                                                                <td>
                                                                                    <h4 class="mb-0">258</h4>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>
                                                                                    <h4 class="mb-0">Ad
                                                                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.">
                                                                                        </i>
                                                                                    </h4>
                                                                                </td>
                                                                                <td>
                                                                                    <h4 class="mb-0">6.9K</h4>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>
                                                                                    <h4 class="mb-0">Reel
                                                                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.">
                                                                                        </i>
                                                                                    </h4>
                                                                                </td>
                                                                                <td>
                                                                                    <h4 class="mb-0">6.5K</h4>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>
                                                                                    <h4 class="mb-0">Story
                                                                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.">
                                                                                        </i>
                                                                                    </h4>
                                                                                </td>
                                                                                <td>
                                                                                    <h4 class="mb-0">53</h4>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </td>

                                                                <!-- Current Month -->
                                                                <td>
                                                                    <table class="table table-sm mb-2">
                                                                        <tbody>
                                                                            <tr>
                                                                                <td>
                                                                                    <h4 class="mb-0">Post
                                                                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.">
                                                                                        </i>
                                                                                    </h4>
                                                                                </td>
                                                                                <td>
                                                                                    <h4 class="mb-0">
                                                                                        321
                                                                                        <small class="text-success">▲ +24.42%</small>
                                                                                    </h4>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>
                                                                                    <h4 class="mb-0">Ad
                                                                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.">
                                                                                        </i>
                                                                                    </h4>
                                                                                </td>
                                                                                <td>
                                                                                    <h4 class="mb-0">
                                                                                        4.9K
                                                                                        <small class="text-danger">▼ 29.84%</small>
                                                                                    </h4>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>
                                                                                    <h4 class="mb-0">Reel
                                                                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.">
                                                                                        </i>
                                                                                    </h4>
                                                                                </td>
                                                                                <td>
                                                                                    <h4 class="mb-0">
                                                                                        4.8K
                                                                                        <small class="text-danger">▼ 26.78%</small>
                                                                                    </h4>
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td>
                                                                                    <h4 class="mb-0">Story
                                                                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.">
                                                                                        </i>
                                                                                    </h4>
                                                                                </td>
                                                                                <td>
                                                                                    <h4 class="mb-0">
                                                                                        45
                                                                                        <small class="text-danger">▼ 15.09%</small>
                                                                                    </h4>
                                                                                </td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <!-- Profile Visits -->
                        <div class="col-md-6 col-sm-6 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-0 pe-xl-0 ps-xl-2">
                            <div class="metric-card">
                                <div class="metric-header">
                                    <h4>
                                        Profile Visits
                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of times that your profile was visited.">
                                        </i>
                                    </h4>
                                </div>
                                <div class="metric-body">
                                    <table class="table table-sm mb-2 align-middle text-center">
                                        <tbody>
                                            <tr>
                                                <th>
                                                    <h3 class="mb-0">20.8K</h3>
                                                </th>
                                                <th>
                                                    <h3 class="mb-0">18.4K</h3>
                                                </th>
                                            </tr>
                                            <tr>
                                                <td class="bg-black text-light">Previous Month</td>
                                                <td class="bg-black text-light">Current Month</td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" class="negative">
                                                    <h4 class="mb-0">▼ 11.61%</h4>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Profile Link Clicks -->
                        <!--<div class="col-md-4 col-sm-4 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-0 pe-xl-0 ps-xl-2">
                <div class="row">
                    <div class="col-md-12 col-sm-12 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-1 pe-xl-0 ps-xl-2">
                        <div class="metric-card">
                            <div class="metric-header">
                                <h4>
                                    Profile Link Clicks
                                    <i class="bx bx-question-mark text-primary" 
                                    style="cursor: pointer; font-size: 18px;" 
                                    data-bs-toggle="tooltip" data-bs-placement="top" 
                                    data-bs-custom-class="success-tooltip"
                                    data-bs-title="The number of taps on your business address, call button, email button and text button.">
                            </i> 
                                </h4>
                            </div>
                            <div class="metric-body">
                                <table class="table table-sm mb-2 align-middle text-center">
                                    
                                    <tr>
                                        <th><h3 class="mb-0">8</h3></th>
                                        <th><h3 class="mb-0">7</h3></th>
                                    </tr>
                                    <tr>
                                        <td class="bg-black text-light">Previous Month</td>
                                        <td class="bg-black text-light">Current Month</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="negative">
                                            <h4 class="mb-0">▼ 12.5%</h4>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>-->

                        <!-- Engagement -->
                        <div class="col-md-6 col-sm-6 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-1 pe-xl-0 ps-xl-2">
                            <div class="row">
                                <div class="col-md-12 col-sm-12 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-1 pe-xl-0 ps-xl-2 mb-1">
                                    <div class="metric-card">
                                        <div class="metric-header">
                                            <h4>
                                                Engagement
                                                <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of accounts that have interacted with your content, including in ads. Content includes posts, stories, reels, videos and live videos. Interactions can include actions such as likes, saves, comments, shares or replies. These metrics are estimated and in development.">
                                                </i>
                                            </h4>
                                        </div>
                                        <div class="metric-body">
                                            <table class="table table-sm mb-2 align-middle text-center">
                                                <tbody>
                                                    <tr>
                                                        <th>
                                                            <h3 class="mb-0">11K</h3>
                                                        </th>
                                                        <th>
                                                            <h3 class="mb-0">7.6K</h3>
                                                        </th>
                                                    </tr>
                                                    <tr>
                                                        <td class="bg-black text-light">Previous Month</td>
                                                        <td class="bg-black text-light">Current Month</td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="2" class="negative">
                                                            <h4 class="mb-0">▼ 31.12%</h4>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="row g-4">
                        <div class="post-section">
                            <div class="row justify-content-center">
                                <div class="col-lg-12">
                                    <div class="table-responsive metrics-table mt-3">
                                        <table class="table table-bordered align-middle text-center mb-0">
                                            <thead>
                                                <tr>
                                                    <th colspan="2">Number of Posts</th>
                                                    <th colspan="2">Number of Reels</th>
                                                </tr>
                                                <tr>
                                                    <th class="metric-section-header">Prev. Month</th>
                                                    <th class="metric-section-header">Current</th>

                                                    <th class="metric-section-header">Prev. Month</th>
                                                    <th class="metric-section-header">Current</th>

                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="highlight">3</td>
                                                    <td>4</td>
                                                    <td class="highlight">21</td>
                                                    <td>21</td>

                                                </tr>
                                                <tr>
                                                    <td colspan="2" style="background-color: #28a745; color: #fff; font-weight:600;">+33.33%</td>
                                                    <td colspan="2" style="background-color: #f8f9fa;">0%</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-xxl-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center gap-1">
                        <h4 class="card-title mb-0">
                            Top 10 Cities Audience
                            <i id="audienceByCitiesTitle" class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="warning-tooltip" data-bs-title="The demographic characteristics of the engaged audience, including countries, cities and gender distribution.">
                            </i>
                        </h4>
                        <select id="timeframe" class="form-select form-select-sm w-auto">
                            <option value="this_month" selected="">This Month</option>
                            <option value="this_week">This Week</option>
                        </select>
                    </div>
                    <div class="card-body">
                        <div id="geolocationContainer">
                            <div class="row">
                                <!-- Map Section -->
                                <div class="col-lg-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">
                                                <i class="fas fa-globe-americas me-2"></i>
                                                Geographic Distribution
                                            </h6>
                                            <button class="btn btn-sm btn-outline-secondary" id="resetMapBtn">
                                                <i class="fas fa-sync-alt me-1"></i> Reset View
                                            </button>
                                        </div>
                                        <div class="card-body p-0">
                                            <div id="worldMap" style="height: 400px; border-radius: 0px 0px 8px 8px; position: relative;" class="leaflet-container leaflet-touch leaflet-retina leaflet-fade-anim leaflet-grab leaflet-touch-drag leaflet-touch-zoom" tabindex="0">
                                                <div class="leaflet-pane leaflet-map-pane" style="transform: translate3d(0px, 0px, 0px);">
                                                    <div class="leaflet-pane leaflet-tile-pane">
                                                        <div class="leaflet-layer " style="z-index: 1; opacity: 1;">
                                                            <div class="leaflet-tile-container leaflet-zoom-animated" style="z-index: 16; transform: translate3d(-1624px, -597px, 0px) scale(4);"></div>
                                                            <div class="leaflet-tile-container leaflet-zoom-animated" style="z-index: 18; transform: translate3d(0px, 0px, 0px) scale(1);"><img alt="" src="https://c.basemaps.cartocdn.com/rastertiles/voyager/4/11/6@2x.png" class="leaflet-tile leaflet-tile-loaded" style="width: 256px; height: 256px; transform: translate3d(148px, -77px, 0px); opacity: 1;"><img alt="" src="https://a.basemaps.cartocdn.com/rastertiles/voyager/4/11/7@2x.png" class="leaflet-tile leaflet-tile-loaded" style="width: 256px; height: 256px; transform: translate3d(148px, 179px, 0px); opacity: 1;"><img alt="" src="https://b.basemaps.cartocdn.com/rastertiles/voyager/4/10/6@2x.png" class="leaflet-tile leaflet-tile-loaded" style="width: 256px; height: 256px; transform: translate3d(-108px, -77px, 0px); opacity: 1;"><img alt="" src="https://a.basemaps.cartocdn.com/rastertiles/voyager/4/12/6@2x.png" class="leaflet-tile leaflet-tile-loaded" style="width: 256px; height: 256px; transform: translate3d(404px, -77px, 0px); opacity: 1;"><img alt="" src="https://c.basemaps.cartocdn.com/rastertiles/voyager/4/10/7@2x.png" class="leaflet-tile leaflet-tile-loaded" style="width: 256px; height: 256px; transform: translate3d(-108px, 179px, 0px); opacity: 1;"><img alt="" src="https://b.basemaps.cartocdn.com/rastertiles/voyager/4/12/7@2x.png" class="leaflet-tile leaflet-tile-loaded" style="width: 256px; height: 256px; transform: translate3d(404px, 179px, 0px); opacity: 1;"></div>
                                                        </div>
                                                    </div>
                                                    <div class="leaflet-pane leaflet-overlay-pane"></div>
                                                    <div class="leaflet-pane leaflet-shadow-pane"></div>
                                                    <div class="leaflet-pane leaflet-marker-pane">
                                                        <div class="leaflet-marker-icon custom-map-marker leaflet-zoom-animated leaflet-interactive" tabindex="0" role="button" style="margin-left: -30px; margin-top: -30px; width: 60px; height: 60px; transform: translate3d(293px, 285px, 0px); z-index: 285;">
                                                            <div class="map-marker" style="
                            width: 60px;
                            height: 60px;
                            background: #FF6B6B;
                            border: 3px solid white;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            color: white;
                            font-weight: bold;
                            font-size: 48px;
                            cursor: pointer;
                            box-shadow: 0 4px 12px #FF6B6B;
                            text-shadow: 1px 1px 3px rgba(0,0,0,0.7);
                            transition: all 0.3s ease;
                        " title="Chennai, Tamil Nadu: 26.06%">
                                                                26%
                                                            </div>
                                                        </div>
                                                        <div class="leaflet-marker-icon custom-map-marker leaflet-zoom-animated leaflet-interactive" tabindex="0" role="button" style="margin-left: -26.8611px; margin-top: -26.8611px; width: 53.7222px; height: 53.7222px; transform: translate3d(263px, 286px, 0px); z-index: 286;">
                                                            <div class="map-marker" style="
                            width: 53.72217958557176px;
                            height: 53.72217958557176px;
                            background: #FF6B6B;
                            border: 3px solid white;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            color: white;
                            font-weight: bold;
                            font-size: 41.72217958557176px;
                            cursor: pointer;
                            box-shadow: 0 4px 12px #FF6B6B;
                            text-shadow: 1px 1px 3px rgba(0,0,0,0.7);
                            transition: all 0.3s ease;
                        " title="Bangalore, Karnataka: 21.97%">
                                                                22%
                                                            </div>
                                                        </div>
                                                        <div class="leaflet-marker-icon custom-map-marker leaflet-zoom-animated leaflet-interactive" tabindex="0" role="button" style="margin-left: -23.0084px; margin-top: -23.0084px; width: 46.0169px; height: 46.0169px; transform: translate3d(273px, 234px, 0px); z-index: 234;">
                                                            <div class="map-marker" style="
                            width: 46.016884113584034px;
                            height: 46.016884113584034px;
                            background: #4ECDC4;
                            border: 3px solid white;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            color: white;
                            font-weight: bold;
                            font-size: 34.016884113584034px;
                            cursor: pointer;
                            box-shadow: 0 4px 12px #4ECDC4;
                            text-shadow: 1px 1px 3px rgba(0,0,0,0.7);
                            transition: all 0.3s ease;
                        " title="Hyderabad, Telangana: 16.95%">
                                                                17%
                                                            </div>
                                                        </div>
                                                        <div class="leaflet-marker-icon custom-map-marker leaflet-zoom-animated leaflet-interactive" tabindex="0" role="button" style="margin-left: -16.3085px; margin-top: -16.3085px; width: 32.617px; height: 32.617px; transform: translate3d(270px, 234px, 0px); z-index: 234;">
                                                            <div class="map-marker" style="
                            width: 32.61703760552571px;
                            height: 32.61703760552571px;
                            background: #45B7D1;
                            border: 3px solid white;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            color: white;
                            font-weight: bold;
                            font-size: 20.61703760552571px;
                            cursor: pointer;
                            box-shadow: 0 4px 12px #45B7D1;
                            text-shadow: 1px 1px 3px rgba(0,0,0,0.7);
                            transition: all 0.3s ease;
                        " title="Chanda Nagar, Telangana: 8.22%">
                                                                8%
                                                            </div>
                                                        </div>
                                                        <div class="leaflet-marker-icon custom-map-marker leaflet-zoom-animated leaflet-interactive" tabindex="0" role="button" style="margin-left: -14.528px; margin-top: -14.528px; width: 29.056px; height: 29.056px; transform: translate3d(209px, 214px, 0px); z-index: 214;">
                                                            <div class="map-marker" style="
                            width: 29.056024558710668px;
                            height: 29.056024558710668px;
                            background: #45B7D1;
                            border: 3px solid white;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            color: white;
                            font-weight: bold;
                            font-size: 17.056024558710668px;
                            cursor: pointer;
                            box-shadow: 0 4px 12px #45B7D1;
                            text-shadow: 1px 1px 3px rgba(0,0,0,0.7);
                            transition: all 0.3s ease;
                        " title="Mumbai, Maharashtra: 5.9%">
                                                                6%
                                                            </div>
                                                        </div>
                                                        <div class="leaflet-marker-icon custom-map-marker leaflet-zoom-animated leaflet-interactive" tabindex="0" role="button" style="margin-left: -13.6915px; margin-top: -13.6915px; width: 27.383px; height: 27.383px; transform: translate3d(256px, 309px, 0px); z-index: 309;">
                                                            <div class="map-marker" style="
                            width: 27.38296239447429px;
                            height: 27.38296239447429px;
                            background: #96CEB4;
                            border: 3px solid white;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            color: white;
                            font-weight: bold;
                            font-size: 15.38296239447429px;
                            cursor: pointer;
                            box-shadow: 0 4px 12px #96CEB4;
                            text-shadow: 1px 1px 3px rgba(0,0,0,0.7);
                            transition: all 0.3s ease;
                        " title="Coimbatore, Tamil Nadu: 4.81%">
                                                                5%
                                                            </div>
                                                        </div>
                                                        <div class="leaflet-marker-icon custom-map-marker leaflet-zoom-animated leaflet-interactive" tabindex="0" role="button" style="margin-left: -13.5303px; margin-top: -13.5303px; width: 27.0606px; height: 27.0606px; transform: translate3d(259px, 95px, 0px); z-index: 95;">
                                                            <div class="map-marker" style="
                            width: 27.06062931696086px;
                            height: 27.06062931696086px;
                            background: #96CEB4;
                            border: 3px solid white;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            color: white;
                            font-weight: bold;
                            font-size: 15.06062931696086px;
                            cursor: pointer;
                            box-shadow: 0 4px 12px #96CEB4;
                            text-shadow: 1px 1px 3px rgba(0,0,0,0.7);
                            transition: all 0.3s ease;
                        " title="Delhi, Delhi: 4.6%">
                                                                5%
                                                            </div>
                                                        </div>
                                                        <div class="leaflet-marker-icon custom-map-marker leaflet-zoom-animated leaflet-interactive" tabindex="0" role="button" style="margin-left: -13.1082px; margin-top: -13.1082px; width: 26.2164px; height: 26.2164px; transform: translate3d(297px, 244px, 0px); z-index: 244;">
                                                            <div class="map-marker" style="
                            width: 26.216423637759018px;
                            height: 26.216423637759018px;
                            background: #96CEB4;
                            border: 3px solid white;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            color: white;
                            font-weight: bold;
                            font-size: 14.216423637759018px;
                            cursor: pointer;
                            box-shadow: 0 4px 12px #96CEB4;
                            text-shadow: 1px 1px 3px rgba(0,0,0,0.7);
                            transition: all 0.3s ease;
                        " title="Vijayawada, Andhra Pradesh: 4.05%">
                                                                4%
                                                            </div>
                                                        </div>
                                                        <div class="leaflet-marker-icon custom-map-marker leaflet-zoom-animated leaflet-interactive" tabindex="0" role="button" style="margin-left: -12.878px; margin-top: -12.878px; width: 25.7559px; height: 25.7559px; transform: translate3d(272px, 285px, 0px); z-index: 285;">
                                                            <div class="map-marker" style="
                            width: 25.75594781273983px;
                            height: 25.75594781273983px;
                            background: #96CEB4;
                            border: 3px solid white;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            color: white;
                            font-weight: bold;
                            font-size: 13.75594781273983px;
                            cursor: pointer;
                            box-shadow: 0 4px 12px #96CEB4;
                            text-shadow: 1px 1px 3px rgba(0,0,0,0.7);
                            transition: all 0.3s ease;
                        " title="Dulapalli, Telangana: 3.75%">
                                                                4%
                                                            </div>
                                                        </div>
                                                        <div class="leaflet-marker-icon custom-map-marker leaflet-zoom-animated leaflet-interactive" tabindex="0" role="button" style="margin-left: -12.8166px; margin-top: -12.8166px; width: 25.6332px; height: 25.6332px; transform: translate3d(206px, 166px, 0px); z-index: 166;">
                                                            <div class="map-marker" style="
                            width: 25.63315425940138px;
                            height: 25.63315425940138px;
                            background: #96CEB4;
                            border: 3px solid white;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            color: white;
                            font-weight: bold;
                            font-size: 13.63315425940138px;
                            cursor: pointer;
                            box-shadow: 0 4px 12px #96CEB4;
                            text-shadow: 1px 1px 3px rgba(0,0,0,0.7);
                            transition: all 0.3s ease;
                        " title="Ahmedabad, Gujarat: 3.67%">
                                                                4%
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="leaflet-pane leaflet-tooltip-pane"></div>
                                                    <div class="leaflet-pane leaflet-popup-pane"></div>
                                                    <div class="leaflet-proxy leaflet-zoom-animated" style="transform: translate3d(2919.52px, 1812.79px, 0px) scale(8);"></div>
                                                </div>
                                                <div class="leaflet-control-container">
                                                    <div class="leaflet-top leaflet-left">
                                                        <div class="leaflet-control-zoom leaflet-bar leaflet-control"><a class="leaflet-control-zoom-in" href="#" title="Zoom in" role="button" aria-label="Zoom in" aria-disabled="false"><span aria-hidden="true">+</span></a><a class="leaflet-control-zoom-out" href="#" title="Zoom out" role="button" aria-label="Zoom out" aria-disabled="false"><span aria-hidden="true">−</span></a></div>
                                                    </div>
                                                    <div class="leaflet-top leaflet-right"></div>
                                                    <div class="leaflet-bottom leaflet-left">
                                                        <div class="leaflet-control-scale leaflet-control">
                                                            <div class="leaflet-control-scale-line" style="width: 55px;">500 km</div>
                                                        </div>
                                                    </div>
                                                    <div class="leaflet-bottom leaflet-right">
                                                        <div class="leaflet-control-attribution leaflet-control"><a href="https://leafletjs.com" title="A JavaScript library for interactive maps"><svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="12" height="8" viewBox="0 0 12 8" class="leaflet-attribution-flag">
                                                                    <path fill="#4C7BE1" d="M0 0h12v4H0z"></path>
                                                                    <path fill="#FFD500" d="M0 4h12v3H0z"></path>
                                                                    <path fill="#E0BC00" d="M0 7h12v1H0z"></path>
                                                                </svg> Leaflet</a> <span aria-hidden="true">|</span> © OpenStreetMap, © CartoDB</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Table Section -->
                                <div class="col-lg-6 mb-4">
                                    <div class="card h-100">
                                        <div class="card-header py-2 d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">
                                                <i class="fas fa-list me-2"></i>
                                                Top Locations (10)
                                            </h6>
                                            <small class="text-muted">Click on any row to highlight on map</small>
                                        </div>
                                        <div class="card-body p-0">

                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover table-bordered">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th width="5%">#</th>
                                                            <th>City Name</th>
                                                            <th width="15%" class="text-end">Percentage</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>

                                                        <tr class="location-row" data-location="Chennai, Tamil Nadu" style="cursor: pointer;">
                                                            <td class="text-center">
                                                                <span class="badge bg-secondary">1</span>
                                                            </td>
                                                            <td>
                                                                <i class="fas fa-map-marker-alt me-1 text-muted"></i>
                                                                Chennai, Tamil Nadu
                                                            </td>
                                                            <td class="text-end">
                                                                <span class="badge" style="background: #FF6B6B; color: white;">
                                                                    26.06%
                                                                </span>
                                                            </td>
                                                        </tr>

                                                        <tr class="location-row" data-location="Bangalore, Karnataka" style="cursor: pointer;">
                                                            <td class="text-center">
                                                                <span class="badge bg-secondary">2</span>
                                                            </td>
                                                            <td>
                                                                <i class="fas fa-map-marker-alt me-1 text-muted"></i>
                                                                Bangalore, Karnataka
                                                            </td>
                                                            <td class="text-end">
                                                                <span class="badge" style="background: #FF6B6B; color: white;">
                                                                    21.97%
                                                                </span>
                                                            </td>
                                                        </tr>

                                                        <tr class="location-row" data-location="Hyderabad, Telangana" style="cursor: pointer;">
                                                            <td class="text-center">
                                                                <span class="badge bg-secondary">3</span>
                                                            </td>
                                                            <td>
                                                                <i class="fas fa-map-marker-alt me-1 text-muted"></i>
                                                                Hyderabad, Telangana
                                                            </td>
                                                            <td class="text-end">
                                                                <span class="badge" style="background: #4ECDC4; color: white;">
                                                                    16.95%
                                                                </span>
                                                            </td>
                                                        </tr>

                                                        <tr class="location-row" data-location="Chanda Nagar, Telangana" style="cursor: pointer;">
                                                            <td class="text-center">
                                                                <span class="badge bg-secondary">4</span>
                                                            </td>
                                                            <td>
                                                                <i class="fas fa-map-marker-alt me-1 text-muted"></i>
                                                                Chanda Nagar, Telangana
                                                            </td>
                                                            <td class="text-end">
                                                                <span class="badge" style="background: #45B7D1; color: white;">
                                                                    8.22%
                                                                </span>
                                                            </td>
                                                        </tr>

                                                        <tr class="location-row" data-location="Mumbai, Maharashtra" style="cursor: pointer;">
                                                            <td class="text-center">
                                                                <span class="badge bg-secondary">5</span>
                                                            </td>
                                                            <td>
                                                                <i class="fas fa-map-marker-alt me-1 text-muted"></i>
                                                                Mumbai, Maharashtra
                                                            </td>
                                                            <td class="text-end">
                                                                <span class="badge" style="background: #45B7D1; color: white;">
                                                                    5.9%
                                                                </span>
                                                            </td>
                                                        </tr>

                                                        <tr class="location-row" data-location="Coimbatore, Tamil Nadu" style="cursor: pointer;">
                                                            <td class="text-center">
                                                                <span class="badge bg-secondary">6</span>
                                                            </td>
                                                            <td>
                                                                <i class="fas fa-map-marker-alt me-1 text-muted"></i>
                                                                Coimbatore, Tamil Nadu
                                                            </td>
                                                            <td class="text-end">
                                                                <span class="badge" style="background: #96CEB4; color: white;">
                                                                    4.81%
                                                                </span>
                                                            </td>
                                                        </tr>

                                                        <tr class="location-row" data-location="Delhi, Delhi" style="cursor: pointer;">
                                                            <td class="text-center">
                                                                <span class="badge bg-secondary">7</span>
                                                            </td>
                                                            <td>
                                                                <i class="fas fa-map-marker-alt me-1 text-muted"></i>
                                                                Delhi, Delhi
                                                            </td>
                                                            <td class="text-end">
                                                                <span class="badge" style="background: #96CEB4; color: white;">
                                                                    4.6%
                                                                </span>
                                                            </td>
                                                        </tr>

                                                        <tr class="location-row" data-location="Vijayawada, Andhra Pradesh" style="cursor: pointer;">
                                                            <td class="text-center">
                                                                <span class="badge bg-secondary">8</span>
                                                            </td>
                                                            <td>
                                                                <i class="fas fa-map-marker-alt me-1 text-muted"></i>
                                                                Vijayawada, Andhra Pradesh
                                                            </td>
                                                            <td class="text-end">
                                                                <span class="badge" style="background: #96CEB4; color: white;">
                                                                    4.05%
                                                                </span>
                                                            </td>
                                                        </tr>

                                                        <tr class="location-row" data-location="Dulapalli, Telangana" style="cursor: pointer;">
                                                            <td class="text-center">
                                                                <span class="badge bg-secondary">9</span>
                                                            </td>
                                                            <td>
                                                                <i class="fas fa-map-marker-alt me-1 text-muted"></i>
                                                                Dulapalli, Telangana
                                                            </td>
                                                            <td class="text-end">
                                                                <span class="badge" style="background: #96CEB4; color: white;">
                                                                    3.75%
                                                                </span>
                                                            </td>
                                                        </tr>

                                                        <tr class="location-row" data-location="Ahmedabad, Gujarat" style="cursor: pointer;">
                                                            <td class="text-center">
                                                                <span class="badge bg-secondary">10</span>
                                                            </td>
                                                            <td>
                                                                <i class="fas fa-map-marker-alt me-1 text-muted"></i>
                                                                Ahmedabad, Gujarat
                                                            </td>
                                                            <td class="text-end">
                                                                <span class="badge" style="background: #96CEB4; color: white;">
                                                                    3.67%
                                                                </span>
                                                            </td>
                                                        </tr>

                                                    </tbody>
                                                </table>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="page-break"></div>
            <div class="col-xxl-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center gap-1">
                        <h4 class="card-title mb-0">
                            Audience By Age Group
                            <i id="audienceByAgeGroup" class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="info-tooltip" data-bs-title="The demographic characteristics of the engaged audience, including countries, cities and gender distribution.">
                            </i>
                        </h4>
                        <select id="ageTimeframe" class="form-select form-select-sm" style="width: 150px;">
                            <option value="this_week">This Week</option>
                            <option value="this_month" selected="">This Month</option>
                        </select>
                    </div>
                    <div class="card-body">
                        <div id="audienceAgeGroupContainer"><canvas id="audienceAgeGroupChart" height="562" style="display: block; box-sizing: border-box; height: 450px; width: 1030.3px;" width="1287"></canvas></div>
                    </div>
                </div>
            </div>
        </div>
        <!--Instagram reach days wise -->
        <div class="row mb-2">
            <div class="col-xxl-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center gap-1">
                        <h5 class="card-title mb-0">Profile Reach Per Day
                            <i id="profileReachTitle" class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="danger-tooltip" data-bs-title="The total number of times that the business account's media objects have been uniquely viewed.">
                            </i>
                        </h5>
                        <small id="reachDateRange" class="text-muted">(14 Nov 2025 → 11 Dec 2025)</small>
                    </div>
                    <div class="card-body">
                        <div id="reachDaysContainer"><canvas id="reachDaysChart" height="500" style="display: block; box-sizing: border-box; height: 400px; width: 1030.3px;" width="1287"></canvas></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="page-break"></div>
        <div class="row mb-2">
            <div class="col-xxl-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center gap-1">
                        <h4 class="card-title mb-0">Instagram Post</h4>
                    </div>
                    <div class="card-body">
                        <div class="instagram_post">
                            <div class="col-lg-12">
                                <div id="post-filter" class="filter-box">
                                    <div class="d-flex flex-wrap align-items-center bg-white p-2 gap-1">
                                        <div class="d-flex align-items-center border-end pe-1">
                                            <select id="media-type-filter" class="form-select form-select-md">
                                                <option value="">All Types</option>
                                                <option value="CAROUSEL_ALBUM">Photos</option>
                                                <option value="VIDEO">Video</option>
                                                <option value="REELS">Reels</option>
                                            </select>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <input type="search" id="post-search" class="form-control form-control-md" placeholder="Search by ID or Caption">
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button id="reset-filters" class="btn btn-danger">
                                                <i class="bx bx-reset me-1"></i> Reset
                                            </button>
                                        </div>

                                    </div>
                                </div>
                            </div>

                            <div id="instagram_post">
                                <div id="instagram-media-table">
                                    <div class="mb-2">
                                        <strong>Showing posts from 14 Nov 2025 to 11 Dec 2025</strong>
                                    </div>


                                    <table class="table align-middle mb-0 table-hover table-centered" id="instagram-posts-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Media</th>
                                                <th data-sort="timestamp" data-order="desc" title="Click to sort">
                                                    Date Published
                                                    <span class="sort-arrow">
                                                        <i class="bx bx-sort-down"></i>
                                                    </span>
                                                </th>

                                                <th>Caption</th>
                                                <th data-sort="media_type" data-order="none" title="Click to sort">
                                                    Media Type
                                                    <span class="sort-arrow">
                                                        <i class="bx bx-sort-alt-2"></i>
                                                    </span>
                                                </th>
                                                <th data-sort="like_count" data-order="none" title="Click to sort">
                                                    Likes
                                                    <span class="sort-arrow">
                                                        <i class="bx bx-sort-alt-2"></i>
                                                    </span>
                                                </th>
                                                <th data-sort="comments_count" data-order="none" title="Click to sort">
                                                    Comments
                                                    <span class="sort-arrow">
                                                        <i class="bx bx-sort-alt-2"></i>
                                                    </span>
                                                </th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="post-row">
                                                <td>1</td>
                                                <td>
                                                    <video class="video-section" width="70" height="70" muted="" autoplay="" loop="" playsinline="" style="object-fit:cover; border-radius:6px;">
                                                        <source src="https://instagram.flko10-2.fna.fbcdn.net/o1/v/t2/f2/m86/AQNB4GLHQ3rtYMV95ReySsHx0TnlweZo2M3Y4Iros50GEWF-SqFB6YbNc8hB3nU73Rch59_Ev1xLFBRcQpmV2-MIQEwGwHQK0prLAkM.mp4?_nc_cat=105&amp;_nc_oc=AdmZDn-iSNnm93XxH9gNLwXH500j5MM9I-NnkWaH8idgq3gRJL7ZS2jXBZ8c6BZwFYJzSnRSpUP4gg5lXy3wKp31&amp;_nc_sid=5e9851&amp;_nc_ht=instagram.flko10-2.fna.fbcdn.net&amp;_nc_ohc=Yk6ZLMIuvi4Q7kNvwGsK_9h&amp;efg=eyJ2ZW5jb2RlX3RhZyI6Inhwdl9wcm9ncmVzc2l2ZS5JTlNUQUdSQU0uQ0xJUFMuQzMuNzIwLmRhc2hfYmFzZWxpbmVfMV92MSIsInhwdl9hc3NldF9pZCI6Nzk0NDM5MjYwMjkyMjY3LCJhc3NldF9hZ2VfZGF5cyI6MSwidmlfdXNlY2FzZV9pZCI6MTAwOTksImR1cmF0aW9uX3MiOjksInVybGdlbl9zb3VyY2UiOiJ3d3cifQ%3D%3D&amp;ccb=17-1&amp;vs=4d3758a89b199a1f&amp;_nc_vs=HBksFQIYUmlnX3hwdl9yZWVsc19wZXJtYW5lbnRfc3JfcHJvZC9EODREODA3OTg5QTQ3QzIyODYwOTRDRjI3RjlDQ0ZBNF92aWRlb19kYXNoaW5pdC5tcDQVAALIARIAFQIYOnBhc3N0aHJvdWdoX2V2ZXJzdG9yZS9HQkZkcHlQMzlfRkFsV1FFQUlMUkFjQ3IwcEp5YnN0VEFRQUYVAgLIARIAKAAYABsCiAd1c2Vfb2lsATEScHJvZ3Jlc3NpdmVfcmVjaXBlATEVAAAm1pK-3rmi6QIVAigCQzMsF0AiPXCj1wo9GBJkYXNoX2Jhc2VsaW5lXzFfdjERAHX-B2XmnQEA&amp;_nc_gid=DJimxgHMhMOL-amye0hS5w&amp;edm=AM6HXa8EAAAA&amp;_nc_zt=28&amp;oh=00_AflxpmwVZxWRKvg_7qSKwvq8KQIK76bNKlf4spiv68YIeA&amp;oe=693DD08A" type="video/mp4">
                                                    </video>
                                                    <img class="pdf-img img-fluid img-thumbnail" src="https://scontent.cdninstagram.com/v/t51.82787-15/597893496_18078594335188361_3747688309255160691_n.jpg?stp=dst-jpg_e35_tt6&amp;_nc_cat=103&amp;ccb=7-5&amp;_nc_sid=18de74&amp;efg=eyJlZmdfdGFnIjoiQ0xJUFMuYmVzdF9pbWFnZV91cmxnZW4uQzMifQ%3D%3D&amp;_nc_ohc=BWqp1pLu-C4Q7kNvwE389JJ&amp;_nc_oc=AdkofpB2XfBvXa9htpxliVi6SpIwrZSoy8fdclZ50NSpT0MW9IiBa-Qy0RLPrnU2dTqmKL5k8ID3N71maxkeWFJU&amp;_nc_zt=23&amp;_nc_ht=scontent.cdninstagram.com&amp;edm=AM6HXa8EAAAA&amp;_nc_gid=DJimxgHMhMOL-amye0hS5w&amp;oh=00_Afm5sgifhsXbGaY9UMTV8TfNjXocO1ye73MoZXinIRbwcQ&amp;oe=6941C1D5" style="display: none; max-width:70px; max-height:88px;" alt="Media">
                                                </td>
                                                <td>10-12-2025 12:19 PM</td>

                                                <td>Trying to conceive? Let’s talk about som...</td>
                                                <td>
                                                    <span class="badge 
                     bg-primary
                    ">
                                                        VIDEO
                                                    </span>
                                                </td>
                                                <td>❤️ 12</td>
                                                <td>💬 1</td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <a href="https://www.instagram.com/reel/DSFSsYWE493/" target="_blank" class="btn btn-soft-primary btn-sm" data-bs-toggle="tooltip" data-bs-original-title="View this post on instagram">
                                                            <i class="ti ti-brand-instagram"></i>
                                                            View Instagram
                                                        </a>
                                                        <a href="http://localhost:8000/instagram/17841435650809281/post/18082075835153399/insights-page" class="btn btn-soft-warning btn-sm" data-bs-toggle="tooltip" data-bs-original-title="View this post data">
                                                            <i class="bx bx-bar-chart"></i> View Insights
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr class="post-row">
                                                <td>2</td>
                                                <td>
                                                    <video class="video-section" width="70" height="70" muted="" autoplay="" loop="" playsinline="" style="object-fit:cover; border-radius:6px;">
                                                        <source src="https://instagram.flko9-2.fna.fbcdn.net/o1/v/t2/f2/m86/AQPen7Ug9Sm5h0WF1tRDmgsnCE7lKYloYXL2xo6ujtJenKAubgCzDkRpaYCZTxielgTKIV3RU0KsBr2MX4ZYwQHrxJEAB5v3b4E8Bgo.mp4?_nc_cat=110&amp;_nc_oc=AdkZKyqIV8xPtk0P0lem1T8NMtqzU7U-XpaE_64a7dMa2avJbsCmOSqABzpzuAWJZuuI1htw-WBqmKTMbvnjDzkn&amp;_nc_sid=5e9851&amp;_nc_ht=instagram.flko9-2.fna.fbcdn.net&amp;_nc_ohc=9kXEOMSZwzwQ7kNvwG2kVcK&amp;efg=eyJ2ZW5jb2RlX3RhZyI6Inhwdl9wcm9ncmVzc2l2ZS5JTlNUQUdSQU0uQ0xJUFMuQzMuNDgwLmRhc2hfYmFzZWxpbmVfMV92MSIsInhwdl9hc3NldF9pZCI6ODkzMjMyNjQ2NTQwOTUxLCJhc3NldF9hZ2VfZGF5cyI6MiwidmlfdXNlY2FzZV9pZCI6MTAwOTksImR1cmF0aW9uX3MiOjE2LCJ1cmxnZW5fc291cmNlIjoid3d3In0%3D&amp;ccb=17-1&amp;_nc_gid=DJimxgHMhMOL-amye0hS5w&amp;edm=AM6HXa8EAAAA&amp;_nc_zt=28&amp;vs=88cf1785d6b07222&amp;_nc_vs=HBksFQIYUmlnX3hwdl9yZWVsc19wZXJtYW5lbnRfc3JfcHJvZC8wQjQ1RTQ0MEY2OTIyQTk2MjE5RUNBRkI0RkJEQzM5Rl92aWRlb19kYXNoaW5pdC5tcDQVAALIARIAFQIYOnBhc3N0aHJvdWdoX2V2ZXJzdG9yZS9HQkpiZlNNMFE1T05QekFJQUN6ZktlUDlPS3c3YnN0VEFRQUYVAgLIARIAKAAYABsCiAd1c2Vfb2lsATEScHJvZ3Jlc3NpdmVfcmVjaXBlATEVAAAmrrrK6f2YlgMVAigCQzMsF0AwCHKwIMScGBJkYXNoX2Jhc2VsaW5lXzFfdjERAHX-B2XmnQEA&amp;oh=00_AflQtBEpnF2K7sTS3kj7z_gwLoy5AAfX_7IkYzJOUVZEnw&amp;oe=693DCBD7" type="video/mp4">
                                                    </video>
                                                    <img class="pdf-img img-fluid img-thumbnail" src="https://scontent.cdninstagram.com/v/t51.71878-15/588444817_1382171629984296_93990585693883718_n.jpg?stp=dst-jpg_e35_tt6&amp;_nc_cat=108&amp;ccb=7-5&amp;_nc_sid=18de74&amp;efg=eyJlZmdfdGFnIjoiQ0xJUFMuYmVzdF9pbWFnZV91cmxnZW4uQzMifQ%3D%3D&amp;_nc_ohc=gGhxcCec2R4Q7kNvwHQYlLh&amp;_nc_oc=AdnfB9jJcekacwhPObQNgERbmsAHop7z5E9vHhf971DOrhJxPY7H2-iSBj-wSQh4k1zdQcR2fMcajOHJtMN8nvuo&amp;_nc_zt=23&amp;_nc_ht=scontent.cdninstagram.com&amp;edm=AM6HXa8EAAAA&amp;_nc_gid=DJimxgHMhMOL-amye0hS5w&amp;oh=00_AfmpxeOX_1eFDLDWRci4kUtEzVnErWOLa7ZH83nuZUtgYQ&amp;oe=6941A2BF" style="display: none; max-width:70px; max-height:88px;" alt="Media">
                                                </td>
                                                <td>09-12-2025 12:34 PM</td>

                                                <td>Every IVF journey is unique, and sometim...</td>
                                                <td>
                                                    <span class="badge 
                     bg-primary
                    ">
                                                        VIDEO
                                                    </span>
                                                </td>
                                                <td>❤️ 34</td>
                                                <td>💬 2</td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <a href="https://www.instagram.com/reel/DSCvkGzk9CB/" target="_blank" class="btn btn-soft-primary btn-sm" data-bs-toggle="tooltip" data-bs-original-title="View this post on instagram">
                                                            <i class="ti ti-brand-instagram"></i>
                                                            View Instagram
                                                        </a>
                                                        <a href="http://localhost:8000/instagram/17841435650809281/post/17933979483132880/insights-page" class="btn btn-soft-warning btn-sm" data-bs-toggle="tooltip" data-bs-original-title="View this post data">
                                                            <i class="bx bx-bar-chart"></i> View Insights
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr class="post-row">
                                                <td>3</td>
                                                <td>
                                                    <img src="https://scontent.cdninstagram.com/v/t51.82787-15/590425415_18078330929188361_7894740423667866221_n.jpg?stp=dst-jpg_e35_tt6&amp;_nc_cat=106&amp;ccb=7-5&amp;_nc_sid=18de74&amp;efg=eyJlZmdfdGFnIjoiQ0FST1VTRUxfSVRFTS5iZXN0X2ltYWdlX3VybGdlbi5DMyJ9&amp;_nc_ohc=EMTrYnPUPrcQ7kNvwHcGnzT&amp;_nc_oc=AdmAFoNsTqjaoxaCCqX8uSeoUVTDmyawK8xscxbO2Ou33O9D-aUv_I2x_sFwaJRGZk3IL8r4A3Ly2ulJdSf9y9oo&amp;_nc_zt=23&amp;_nc_ht=scontent.cdninstagram.com&amp;edm=AM6HXa8EAAAA&amp;_nc_gid=DJimxgHMhMOL-amye0hS5w&amp;oh=00_AfmuyRRVBpax1cLmx7W0pjs7l-lll0MZZ4oO2tZcjGUBbw&amp;oe=6941AC77" alt="Media" class="img-fluid img-thumbnail real-image" style="max-width:70px; max-height:88px;">
                                                    <img class="pdf-img img-fluid img-thumbnail" src="https://scontent.cdninstagram.com/v/t51.82787-15/590425415_18078330929188361_7894740423667866221_n.jpg?stp=dst-jpg_e35_tt6&amp;_nc_cat=106&amp;ccb=7-5&amp;_nc_sid=18de74&amp;efg=eyJlZmdfdGFnIjoiQ0FST1VTRUxfSVRFTS5iZXN0X2ltYWdlX3VybGdlbi5DMyJ9&amp;_nc_ohc=EMTrYnPUPrcQ7kNvwHcGnzT&amp;_nc_oc=AdmAFoNsTqjaoxaCCqX8uSeoUVTDmyawK8xscxbO2Ou33O9D-aUv_I2x_sFwaJRGZk3IL8r4A3Ly2ulJdSf9y9oo&amp;_nc_zt=23&amp;_nc_ht=scontent.cdninstagram.com&amp;edm=AM6HXa8EAAAA&amp;_nc_gid=DJimxgHMhMOL-amye0hS5w&amp;oh=00_AfmuyRRVBpax1cLmx7W0pjs7l-lll0MZZ4oO2tZcjGUBbw&amp;oe=6941AC77" style="display: none; max-width:70px; max-height:88px;" alt="Media">
                                                </td>
                                                <td>08-12-2025 12:46 PM</td>

                                                <td>From zero to 28 million - this is what t...</td>
                                                <td>
                                                    <span class="badge 
                     bg-warning
                    ">
                                                        CAROUSEL_ALBUM
                                                    </span>
                                                </td>
                                                <td>❤️ 13</td>
                                                <td>💬 0</td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <a href="https://www.instagram.com/p/DSAMLRjExMm/" target="_blank" class="btn btn-soft-primary btn-sm" data-bs-toggle="tooltip" data-bs-original-title="View this post on instagram">
                                                            <i class="ti ti-brand-instagram"></i>
                                                            View Instagram
                                                        </a>
                                                        <a href="http://localhost:8000/instagram/17841435650809281/post/18050978771689436/insights-page" class="btn btn-soft-warning btn-sm" data-bs-toggle="tooltip" data-bs-original-title="View this post data">
                                                            <i class="bx bx-bar-chart"></i> View Insights
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr class="post-row">
                                                <td>4</td>
                                                <td>
                                                    <video class="video-section" width="70" height="70" muted="" autoplay="" loop="" playsinline="" style="object-fit:cover; border-radius:6px;">
                                                        <source src="https://instagram.flko9-1.fna.fbcdn.net/o1/v/t2/f2/m86/AQM8cevWDd6kQ8z_iE_cuhpUUbIE48taF0q-yebGUqFMuMS9aYaC5W6V_0xc50erMPg_c3AIAh7LBqPTZG1mrtIy5UyUqWmslAGSBUI.mp4?_nc_cat=103&amp;_nc_oc=Adl6hMXsC0XbAZ4WdYCCUgPq1XZR5mP5SzBh_eCw8oOdkYKAApfty5KIUnf9o0FDnA-E6P8zjisO9Q1JHBkwx3RB&amp;_nc_sid=5e9851&amp;_nc_ht=instagram.flko9-1.fna.fbcdn.net&amp;_nc_ohc=_YACf6NgHYgQ7kNvwHgSxRv&amp;efg=eyJ2ZW5jb2RlX3RhZyI6Inhwdl9wcm9ncmVzc2l2ZS5JTlNUQUdSQU0uQ0xJUFMuQzMuNzIwLmRhc2hfYmFzZWxpbmVfMV92MSIsInhwdl9hc3NldF9pZCI6MTE4Nzc1MzA3OTk2NTU2OCwiYXNzZXRfYWdlX2RheXMiOjUsInZpX3VzZWNhc2VfaWQiOjEwMDk5LCJkdXJhdGlvbl9zIjo2LCJ1cmxnZW5fc291cmNlIjoid3d3In0%3D&amp;ccb=17-1&amp;vs=ea5d022cf928cf16&amp;_nc_vs=HBksFQIYUmlnX3hwdl9yZWVsc19wZXJtYW5lbnRfc3JfcHJvZC82NTQ4RTUyRjNBNDBFMEE4NkU2RTBCMUM0MkY1OTA5Rl92aWRlb19kYXNoaW5pdC5tcDQVAALIARIAFQIYOnBhc3N0aHJvdWdoX2V2ZXJzdG9yZS9HQ2FzZWlQcTdaNjR4ZG9FQURuY2s4MGZ6MzlEYnN0VEFRQUYVAgLIARIAKAAYABsCiAd1c2Vfb2lsATEScHJvZ3Jlc3NpdmVfcmVjaXBlATEVAAAmgJ7MhqqQnAQVAigCQzMsF0AYqfvnbItEGBJkYXNoX2Jhc2VsaW5lXzFfdjERAHX-B2XmnQEA&amp;_nc_gid=DJimxgHMhMOL-amye0hS5w&amp;edm=AM6HXa8EAAAA&amp;_nc_zt=28&amp;oh=00_AfnZbe4E1rnQ2IAIs8ZiVNiPP9OpP7JhhLU5rivQ3LGl6Q&amp;oe=693DAFE8" type="video/mp4">
                                                    </video>
                                                    <img class="pdf-img img-fluid img-thumbnail" src="https://scontent.cdninstagram.com/v/t51.82787-15/590936221_18078149651188361_5275429295364065129_n.jpg?stp=dst-jpg_e35_tt6&amp;_nc_cat=107&amp;ccb=7-5&amp;_nc_sid=18de74&amp;efg=eyJlZmdfdGFnIjoiQ0xJUFMuYmVzdF9pbWFnZV91cmxnZW4uQzMifQ%3D%3D&amp;_nc_ohc=bBJGNmB8RPoQ7kNvwFJLLQa&amp;_nc_oc=AdnrXlVNxk9gQWHTE20VBIl0u7MOCstgPRXSXsaiXbk38P6bgTjPPgCCoqUFWs8w1NSib9zDCx-0Y0epZs8riD6V&amp;_nc_zt=23&amp;_nc_ht=scontent.cdninstagram.com&amp;edm=AM6HXa8EAAAA&amp;_nc_gid=DJimxgHMhMOL-amye0hS5w&amp;oh=00_AfntcmOEBXeY0_Z9YpLw0UaDw6Sm4Zrg2hd54mi8-TcY2w&amp;oe=6941CA5F" style="display: none; max-width:70px; max-height:88px;" alt="Media">
                                                </td>
                                                <td>06-12-2025 01:38 PM</td>

                                                <td>Every couple asks this at some point: “D...</td>
                                                <td>
                                                    <span class="badge 
                     bg-primary
                    ">
                                                        VIDEO
                                                    </span>
                                                </td>
                                                <td>❤️ 28</td>
                                                <td>💬 3</td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <a href="https://www.instagram.com/reel/DR7IeKHEynp/" target="_blank" class="btn btn-soft-primary btn-sm" data-bs-toggle="tooltip" data-bs-original-title="View this post on instagram">
                                                            <i class="ti ti-brand-instagram"></i>
                                                            View Instagram
                                                        </a>
                                                        <a href="http://localhost:8000/instagram/17841435650809281/post/17883032340305240/insights-page" class="btn btn-soft-warning btn-sm" data-bs-toggle="tooltip" data-bs-original-title="View this post data">
                                                            <i class="bx bx-bar-chart"></i> View Insights
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr class="post-row">
                                                <td>5</td>
                                                <td>
                                                    <video class="video-section" width="70" height="70" muted="" autoplay="" loop="" playsinline="" style="object-fit:cover; border-radius:6px;">
                                                        <source src="https://instagram.flko10-2.fna.fbcdn.net/o1/v/t2/f2/m86/AQPXHQjAQ4dt4Oqwb1KAKJa4HG-FJgXmQJ8ywBT_Nn77NTwdh9bCBRR53x3W_LSvD1yyXilNjpTCq1b860vV3b__9CR5Ub1UoLx3-d8.mp4?_nc_cat=107&amp;_nc_oc=Adn4-6AV2wqC5mmm1-KK8bjang1OHy_6A7fuvGfFvee55kOBZH6q5pxoKBULUJtMS68b61EaTMwTtfAs7t_ZgzIW&amp;_nc_sid=5e9851&amp;_nc_ht=instagram.flko10-2.fna.fbcdn.net&amp;_nc_ohc=B1dlq0CR_Z8Q7kNvwETnTkb&amp;efg=eyJ2ZW5jb2RlX3RhZyI6Inhwdl9wcm9ncmVzc2l2ZS5JTlNUQUdSQU0uQ0xJUFMuQzMuNzIwLmRhc2hfYmFzZWxpbmVfMV92MSIsInhwdl9hc3NldF9pZCI6ODM3MzQ1NDA1NTc5MjY3LCJhc3NldF9hZ2VfZGF5cyI6NiwidmlfdXNlY2FzZV9pZCI6MTAwOTksImR1cmF0aW9uX3MiOjcsInVybGdlbl9zb3VyY2UiOiJ3d3cifQ%3D%3D&amp;ccb=17-1&amp;_nc_gid=DJimxgHMhMOL-amye0hS5w&amp;edm=AM6HXa8EAAAA&amp;_nc_zt=28&amp;vs=94219b815ef31f43&amp;_nc_vs=HBksFQIYUmlnX3hwdl9yZWVsc19wZXJtYW5lbnRfc3JfcHJvZC81RTQ0MUY4MTMwQzRBNzcwRjI1MDk1NUNDMThDQzRCOV92aWRlb19kYXNoaW5pdC5tcDQVAALIARIAFQIYOnBhc3N0aHJvdWdoX2V2ZXJzdG9yZS9HUGgxWENQZUVNR1N6bjhFQUN0Q3dkQlZEdVVIYnN0VEFRQUYVAgLIARIAKAAYABsCiAd1c2Vfb2lsATEScHJvZ3Jlc3NpdmVfcmVjaXBlATEVAAAmhsCht_Xj_AIVAigCQzMsF0Adu2RaHKwIGBJkYXNoX2Jhc2VsaW5lXzFfdjERAHX-B2XmnQEA&amp;oh=00_AfmvHwYLxszP1zxraSyOCdDnC4xFmxyNfxolnmZmhzYX3w&amp;oe=693DD844" type="video/mp4">
                                                    </video>
                                                    <img class="pdf-img img-fluid img-thumbnail" src="https://scontent.cdninstagram.com/v/t51.82787-15/589422368_18078050039188361_2507773386739415849_n.jpg?stp=dst-jpg_e35_tt6&amp;_nc_cat=111&amp;ccb=7-5&amp;_nc_sid=18de74&amp;efg=eyJlZmdfdGFnIjoiQ0xJUFMuYmVzdF9pbWFnZV91cmxnZW4uQzMifQ%3D%3D&amp;_nc_ohc=LpESNghy8iEQ7kNvwGvNUpD&amp;_nc_oc=AdkLR8PXE_KLqntEDOfjKXq9HXn7WfApv30koXdDoLDoz_iR-DcIyZciSIf2rEICX3R3eJfC526Vy1u1G-PmXEWn&amp;_nc_zt=23&amp;_nc_ht=scontent.cdninstagram.com&amp;edm=AM6HXa8EAAAA&amp;_nc_gid=DJimxgHMhMOL-amye0hS5w&amp;oh=00_Afnvm0HiTGr2zVLrlJ2MMVRgm-YkKvU9zp-qeSa6wqTLXw&amp;oe=6941B5A5" style="display: none; max-width:70px; max-height:88px;" alt="Media">
                                                </td>
                                                <td>05-12-2025 12:30 PM</td>

                                                <td>Referrals aren’t just numbers - they’re...</td>
                                                <td>
                                                    <span class="badge 
                     bg-primary
                    ">
                                                        VIDEO
                                                    </span>
                                                </td>
                                                <td>❤️ 102</td>
                                                <td>💬 9</td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <a href="https://www.instagram.com/reel/DR4b9FAE8LE/" target="_blank" class="btn btn-soft-primary btn-sm" data-bs-toggle="tooltip" data-bs-original-title="View this post on instagram">
                                                            <i class="ti ti-brand-instagram"></i>
                                                            View Instagram
                                                        </a>
                                                        <a href="http://localhost:8000/instagram/17841435650809281/post/18089576173756254/insights-page" class="btn btn-soft-warning btn-sm" data-bs-toggle="tooltip" data-bs-original-title="View this post data">
                                                            <i class="bx bx-bar-chart"></i> View Insights
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr class="post-row">
                                                <td>6</td>
                                                <td>
                                                    <img src="https://scontent.cdninstagram.com/v/t51.82787-15/588579161_18077970278188361_3379381611468204936_n.jpg?stp=dst-jpg_e35_tt6&amp;_nc_cat=101&amp;ccb=7-5&amp;_nc_sid=18de74&amp;efg=eyJlZmdfdGFnIjoiQ0FST1VTRUxfSVRFTS5iZXN0X2ltYWdlX3VybGdlbi5DMyJ9&amp;_nc_ohc=n1_QLU1c2jYQ7kNvwEOHDqF&amp;_nc_oc=Adk2dnfco6q3JIFQYGagp9xbjkqZ-i4eEj5aCNnWIVZBsUr4pmSn4BpwYd2zSbwC-IhByCLpjrzyjMSNcGi2CXfl&amp;_nc_zt=23&amp;_nc_ht=scontent.cdninstagram.com&amp;edm=AM6HXa8EAAAA&amp;_nc_gid=DJimxgHMhMOL-amye0hS5w&amp;oh=00_AfnGqfjLIcRw2Ni-cAKmhli8Bvu1kI_5JASyqiHmrG43HA&amp;oe=6941A6F3" alt="Media" class="img-fluid img-thumbnail real-image" style="max-width:70px; max-height:88px;">
                                                    <img class="pdf-img img-fluid img-thumbnail" src="https://scontent.cdninstagram.com/v/t51.82787-15/588579161_18077970278188361_3379381611468204936_n.jpg?stp=dst-jpg_e35_tt6&amp;_nc_cat=101&amp;ccb=7-5&amp;_nc_sid=18de74&amp;efg=eyJlZmdfdGFnIjoiQ0FST1VTRUxfSVRFTS5iZXN0X2ltYWdlX3VybGdlbi5DMyJ9&amp;_nc_ohc=n1_QLU1c2jYQ7kNvwEOHDqF&amp;_nc_oc=Adk2dnfco6q3JIFQYGagp9xbjkqZ-i4eEj5aCNnWIVZBsUr4pmSn4BpwYd2zSbwC-IhByCLpjrzyjMSNcGi2CXfl&amp;_nc_zt=23&amp;_nc_ht=scontent.cdninstagram.com&amp;edm=AM6HXa8EAAAA&amp;_nc_gid=DJimxgHMhMOL-amye0hS5w&amp;oh=00_AfnGqfjLIcRw2Ni-cAKmhli8Bvu1kI_5JASyqiHmrG43HA&amp;oe=6941A6F3" style="display: none; max-width:70px; max-height:88px;" alt="Media">
                                                </td>
                                                <td>04-12-2025 02:46 PM</td>

                                                <td>Went live today on Vanita TV to talk abo...</td>
                                                <td>
                                                    <span class="badge 
                     bg-warning
                    ">
                                                        CAROUSEL_ALBUM
                                                    </span>
                                                </td>
                                                <td>❤️ 99</td>
                                                <td>💬 0</td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <a href="https://www.instagram.com/p/DR2GxhtE-XS/" target="_blank" class="btn btn-soft-primary btn-sm" data-bs-toggle="tooltip" data-bs-original-title="View this post on instagram">
                                                            <i class="ti ti-brand-instagram"></i>
                                                            View Instagram
                                                        </a>
                                                        <a href="http://localhost:8000/instagram/17841435650809281/post/18068199029386684/insights-page" class="btn btn-soft-warning btn-sm" data-bs-toggle="tooltip" data-bs-original-title="View this post data">
                                                            <i class="bx bx-bar-chart"></i> View Insights
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr class="post-row">
                                                <td>7</td>
                                                <td>
                                                    <video class="video-section" width="70" height="70" muted="" autoplay="" loop="" playsinline="" style="object-fit:cover; border-radius:6px;">
                                                        <source src="https://instagram.flko9-2.fna.fbcdn.net/o1/v/t2/f2/m86/AQNvL7MZtMje9HZ4f9bFMJp-TaaAI9Aa_-tKW_o1hWBorEKiJItfua9HaGs_y2R2W4YsoJXTqmdcS3IKXQS3Vs5CkDOslr9Avuu10iY.mp4?_nc_cat=109&amp;_nc_oc=AdkY8tTiH4Wad6i3qkx8krjnCIvpqbZXcwjyRY1GMmvZlkbtPJm0XA4JAT3kFtdwQDJWZpfN5VpGrNCBvZH_bgKN&amp;_nc_sid=5e9851&amp;_nc_ht=instagram.flko9-2.fna.fbcdn.net&amp;_nc_ohc=D8RvnUsrMk4Q7kNvwHGoQNT&amp;efg=eyJ2ZW5jb2RlX3RhZyI6Inhwdl9wcm9ncmVzc2l2ZS5JTlNUQUdSQU0uQ0xJUFMuQzMuNzIwLmRhc2hfYmFzZWxpbmVfMV92MSIsInhwdl9hc3NldF9pZCI6MTkxNjE2MzY3NTk3Mjk4NiwiYXNzZXRfYWdlX2RheXMiOjcsInZpX3VzZWNhc2VfaWQiOjEwMDk5LCJkdXJhdGlvbl9zIjozNiwidXJsZ2VuX3NvdXJjZSI6Ind3dyJ9&amp;ccb=17-1&amp;_nc_gid=DJimxgHMhMOL-amye0hS5w&amp;edm=AM6HXa8EAAAA&amp;_nc_zt=28&amp;vs=2931a22d528d3037&amp;_nc_vs=HBksFQIYUmlnX3hwdl9yZWVsc19wZXJtYW5lbnRfc3JfcHJvZC83QTQwMzY0REZFNkIwNjNBMDkzRjQzQUNGRTlGQTg4Nl92aWRlb19kYXNoaW5pdC5tcDQVAALIARIAFQIYOnBhc3N0aHJvdWdoX2V2ZXJzdG9yZS9HQk4zSmlNa3kyRzZuZEFFQUpPOE5faDlqUzQwYnN0VEFRQUYVAgLIARIAKAAYABsCiAd1c2Vfb2lsATEScHJvZ3Jlc3NpdmVfcmVjaXBlATEVAAAm9JXn87Ov5wYVAigCQzMsF0BCAAAAAAAAGBJkYXNoX2Jhc2VsaW5lXzFfdjERAHX-B2XmnQEA&amp;oh=00_Afn-SaaYxb2-_5miRMglPOop01HaGgyzqNarjgQEs6XC8w&amp;oe=693DB56E" type="video/mp4">
                                                    </video>
                                                    <img class="pdf-img img-fluid img-thumbnail" src="https://scontent.cdninstagram.com/v/t51.71878-15/588656789_716554021138239_9132432751198959987_n.jpg?stp=dst-jpg_e35_tt6&amp;_nc_cat=106&amp;ccb=7-5&amp;_nc_sid=18de74&amp;efg=eyJlZmdfdGFnIjoiQ0xJUFMuYmVzdF9pbWFnZV91cmxnZW4uQzMifQ%3D%3D&amp;_nc_ohc=QXTw3Rlhq_4Q7kNvwGiWNSK&amp;_nc_oc=AdmJYuLXr0fUUd8QWkQ0xoDs7_HIVanEMzWCxAUyNZNlEzwm8IaWMfLwzUt4Exg6I5xVqnCoNGxOZioZwc9fszSo&amp;_nc_zt=23&amp;_nc_ht=scontent.cdninstagram.com&amp;edm=AM6HXa8EAAAA&amp;_nc_gid=DJimxgHMhMOL-amye0hS5w&amp;oh=00_Afmq4DwQgmUyzTaKXdEOLTVE_FBm0qUOqzb9KFwwMIpCcQ&amp;oe=6941B50D" style="display: none; max-width:70px; max-height:88px;" alt="Media">
                                                </td>
                                                <td>04-12-2025 12:41 PM</td>

                                                <td>Struggling with a thin lining or repeate...</td>
                                                <td>
                                                    <span class="badge 
                     bg-primary
                    ">
                                                        VIDEO
                                                    </span>
                                                </td>
                                                <td>❤️ 36</td>
                                                <td>💬 2</td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <a href="https://www.instagram.com/reel/DR14cLfExE2/" target="_blank" class="btn btn-soft-primary btn-sm" data-bs-toggle="tooltip" data-bs-original-title="View this post on instagram">
                                                            <i class="ti ti-brand-instagram"></i>
                                                            View Instagram
                                                        </a>
                                                        <a href="http://localhost:8000/instagram/17841435650809281/post/17865045738514911/insights-page" class="btn btn-soft-warning btn-sm" data-bs-toggle="tooltip" data-bs-original-title="View this post data">
                                                            <i class="bx bx-bar-chart"></i> View Insights
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr class="post-row">
                                                <td>8</td>
                                                <td>
                                                    <video class="video-section" width="70" height="70" muted="" autoplay="" loop="" playsinline="" style="object-fit:cover; border-radius:6px;">
                                                        <source src="https://instagram.flko10-1.fna.fbcdn.net/o1/v/t2/f2/m86/AQNhhzXE804fX0IiADCC-dGnEJ-pfNRLx2Mt7-BjB26Ck7ZFXblwvnfXq7upVEYCPCU_Nde9dhW_2ryPXoq4epe08dBnc0Y03tKIgDw.mp4?_nc_cat=106&amp;_nc_oc=Adl3ZbsaUy83oCwfYQzqqNeiw9fx-3j3-mPAzkeItZOuT-fIENifZFdJJ7Z_d5sPY6VF_H_KgiN_3lmwepmJHHJn&amp;_nc_sid=5e9851&amp;_nc_ht=instagram.flko10-1.fna.fbcdn.net&amp;_nc_ohc=Vv0XDLZyCVgQ7kNvwFcwevd&amp;efg=eyJ2ZW5jb2RlX3RhZyI6Inhwdl9wcm9ncmVzc2l2ZS5JTlNUQUdSQU0uQ0xJUFMuQzMuNzIwLmRhc2hfYmFzZWxpbmVfMV92MSIsInhwdl9hc3NldF9pZCI6MTM2NTUxMjEzNTI2NzMzNSwiYXNzZXRfYWdlX2RheXMiOjgsInZpX3VzZWNhc2VfaWQiOjEwMDk5LCJkdXJhdGlvbl9zIjoxNSwidXJsZ2VuX3NvdXJjZSI6Ind3dyJ9&amp;ccb=17-1&amp;vs=dcdc296b69b704ef&amp;_nc_vs=HBksFQIYUmlnX3hwdl9yZWVsc19wZXJtYW5lbnRfc3JfcHJvZC80MTQwNEI5NjBEMzBERjk2OTFCRTI2OTlGRjVCMUU4Ql92aWRlb19kYXNoaW5pdC5tcDQVAALIARIAFQIYOnBhc3N0aHJvdWdoX2V2ZXJzdG9yZS9HRkdiTlNOV2FFWmtYOThHQU9WM0xHQVFzczFkYnN0VEFRQUYVAgLIARIAKAAYABsCiAd1c2Vfb2lsATEScHJvZ3Jlc3NpdmVfcmVjaXBlATEVAAAmjtCSnKL77AQVAigCQzMsF0AuzMzMzMzNGBJkYXNoX2Jhc2VsaW5lXzFfdjERAHX-B2XmnQEA&amp;_nc_gid=DJimxgHMhMOL-amye0hS5w&amp;edm=AM6HXa8EAAAA&amp;_nc_zt=28&amp;oh=00_AfmvCDsstw2dQLFiC29aa-4AqbKN5gYbmQiRiapbV0tnlA&amp;oe=693DB53F" type="video/mp4">
                                                    </video>
                                                    <img class="pdf-img img-fluid img-thumbnail" src="https://scontent.cdninstagram.com/v/t51.82787-15/588360565_18077861510188361_4385289212964902159_n.jpg?stp=dst-jpg_e35_tt6&amp;_nc_cat=111&amp;ccb=7-5&amp;_nc_sid=18de74&amp;efg=eyJlZmdfdGFnIjoiQ0xJUFMuYmVzdF9pbWFnZV91cmxnZW4uQzMifQ%3D%3D&amp;_nc_ohc=3BW_FtBj2csQ7kNvwG0UiJF&amp;_nc_oc=AdkAtLnq4WI9chqO8FD7YA07vLEsJmR3xamjYtQYAYfSIJ6-NTZ09TpMkT1XLF0eIZ4LIWsMkqPeI40cYId9RApu&amp;_nc_zt=23&amp;_nc_ht=scontent.cdninstagram.com&amp;edm=AM6HXa8EAAAA&amp;_nc_gid=DJimxgHMhMOL-amye0hS5w&amp;oh=00_AfmZehWMr-UmvISSi6D3tLFAsW9k37BXnWKRfBwSBjDR-g&amp;oe=6941CF28" style="display: none; max-width:70px; max-height:88px;" alt="Media">
                                                </td>
                                                <td>03-12-2025 12:05 PM</td>

                                                <td>Some foods quietly do more for male fert...</td>
                                                <td>
                                                    <span class="badge 
                     bg-primary
                    ">
                                                        VIDEO
                                                    </span>
                                                </td>
                                                <td>❤️ 45</td>
                                                <td>💬 0</td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <a href="https://www.instagram.com/reel/DRzPkXuE2tT/" target="_blank" class="btn btn-soft-primary btn-sm" data-bs-toggle="tooltip" data-bs-original-title="View this post on instagram">
                                                            <i class="ti ti-brand-instagram"></i>
                                                            View Instagram
                                                        </a>
                                                        <a href="http://localhost:8000/instagram/17841435650809281/post/17875858821446912/insights-page" class="btn btn-soft-warning btn-sm" data-bs-toggle="tooltip" data-bs-original-title="View this post data">
                                                            <i class="bx bx-bar-chart"></i> View Insights
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr class="post-row">
                                                <td>9</td>
                                                <td>
                                                    <img src="https://scontent.cdninstagram.com/v/t51.82787-15/589025231_18077776709188361_1474406041077854148_n.jpg?stp=dst-jpg_e35_tt6&amp;_nc_cat=104&amp;ccb=7-5&amp;_nc_sid=18de74&amp;efg=eyJlZmdfdGFnIjoiQ0FST1VTRUxfSVRFTS5iZXN0X2ltYWdlX3VybGdlbi5DMyJ9&amp;_nc_ohc=n_wYG1h6FY0Q7kNvwG4E7XN&amp;_nc_oc=Adn-QWnGukVObcRVKjNFlheSg4WK6YPZ3B1KeWxNcWiaIzOaD-6TMtI7xyMjM4LnU6KPo8qrhoMK4dWAkmYvLqBe&amp;_nc_zt=23&amp;_nc_ht=scontent.cdninstagram.com&amp;edm=AM6HXa8EAAAA&amp;_nc_gid=DJimxgHMhMOL-amye0hS5w&amp;oh=00_AfljwcNEZh0b9rt7M6d2samkPBRXRzA17LRP8rMeN4Rzzw&amp;oe=6941AB0A" alt="Media" class="img-fluid img-thumbnail real-image" style="max-width:70px; max-height:88px;">
                                                    <img class="pdf-img img-fluid img-thumbnail" src="https://scontent.cdninstagram.com/v/t51.82787-15/589025231_18077776709188361_1474406041077854148_n.jpg?stp=dst-jpg_e35_tt6&amp;_nc_cat=104&amp;ccb=7-5&amp;_nc_sid=18de74&amp;efg=eyJlZmdfdGFnIjoiQ0FST1VTRUxfSVRFTS5iZXN0X2ltYWdlX3VybGdlbi5DMyJ9&amp;_nc_ohc=n_wYG1h6FY0Q7kNvwG4E7XN&amp;_nc_oc=Adn-QWnGukVObcRVKjNFlheSg4WK6YPZ3B1KeWxNcWiaIzOaD-6TMtI7xyMjM4LnU6KPo8qrhoMK4dWAkmYvLqBe&amp;_nc_zt=23&amp;_nc_ht=scontent.cdninstagram.com&amp;edm=AM6HXa8EAAAA&amp;_nc_gid=DJimxgHMhMOL-amye0hS5w&amp;oh=00_AfljwcNEZh0b9rt7M6d2samkPBRXRzA17LRP8rMeN4Rzzw&amp;oe=6941AB0A" style="display: none; max-width:70px; max-height:88px;" alt="Media">
                                                </td>
                                                <td>02-12-2025 12:19 PM</td>

                                                <td>5 Silent Signs Your Fertility Might Be A...</td>
                                                <td>
                                                    <span class="badge 
                     bg-warning
                    ">
                                                        CAROUSEL_ALBUM
                                                    </span>
                                                </td>
                                                <td>❤️ 8</td>
                                                <td>💬 0</td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <a href="https://www.instagram.com/p/DRwsWj_E8DQ/" target="_blank" class="btn btn-soft-primary btn-sm" data-bs-toggle="tooltip" data-bs-original-title="View this post on instagram">
                                                            <i class="ti ti-brand-instagram"></i>
                                                            View Instagram
                                                        </a>
                                                        <a href="http://localhost:8000/instagram/17841435650809281/post/18097123552848076/insights-page" class="btn btn-soft-warning btn-sm" data-bs-toggle="tooltip" data-bs-original-title="View this post data">
                                                            <i class="bx bx-bar-chart"></i> View Insights
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr class="post-row">
                                                <td>10</td>
                                                <td>
                                                    <video class="video-section" width="70" height="70" muted="" autoplay="" loop="" playsinline="" style="object-fit:cover; border-radius:6px;">
                                                        <source src="https://instagram.flko10-2.fna.fbcdn.net/o1/v/t2/f2/m86/AQPsUWWPdix93yp54QHIw2AOR3bQjwa5Ngn4EGukWPDM37_5GkOjcmalAJHfbzLek52n_ZEQpq16_ZXzKhljrr4dP8hm4imgVDoO1Os.mp4?_nc_cat=104&amp;_nc_oc=AdkFN9Vi-CTMSNMEEAtK9ZthCwlB_e7tZ7hKdKkTv4Oo19oqD6nNtxL4ChL4YWZBk9B2znpi0EpfPltnOwLc0C01&amp;_nc_sid=5e9851&amp;_nc_ht=instagram.flko10-2.fna.fbcdn.net&amp;_nc_ohc=083SrMyvwGwQ7kNvwEL_yTo&amp;efg=eyJ2ZW5jb2RlX3RhZyI6Inhwdl9wcm9ncmVzc2l2ZS5JTlNUQUdSQU0uQ0xJUFMuQzMuNzIwLmRhc2hfYmFzZWxpbmVfMV92MSIsInhwdl9hc3NldF9pZCI6MTM4OTcwMjYxNjIwNDgwOCwiYXNzZXRfYWdlX2RheXMiOjEwLCJ2aV91c2VjYXNlX2lkIjoxMDA5OSwiZHVyYXRpb25fcyI6MjEsInVybGdlbl9zb3VyY2UiOiJ3d3cifQ%3D%3D&amp;ccb=17-1&amp;vs=9f18a956f6f1dd9d&amp;_nc_vs=HBksFQIYUmlnX3hwdl9yZWVsc19wZXJtYW5lbnRfc3JfcHJvZC84QTQ5OTlEQzM4NDREQjA1QkU0REU2QzYxNzQ3N0E4OF92aWRlb19kYXNoaW5pdC5tcDQVAALIARIAFQIYOnBhc3N0aHJvdWdoX2V2ZXJzdG9yZS9HRHF1WUNOOTUzaEd1S3NGQURxd2REd0UxeklIYnN0VEFRQUYVAgLIARIAKAAYABsCiAd1c2Vfb2lsATEScHJvZ3Jlc3NpdmVfcmVjaXBlATEVAAAmkJjCrKv79wQVAigCQzMsF0A1XbItDlYEGBJkYXNoX2Jhc2VsaW5lXzFfdjERAHX-B2XmnQEA&amp;_nc_gid=DJimxgHMhMOL-amye0hS5w&amp;edm=AM6HXa8EAAAA&amp;_nc_zt=28&amp;oh=00_AfkwomR9kmf6fQWiFW8IhcNfG-C24ZZ05DYLcgQyv6NUtA&amp;oe=693DB021" type="video/mp4">
                                                    </video>
                                                    <img class="pdf-img img-fluid img-thumbnail" src="https://scontent.cdninstagram.com/v/t51.82787-15/587954441_18077690501188361_4740818568126569302_n.jpg?stp=dst-jpg_e35_tt6&amp;_nc_cat=109&amp;ccb=7-5&amp;_nc_sid=18de74&amp;efg=eyJlZmdfdGFnIjoiQ0xJUFMuYmVzdF9pbWFnZV91cmxnZW4uQzMifQ%3D%3D&amp;_nc_ohc=lnqhyf0XAIgQ7kNvwEJ3csh&amp;_nc_oc=AdnCEljcGvxnlU2EimkFtRBoyNJ35scVUHU9CVnAS6pN22QTrT6l6JF8yOMAxZnEYkDfJAIxUygTFZxacaDABy9B&amp;_nc_zt=23&amp;_nc_ht=scontent.cdninstagram.com&amp;edm=AM6HXa8EAAAA&amp;_nc_gid=DJimxgHMhMOL-amye0hS5w&amp;oh=00_AfnlIGkJxf06AE71c3vqSNIE7dq5vlDcG_3EoRlwwrVlww&amp;oe=6941B045" style="display: none; max-width:70px; max-height:88px;" alt="Media">
                                                </td>
                                                <td>01-12-2025 12:55 PM</td>

                                                <td>Here’s the truth most people never hear...</td>
                                                <td>
                                                    <span class="badge 
                     bg-primary
                    ">
                                                        VIDEO
                                                    </span>
                                                </td>
                                                <td>❤️ 33</td>
                                                <td>💬 2</td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <a href="https://www.instagram.com/reel/DRuLqrFk0sQ/" target="_blank" class="btn btn-soft-primary btn-sm" data-bs-toggle="tooltip" data-bs-original-title="View this post on instagram">
                                                            <i class="ti ti-brand-instagram"></i>
                                                            View Instagram
                                                        </a>
                                                        <a href="http://localhost:8000/instagram/17841435650809281/post/17914132722242092/insights-page" class="btn btn-soft-warning btn-sm" data-bs-toggle="tooltip" data-bs-original-title="View this post data">
                                                            <i class="bx bx-bar-chart"></i> View Insights
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr class="post-row">
                                                <td>11</td>
                                                <td>
                                                    <video class="video-section" width="70" height="70" muted="" autoplay="" loop="" playsinline="" style="object-fit:cover; border-radius:6px;">
                                                        <source src="https://instagram.flko9-2.fna.fbcdn.net/o1/v/t2/f2/m86/AQPTLaLnfOEYG2OkCMP4p_48jxTCwisw9GCqNZ7PTZ32FObbuTr2l-VzN5Wo9aBZ93fCSg9_8vgE_ms-HSDkXwiPeD-yN6lO4Cxtlzk.mp4?_nc_cat=101&amp;_nc_oc=AdkQwwV0K9x7akaaKIcHZXk3qMVa_qQODs4odVF4V4Eroy0GdHeXOthlbwZlIroXVzt7HPWkXkiYs4L5vlppeIgS&amp;_nc_sid=5e9851&amp;_nc_ht=instagram.flko9-2.fna.fbcdn.net&amp;_nc_ohc=7aWwuDCXwUAQ7kNvwHUAAc5&amp;efg=eyJ2ZW5jb2RlX3RhZyI6Inhwdl9wcm9ncmVzc2l2ZS5JTlNUQUdSQU0uQ0xJUFMuQzMuNzIwLmRhc2hfYmFzZWxpbmVfMV92MSIsInhwdl9hc3NldF9pZCI6MTU0MTgxMTc0NzA2MDYwMiwiYXNzZXRfYWdlX2RheXMiOjEyLCJ2aV91c2VjYXNlX2lkIjoxMDA5OSwiZHVyYXRpb25fcyI6MTMsInVybGdlbl9zb3VyY2UiOiJ3d3cifQ%3D%3D&amp;ccb=17-1&amp;_nc_gid=DJimxgHMhMOL-amye0hS5w&amp;edm=AM6HXa8EAAAA&amp;_nc_zt=28&amp;vs=2ffc8accdd196102&amp;_nc_vs=HBksFQIYUmlnX3hwdl9yZWVsc19wZXJtYW5lbnRfc3JfcHJvZC9EQTQ0Njg3Q0MxNTA0RTFBMDk5MjZGOTBDQjM3QURBOF92aWRlb19kYXNoaW5pdC5tcDQVAALIARIAFQIYOnBhc3N0aHJvdWdoX2V2ZXJzdG9yZS9HTWE4SXlNS1M0eDM1V0lGQUg4VDR0UGRzWDBIYnN0VEFRQUYVAgLIARIAKAAYABsCiAd1c2Vfb2lsATEScHJvZ3Jlc3NpdmVfcmVjaXBlATEVAAAm9O3z16CRvQUVAigCQzMsF0Aru2RaHKwIGBJkYXNoX2Jhc2VsaW5lXzFfdjERAHX-B2XmnQEA&amp;oh=00_AfnxGmWtYaZi63oqOGv8kLvOWTuUFcktIW739UmxYLyMIw&amp;oe=693DCBEA" type="video/mp4">
                                                    </video>
                                                    <img class="pdf-img img-fluid img-thumbnail" src="https://scontent.cdninstagram.com/v/t51.82787-15/587785452_18077505656188361_5709704178901334787_n.jpg?stp=dst-jpg_e35_tt6&amp;_nc_cat=109&amp;ccb=7-5&amp;_nc_sid=18de74&amp;efg=eyJlZmdfdGFnIjoiQ0xJUFMuYmVzdF9pbWFnZV91cmxnZW4uQzMifQ%3D%3D&amp;_nc_ohc=K02s3dZYMVMQ7kNvwGwwLrY&amp;_nc_oc=AdnsHPulg-AdhQSBSLWLPfUjQy8rbXIYV_GyTnFeQ2UM8qAu6EsQiG-QQn4fBAq4HeBrxphzrVH7QuBzXVJTO7j3&amp;_nc_zt=23&amp;_nc_ht=scontent.cdninstagram.com&amp;edm=AM6HXa8EAAAA&amp;_nc_gid=DJimxgHMhMOL-amye0hS5w&amp;oh=00_Afmj7Z2dYd_tKUJ76hj1L8zYbX2Y31Jg_eUK6dTnI6DP4g&amp;oe=6941C476" style="display: none; max-width:70px; max-height:88px;" alt="Media">
                                                </td>
                                                <td>29-11-2025 12:05 PM</td>

                                                <td>How does IUI improve motility?
                                                    Because a...</td>
                                                <td>
                                                    <span class="badge 
                     bg-primary
                    ">
                                                        VIDEO
                                                    </span>
                                                </td>
                                                <td>❤️ 31</td>
                                                <td>💬 0</td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <a href="https://www.instagram.com/reel/DRo8Vn1k_Ko/" target="_blank" class="btn btn-soft-primary btn-sm" data-bs-toggle="tooltip" data-bs-original-title="View this post on instagram">
                                                            <i class="ti ti-brand-instagram"></i>
                                                            View Instagram
                                                        </a>
                                                        <a href="http://localhost:8000/instagram/17841435650809281/post/18066081392426807/insights-page" class="btn btn-soft-warning btn-sm" data-bs-toggle="tooltip" data-bs-original-title="View this post data">
                                                            <i class="bx bx-bar-chart"></i> View Insights
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr class="post-row">
                                                <td>12</td>
                                                <td>
                                                    <video class="video-section" width="70" height="70" muted="" autoplay="" loop="" playsinline="" style="object-fit:cover; border-radius:6px;">
                                                        <source src="https://instagram.flko9-1.fna.fbcdn.net/o1/v/t2/f2/m86/AQOoTeWCbXRbL3p8Skp81PNY9b1ZMZos7Z_ZqsMGMRx8Bbd4qNvGbrlDSKgnzKiQtSm_Aenpa2ZQXNJQ7W9HsCkNcGIw30MmLr718MM.mp4?_nc_cat=100&amp;_nc_oc=AdlhoEnc7BNS_c2dPZb_JhlDZ6dOR16uu2FmSVEhuH5rBBcuF2XSxFmX7B_hvmlv-vqbaGnUeLoy-iCnZ0Gv_oBw&amp;_nc_sid=5e9851&amp;_nc_ht=instagram.flko9-1.fna.fbcdn.net&amp;_nc_ohc=zruyuoQ6Y_MQ7kNvwGP4PGN&amp;efg=eyJ2ZW5jb2RlX3RhZyI6Inhwdl9wcm9ncmVzc2l2ZS5JTlNUQUdSQU0uQ0xJUFMuQzMuNzIwLmRhc2hfYmFzZWxpbmVfMV92MSIsInhwdl9hc3NldF9pZCI6Mjk3NTU2OTM1MjYyOTA2MSwiYXNzZXRfYWdlX2RheXMiOjEzLCJ2aV91c2VjYXNlX2lkIjoxMDA5OSwiZHVyYXRpb25fcyI6MTIsInVybGdlbl9zb3VyY2UiOiJ3d3cifQ%3D%3D&amp;ccb=17-1&amp;vs=949b6f399145ddbf&amp;_nc_vs=HBksFQIYUmlnX3hwdl9yZWVsc19wZXJtYW5lbnRfc3JfcHJvZC9EOTQ4N0EzOEU1MDVFODU5NjMyQUU3QkY3NkFCQTJBQ192aWRlb19kYXNoaW5pdC5tcDQVAALIARIAFQIYOnBhc3N0aHJvdWdoX2V2ZXJzdG9yZS9HTXMzRENQU1FwRjVRVk1DQUw2empkMnJzQWN4YnN0VEFRQUYVAgLIARIAKAAYABsCiAd1c2Vfb2lsATEScHJvZ3Jlc3NpdmVfcmVjaXBlATEVAAAmiu2kpPeQyQoVAigCQzMsF0ApIcrAgxJvGBJkYXNoX2Jhc2VsaW5lXzFfdjERAHX-B2XmnQEA&amp;_nc_gid=DJimxgHMhMOL-amye0hS5w&amp;edm=AM6HXa8EAAAA&amp;_nc_zt=28&amp;oh=00_AfnwiQl6YyAychJ4GaHgBjnM-hRHsDloCW0-FcrPELkXhw&amp;oe=693DBE41" type="video/mp4">
                                                    </video>
                                                    <img class="pdf-img img-fluid img-thumbnail" src="https://scontent.cdninstagram.com/v/t51.82787-15/587259843_18077413820188361_4994845767227278358_n.jpg?stp=dst-jpg_e35_tt6&amp;_nc_cat=100&amp;ccb=7-5&amp;_nc_sid=18de74&amp;efg=eyJlZmdfdGFnIjoiQ0xJUFMuYmVzdF9pbWFnZV91cmxnZW4uQzMifQ%3D%3D&amp;_nc_ohc=wxumZ3-b8V4Q7kNvwED6KbU&amp;_nc_oc=Adn78n5jZP1tHjSl1ktE8XpA1561k3iuwstthoDRtXSwuOq7c9HV5-vQui-ZJTEgC8D0DwTe2hrHLiK5f_uWH4cy&amp;_nc_zt=23&amp;_nc_ht=scontent.cdninstagram.com&amp;edm=AM6HXa8EAAAA&amp;_nc_gid=DJimxgHMhMOL-amye0hS5w&amp;oh=00_Aflz67z30wmDmlDMQgILxaKhnfNNY9VoWWyu_0wI46rrow&amp;oe=6941B6F8" style="display: none; max-width:70px; max-height:88px;" alt="Media">
                                                </td>
                                                <td>28-11-2025 11:39 AM</td>

                                                <td>Sometimes the hardest part of fertility...</td>
                                                <td>
                                                    <span class="badge 
                     bg-primary
                    ">
                                                        VIDEO
                                                    </span>
                                                </td>
                                                <td>❤️ 37</td>
                                                <td>💬 3</td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <a href="https://www.instagram.com/reel/DRmYuqTE9vp/" target="_blank" class="btn btn-soft-primary btn-sm" data-bs-toggle="tooltip" data-bs-original-title="View this post on instagram">
                                                            <i class="ti ti-brand-instagram"></i>
                                                            View Instagram
                                                        </a>
                                                        <a href="http://localhost:8000/instagram/17841435650809281/post/18539415454000222/insights-page" class="btn btn-soft-warning btn-sm" data-bs-toggle="tooltip" data-bs-original-title="View this post data">
                                                            <i class="bx bx-bar-chart"></i> View Insights
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <div class="d-flex justify-content-end gap-2 mt-3 pagination">

                                        <a href="http://localhost:8000/instagram/fetchpost/17841435650809281?end_date=2025-12-11&amp;start_date=2025-11-14&amp;page=2" class="btn btn-outline-primary btn-sm page-link">Next →</a>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</body>

</html>