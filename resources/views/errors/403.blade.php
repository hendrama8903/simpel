@extends('layouts.admin')

@section('content')
<section class="content">
    <div class="error-page">
        <h2 class="headline text-yellow">403</h2>
        <div class="error-content">
            <h3><i class="fa fa-lock text-yellow"></i> Akses Ditolak</h3>
            <p>Anda tidak memiliki akses ke halaman ini. Hubungi Administrator bila seharusnya Anda bisa membuka menu ini.</p>
            <p>
                <a href="{{ url('/') }}">Kembali ke Dashboard</a>
            </p>
        </div>
    </div>
</section>
@endsection
