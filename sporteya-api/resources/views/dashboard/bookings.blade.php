@extends('layouts.app')

@section('title', 'سجل الحجوزات')
@section('page_title', 'سجل حجوزات الملعب وفحص الـ QR')

@section('content')
<div class="card" x-data="{ qrCode: '', showScanner: false }">
    <div class="card-header" style="flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <a href="{{ route('dashboard.bookings') }}" class="btn {{ empty($status) ? 'btn-primary' : 'btn-secondary' }}" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">الكل</a>
            <a href="{{ route('dashboard.bookings', ['status' => 'confirmed']) }}" class="btn {{ $status === 'confirmed' ? 'btn-primary' : 'btn-secondary' }}" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">المؤكدة</a>
            <a href="{{ route('dashboard.bookings', ['status' => 'completed']) }}" class="btn {{ $status === 'completed' ? 'btn-primary' : 'btn-secondary' }}" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">المكتملة</a>
            <a href="{{ route('dashboard.bookings', ['status' => 'cancelled']) }}" class="btn {{ $status === 'cancelled' ? 'btn-primary' : 'btn-secondary' }}" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">الملغاة</a>
        </div>

        <div>
            <button @click="showScanner = !showScanner" class="btn btn-primary" style="font-size: 0.85rem;">
                <span>📷</span>
                <span>فحص كود QR يدوي للتحقق من الحضور</span>
            </button>
        </div>
    </div>

    <!-- Quick Check-in Box -->
    <div x-show="showScanner" style="background: var(--surface-muted); border: 1px dashed var(--primary); padding: 1.25rem; border-radius: 8px; margin-bottom: 1.5rem;" x-cloak>
        <h3 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 0.5rem;">فحص كود تسجيل الحضور (Check-in)</h3>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">أدخل رمز التحقق (QR Token) المكون من 64 رمزاً الموجود في تذكرة اللاعب لتأكيد الحضور.</p>
        <form @submit.prevent="
            fetch('/api/v1/checkin/qr', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ qr_token: qrCode })
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    alert('✓ ' + data.message);
                    window.location.reload();
                } else {
                    alert('خطأ: ' + (data.message || 'رمز الـ QR غير صالح أو منتهي'));
                }
            })
            .catch(() => alert('تعذر الاتصال بالخادم.'))
        " style="display: flex; gap: 0.75rem; max-width: 600px;">
            <input type="text" x-model="qrCode" class="form-control" placeholder="الصق أو امسح رمز الـ QR..." required style="font-family: monospace;">
            <button type="submit" class="btn btn-primary" style="white-space: nowrap;">تحقق وتأكيد</button>
        </form>
    </div>

    @if($bookings->isEmpty())
        <p style="text-align: center; color: var(--text-muted); padding: 3rem;">لا توجد أي حجوزات تطابق هذا التصنيف.</p>
    @else
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>رقم الحجز</th>
                        <th>الرياضة</th>
                        <th>اسم اللاعب</th>
                        <th>الموعد</th>
                        <th>المبلغ الصافي</th>
                        <th>رمز QR</th>
                        <th>تسجيل الحضور</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bookings as $booking)
                        <tr>
                            <td style="font-family: monospace; font-weight: 700; font-size: 0.8rem;">
                                {{ substr($booking->id, 0, 8) }}...
                            </td>
                            <td>{{ $booking->lobby->sport->name_ar ?? 'غير محدد' }}</td>
                            <td>{{ $booking->lobby->creator->user->name ?? 'غير معروف' }}</td>
                            <td>
                                {{ \Carbon\Carbon::parse($booking->lobby->match_date)->format('Y-m-d') }}
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    {{ substr($booking->lobby->start_time, 0, 5) }} - {{ substr($booking->lobby->end_time, 0, 5) }}
                                </div>
                            </td>
                            <td style="font-weight: 700; color: var(--primary);">
                                {{ number_format($booking->venue_amount, 2) }} ج.م
                            </td>
                            <td style="font-family: monospace; font-size: 0.75rem; color: var(--text-muted);">
                                {{ substr($booking->qr_token, 0, 10) }}...
                            </td>
                            <td>
                                @if($booking->checked_in_at)
                                    <span class="badge badge-success">تم الحضور ({{ \Carbon\Carbon::parse($booking->checked_in_at)->format('H:i') }})</span>
                                @else
                                    <span class="badge badge-warning">لم يحضر بعد</span>
                                @endif
                            </td>
                            <td>
                                @if($booking->status === 'confirmed')
                                    <span class="badge badge-success">مؤكد</span>
                                @elseif($booking->status === 'completed')
                                    <span class="badge badge-info">مكتمل</span>
                                @elseif($booking->status === 'cancelled')
                                    <span class="badge badge-danger">ملغي</span>
                                @else
                                    <span class="badge badge-warning">{{ $booking->status }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1.5rem;">
            {{ $bookings->appends(['status' => $status])->links() }}
        </div>
    @endif
</div>
@endsection
