<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Aktivasi SIMOLA Operator</title>
<style>
*{box-sizing:border-box}
:root{font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
body{margin:0;min-height:100vh;display:grid;place-items:center;background:radial-gradient(circle at 20% 10%,rgba(37,99,235,.10),transparent 34%),#eef3f9;color:#172033;padding:18px}
.card{width:min(390px,100%);border:1px solid #dbe4ef;border-radius:20px;background:#fff;padding:24px;box-shadow:0 20px 55px rgba(15,23,42,.12)}
.logo{width:44px;height:44px;display:grid;place-items:center;border-radius:14px;background:#172844;color:#fff;font-weight:900;margin-bottom:15px}
h1{margin:0;font-size:19px}
p{margin:7px 0 0;color:#6b7a91;font-size:11px;line-height:1.65}
label{display:block;margin-top:20px;margin-bottom:6px;color:#46566e;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.04em}
input{width:100%;height:52px;border:1px solid #cbd6e4;border-radius:12px;background:#f8fafc;color:#172033;text-align:center;font-size:22px;font-weight:900;letter-spacing:.22em;outline:none}
input:focus{border-color:#60a5fa;box-shadow:0 0 0 4px rgba(59,130,246,.10)}
button{width:100%;margin-top:10px;border:0;border-radius:11px;background:#2563eb;color:#fff;padding:12px;font-size:11px;font-weight:900;cursor:pointer}
.alert{margin-top:14px;border:1px solid #fecaca;border-radius:10px;background:#fef2f2;color:#b91c1c;padding:10px;font-size:9px;line-height:1.5}
.help{margin-top:16px;padding-top:14px;border-top:1px solid #edf1f6;color:#8795a8;font-size:9px;line-height:1.6}
</style>
</head>
<body>
<section class="card">
    <div class="logo">S</div>
    <h1>Aktivasi PC Operator</h1>
    <p>
        Tidak perlu akun atau password. Masukkan kode 6 digit yang dibuat pengawas
        untuk PC ini. Aktivasi hanya dilakukan sekali pada perangkat ini.
    </p>

    @if(session('error'))
        <div class="alert">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('operator-device.activate') }}">
        @csrf
        <label>Kode Aktivasi</label>
        <input
            name="code"
            inputmode="numeric"
            pattern="[0-9]{6}"
            maxlength="6"
            autocomplete="one-time-code"
            placeholder="000000"
            value="{{ old('code') }}"
            required
            autofocus
        >
        <button type="submit">Aktifkan PC</button>
    </form>

    <div class="help">
        Jika PC ini sebelumnya digunakan pada perangkat lain, pengawas harus
        memilih <b>Lepas Akses</b> terlebih dahulu sebelum membuat kode baru.
    </div>
</section>
</body>
</html>
