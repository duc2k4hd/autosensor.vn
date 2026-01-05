{{-- Comments List Component --}}
<div class="comments-section mb-4">
    <h4 class="mb-3">💬 Bình luận ({{ $totalComments ?? 0 }})</h4>

    {{-- Rating Stats --}}
    @if(isset($ratingStats) && $ratingStats['total_comments'] > 0)
        <div class="rating-stats mb-4 p-3 bg-light rounded">
            <div class="row align-items-center">
                <div class="col-md-4 text-center">
                    <div class="display-4 fw-bold">{{ number_format($ratingStats['average_rating'], 1) }}</div>
                    <div class="text-muted">Đánh giá trung bình</div>
                    <div class="mt-2">
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= round($ratingStats['average_rating']))
                                ⭐
                            @else
                                ☆
                            @endif
                        @endfor
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="row g-2">
                        @for($i = 5; $i >= 1; $i--)
                            @php
                                $count = $ratingStats['star_' . $i . '_count'] ?? 0;
                                $percentage = $ratingStats['total_comments'] > 0 
                                    ? round(($count / $ratingStats['total_comments']) * 100, 1) 
                                    : 0;
                            @endphp
                            <div class="col-12">
                                <div class="d-flex align-items-center">
                                    <div class="me-2" style="width: 60px;">
                                        {{ $i }} ⭐
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: {{ $percentage }}%"
                                                 aria-valuenow="{{ $percentage }}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                {{ $percentage }}%
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ms-2" style="width: 50px; text-align: right;">
                                        {{ $count }}
                                    </div>
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Comments List --}}
    <div id="commentsList">
        @forelse($comments ?? [] as $comment)
            <div class="comment-item border-bottom pb-3 mb-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <strong>{{ $comment->account->name ?? $comment->name }}</strong>
                        @if($comment->rating)
                            <span class="ms-2">
                                @for($i = 1; $i <= $comment->rating; $i++)
                                    ⭐
                                @endfor
                            </span>
                        @endif
                    </div>
                    <small class="text-muted">{{ $comment->created_at->format('d/m/Y H:i') }}</small>
                </div>
                <div class="comment-content mb-2">
                    {{ $comment->content }}
                </div>
                
                {{-- Admin Reply --}}
                @if($comment->adminReply)
                    <div class="admin-reply ms-4 mt-2 p-3 bg-light rounded border-start border-3 border-primary">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <strong class="text-primary">👤 Admin</strong>
                            <small class="text-muted">{{ $comment->adminReply->created_at->format('d/m/Y H:i') }}</small>
                        </div>
                        <div>{{ $comment->adminReply->content }}</div>
                    </div>
                @endif
            </div>
        @empty
            <div class="text-center text-muted py-4">
                Chưa có bình luận nào. Hãy là người đầu tiên bình luận!
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if(isset($comments) && method_exists($comments, 'links'))
        <div class="d-flex justify-content-center mt-4">
            {{ $comments->links() }}
        </div>
    @endif
</div>

