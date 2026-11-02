{{-- Kegiatan ujian: add/edit/delete + jadwal ujian per tingkat (tombol Jadwal). --}}
@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="dp-card">
        <div id="divSearch">
            @include('exam._search-criteria')
        </div>
        <div class="box-body">
            <div id="datagrid"></div>
        </div>
    </div>
</section>
@include('exam._popup-form')
@include('exam._schedule-form')
@endsection

@section('scripts')
@include('exam._scripts')
@endsection
