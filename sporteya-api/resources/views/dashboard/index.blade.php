@extends('layouts.app')

@section('title', 'الرئيسية - ' . ($venue->name ?? 'لوحة التحكم'))
@section('page_title', 'نظرة عامة على ' . ($venue->name ?? 'الملعب'))

@section('content')
<div class="grid-stats">
    <div class="stat-card">
        <span class="stat-label">حجوزات اليوم المؤكدة</span>
        <span class="stat-value">{{ $todayBookings }}</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">أرباح اليوم</span>
        <span class="stat-value" style="color: var(--primary);">{{ number_format($todayRevenue, 2) }} <small style="font-size: 1rem;">ج.م</small></span>
    </div>
    <div class="stat-card">
        <span class="stat-label">أرباح آخر 7 أيام</span>
        <span class="stat-value" style="color: var(--secondary);">{{ number_format($weekRevenue, 2) }} <small style="font-size: 1rem;">ج.م</small></span>
    </div>
    <div class="stat-card">
        <span class="stat-label">حالة الملعب</span>
        <div>
            @if($venue->is_active)
                <span class="badge badge-success">نشط ومتاح للحجز</span>
            @else
                <span class="badge badge-warning">معطل مؤقتاً</span>
            @endif
            @if($venue->is_verified)
                <span class="badge badge-info">موثق من الإدارة ✓</span>
            @else
                <span class="badge badge-danger">قيد مراجعة التوثيق</span>
            @endif
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 style="font-size: 1.15rem; font-weight: 700;">أحدث الحجوزات القادمة</h2>
        <a href="{{ route('dashboard.bookings') }}" class="btn btn-secondary" style="font-size: 0.85rem;">عرض كل الحجوزات</a>
    </div>

    @if($upcomingBookings->isEmpty())
        <p style="text-align: center; color: var(--text-muted); padding: 2rem;">لا توجد أي حجوزات قادمة حتى الآن.</p>
    @else
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>رقم الحجز</th>
                        <th>الرياضة</th>
                        <th>منشئ الحجز</th>
                        <th>التاريخ والوقت</th>
                        <th>المبلغ</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($upcomingBookings as $booking)
                        <tr>
                            <td style="font-family: monospace; font-weight: 700;">{{ substr($booking->id, 0, 8) }}...</td>
                            <td>{{ $booking->lobby->sport->name_ar ?? 'غير محدد' }}</td>
                            <td>{{ $booking->lobby->creator->user->name ?? 'غير معروف' }}</td>
                            <td>
                                {{ \Carbon\Carbon::parse($booking->lobby->match_date)->format('Y-m-d') }}
                                <span style="color: var(--text-muted); font-size: 0.85rem;">({{ substr($booking->lobby->start_time, 0, 5) }} - {{ substr($booking->lobby->end_time, 0, 5) }})</span>
                            </td>
                            <td style="font-weight: 700;">{{ number_format($booking->venue_amount, 2) }} ج.م</td>
                            <td>
                                @if($booking->status === 'confirmed')
                                    <span class="badge badge-success">مؤكد</span>
                                @elseif($booking->status === 'cancelled')
                                    <span class="badge badge-danger">ملغي</span>
                                @else
                                    <span class="badge badge-info">{{ $booking->status }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
    <div class="card">
        <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1rem;">إجراءات سريعة</h3>
        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            <a href="{{ route('dashboard.availability') }}" class="btn btn-secondary" style="justify-content: flex-start;">
                <span>📅</span>
                <span>تعديل مواعيد العمل أو حظر وقت للصيانة</span>
            </a>
            <a href="{{ route('dashboard.pricing') }}" class="btn btn-secondary" style="justify-content: flex-start;">
                <span>💵</span>
                <span>تحديث أسعار الساعات لكل رياضة</span>
            </a>
            <a href="{{ route('dashboard.bookings') }}" class="btn btn-secondary" style="justify-content: flex-start;">
                <span>🎟️</span>
                <span>فحص تذاكر اللاعبين والـ QR Code</span>
            </a>
            <a href="{{ route('dashboard.settings') }}" class="btn btn-secondary" style="justify-content: flex-start;">
                <span>⚙️</span>
                <span>تحديث بيانات الملعب والصور وسياسة الإلغاء</span>
            </a>
        </div>
    </div>

    <div class="card">
        <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1rem;">بيانات الملعب السريعة</h3>
        <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.9rem;">
            <div><strong>العنوان:</strong> {{ $venue->address }}, {{ $venue->city }}</div>
            <div><strong>الهاتف:</strong> {{ $venue->phone ?? 'غير مسجل' }}</div>
            <div><strong>التقييم:</strong> ⭐ {{ number_format($venue->rating_avg, 1) }} ({{ $venue->rating_count }} تقييم)</div>
            <div><strong>المرافق:</strong>
                {{ $venue->has_lighting ? '✓ إضاءة ' : '' }}
                {{ $venue->has_changing_room ? '✓ غرف تبديل ' : '' }}
                {{ $venue->has_parking ? '✓ موقف سيارات' : '' }}
            </div>
        </div>
    </div>
</div>
@endsection
