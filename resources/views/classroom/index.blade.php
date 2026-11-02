{{-- Rombel per tahun ajaran: add/edit/delete, salin dari tahun lain, export Excel. --}}
@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="dp-card">
        <div id="divSearch">
            @include('classroom._search-criteria')
        </div>
        <div class="box-body">
            <div id="datagrid"></div>
        </div>
    </div>
</section>
@include('classroom._popup-form')
@include('classroom._copy-form')
@endsection

@section('scripts')
@include('classroom._scripts')
@endsection
