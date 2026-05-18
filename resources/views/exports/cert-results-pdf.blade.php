@extends('exports.pdf-layout')

@section('department', 'SCHOOL OF INFORMATION TECHNOLOGY AND ENGINEERING')

@section('styles')
/* ── Document title polish ── */
.doc-subtitle { font-size: 10.5pt; color: #333333; margin-bottom: 0; }
.doc-period   { font-size: 10.5pt; margin-top: 2px; margin-bottom: 16px; }

/* ── Section heading (shared) ── */
.section-heading {
    font-family: 'Helvetica', Arial, sans-serif;
    font-size: 10.5pt;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #000000;
    text-align: center;
    margin: 6px 0 8px;
    padding-bottom: 4px;
    border-bottom: 1.2px solid #000000;
}

/* ── Summary table (Certification / Passed) ── */
.summary-table {
    width: 100%;
    border-collapse: collapse;
    margin: 10px auto 0;
    font-family: 'Helvetica', Arial, sans-serif;
    font-size: 9.5pt;
    color: #000000;
    table-layout: fixed;
}
.summary-table th,
.summary-table td {
    border: 1px solid #333333;
    padding: 10px 12px;
    line-height: 1.35;
    vertical-align: middle;
    word-wrap: break-word;
}
.summary-table thead th {
    background-color: #C9A227; /* gold */
    color: #000000;
    font-weight: bold;
    font-size: 9pt;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 9px 12px;
    border-top: 1px solid #000000;
    border-bottom: 2.5px solid #006633; /* green */
}
.summary-table td.col-cert { width: 70%; text-align: left; }
.summary-table td.col-num  { width: 30%; text-align: center; }
.summary-table tfoot td {
    font-weight: bold;
    background-color: #f4f4f4;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    border-top: 2px solid #000000;
}

/* ── Passers table ── */
.passers-wrapper { margin-top: 22px; page-break-inside: avoid; }

.passers-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0 auto;
    font-family: 'Helvetica', Arial, sans-serif;
    font-size: 9.5pt;
    color: #000000;
    table-layout: fixed;
}
.passers-table th,
.passers-table td {
    border: 1px solid #333333;
    padding: 10px 12px;
    line-height: 1.4;
    vertical-align: middle;
    word-wrap: break-word;
}
.passers-table thead th {
    background-color: #C9A227; /* gold */
    color: #000000;
    font-weight: bold;
    font-size: 9pt;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 9px 12px;
    border-top: 1px solid #000000;
    border-bottom: 2.5px solid #006633; /* green */
}
.passers-table th.col-no,
.passers-table td.col-no    { width: 12%; text-align: center; }
.passers-table th.col-name,
.passers-table td.col-name  { width: 88%; text-align: left; }

/* Group header row inside passers table */
.passers-table tr.group-row td {
    background-color: #f4f4f4;
    font-weight: bold;
    font-size: 9.5pt;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    text-align: left;
    padding: 10px 12px;
    border-top: 1.5px solid #006633;
    color: #000000;
}

/* ── Footer note ── */
.generated-note {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 8.5pt;
    color: #555555;
    font-style: italic;
    text-align: center;
    margin-top: 26px;
}
@endsection

@section('content')
<div style="height: 8px;"></div>
<div class="doc-title">IT Certification Results</div>
<div class="doc-subtitle">{{ $certName }}</div>
<div class="doc-period"><u>{{ strtoupper($batchLabel) }}</u></div>

<div class="section-heading">Certification Summary</div>
<table class="summary-table">
    <thead>
        <tr>
            <th class="col-cert" style="text-align:left;">Certification</th>
            <th class="col-num">Passers</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="col-cert">{{ $certName }}</td>
            <td class="col-num">{{ $passedCount }}</td>
        </tr>
    </tbody>
    <tfoot>
        <tr>
            <td class="col-cert">Total</td>
            <td class="col-num">{{ $passedCount }}</td>
        </tr>
    </tfoot>
</table>

@if(!empty($passerNames))
<div class="passers-wrapper">
    <div class="section-heading">List of Passers</div>
    <table class="passers-table">
        <thead>
            <tr>
                <th class="col-no">No.</th>
                <th class="col-name">Name of Passer</th>
            </tr>
        </thead>
        <tbody>
            <tr class="group-row">
                <td colspan="2">{{ $certName }}</td>
            </tr>
            @foreach($passerNames as $i => $name)
            <tr>
                <td class="col-no">{{ $i + 1 }}</td>
                <td class="col-name">{{ $name }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="generated-note">
    Generated: {{ now()->format('F d, Y h:i A') }}&nbsp;&nbsp;|&nbsp;&nbsp;Recorded by: {{ $recorderName }}
</div>
@endsection
