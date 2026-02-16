<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: none;
            width: 100% !important;
        }

        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f8fafc;
            padding-bottom: 40px;
        }

        .main {
            background-color: #ffffff;
            margin: 0 auto;
            width: 100%;
            max-width: 600px;
            border-spacing: 0;
            color: #334155;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            margin-top: 40px;
        }

        .header {
            padding: 32px;
            text-align: center;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        }

        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.025em;
        }

        .content {
            padding: 32px;
            line-height: 1.6;
        }

        .footer {
            text-align: center;
            padding: 32px;
            color: #94a3b8;
            font-size: 14px;
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #4f46e5;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 24px;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 24px 0;
            background-color: #f1f5f9;
            border-radius: 8px;
            overflow: hidden;
        }

        .details-table th,
        .details-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .details-table th {
            font-weight: 600;
            color: #64748b;
            font-size: 13px;
            text-transform: uppercase;
            width: 40%;
        }

        .details-table td {
            color: #1e293b;
            font-size: 15px;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge-success {
            background-color: #dcfce7;
            color: #166534;
        }

        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }

        @media only screen and (max-width: 600px) {
            .main {
                margin-top: 0 !important;
                border-radius: 0 !important;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <table class="main" role="presentation">
            <tr>
                <td class="header">
                    <h1>FlowManager</h1>
                </td>
            </tr>
            <tr>
                <td class="content">
                    @yield('content')
                </td>
            </tr>
        </table>
        <div class="footer">
            <p>&copy; {{ date('Y') }} FlowManager. All rights reserved.</p>
            <p>This is an automated message, please do not reply.</p>
        </div>
    </div>
</body>

</html>