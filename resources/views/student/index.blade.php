{{-- Data induk siswa: add/edit/delete, foto, upload & export Excel. Status diubah lewat menu Mutasi & Kelulusan. --}}
@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="dp-card">
        <div id="divSearch">
            @include('student._search-criteria')
        </div>
        <div class="box-body">
            <div id="datagrid"></div>
        </div>
    </div>
</section>
@include('student._popup-form')
@if ($upload)
    @include('partials.excel-upload', ['title' => 'Data Siswa', 'baseUrl' => url('/Student'), 'hint' => 'Siswa dicocokkan berdasarkan NIS; data yang sudah ada diperbarui.'])
@endif
@endsection

@section('scripts')
@include('student._scripts')
@stack('excel-upload-scripts')
@endsection
