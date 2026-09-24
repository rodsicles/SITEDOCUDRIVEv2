<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>Teacher's Load</title>
<style>
@page { margin: 14mm 10mm 16mm; }
body { font-family: 'DejaVu Serif', serif; color: #111; font-size: 10pt; line-height: 1.3; }
.form-number { border: 1px solid #222; padding: 3px 7px; font-size: 8pt; display: inline-block; }
.masthead { width: 100%; margin-top: 5mm; border-bottom: 1px solid #666; padding-bottom: 7mm; }
.masthead td { border: 0; vertical-align: middle; }
.logo-cell { width: 24mm; text-align: right; }
.logo { width: 17mm; height: 17mm; }
.school { font-size: 18pt; font-weight: bold; text-align: center; }
.address { font-size: 11pt; font-weight: normal; }
.office { text-align: center; font-size: 13pt; margin: 4mm 0; font-weight: bold; }
h1 { font-size: 12pt; text-align: center; margin: 4mm 0 1mm; }
.term { text-align: center; margin-bottom: 8mm; }
.details { margin-bottom: 5mm; border-collapse: collapse; }
.details td { padding: 1mm 3mm 1mm 0; }
.grid { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 7pt; }
.grid th,.grid td { border: .6pt solid #444; padding: 4px 3px; vertical-align: middle; word-wrap: break-word; }
.grid th { background: #f1f1f1; text-align: center; }
.grid td { text-align: center; }
.grid .title { text-align: left; }
thead { display: table-header-group; }
tr { page-break-inside: avoid; }
.meeting + .meeting { margin-top: 5px; }
.totals { margin-top: 4mm; page-break-inside: avoid; }
.signatures { page-break-inside: avoid; margin-top: 12mm; }
.signature { margin: 5mm 0 12mm; }
.line { width: 72mm; border-bottom: .6pt solid #222; height: 13mm; }
.draft { color: #666; font-size: 8pt; text-align: center; margin-top: 2mm; }
</style></head><body>
<div class="form-number">Reg Form – 063</div>
<table class="masthead"><tr><td class="logo-cell"><img class="logo" src="{{ $logo }}" alt="SPUP logo"></td><td class="school">St. Paul University Philippines<div class="address">Tuguegarao City, Cagayan 3500</div></td></tr></table>
<div class="office">OFFICE OF THE REGISTRAR</div>
<h1>TEACHER’S LOAD</h1>
<div class="term">{{ match($load->semester) { '1st' => 'First Semester', '2nd' => 'Second Semester', default => 'Summer' } }}, AY {{ $load->academic_year }}
@if($load->status !== 'finalized')<div class="draft">DRAFT — For review</div>@endif</div>
<table class="details">
<tr><td>Name of Faculty:</td><td><strong>{{ $load->faculty_name }}</strong></td></tr>
<tr><td>Status:</td><td>{{ $load->employment_status }}</td></tr>
<tr><td>Department:</td><td>SITE</td></tr>
<tr><td>Program:</td><td>{{ $load->program }}</td></tr>
</table>
@php
    $dayGroups = ['M/TH' => ['Mon', 'Thu'], 'W' => ['Wed'], 'T/F' => ['Tue', 'Fri'], 'S' => ['Sat']];
    if ($load->items->contains(fn ($item) => collect($item->schedules)->contains(fn ($meeting) => in_array('Sun', $meeting['days'])))) $dayGroups['SUN'] = ['Sun'];
    $number = fn ($value) => rtrim(rtrim(number_format((float) $value, 3, '.', ''), '0'), '.');
    $time = fn ($value) => \Carbon\Carbon::createFromFormat('H:i', $value)->format('g:i A');
@endphp
<table class="grid">
<colgroup><col style="width:8%"><col style="width:20%"><col style="width:8%"><col style="width:4.5%"><col style="width:4.5%"><col style="width:7%"><col style="width:5%">@foreach($dayGroups as $days)<col style="width:{{ 34/count($dayGroups) }}%">@endforeach<col style="width:9%"></colgroup>
<thead><tr><th rowspan="2">Course<br>Code</th><th rowspan="2">Course Title</th><th rowspan="2">Section</th><th colspan="2">Units</th><th rowspan="2">Load<br>Equivalent</th><th rowspan="2">Class<br>Size</th><th colspan="{{ count($dayGroups) }}">SCHEDULE</th><th rowspan="2">Room</th></tr>
<tr><th>Lec</th><th>Lab</th>@foreach($dayGroups as $label => $days)<th>{{ $label }}</th>@endforeach</tr></thead>
<tbody>@foreach($load->items as $item)<tr>
<td>{{ $item->course_code ?: '—' }}</td><td class="title">{{ $item->title }}</td><td>{{ $item->section ?: '—' }}</td>
<td>{{ $item->kind === 'course' ? $number($item->lecture_units) : '—' }}</td><td>{{ $item->kind === 'course' ? $number($item->lab_units) : '—' }}</td>
<td>{{ $number($item->load_equivalent) }}</td><td>{{ $item->class_size ?: '—' }}</td>
@foreach($dayGroups as $days)<td>
@foreach($item->schedules ?? [] as $meeting)
@php $matching = array_values(array_intersect($days, $meeting['days'])); @endphp
@if($matching)<div class="meeting">@if(count($matching) !== count($days)){{ implode('/', $matching) }}<br>@endif{{ $time($meeting['start']) }}–{{ $time($meeting['end']) }}<br>{{ $meeting['mode'] }} · {{ $meeting['room'] }}</div>@endif
@endforeach</td>@endforeach
<td>{{ collect($item->schedules)->pluck('room')->unique()->implode(' / ') ?: '—' }}</td>
</tr>@endforeach</tbody></table>
<div class="totals">Total Load Equivalent: &nbsp; <strong>{{ $number($load->total_load) }}</strong><br>Total Teaching Units: &nbsp; <strong>{{ $number($load->total_units) }}</strong></div>
<div class="signatures"><div class="signature">ENDORSED BY:<div class="line"></div>University Registrar</div><div class="signature">APPROVED BY:<div class="line"></div>Vice President for Academics</div></div>
</body></html>
