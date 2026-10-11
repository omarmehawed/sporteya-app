@extends('layouts.app')

@section('title', 'مراجعة الملعب - ' . $venue->name)
@section('page_title', 'تفاصيل ومراجعة الملعب: ' . $venue->name)

@section('content')
<div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; justify-content: flex-end; flex-wrap: wrap;">
    @if(!$venue->is_verified)
        <form action="{{ route('admin.venues.verify', $venue->id) }}" method="POST">
            @csrf
            <input type="hidden" name="action" value="approve">
            <button type="submit" class="btn btn-primary">✓ اعتماد وتوثيق الملعب</button>
        </form>
        <form action="{{ route('admin.venues.verify', $venue->id) }}" method="POST" onsubmit="return confirm('هل تريد رفض هذا الملعب؟')">
            @csrf
            <input type="hidden" name="action" value="reject">
            <button type="submit" class="btn btn-danger">✕ رفض الاعتماد</button>
        </form>
    @else
        <form action="{{ route('admin.venues.verify', $venue->id) }}" method="POST">
            @csrf
            <input type="hidden" name="action" value="toggle_active">
            <button type="submit" class="btn {{ $venue->is_active ? 'btn-danger' : 'btn-primary' }}">
                {{ $venue->is_active ? 'تعطيل الملعب مؤقتاً' : 'إعادة تفعيل الملعب' }}
            </button>
        </form>
    @endif
    <a href="{{ route('admin.venues') }}" class="btn btn-secondary">← العودة لقائمة الملاعب</a>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">
    <!-- Basic Info -->
    <div class="card">
        <h2 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1rem;">بيانات الملعب والمالك</h2>
        <div style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.95rem;">
            <div><strong>اسم الملعب:</strong> {{ $venue->name }}</div>
            <div><strong>المدينة والعنوان:</strong> {{ $venue->city }} - {{ $venue->address }}</div>
            <div><strong>هاتف الملعب:</strong> {{ $venue->phone ?? 'غير متوفر' }}</div>
            <div><strong>اسم صاحب الملعب:</strong> {{ $venue->owner->name ?? 'غير متوفر' }}</div>
            <div><strong>البريد الإلكتروني للمالك:</strong> {{ $venue->owner->email ?? 'غير متوفر' }}</div>
            <div><strong>حالة التحقق:</strong>
                @if($venue->is_verified)
                    <span class="badge badge-success">موثق ({{ \Carbon\Carbon::parse($venue->verified_at)->format('Y-m-d') }})</span>
                @else
                    <span class="badge badge-warning">قيد المراجعة</span>
                @endif
            </div>
            <div><strong>حالة التنشيط:</strong>
                @if($venue->is_active)
                    <span class="badge badge-success">نشط</span>
                @else
                    <span class="badge badge-danger">معطل</span>
                @endif
            </div>
            <div><strong>المرافق:</strong>
                {{ $venue->has_lighting ? '✓ إضاءة ' : '' }}
                {{ $venue->has_changing_room ? '✓ غرف تبديل ' : '' }}
                {{ $venue->has_parking ? '✓ مواقف سيارات' : '' }}
            </div>
            @if($venue->description)
                <div style="margin-top: 0.5rem; background: var(--surface-muted); padding: 0.75rem; border-radius: 8px;">
                    <strong>الوصف:</strong>
                    <p style="margin-top: 0.25rem;">{{ $venue->description }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Cancellation Policy & Sports -->
    <div class="card">
        <h2 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1rem;">الرياضات وسياسة الإلغاء</h2>
        <div style="margin-bottom: 1.25rem;">
            <strong style="display: block; margin-bottom: 0.5rem;">الرياضات المتوفرة:</strong>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                @forelse($venue->sports as $sportRel)
                    <span class="badge badge-info">{{ $sportRel->sport->name_ar ?? '' }}</span>
                @empty
                    <span style="color: var(--text-muted); font-size: 0.85rem;">لم يحدد رياضات بعد</span>
                @endforelse
            </div>
        </div>

        <div style="background: var(--surface-muted); padding: 1rem; border-radius: 8px; border: 1px solid var(--border);">
            <strong style="display: block; margin-bottom: 0.5rem;">سياسة الإلغاء المعتمدة:</strong>
            @if($venue->cancellationPolicy)
                <ul style="padding-right: 1.25rem; font-size: 0.9rem; line-height: 1.6;">
                    <li>استرداد 100% قبل: <strong>{{ $venue->cancellationPolicy->full_refund_hours }}</strong> ساعة</li>
                    <li>استرداد 50% قبل: <strong>{{ $venue->cancellationPolicy->half_refund_hours }}</strong> ساعة</li>
                    <li>عدم استرداد خلال: <strong>{{ $venue->cancellationPolicy->no_refund_hours }}</strong> ساعة</li>
                </ul>
            @else
                <p style="color: var(--text-muted); font-size: 0.85rem;">لم تسجل سياسة إلغاء مخصصة.</p>
            @endif
        </div>
    </div>
</div>

<!-- Photos -->
<div class="card" style="margin-top: 1.5rem;">
    <h2 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1rem;">معرض الصور المرفوعة للملعب</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
        @forelse($venue->media as $media)
            <div style="border: 1px solid var(--border); border-radius: 8px; overflow: hidden;">
                <img src="{{ $media->url }}" alt="صورة الملعب" style="width: 100%; height: 160px; object-fit: cover;">
            </div>
        @empty
            <p style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 2rem;">لم يقم صاحب الملعب برفع أي صور حتى الآن.</p>
        @endforelse
    </div>
</div>
@endsection
