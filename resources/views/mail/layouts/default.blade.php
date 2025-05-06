<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

    {{-- Email clientlər üçün meta taglar --}}
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">

    <title>{{ config('app.name') }}</title>

    <style>
        /* Email client-lər üçün base CSS */
        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            -webkit-font-smoothing: antialiased;
            font-size: 16px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
        }

        /* Konteyner */
        .email-wrapper {
            background-color: #ffffff;
            margin: 0 auto;
            max-width: 600px;
            width: 100%;
        }

        .email-content {
            box-sizing: border-box;
            padding: 24px;
        }

        /* Responsivlik */
        @media only screen and (max-width: 620px) {
            .email-wrapper {
                width: 100% !important;
            }

            .email-content {
                padding: 16px !important;
            }
        }

        /* Base stilləri */
        img {
            border: none;
            -ms-interpolation-mode: bicubic;
            max-width: 100%;
            height: auto;
        }

        table {
            border-collapse: separate;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
            width: 100%;
        }

        table td {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            font-size: 16px;
            vertical-align: top;
        }

        /* Sistem tərəfindən əlavə edilən stillər */
        {!! $template->design['styles'] ?? '' !!}
    </style>
</head>
<body>
<table role="presentation" border="0" cellpadding="0" cellspacing="0" class="body">
    <tr>
        <td>&nbsp;</td>
        <td class="email-wrapper">
            <div class="email-content">
                {{-- Template kontenti bura yerləşdiriləcək --}}
                {!! $content !!}

                {{-- Footer --}}
                @if(config('mail.add_footer', true))
                    <div class="footer" style="margin-top: 24px; text-align: center; color: #6c757d; font-size: 14px;">
                        <p>
                            © {{ date('Y') }} {{ config('app.name') }}.
                            @lang('mail.all_rights_reserved')
                        </p>

                        {{-- Unsubscribe linki --}}
                        @if(isset($unsubscribeUrl))
                            <p>
                                <a href="{{ $unsubscribeUrl }}" style="color: #6c757d; text-decoration: underline;">
                                    @lang('mail.unsubscribe')
                                </a>
                            </p>
                        @endif
                    </div>
                @endif
            </div>
        </td>
        <td>&nbsp;</td>
    </tr>
</table>

{{-- Tracking pixel --}}
@if(isset($trackingPixel))
    <img src="{{ $trackingPixel }}" alt="" width="1" height="1" style="display:none">
@endif
</body>
</html>
