<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>Teacher's Load</title>
<style>
@page { margin: 14mm 10mm 16mm; }
body { font-family: 'DejaVu Serif', serif; color: #111; font-size: 10pt; line-height: 1.3; }
.form-number { border: 1px solid #222; padding: 3px 7px; font-size: 8pt; display: inline-block; }
.masthead { width: 100%; margin-top: 5mm; border-bottom: 1px solid #666; padding-bottom: 7mm; }
.masthead td { border: 0; vertical-align: middle; }
.brand { text-align: center; }
.brand-inner { margin: 0 auto; border-collapse: collapse; }
.logo-cell { width: 18mm; padding: 0 2.5mm 0 0; text-align: right; }
.logo { width: 17mm; height: 17mm; display: block; }
.school { font-size: 18pt; font-weight: bold; text-align: left; line-height: 1.2; padding: 0; }
.address { font-size: 11pt; font-weight: normal; text-align: left; }
.office { text-align: center; font-size: 13pt; margin: 4mm 0; font-weight: bold; }
h1 { font-size: 12pt; text-align: center; margin: 4mm 0 1mm; }
.term { text-align: center; margin-bottom: 8mm; }
.details { margin-bottom: 5mm; border-collapse: collapse; }
.details td { padding: 1mm 3mm 1mm 0; }
.grid { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 7pt; line-height: 1.15; }
.grid th,.grid td { border: .6pt solid #444; padding: 2px 2px; vertical-align: middle; word-wrap: break-word; }
.grid th { background: #f1f1f1; text-align: center; font-size: 6.5pt; }
.grid td { text-align: center; }
.grid .title { text-align: left; padding-left: 3px; }
thead { display: table-header-group; }
tr { page-break-inside: avoid; }
.meeting { font-size: 6.5pt; }
.meeting + .meeting { margin-top: 3px; padding-top: 2px; border-top: .4pt dotted #999; }
.meeting .mode { color: #444; }
.totals { margin-top: 3mm; page-break-inside: avoid; }
.signatures { page-break-inside: avoid; margin-top: 8mm; }
.signature { margin: 0 0 7mm; }
.sig-name { width: 72mm; margin-top: 8mm; min-height: 4mm; text-align: center; font-weight: bold; }
.sig-rule { width: 72mm; border-bottom: .6pt solid #222; }
.sig-title { width: 72mm; }
.draft { color: #666; font-size: 8pt; text-align: center; margin-top: 2mm; }
</style></head><body>
<div class="form-number">Reg Form – 063</div>
<table class="masthead"><tr><td class="brand"><table class="brand-inner"><tr>
<td class="logo-cell"><img class="logo" src="{{ $logo }}" alt="SPUP logo"></td>
<td class="school">St. Paul University Philippines<div class="address">Tuguegarao City, Cagayan 3500</div></td>
</tr></table></td></tr></table>
<div class="office">OFFICE OF THE REGISTRAR</div>
<h1>TEACHER’S LOAD</h1>
<div class="term">{{ match($load->semester) { '1st' => 'First Semester', '2nd' => 'Second Semester', default => 'Summer' } }}, AY {{ $load->academic_year }}
@if($load->status !== 'finalized')<div class="draft">{{ $load->status === 'void' ? 'VOID — '.$load->void_reason : 'DRAFT — For review' }}</div>@endif
<div>Revision {{ $load->revision_number ?? 1 }}</div></div>
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
    // Compact range: "8:46–11:45 AM" when both ends share AM/PM, otherwise "11:00 AM–1:00 PM".
    $range = function ($start, $end) {
        [$from, $to] = [\Carbon\Carbon::createFromFormat('H:i', $start), \Carbon\Carbon::createFromFormat('H:i', $end)];
        return $from->format('A') === $to->format('A')
            ? $from->format('g:i').'–'.$to->format('g:i').'&nbsp;'.$to->format('A')
            : $from->format('g:i').'&nbsp;'.$from->format('A').'–'.$to->format('g:i').'&nbsp;'.$to->format('A');
    };
    $signature = fn ($name, $title, $default) => ['name' => trim((string) $name), 'title' => trim((string) $title) ?: $default];
    $endorsed = $signature($load->endorsed_by_name, $load->endorsed_by_title, \App\Services\TeacherLoadService::DEFAULT_ENDORSED_TITLE);
    $approved = $signature($load->approved_by_name, $load->approved_by_title, \App\Services\TeacherLoadService::DEFAULT_APPROVED_TITLE);
@endphp
<table class="grid">
<colgroup><col style="width:7%"><col style="width:16%"><col style="width:7%"><col style="width:4%"><col style="width:4%"><col style="width:6%"><col style="width:5%">@foreach($dayGroups as $days)<col style="width:{{ 43/count($dayGroups) }}%">@endforeach<col style="width:8%"></colgroup>
<thead><tr><th rowspan="2">Course<br>Code</th><th rowspan="2">Course Title</th><th rowspan="2">Section</th><th colspan="2">Units</th><th rowspan="2">Load<br>Eq.</th><th rowspan="2">Class<br>Size</th><th colspan="{{ count($dayGroups) }}">SCHEDULE</th><th rowspan="2">Room</th></tr>
<tr><th>Lec</th><th>Lab</th>@foreach($dayGroups as $label => $days)<th>{{ $label }}</th>@endforeach</tr></thead>
<tbody>@foreach($load->items as $item)<tr>
<td>{{ $item->course_code ?: '—' }}</td><td class="title">{{ $item->title }}</td><td>{{ $item->section ?: '—' }}</td>
<td>{{ $item->kind === 'course' ? $number($item->lecture_units) : '—' }}</td><td>{{ $item->kind === 'course' ? $number($item->lab_units) : '—' }}</td>
<td>{{ $number($item->load_equivalent) }}</td><td>{{ $item->class_size ?: '—' }}</td>
@foreach($dayGroups as $days)<td>
@foreach($item->schedules ?? [] as $meeting)
@php $matching = array_values(array_intersect($days, $meeting['days'])); @endphp
@if($matching)<div class="meeting">@if(count($matching) !== count($days)){{ implode('/', $matching) }}<br>@endif{!! $range($meeting['start'], $meeting['end']) !!}<br><span class="mode">{{ $meeting['mode'] }}</span></div>@endif
@endforeach</td>@endforeach
<td>{{ collect($item->schedules)->pluck('room')->unique()->implode(' / ') ?: '—' }}</td>
</tr>@endforeach</tbody></table>
<div class="totals">Total Load Equivalent: &nbsp; <strong>{{ $number($load->total_load) }}</strong><br>Total Teaching Units: &nbsp; <strong>{{ $number($load->total_units) }}</strong></div>
<div class="signatures">
@foreach(['ENDORSED BY:' => $endorsed, 'APPROVED BY:' => $approved] as $heading => $signer)
<div class="signature">{{ $heading }}<div class="sig-name">{{ $signer['name'] !== '' ? $signer['name'] : "\u{00A0}" }}</div><div class="sig-rule"></div><div class="sig-title">{{ $signer['title'] }}</div></div>
@endforeach
</div>
</body></html>
