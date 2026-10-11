<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - سبورتيا</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Cairo', sans-serif; }
        body {
            background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .login-card {
            background: white;
            border-radius: 16px;
            width: 100%;
            max-width: 440px;
            padding: 2.5rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        .logo-box {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }
        .brand-logo {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #10B981, #3B82F6);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 1.5rem;
            color: white;
        }
        .title { font-size: 1.5rem; font-weight: 800; text-align: center; margin-bottom: 0.5rem; color: #0F172A; }
        .subtitle { text-align: center; color: #64748B; font-size: 0.9rem; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.25rem; }
        .form-label { display: block; margin-bottom: 0.4rem; font-weight: 600; font-size: 0.875rem; color: #334155; }
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border: 1px solid #CBD5E1;
            font-size: 0.95rem;
            outline: none;
            transition: all 0.2s;
        }
        .form-control:focus { border-color: #10B981; box-shadow: 0 0 0 3px rgba(16,185,129,0.15); }
        .btn-submit {
            width: 100%;
            padding: 0.85rem;
            border-radius: 10px;
            background: #10B981;
            color: white;
            font-weight: 700;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 1rem;
        }
        .btn-submit:hover { background: #059669; }
        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            margin-bottom: 1.25rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-box">
            <div class="brand-logo">S</div>
            <h1 style="font-size: 1.5rem; font-weight: 800; color: #0F172A;">سبورتيا</h1>
        </div>

        <h2 class="title">تسجيل الدخول للوحة التحكم</h2>
        <p class="subtitle">خاص بمسؤولي النظام وأصحاب الملاعب الرياضية</p>

        @if($errors->any())
            <div class="alert-error">
                @foreach($errors->all() as $error)
                    <div>• {{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">البريد الإلكتروني</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus placeholder="name@example.com">
            </div>

            <div class="form-group">
                <label class="form-label">كلمة المرور</label>
                <input type="password" name="password" class="form-control" required placeholder="••••••••">
            </div>

            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                <input type="checkbox" name="remember" id="remember">
                <label for="remember" style="font-size: 0.85rem; color: #64748B; cursor: pointer;">تذكر تسجيل دخولي</label>
            </div>

            <button type="submit" class="btn-submit">دخول</button>
        </form>
    </div>
</body>
</html>
