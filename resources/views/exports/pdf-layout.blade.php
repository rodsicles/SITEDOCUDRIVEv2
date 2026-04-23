<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        /* ── Page setup ── */
        @page {
            size: letter portrait;
            margin: 70mm 25mm 48mm 25mm;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            color: #000000;
        }

        /* ─────────────────────────────────────────
           FIXED HEADER  — top:0/left:0 = page edge (dompdf)
        ───────────────────────────────────────── */
        #pdf-header {
            position: fixed;
            top:  0;
            left: 0;
            right: 0;
            background-color: #ffffff;
            padding: 8mm 25mm 0;
        }

        .lh-table {
            border-collapse: collapse;
            margin: 0 auto;
        }

        .lh-seal-cell {
            width: 65px;
            padding-right: 8px;
            vertical-align: middle;
        }

        .lh-seal {
            width: 55px;
            height: 55px;
            display: block;
        }

        .lh-text {
            vertical-align: middle;
            text-align: center;
            line-height: 1.25;
        }

        .lh-name {
            font-family: 'Times New Roman', Times, serif;
            font-size: 17pt;
            font-weight: bold;
            line-height: 1.05;
            margin-bottom: 1px;
        }

        .lh-addr-line {
            font-size: 8.5pt;
            line-height: 1.25;
        }

        .lh-url {
            color: #0000CC;
            font-size: 8.5pt;
            line-height: 1.25;
            font-weight: bold;
        }

        /* Thin double separator: gold then green (2px each, no gap) */
        .bar-gold {
            height: 3px;
            background-color: #C9A227;
            font-size: 0;
            line-height: 0;
            margin-top: 3mm;
        }

        .bar-green {
            height: 3px;
            background-color: #006633;
            font-size: 0;
            line-height: 0;
        }

        /* Department label */
        .dept-label {
            text-align: center;
            font-weight: bold;
            font-size: 9pt;
            letter-spacing: 0.2px;
            padding: 6px 0 10px;
        }

        /* ─────────────────────────────────────────
           FIXED FOOTER  — bottom:0 = page edge (dompdf)
        ───────────────────────────────────────── */
        #pdf-footer {
            position: fixed;
            bottom: 0;
            left:   0;
            right:  0;
            background-color: #ffffff;
            padding: 0 25mm 7mm;
        }

        .footer-badges {
            display: block;
            width: 100%;
            height: auto;
            margin-top: 3px;
        }

        /* ─────────────────────────────────────────
           DOCUMENT TITLE BLOCK
        ───────────────────────────────────────── */
        .doc-title {
            font-family: 'Times New Roman', Times, serif;
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            margin-top: 14px;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }

        .doc-subtitle {
            text-align: center;
            font-size: 11pt;
            margin-bottom: 2px;
        }

        .doc-period {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 14px;
        }

        /* ── Clean data table (matches official template) ── */
        .pdf-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin: 10px auto 0;
            line-height: 1.3;
        }

        .pdf-table th {
            border: 1px solid #000000;
            padding: 14px 10px;
            font-weight: bold;
            font-size: 8.5pt;
            text-align: center;
            vertical-align: middle;
            background-color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            line-height: 1.25;
        }

        .pdf-table td {
            border: 1px solid #000000;
            padding: 9px 10px;
            vertical-align: middle;
            background-color: #ffffff;
            font-size: 9pt;
        }

        .pdf-table tbody tr:last-child td {
            font-weight: bold;
        }

        /* ── Misc utilities ── */
        .text-center { text-align: center; }
        .text-right  { text-align: right; }
        .font-bold   { font-weight: bold; }
        .mt-8  { margin-top: 8px; }
        .mt-12 { margin-top: 12px; }

        @yield('styles')
    </style>
</head>
<body>

@php
    $sealB64   = base64_encode(file_get_contents(public_path('images/SPUP-final-logo.png')));
    $footerB64 = base64_encode(file_get_contents(public_path('images/spup-footer-badges.jpg')));
@endphp

{{-- ══════════════ FIXED HEADER ══════════════ --}}
<div id="pdf-header">
    <table class="lh-table" cellspacing="0" cellpadding="0">
        <tr>
            <td class="lh-seal-cell">
                <img src="data:image/png;base64,{{ $sealB64 }}" class="lh-seal" alt="SPUP Seal">
            </td>
            <td class="lh-text">
                <div class="lh-name">St. Paul University Philippines</div>
                <div class="lh-addr-line">Tuguegarao City, Cagayan 3500</div>
                <div class="lh-addr-line">Tel: 396-1987-1994</div>
                <div class="lh-addr-line">Fax: 078-8464305</div>
                <div class="lh-url">www.spup.edu.ph</div>
            </td>
        </tr>
    </table>
    <div class="bar-gold"></div>
    <div class="bar-green"></div>
    <div class="dept-label">@yield('department', 'STUDENT AFFAIRS AND ACADEMIC SUPPORT SERVICES')</div>
</div>{{-- /#pdf-header --}}


{{-- ══════════════ FIXED FOOTER ══════════════ --}}
<div id="pdf-footer">
    <div class="bar-gold"></div>
    <div class="bar-green"></div>
    <img src="data:image/jpeg;base64,{{ $footerB64 }}" class="footer-badges" alt="Accreditation Logos">
</div>{{-- /#pdf-footer --}}


{{-- ══════════════ MAIN CONTENT ══════════════ --}}
<div style="padding: 14px 6mm 0;">
    @yield('content')
</div>

</body>
</html>
