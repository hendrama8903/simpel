{{--
    Kop sekolah untuk PDF (dompdf). Parameter: $school (App\Models\School).
    Logo disisipkan sebagai data URI supaya dompdf tidak perlu mengakses URL.
--}}
@php
    $logoFile = \App\Repositories\SchoolRepo::logoFile();
    $logoSrc = $logoFile === null ? null : 'data:' . mime_content_type($logoFile) . ';base64,' . base64_encode(file_get_contents($logoFile));
    $addressLine = collect([$school->address, $school->village, $school->district, $school->city, $school->province, $school->postal_code])->filter()->implode(', ');
@endphp
<table class="kop">
    <tr>
        <td class="kop-logo">@if ($logoSrc)<img src="{{ $logoSrc }}">@endif</td>
        <td class="kop-text">
            <div class="kop-name">{{ mb_strtoupper($school->school_name) }}</div>
            @if ($school->npsn)<div>NPSN: {{ $school->npsn }}</div>@endif
            @if ($addressLine)<div>{{ $addressLine }}</div>@endif
            @if ($school->phone || $school->email)<div>{{ collect([$school->phone ? 'Telp. ' . $school->phone : null, $school->email])->filter()->implode(' · ') }}</div>@endif
        </td>
        <td class="kop-logo"></td>
    </tr>
</table>
