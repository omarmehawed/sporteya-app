<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'سبورتيا') - منصة حجز الملاعب الرياضية</title>
    <!-- Google Font: Cairo -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <style>
        :root {
            --primary: #10B981;
            --primary-dark: #059669;
            --primary-light: #D1FAE5;
            --secondary: #3B82F6;
            --dark: #0F172A;
            --dark-card: #1E293B;
            --surface: #FFFFFF;
            --surface-muted: #F8FAFC;
            --border: #E2E8F0;
            --text-main: #0F172A;
            --text-muted: #64748B;
            --danger: #EF4444;
            --warning: #F59E0B;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Cairo', sans-serif;
        }

        body {
            background-color: var(--surface-muted);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background-color: var(--dark);
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            right: 0;
            z-index: 40;
            box-shadow: -4px 0 20px rgba(0,0,0,0.05);
        }

        .sidebar-brand {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .brand-logo {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 1.25rem;
            color: white;
        }

        .brand-title {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .sidebar-nav {
            flex: 1;
            padding: 1.25rem 0.75rem;
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            overflow-y: auto;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: #94A3B8;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.08);
        }

        .nav-link.active {
            background-color: var(--primary);
            color: white;
        }

        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid rgba(255,255,255,0.08);
        }

        /* Main Content */
        .main-wrapper {
            flex: 1;
            margin-right: 260px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .topbar {
            height: 70px;
            background: white;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 30;
        }

        .page-title {
            font-size: 1.25rem;
            font-weight: 700;
        }

        .user-pill {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-badge {
            background: var(--primary-light);
            color: var(--primary-dark);
            padding: 0.25rem 0.6rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .content-container {
            padding: 2rem;
            flex: 1;
        }

        /* UI Components */
        .card {
            background: white;
            border-radius: 12px;
            border: 1px solid var(--border);
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            margin-bottom: 1.5rem;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border);
        }

        .grid-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text-main);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-secondary {
            background: #E2E8F0;
            color: var(--text-main);
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: right;
        }

        th {
            background: #F8FAFC;
            padding: 0.75rem 1rem;
            font-weight: 700;
            color: var(--text-muted);
            font-size: 0.85rem;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            font-size: 0.9rem;
        }

        tr:hover td {
            background-color: #F8FAFC;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .badge-success { background: #DEF7EC; color: #03543F; }
        .badge-danger { background: #FDE8E8; color: #9B1C1C; }
        .badge-warning { background: #FEF08A; color: #713F12; }
        .badge-info { background: #E1EFFE; color: #1E429F; }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .form-control {
            width: 100%;
            padding: 0.65rem 0.85rem;
            border-radius: 8px;
            border: 1px solid var(--border);
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            border-color: var(--primary);
        }

        .alert {
            padding: 0.85rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.25rem;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .alert-success { background: #DEF7EC; color: #03543F; }
        .alert-danger { background: #FDE8E8; color: #9B1C1C; }
        .alert-warning { background: #FEF08A; color: #713F12; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-logo">S</div>
            <div>
                <div class="brand-title">سبورتيا</div>
                <div style="font-size: 0.75rem; color: #94A3B8;">
                    @if(Auth::check() && Auth::user()->role === 'admin')
                        لوحة تحكم الإدارة
                    @else
                        بوابة صاحب الملعب
                    @endif
                </div>
            </div>
        </div>

        <nav class="sidebar-nav">
            @if(Auth::check() && Auth::user()->role === 'admin')
                <a href="{{ route('admin.venues') }}" class="nav-link {{ request()->routeIs('admin.venues*') ? 'active' : '' }}">
                    <span>🏟️</span>
                    <span>إدارة الملاعب</span>
                </a>
            @else
                <a href="{{ route('dashboard.index') }}" class="nav-link {{ request()->routeIs('dashboard.index') ? 'active' : '' }}">
                    <span>📊</span>
                    <span>الرئيسية والإحصائيات</span>
                </a>
                <a href="{{ route('dashboard.bookings') }}" class="nav-link {{ request()->routeIs('dashboard.bookings') ? 'active' : '' }}">
                    <span>🎟️</span>
                    <span>الحجوزات والـ QR</span>
                </a>
                <a href="{{ route('dashboard.availability') }}" class="nav-link {{ request()->routeIs('dashboard.availability') ? 'active' : '' }}">
                    <span>📅</span>
                    <span>الأوقات وحظر المواعيد</span>
                </a>
                <a href="{{ route('dashboard.pricing') }}" class="nav-link {{ request()->routeIs('dashboard.pricing') ? 'active' : '' }}">
                    <span>💵</span>
                    <span>التسعير والرياضات</span>
                </a>
                <a href="{{ route('dashboard.reviews') }}" class="nav-link {{ request()->routeIs('dashboard.reviews') ? 'active' : '' }}">
                    <span>⭐</span>
                    <span>تقييمات اللاعبين</span>
                </a>
                <a href="{{ route('dashboard.settings') }}" class="nav-link {{ request()->routeIs('dashboard.settings') ? 'active' : '' }}">
                    <span>⚙️</span>
                    <span>بيانات الملعب وسياسة الإلغاء</span>
                </a>
            @endif
        </nav>

        <div class="sidebar-footer">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-secondary" style="width: 100%; justify-content: flex-start; background: rgba(255,255,255,0.06); color: #EF4444;">
                    <span>🚪</span>
                    <span>تسجيل الخروج</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Wrapper -->
    <div class="main-wrapper">
        <header class="topbar">
            <h1 class="page-title">@yield('page_title', 'لوحة التحكم')</h1>
            <div class="user-pill">
                @if(Auth::check())
                    <span class="user-badge">{{ Auth::user()->role === 'admin' ? 'مدير النظام' : 'صاحب ملعب' }}</span>
                    <span style="font-weight: 700;">{{ Auth::user()->name }}</span>
                @endif
            </div>
        </header>

        <main class="content-container">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('warning'))
                <div class="alert alert-warning">{{ session('warning') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                        <div>• {{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>
