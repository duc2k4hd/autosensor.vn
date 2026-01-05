@php
    $userName = $comment->account->name ?? $comment->name;
    $userInitial = mb_strtoupper(mb_substr($userName, 0, 1));
@endphp
<div class="comment-item" data-comment-id="{{ $comment->id }}">
    <div class="comment-user">
        <div class="comment-avatar">
            {{ $userInitial }}
        </div>
        <div class="comment-info">
            <div class="comment-header">
                <strong class="comment-name">{{ $userName }}</strong>
                @if($comment->rating)
                    <span class="comment-rating">
                        @for($i = 1; $i <= $comment->rating; $i++)
                            ⭐
                        @endfor
                    </span>
                @endif
            </div>
            <div class="comment-meta">
                <span class="comment-date">{{ $comment->created_at->format('d/m/Y') }}</span>
            </div>
            <div class="comment-content">{{ $comment->content }}</div>
        </div>
    </div>
    @if($comment->adminReply)
        @php
            $adminName = $comment->adminReply->account->name ?? 'Admin';
            $adminInitial = mb_strtoupper(mb_substr($adminName, 0, 1));
        @endphp
        <div class="comment-reply">
            <div class="comment-reply-avatar">
                {{ $adminInitial }}
                <span class="admin-badge">QTV</span>
            </div>
            <div class="comment-reply-info">
                <div class="comment-reply-header">
                    <strong class="comment-reply-name">Quản Trị Viên</strong>
                    <span class="comment-reply-date">{{ $comment->adminReply->created_at->format('d/m/Y') }}</span>
                </div>
                <div class="comment-reply-content">{{ $comment->adminReply->content }}</div>
            </div>
        </div>
    @endif
</div>

