@extends('layouts.app')

@section('title', 'التسعير والرياضات')
@section('page_title', 'إدارة أسعار الساعات والرياضات')

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
        <h2 style="font-size: 1.15rem; font-weight: 700;">إضافة تسعيرة جديدة</h2>
    </div>

    <form action="{{ route('dashboard.pricing.save') }}" method="POST" style="background: var(--surface-muted); padding: 1.25rem; border-radius: 8px; border: 1px solid var(--border);">
        @csrf
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; align-items: end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">الرياضة</label>
                <select name="sport_id" class="form-control" required>
                    <option value="">اختر الرياضة...</option>
                    @foreach($sports as $sport)
                        <option value="{{ $sport->id }}">{{ $sport->name_ar }} ({{ $sport->name_en }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">اليوم</label>
                <select name="day_of_week" class="form-control" required>
                    @foreach($days as $idx => $dayName)
                        <option value="{{ $idx }}">{{ $dayName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">من الساعة</label>
                <input type="time" name="start_time" class="form-control" value="08:00" required>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">إلى الساعة</label>
                <input type="time" name="end_time" class="form-control" value="23:00" required>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label">السعر للساعة (ج.م)</label>
                <input type="number" step="0.5" min="0" name="price_per_hour" class="form-control" placeholder="250.00" required>
            </div>
            <div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">إضافة التسعيرة</button>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h2 style="font-size: 1.15rem; font-weight: 700;">قواعد التسعير المسجلة</h2>
    </div>

    @if($pricingRules->isEmpty())
        <p style="text-align: center; color: var(--text-muted); padding: 1.5rem;">لا توجد أي قواعد تسعير مسجلة بعد. سيتم استخدام السعر الافتراضي إن وجد.</p>
    @else
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>الرياضة</th>
                        <th>اليوم</th>
                        <th>الفترة الزمنية</th>
                        <th>السعر للساعة</th>
                        <th>إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pricingRules as $rule)
                        <tr>
                            <td style="font-weight: 700;">{{ $rule->sport->name_ar ?? 'رياضة غير محددة' }}</td>
                            <td>{{ $days[$rule->day_of_week] ?? $rule->day_of_week }}</td>
                            <td>{{ substr($rule->start_time, 0, 5) }} - {{ substr($rule->end_time, 0, 5) }}</td>
                            <td style="font-weight: 700; color: var(--primary);">{{ number_format($rule->price_per_hour, 2) }} ج.م</td>
                            <td>
                                <form action="{{ route('dashboard.pricing.delete', $rule->id) }}" method="POST" onsubmit="return confirm('هل تريد حذف هذه التسعيرة؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">حذف</button>
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
