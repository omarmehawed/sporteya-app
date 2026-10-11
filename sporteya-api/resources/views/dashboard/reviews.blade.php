@extends('layouts.app')

@section('title', 'تقييمات وآراء اللاعبين')
@section('page_title', 'تقييمات اللاعبين للملعب')

@section('content')
<div class="card" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1.5rem;">
    <div>
        <h2 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 0.25rem;">تقييم الملعب العام</h2>
        <p style="color: var(--text-muted); font-size: 0.9rem;">بناءً على التقييمات المسجلة من اللاعبين بعد المباريات المنتهية</p>
    </div>
    <div style="display: flex; align-items: center; gap: 1rem;">
        <div style="font-size: 3rem; font-weight: 900; color: #F59E0B;">{{ number_format($venue->rating_avg ?? 0, 1) }}</div>
        <div>
            <div style="font-size: 1.25rem; color: #F59E0B;">
                @php $rating = round($venue->rating_avg ?? 0); @endphp
                @for($i = 1; $i <= 5; $i++)
                    {{ $i <= $rating ? '★' : '☆' }}
                @endfor
            </div>
            <div style="font-size: 0.85rem; color: var(--text-muted);">{{ $venue->rating_count ?? 0 }} تقييم</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 style="font-size: 1.15rem; font-weight: 700;">آراء وتعليقات اللاعبين</h2>
    </div>

    @if(empty($reviews) || (method_exists($reviews, 'isEmpty') && $reviews->isEmpty()))
        <p style="text-align: center; color: var(--text-muted); padding: 3rem;">لا توجد أي تقييمات مسجلة للملعب حتى الآن.</p>
    @else
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            @foreach($reviews as $review)
                <div style="padding: 1rem; border-bottom: 1px solid var(--border); display: flex; flex-direction: column; gap: 0.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: 700;">{{ $review->player->user->name ?? 'لاعب مجهول' }}</span>
                        <span style="color: #F59E0B; font-size: 1.1rem;">
                            @for($i = 1; $i <= 5; $i++)
                                {{ $i <= $review->rating ? '★' : '☆' }}
                            @endfor
                        </span>
                    </div>
                    @if($review->comment)
                        <p style="color: var(--text-main); font-size: 0.95rem; line-height: 1.5;">{{ $review->comment }}</p>
                    @endif
                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                        {{ \Carbon\Carbon::parse($review->created_at)->diffForHumans() }}
                    </div>
                </div>
            @endforeach
        </div>

        @if(method_exists($reviews, 'links'))
            <div style="margin-top: 1.5rem;">
                {{ $reviews->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
