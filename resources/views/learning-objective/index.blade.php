{{-- Tujuan Pembelajaran per mapel, kelas & semester. Deskripsi dipakai di deskripsi capaian rapor. --}}
@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="dp-card">
        <div id="divSearch">
            @include('learning-objective._search-criteria')
        </div>
        <div class="box-body">
            <div id="datagrid"></div>
        </div>
    </div>
</section>
@include('learning-objective._popup-form')
@endsection

@section('scripts')
@include('learning-objective._scripts')
@endsection
