<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Password Reset | TDT Powersteel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body{font-family:'Montserrat',sans-serif;background:#2F2D30;color:#111;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:20px}
        .card{background:#fff;border-radius:12px;box-shadow:0 20px 50px rgba(0,0,0,0.3);border-top:4px solid #E67026;max-width:480px;width:100%;padding:32px}
        .logo{text-align:center;margin-bottom:20px}
        .logo img{height:40px}
        h1{font-size:22px;font-weight:700;text-align:center;margin:0 0 8px}
        p.sub{font-size:13px;color:#717074;text-align:center;margin:0 0 20px}
        label{font-size:13px;font-weight:600;display:block;margin:12px 0 6px}
        input{width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box}
        input:focus{border-color:#E67026;outline:none;box-shadow:0 0 0 3px rgba(230,112,38,0.15)}
        .btn{width:100%;margin-top:18px;background:#E67026;color:#fff;border:none;border-radius:8px;padding:11px;font-weight:700;cursor:pointer;font-size:14px}
        .btn:hover{background:#C55618}
        .link{display:block;text-align:center;margin-top:14px;font-size:13px;color:#717074}
        .link a{color:#E67026;text-decoration:underline}
        .alert{padding:10px 12px;border-radius:8px;font-size:13px;margin-bottom:12px}
        .alert-success{background:#ecfdf5;border:1px solid #10b981;color:#065f46}
        .alert-error{background:#fef2f2;border:1px solid #ef4444;color:#991b1b}
    </style>
</head>
<body>
    <div class="card">
        <div class="logo"><img src="/static/images/logo.png" alt="TDT Powersteel"></div>
        <h1>Request Password Reset</h1>
        <p class="sub">Enter your email. An admin will review your request and approve a temporary password.</p>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-error">
                @foreach ($errors->all() as $error) <div>{{ $error }}</div> @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.request.post') }}">
            @csrf
            <label for="email">Email address</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus placeholder="your@email.com">
            <button type="submit" class="btn">Submit Request</button>
        </form>

        <div class="link"><a href="/admin/login">Back to Sign in</a></div>
    </div>
</body>
</html>
