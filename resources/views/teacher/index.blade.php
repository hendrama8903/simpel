{{-- Guru & tendik: add/edit/delete, upload & export Excel, buat akun login. --}}
@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="dp-card">
        <div id="divSearch">
            @include('teacher._search-criteria')
        </div>
        <div class="box-body">
            <div id="datagrid"></div>
        </div>
    </div>
</section>
@include('teacher._popup-form')
@if ($upload)
    @include('partials.excel-upload', ['title' => 'Data Guru', 'baseUrl' => url('/Teacher'), 'hint' => 'Guru dicocokkan berdasarkan NIP lalu NUPTK; data yang sudah ada diperbarui.'])
@endif
@endsection

@section('scripts')
@include('teacher._scripts')
@stack('excel-upload-scripts')
@endsection
