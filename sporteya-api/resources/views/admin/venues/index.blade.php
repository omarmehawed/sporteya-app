@extends('layouts.app')

@section('title', 'إدارة الملاعب - الإدارة')
@section('page_title', 'قائمة الملاعب المسجلة على سبورتيا')

@section('content')
<div class="card">
    <div class="card-header" style="flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <a href="{{ route('admin.venues') }}" class="btn {{ empty($status) ? 'btn-primary' : 'btn-secondary' }}" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">الكل</a>
            <a href="{{ route('admin.venues', ['status' => 'pending']) }}" class="btn {{ $status === 'pending' ? 'btn-primary' : 'btn-secondary' }}" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">بانتظار التحقق</a>
            <a href="{{ route('admin.venues', ['status' => 'active']) }}" class="btn {{ $status === 'active' ? 'btn-primary' : 'btn-secondary' }}" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">المفعلة والنشطة</a>
        </div>
    </div>

    @if($venues->isEmpty())
        <p style="text-align: center; color: var(--text-muted); padding: 3rem;">لا توجد ملاعب تطابق معايير البحث.</p>
    @else
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>اسم الملعب</th>
                        <th>المدينة</th>
                        <th>المالك</th>
                        <th>الرياضات المدعومة</th>
                        <th>التحقق</th>
                        <th>الحالة</th>
                        <th>تاريخ التسجيل</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($venues as $venue)
                        <tr>
                            <td style="font-weight: 700;">{{ $venue->name }}</td>
                            <td>{{ $venue->city }}</td>
                            <td>
                                <div>{{ $venue->owner->name ?? 'غير مسجل' }}</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $venue->owner->email ?? '' }}</div>
                            </td>
                            <td>
                                @foreach($venue->sports as $sportRelation)
                                    <span class="badge badge-info" style="margin: 2px;">{{ $sportRelation->sport->name_ar ?? '' }}</span>
                                @endforeach
                            </td>
                            <td>
                                @if($venue->is_verified)
                                    <span class="badge badge-success">موثق ✓</span>
                                @else
                                    <span class="badge badge-warning">بانتظار التحقق</span>
                                @endif
                            </td>
                            <td>
                                @if($venue->is_active)
                                    <span class="badge badge-success">نشط</span>
                                @else
                                    <span class="badge badge-danger">معطل</span>
                                @endif
                            </td>
                            <td style="font-size: 0.85rem; color: var(--text-muted);">
                                {{ \Carbon\Carbon::parse($venue->created_at)->format('Y-m-d') }}
                            </td>
                            <td>
                                <a href="{{ route('admin.venues.show', $venue->id) }}" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">
                                    فحص وتحقق
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1.5rem;">
            {{ $venues->appends(['status' => $status])->links() }}
        </div>
    @endif
</div>
@endsection
