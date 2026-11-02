<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ config('app.name') }} | Login</title>
<link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
<link href="{{ asset('css/fonts.css') }}" rel="stylesheet">
@php
  // judul panel kiri: kepanjangan nama aplikasi dibagi dua baris (baris kedua berwarna)
  $descWords = explode(' ', config('app.description'));
  $descHalf = (int) ceil(count($descWords) / 2);
  $titleTop = implode(' ', array_slice($descWords, 0, $descHalf));
  $titleBottom = implode(' ', array_slice($descWords, $descHalf));
@endphp
<style>
  :root{
    --blue-900:#0A2A8A;
    --blue-800:#0E3FA5;
    --blue-700:#1455D9;
    --blue-500:#2F7BF5;
    --sky:#22C4F0;
    --ink:#0B1B3A;
    --muted:#7C8AA5;
    --field:#F3F6FC;
    --line:#E3E9F4;
  }
  *{box-sizing:border-box; margin:0; padding:0;}
  html, body{height:100%; overflow-x:hidden;}
  body{
    font-family:'Plus Jakarta Sans','Inter',sans-serif;
    color:var(--ink);
    min-height:100vh;
    background:#F2F6FD;
    overflow-x:hidden;
  }
  .login{display:flex; min-height:100vh;}

  /* ---------- Panel kiri ---------- */
  .hero{
    position:relative; flex:0 0 52%; overflow:hidden; color:#fff;
    background:linear-gradient(165deg, #1260E6 0%, #0D47C4 45%, var(--blue-900) 100%);
    padding:70px 190px 48px 7.5%;
    display:flex; flex-direction:column;
  }
  /* foto meja kerja, menyatu ke biru di bagian atas */
  .hero-photo{
    position:absolute; left:0; right:0; bottom:0; height:68%; z-index:0;
    background:url('{{ asset('images/login-bg.jpg') }}') center 35% / cover no-repeat;
    filter:saturate(.85) blur(.6px);
    -webkit-mask-image:linear-gradient(180deg, transparent 0%, #000 38%);
            mask-image:linear-gradient(180deg, transparent 0%, #000 38%);
  }
  .hero-photo::after{
    content:""; position:absolute; inset:0;
    background:linear-gradient(90deg, rgba(10,50,170,.78) 0%, rgba(14,71,196,.42) 55%, rgba(20,85,217,.2) 100%);
  }
  /* tepi kanan melengkung ke panel putih */
  .hero-curve{position:absolute; top:0; right:-1px; height:100%; width:260px; z-index:3;}
  .hero > *:not(.hero-curve):not(.hero-photo){position:relative; z-index:2;}

  .hero-logo{width:200px; height:auto; filter:drop-shadow(0 12px 24px rgba(4,20,80,.35));}
  .hero-title{margin-top:30px; font-size:42px; font-weight:800; line-height:1.12; letter-spacing:-.2px;}
  .hero-title span{display:block; color:var(--sky);}
  .hero-sub{margin-top:10px; font-size:24px; font-weight:500; opacity:.95;}
  .hero-bar{width:72px; height:3px; border-radius:2px; background:var(--sky); margin-top:20px;}

  .features{margin-top:34px; display:flex; gap:36px;}
  .feature{width:120px;}
  .feature-ic{
    width:76px; height:76px; border-radius:16px;
    display:flex; align-items:center; justify-content:center;
    background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.28);
    backdrop-filter:blur(4px);
  }
  .feature-ic svg{width:32px; height:32px;}
  .feature span{display:block; margin-top:12px; font-size:17px; font-weight:500; line-height:1.25;}

  .hero-foot{margin-top:auto; padding-top:32px;}
  .hero-foot .hero-bar{margin:0 0 18px;}
  .hero-foot p{font-size:17px; font-style:italic; line-height:1.45; white-space:pre-line;}

  /* ---------- Panel kanan ---------- */
  .side{
    position:relative; flex:1 1 48%; overflow:hidden;
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    padding:80px 48px 28px;
    background:
      radial-gradient(ellipse at 100% 100%, rgba(207,226,255,.75) 0, transparent 40%),
      radial-gradient(circle at 95% 5%, rgba(226,236,252,.9) 0, transparent 30%),
      #F4F7FD;
  }

  .card{
    width:100%; max-width:630px; background:#fff; border-radius:24px;
    padding:52px 48px 46px;
    box-shadow:0 30px 70px -24px rgba(11,27,58,.18), 0 2px 8px rgba(11,27,58,.03);
    animation:fadeUp .5s cubic-bezier(.2,.7,.3,1) both;
  }
  @keyframes fadeUp{from{opacity:0; transform:translateY(12px);} to{opacity:1; transform:none;}}
  .card-brand{text-align:center;}
  .card-brand img{width:150px; height:auto;}
  .welcome{margin-top:14px; font-size:34px; font-weight:800; letter-spacing:-.2px;}
  .welcome-sub{color:var(--muted); font-size:17px; margin-top:6px; margin-bottom:34px;}

  label.fl{display:block; font-size:17px; font-weight:600; margin-bottom:10px;}
  .field{margin-bottom:24px;}
  .input{position:relative;}
  .input svg.lead{position:absolute; left:22px; top:50%; transform:translateY(-50%); width:22px; height:22px; color:var(--blue-700);}
  .input input{
    width:100%; height:64px; padding:0 58px 0 72px; border-radius:14px;
    border:1.5px solid var(--line); background:var(--field);
    font:500 17px 'Plus Jakarta Sans',sans-serif; color:var(--ink); outline:none;
    transition:border-color .15s, box-shadow .15s, background .15s;
  }
  .input input::placeholder{color:#97A1B3; font-weight:400;}
  .input input:focus{background:#fff; border-color:var(--blue-700); box-shadow:0 0 0 4px rgba(20,85,217,.12);}
  .eye{position:absolute; right:18px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#4B5568; padding:4px;}
  .eye svg{width:22px; height:22px; display:block;}
  .eye:hover{color:var(--blue-700);}

  .keep{display:flex; align-items:center; gap:10px; font-size:14.5px; color:#334155; cursor:pointer; margin:-4px 0 26px; user-select:none;}
  .keep input{appearance:none; width:20px; height:20px; border:1.5px solid #B8C2D3; border-radius:5px; background:#fff; cursor:pointer; position:relative; flex:none;}
  .keep input:checked{background:var(--blue-700); border-color:var(--blue-700);}
  .keep input:checked::after{content:""; position:absolute; left:6px; top:2px; width:5px; height:10px; border:solid #fff; border-width:0 2px 2px 0; transform:rotate(45deg);}

  .btn{
    position:relative; width:100%; height:68px; border:none; border-radius:14px; cursor:pointer;
    background:linear-gradient(90deg, #138BF5 0%, var(--blue-700) 60%, #0F45C8 100%);
    color:#fff; font:700 20px 'Plus Jakarta Sans',sans-serif;
    display:flex; align-items:center; justify-content:center; gap:14px;
    box-shadow:0 18px 28px -14px rgba(14,63,165,.65);
    transition:transform .15s, opacity .15s;
  }
  .btn:hover{transform:translateY(-2px);}
  .btn:disabled{opacity:.75; cursor:wait; transform:none;}
  .btn svg{width:22px; height:22px;}
  .btn .go{
    position:absolute; right:22px; top:50%; transform:translateY(-50%);
    width:38px; height:38px; border-radius:50%; background:rgba(255,255,255,.22);
    display:flex; align-items:center; justify-content:center;
  }
  .btn .go svg{width:20px; height:20px;}

  .footer{margin-top:32px; display:flex; align-items:center; gap:24px; color:var(--muted); font-size:13px;}
  .footer::before, .footer::after{content:""; width:68px; height:1px; background:#C9D3E3;}

  .bootstrap-growl{
    font-family:'Plus Jakarta Sans',sans-serif; font-size:13px; font-weight:500; line-height:1.4;
    padding:12px 34px 12px 16px; border-radius:10px; border:1px solid transparent;
    box-shadow:0 14px 30px -10px rgba(14,63,165,.35);
  }
  .bootstrap-growl.alert-danger{background:#fdecec; border-color:#f5c2c2; color:#a12b2b;}
  .bootstrap-growl.alert-success{background:#e8f8f0; border-color:#b7e6cc; color:#1a7a4c;}
  .bootstrap-growl .close{position:absolute; top:8px; right:10px; font-size:16px; line-height:1; color:inherit; opacity:.55; cursor:pointer; background:none; border:none;}

  @media (max-width:1400px){
    .hero{padding-right:170px;}
    .hero-title{font-size:36px;}
    .hero-sub{font-size:21px;}
    .features{gap:24px;}
    .feature{width:108px;}
    .feature span{font-size:15.5px;}
    .card{max-width:540px; padding:42px 40px 38px;}
  }
  /* layar pendek (laptop / browser tidak full screen): semua dirapatkan supaya muat tanpa scroll */
  @media (max-height:860px){
    .hero{padding-top:40px; padding-bottom:32px;}
    .hero-logo{width:150px;}
    .hero-title{margin-top:20px; font-size:34px;}
    .hero-sub{font-size:19px;}
    .hero-bar{margin-top:14px;}
    .features{margin-top:24px;}
    .feature-ic{width:62px; height:62px; border-radius:14px;}
    .feature-ic svg{width:26px; height:26px;}
    .feature span{font-size:14.5px; margin-top:8px;}
    .hero-foot p{font-size:15px;}
    .side{padding:64px 40px 16px;}
    .card{max-width:500px; padding:30px 40px 32px;}
    .card-brand img{width:112px;}
    .welcome{margin-top:8px; font-size:27px;}
    .welcome-sub{font-size:15px; margin-bottom:22px;}
    label.fl{font-size:15px; margin-bottom:8px;}
    .field{margin-bottom:16px;}
    .input input{height:52px; font-size:15.5px; padding-left:60px;}
    .input svg.lead{left:20px; width:20px; height:20px;}
    .keep{margin-bottom:18px;}
    .btn{height:56px; font-size:18px;}
    .btn .go{width:32px; height:32px;}
    .footer{margin-top:18px;}
  }
  @media (max-height:700px){
    .hero-logo{width:110px;}
    .hero-title{font-size:30px; margin-top:14px;}
    .features{margin-top:18px;}
    .feature-ic{width:52px; height:52px;}
    .card-brand img{width:84px;}
    .welcome{font-size:23px;}
    .welcome-sub{margin-bottom:16px;}
    .field{margin-bottom:12px;}
    .input input{height:46px;}
    .btn{height:50px;}
  }
  /* layar kecil: panel kiri disembunyikan, cukup kartu login */
  @media (max-width:960px){
    .hero{display:none;}
    .side{padding:80px 16px 24px;}
    .card{padding:30px 22px 32px;}
    .welcome{font-size:27px;}
    .welcome-sub{font-size:15px;}
  }
</style>
</head>
<body>
<div class="login">
  <section class="hero">
    <div class="hero-photo" aria-hidden="true"></div>

    <img class="hero-logo" src="{{ asset('images/logo-login.svg') }}" alt="{{ config('app.name') }}">
    <h1 class="hero-title">{{ $titleTop }}<span>{{ $titleBottom }}</span></h1>
    <p class="hero-sub">{{ config('app.name') }} Web</p>
    <div class="hero-bar"></div>

    <div class="features">
      <div class="feature">
        <div class="feature-ic"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.6" stroke-linejoin="round"><path d="M6 2.5h8.5l4.5 4.5v13a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 5 20V4a1.5 1.5 0 0 1 1-1.5z"/><path d="M14 2.5V7h5M8.5 11h7M8.5 14h7M8.5 17h5"/></svg></div>
        <span>Data Siswa</span>
      </div>
      <div class="feature">
        <div class="feature-ic"><svg viewBox="0 0 24 24" fill="#fff"><circle cx="10" cy="7" r="4"/><path d="M2.5 20c0-4.2 3.4-7 7.5-7 1.6 0 3 .4 4.2 1.1A6 6 0 0 0 12.6 20z"/><circle cx="18" cy="17.5" r="4.5"/><path d="m15.9 17.6 1.5 1.5 2.8-2.9" fill="none" stroke="#1455D9" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
        <span>Presensi &amp; Nilai</span>
      </div>
      <div class="feature">
        <div class="feature-ic"><svg viewBox="0 0 24 24" fill="#fff"><rect x="4" y="12" width="3" height="8" rx="1"/><rect x="9" y="7" width="3" height="13" rx="1"/><rect x="14" y="10" width="3" height="10" rx="1"/><rect x="19" y="5" width="2.5" height="15" rx="1"/><rect x="2.5" y="20.5" width="19" height="1.5" rx=".75"/></svg></div>
        <span>E-Rapor</span>
      </div>
    </div>

    <div class="hero-foot">
      <div class="hero-bar"></div>
      <p>Administrasi Sekolah Terpadu
untuk Pembelajaran yang Lebih Baik</p>
    </div>

    {{-- tepi kanan melengkung: pita biru muda lalu panel putih --}}
    <svg class="hero-curve" viewBox="0 0 260 1000" preserveAspectRatio="none" aria-hidden="true">
      <defs>
        <linearGradient id="ribbon" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#2E9BF5" stop-opacity=".9"/><stop offset="1" stop-color="#9FD3FF" stop-opacity=".9"/></linearGradient>
      </defs>
      <path d="M260 0H20C140 120 210 260 200 470S110 830 150 1000H260z" fill="url(#ribbon)"/>
      <path d="M260 0H130C215 150 250 300 236 500S150 850 190 1000H260z" fill="#F4F7FD"/>
    </svg>
  </section>

  <section class="side">

    <div class="card">
      <div class="card-brand">
        <img src="{{ asset('images/logo-login.svg') }}" alt="{{ config('app.name') }}">
        <h3 class="welcome" >Selamat Datang!</h3>
        <p class="welcome-sub" >Masuk untuk mengakses {{ config('app.description') }}</p>
      </div>

      <form id="loginForm" novalidate>
        @csrf
        <div class="field">
          <label class="fl" for="username" >Username</label>
          <div class="input">
            <svg class="lead" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>
            <input type="text" id="username" name="username" placeholder="Masukkan username" autocomplete="username" autofocus>
          </div>
        </div>

        <div class="field">
          <label class="fl" for="password" >Password</label>
          <div class="input">
            <svg class="lead" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="10.5" width="14" height="10" rx="2"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg>
            <input type="password" id="password" name="password" placeholder="Masukkan password" autocomplete="current-password">
            <button type="button" class="eye" id="togglePw" aria-label="Tampilkan password">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1.5 12S5.5 5 12 5s10.5 7 10.5 7-4 7-10.5 7S1.5 12 1.5 12z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
        </div>

        <label class="keep"><input type="checkbox" id="keepSignedIn"> <span>Tetap masuk</span></label>

        <button type="submit" class="btn" id="btn_login">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="10.5" width="14" height="10" rx="2"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/></svg>
          <span id="btnText" >Masuk</span>
          <span class="go"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
        </button>
      </form>
    </div>

    <div class="footer"><span>&copy; {{ date('Y') }} {{ config('simpel.login_footer') }}. <span>Hak cipta dilindungi.</span></span></div>
  </section>
</div>

<script src="{{ asset('lib/jquery/dist/jquery.min.js') }}"></script>
<script src="{{ asset('lib/bootstrap-growl/jquery.bootstrap-growl.js') }}"></script>
<script>
  // ---------- Teks pesan halaman login ----------
  var TEXT = {
    signIn: 'Masuk', signingIn: 'Sedang masuk...',
    required: 'Username dan password wajib diisi.',
    success: 'Login berhasil, mengalihkan...',
    expired: 'Halaman login sudah kedaluwarsa. Silakan muat ulang halaman.',
    failed: 'Login gagal. Silakan coba lagi.'
  };
  function t(key) { return TEXT[key] || key; }

  // ---------- Login ----------
  $('#togglePw').on('click', function () {
    var pw = document.getElementById('password');
    pw.type = pw.type === 'password' ? 'text' : 'password';
  });

  $('#loginForm').on('submit', function (e) {
    e.preventDefault();
    if (!$.trim($('#username').val()) || !$('#password').val()) {
      growl(t('required'), 'danger');
      return;
    }
    validateLogin();
  });

  function growl(message, type, delay) {
    $('.bootstrap-growl').remove();
    $.bootstrapGrowl($('<div>').text(message).html(), { type: type, allow_dismiss: true, align: 'right', delay: delay || 6000 });
  }

  function setLoading(loading) {
    $('#btn_login').prop('disabled', loading);
    $('#btnText').text(loading ? t('signingIn') : t('signIn'));
  }

  function validateLogin() {
    setLoading(true);

    $.ajax({
      type: 'POST',
      url: '{{ url("/Auth/Validate") }}',
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      data: {
        username: $('#username').val(),
        password: $('#password').val(),
        keepSignedIn: $('#keepSignedIn').is(':checked') ? 1 : 0,
        returnUrl: @json($returnUrl ?? '')
      }
    }).done(function (result) {
      if (result.status === true) {
        growl(t('success'), 'success', 1200);
        setTimeout(function () { window.location.href = result.data; }, 500);
      } else {
        setLoading(false);
        growl(result.message, 'danger');
      }
    }).fail(function (xhr) {
      setLoading(false);
      growl(xhr.status === 419 ? t('expired') : t('failed'), 'danger');
    });
  }
</script>
</body>
</html>
