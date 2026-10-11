@extends('layouts.app')

@section('title', 'إعدادات الملعب وسياسة الإلغاء')
@section('page_title', 'بيانات الملعب وسياسة الإلغاء')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 style="font-size: 1.15rem; font-weight: 700;">البيانات الأساسية والمرافق</h2>
    </div>

    <form action="{{ route('dashboard.settings.update') }}" method="POST">
        @csrf
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem;">
            <div class="form-group">
                <label class="form-label">اسم الملعب</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $venue->name ?? '') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">رقم الهاتف للتواصل</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $venue->phone ?? '') }}" placeholder="+2010xxxxxxxx">
            </div>
            <div class="form-group">
                <label class="form-label">المدينة</label>
                <input type="text" name="city" class="form-control" value="{{ old('city', $venue->city ?? '') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">العنوان بالتفصيل</label>
                <input type="text" name="address" class="form-control" value="{{ old('address', $venue->address ?? '') }}" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">وصف الملعب والمميزات</label>
            <textarea name="description" class="form-control" rows="3" placeholder="أدخل نبذة عن الملعب ونوعية الأرضية والتجهيزات...">{{ old('description', $venue->description ?? '') }}</textarea>
        </div>

        <div style="margin: 1.5rem 0; padding: 1rem; background: var(--surface-muted); border-radius: 8px;">
            <h3 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 0.75rem;">المرافق المتوفرة</h3>
            <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="has_lighting" value="1" {{ ($venue->has_lighting ?? false) ? 'checked' : '' }}>
                    <span>💡 إضاءة ليلية متوفرة</span>
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="has_changing_room" value="1" {{ ($venue->has_changing_room ?? false) ? 'checked' : '' }}>
                    <span>👕 غرف تبديل ملابس وحمامات</span>
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="has_parking" value="1" {{ ($venue->has_parking ?? false) ? 'checked' : '' }}>
                    <span>🚗 موقف سيارات مخصص</span>
                </label>
            </div>
        </div>

        <div style="margin: 1.5rem 0; padding: 1.25rem; background: #FEF3C7; border: 1px solid #FCD34D; border-radius: 8px;">
            <h3 style="font-size: 1rem; font-weight: 800; color: #92400E; margin-bottom: 0.5rem;">سياسة الإلغاء والاسترداد المالي للـ Lobbies</h3>
            <p style="font-size: 0.85rem; color: #78350F; margin-bottom: 1rem;">حدد الساعات المطلوبة قبل بدء موعد المباراة لتطبيق قواعد الاسترداد للاعبين:</p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="color: #92400E;">استرداد 100% (قبل أكثر من X ساعة)</label>
                    <input type="number" name="full_refund_hours" class="form-control" value="{{ old('full_refund_hours', $venue->cancellationPolicy->full_refund_hours ?? 24) }}" required min="0">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="color: #92400E;">استرداد 50% (قبل بين X و Y ساعة)</label>
                    <input type="number" name="half_refund_hours" class="form-control" value="{{ old('half_refund_hours', $venue->cancellationPolicy->half_refund_hours ?? 12) }}" required min="0">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="color: #92400E;">لا يوجد استرداد (أقل من X ساعة)</label>
                    <input type="number" name="no_refund_hours" class="form-control" value="{{ old('no_refund_hours', $venue->cancellationPolicy->no_refund_hours ?? 6) }}" required min="0">
                </div>
            </div>
        </div>

        <div style="text-align: left; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">حفظ الإعدادات والسياسة</button>
        </div>
    </form>
</div>

<!-- Photo Management -->
<div class="card">
    <div class="card-header">
        <h2 style="font-size: 1.15rem; font-weight: 700;">صور الملعب (Media)</h2>
    </div>

    <form action="{{ route('dashboard.photos.upload') }}" method="POST" enctype="multipart/form-data" style="margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
        @csrf
        <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 250px;">
            <label class="form-label">إضافة صورة جديدة للملعب</label>
            <input type="file" name="photo" class="form-control" accept="image/*" required>
        </div>
        <button type="submit" class="btn btn-primary">رفع الصورة</button>
    </form>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem;">
        @forelse($venue->media ?? [] as $media)
            <div style="border: 1px solid var(--border); border-radius: 8px; overflow: hidden; position: relative;">
                <img src="{{ $media->url }}" alt="صورة الملعب" style="width: 100%; height: 130px; object-fit: cover;">
                <div style="padding: 0.5rem; text-align: center; background: white;">
                    <form action="{{ route('dashboard.photos.delete', $media->id) }}" method="POST" onsubmit="return confirm('هل تريد حذف هذه الصورة؟')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" style="padding: 0.25rem 0.6rem; font-size: 0.75rem; width: 100%;">حذف الصورة</button>
                    </form>
                </div>
            </div>
        @empty
            <p style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 1.5rem;">لم يتم رفع أي صور للملعب بعد.</p>
        @endforelse
    </div>
</div>
@endsection
