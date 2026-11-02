{{-- Mutasi keluar & kelulusan. Menyimpan = status siswa berubah; menghapus = status siswa kembali Aktif. --}}
@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="dp-card">
        <div id="divSearch">
            @include('student-exit._search-criteria')
        </div>
        <div class="box-body">
            <div id="datagrid"></div>
        </div>
    </div>
</section>
@include('student-exit._popup-form')
@include('student-exit._graduate-form')
@endsection

@section('scripts')
@include('student-exit._scripts')
@endsection
