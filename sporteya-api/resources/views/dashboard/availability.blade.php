@extends('layouts.app')

@section('title', 'أوقات العمل وحظر المواعيد')
@section('page_title', 'إدارة أوقات العمل وحظر المواعيد')

@section('content')
@php
    $days = [
        0 => 'الأحد',
        1 => 'الإثنين',
        2 => 'الثلاثاء',
        3 => 'الأربعاء',
        4 => 'الخميس',
        5 => 'الجمعة',
        6 => 'السبت',
    ];
@endphp

<div class="card">
    <div class="card-header">
        <h2 style="font-size: 1.15rem; font-weight: 700;">جدول مواعيد العمل الأسبوعية</h2>
    </div>

    <form action="{{ route('dashboard.schedules.save') }}" method="POST">
        @csrf
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>اليوم</th>
                        <th>ساعة الفتح</th>
                        <th>ساعة الإغلاق</th>
                        <th>مغلق بالكامل؟</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($days as $dayIndex => $dayName)
                        @php
                            $sched = $schedules->get($dayIndex);
                            $openTime = $sched ? substr($sched->open_time, 0, 5) : '08:00';
                            $closeTime = $sched ? substr($sched->close_time, 0, 5) : '23:00';
                            $isClosed = $sched ? (bool)$sched->is_closed : false;
                        @endphp
                        <tr>
                            <td style="font-weight: 700;">{{ $dayName }}</td>
                            <td>
                                <input type="time" name="schedules[{{ $dayIndex }}][open_time]" value="{{ $openTime }}" class="form-control" style="max-width: 150px;">
                            </td>
                            <td>
                                <input type="time" name="schedules[{{ $dayIndex }}][close_time]" value="{{ $closeTime }}" class="form-control" style="max-width: 150px;">
                            </td>
                            <td>
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                    <input type="checkbox" name="schedules[{{ $dayIndex }}][is_closed]" value="1" {{ $isClosed ? 'checked' : '' }}>
                                    <span style="font-size: 0.85rem; color: var(--danger);">عطلة / مغلق</span>
                                </label>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1.25rem; text-align: left;">
            <button type="submit" class="btn btn-primary">حفظ أوقات العمل</button>
        </div>
    </form>
</div>

<!-- Blocked Slots Section -->
<div class="card">
    <div class="card-header">
        <h2 style="font-size: 1.15rem; font-weight: 700;">حظر موعد (للصيانة أو الحجز الخارجي)</h2>
    </div>

    <form action="{{ route('dashboard.slots.block') }}" method="POST" style="margin-bottom: 2rem; background: var(--surface-muted); padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border);">
        @csrf
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">التاريخ</label>
                <input type="date" name="date" class="form-control" required min="{{ date('Y-m-d') }}">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">من الساعة</label>
                <input type="time" name="start_time" class="form-control" required>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">إلى الساعة</label>
                <input type="time" name="end_time" class="form-control" required>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">السبب (اختياري)</label>
                <input type="text" name="reason" class="form-control" placeholder="صيانة، حجز أكاديمية...">
            </div>
            <div>
                <button type="submit" class="btn btn-danger" style="width: 100%;">حظر هذا الموعد</button>
            </div>
        </div>
    </form>

    <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1rem;">قائمة المواعيد المحظورة حالياً</h3>
    @if($blockedSlots->isEmpty())
        <p style="text-align: center; color: var(--text-muted); padding: 1.5rem;">لا توجد أي مواعيد محظورة مسجلة.</p>
    @else
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>من الساعة</th>
                        <th>إلى الساعة</th>
                        <th>السبب</th>
                        <th>إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($blockedSlots as $slot)
                        <tr>
                            <td style="font-weight: 700;">{{ \Carbon\Carbon::parse($slot->date)->format('Y-m-d') }}</td>
                            <td>{{ substr($slot->start_time, 0, 5) }}</td>
                            <td>{{ substr($slot->end_time, 0, 5) }}</td>
                            <td>{{ $slot->reason ?? 'صيانة' }}</td>
                            <td>
                                <form action="{{ route('dashboard.slots.delete', $slot->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من إلغاء حظر هذا الموعد؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">إلغاء الحظر</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
