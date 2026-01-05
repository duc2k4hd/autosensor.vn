{{-- Comments Section - Simple & Clean --}}
<div class="comments-section">
    <h3>Bình luận</h3>

    {{-- Rating Summary --}}
    @if(isset($ratingStats) && $ratingStats['total_comments'] > 0)
        <div class="rating-summary">
            <div class="rating-avg">
                <span class="rating-number">{{ number_format($ratingStats['average_rating'], 1) }}</span>
                <div class="rating-stars">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= round($ratingStats['average_rating']))
                            ⭐
                        @else
                            ☆
                        @endif
                    @endfor
                </div>
                <span class="rating-count">({{ $ratingStats['total_comments'] }} đánh giá)</span>
            </div>
            <div class="rating-breakdown">
                @for($i = 5; $i >= 1; $i--)
                    @php
                        $count = $ratingStats['star_' . $i . '_count'] ?? 0;
                        $percentage = $ratingStats['total_comments'] > 0 
                            ? round(($count / $ratingStats['total_comments']) * 100) 
                            : 0;
                    @endphp
                    <div class="rating-row">
                        <span>{{ $i }}⭐</span>
                        <div class="rating-bar">
                            <div class="rating-bar-fill" style="width: {{ $percentage }}%"></div>
                        </div>
                        <span>{{ $count }}</span>
                    </div>
                @endfor
            </div>
        </div>
    @endif

    {{-- Comment Form --}}
    <div class="comment-form">
        <h4>Viết bình luận</h4>
        <form id="commentForm">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <input type="hidden" name="object_id" value="{{ $objectId }}">

            <div class="form-group">
                <label>Đánh giá <span>*</span></label>
                <div class="rating-input" id="ratingContainer">
                    @for($i = 1; $i <= 5; $i++)
                        <input type="radio" name="rating" id="rating{{ $i }}" value="{{ $i }}" required>
                        <label for="rating{{ $i }}" class="star" data-rating="{{ $i }}">☆</label>
                    @endfor
                    <span class="rating-text" id="ratingText">Chọn số sao</span>
                </div>
                <div class="rating-error" id="ratingError" style="display: none; color: #d32f2f; font-size: 12px; margin-top: 5px;"></div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Tên <span>*</span></label>
                    <input type="text" name="name" value="{{ auth('web')->user()?->name ?? '' }}" required>
                </div>
                <div class="form-group">
                    <label>Email <span>*</span></label>
                    <input type="email" name="email" value="{{ auth('web')->user()?->email ?? '' }}" required>
                </div>
            </div>

            <div class="form-group">
                <label>Nội dung <span>*</span></label>
                <textarea name="content" id="commentContent" rows="4" required placeholder="Nhập bình luận của bạn..." maxlength="200"></textarea>
                <div class="char-counter" style="text-align: right; margin-top: 5px; font-size: 12px; color: #666;">
                    <span id="charCount">0</span>/200 ký tự
                </div>
            </div>

            <button type="submit">Gửi bình luận</button>
        </form>
    </div>

    {{-- Comments List --}}
    <div class="comments-list" id="commentsList">
        @forelse($comments ?? [] as $comment)
            @include('clients.partials.comment-item', ['comment' => $comment])
        @empty
            <div class="comment-empty">Chưa có bình luận nào.</div>
        @endforelse
    </div>

    {{-- Load More Button --}}
    @if(isset($totalComments) && $totalComments > count($comments ?? []))
        <div class="comments-load-more" id="commentsLoadMore">
            <button type="button" class="load-more-btn" id="loadMoreBtn" data-type="{{ $type }}" data-object-id="{{ $objectId }}" data-offset="{{ count($comments ?? []) }}">
                <span class="btn-text">Xem thêm bình luận ({{ $totalComments - count($comments ?? []) }} còn lại)</span>
                <span class="btn-loading" style="display: none;">Đang tải...</span>
            </button>
        </div>
    @endif
</div>

<style>
.comments-section {
    margin: 30px 0;
}

.comments-section h3 {
    font-size: 20px;
    margin-bottom: 20px;
    font-weight: 600;
    color: #333;
}

.rating-summary {
    margin-bottom: 25px;
    padding: 20px;
    background: #fafafa;
    border-radius: 8px;
    border: 1px solid #e5e5e5;
}

.rating-avg {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e5e5e5;
}

.rating-number {
    font-size: 36px;
    font-weight: 700;
    color: #333;
}

.rating-stars {
    font-size: 22px;
    line-height: 1;
}

.rating-count {
    color: #666;
    font-size: 14px;
}

.rating-breakdown {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.rating-row {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 13px;
}

.rating-row span:first-child {
    width: 35px;
    font-size: 13px;
}

.rating-bar {
    flex: 1;
    height: 10px;
    background: #e5e5e5;
    border-radius: 5px;
    overflow: hidden;
}

.rating-bar-fill {
    height: 100%;
    background: #ffc107;
    border-radius: 5px;
}

.comment-form {
    margin-bottom: 30px;
    padding: 20px;
    background: #fff;
    border: 1px solid #e5e5e5;
    border-radius: 8px;
}

.comment-form h4 {
    font-size: 16px;
    margin-bottom: 18px;
    font-weight: 600;
    color: #333;
}

.form-group {
    margin-bottom: 18px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-size: 14px;
    font-weight: 500;
    color: #333;
}

.form-group label span {
    color: #d32f2f;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    transition: border-color 0.2s;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #4a90e2;
}

.rating-input {
    display: flex;
    align-items: center;
    gap: 6px;
}

.rating-input input[type="radio"] {
    display: none;
}

.rating-input .star {
    font-size: 26px;
    cursor: pointer;
    transition: all 0.2s;
    user-select: none;
    color: transparent;
    -webkit-text-stroke: 2px #666;
    text-stroke: 2px #666;
    display: inline-block;
    line-height: 1;
}

.rating-input .star.filled {
    -webkit-text-stroke: 0;
    color: #ffc107;
}

.rating-input .star:hover {
    transform: scale(1.15);
}

.rating-text {
    margin-left: 8px;
    font-size: 13px;
    color: #666;
}

.comment-form button {
    padding: 12px 24px;
    background: #d32f2f;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: background 0.2s;
}

.comment-form button:hover:not(:disabled) {
    background: #b71c1c;
}

.comment-form button:disabled {
    background: #ccc;
    cursor: not-allowed;
    opacity: 0.6;
}

.comments-list {
    margin-top: 25px;
}

.comment-item {
    padding: 20px 0;
    border-bottom: 1px solid #e5e5e5;
}

.comment-item:last-child {
    border-bottom: none;
}

.comment-user {
    display: flex;
    gap: 12px;
}

.comment-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 18px;
    flex-shrink: 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.comment-info {
    flex: 1;
    min-width: 0;
}

.comment-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 6px;
    flex-wrap: wrap;
}

.comment-name {
    font-size: 15px;
    font-weight: 600;
    color: #333;
}

.comment-rating {
    font-size: 14px;
    line-height: 1;
}

.comment-meta {
    margin-bottom: 10px;
}

.comment-date {
    color: #999;
    font-size: 12px;
}

.comment-content {
    font-size: 14px;
    line-height: 1.7;
    color: #444;
    word-wrap: break-word;
}

.comment-reply {
    margin-top: 18px;
    margin-left: 57px;
    padding: 15px 18px;
    background: #fff5f5;
    border-left: 4px solid #d32f2f;
    border-radius: 6px;
    display: flex;
    gap: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.comment-reply-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: linear-gradient(135deg, #d32f2f 0%, #b71c1c 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 18px;
    flex-shrink: 0;
    position: relative;
    box-shadow: 0 2px 4px rgba(211,47,47,0.2);
}

.admin-badge {
    position: absolute;
    bottom: -3px;
    right: -3px;
    background: #d32f2f;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 3px 5px;
    border-radius: 4px;
    border: 2px solid #fff;
    line-height: 1;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

.comment-reply-info {
    flex: 1;
    min-width: 0;
}

.comment-reply-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
    flex-wrap: wrap;
    gap: 8px;
}

.comment-reply-name {
    font-size: 15px;
    font-weight: 600;
    color: #d32f2f;
}

.comment-reply-date {
    color: #999;
    font-size: 12px;
}

.comment-reply-content {
    font-size: 14px;
    line-height: 1.7;
    color: #444;
    word-wrap: break-word;
}

.comment-empty {
    text-align: center;
    padding: 40px 20px;
    color: #999;
    font-size: 14px;
}

.comments-pagination {
    margin-top: 25px;
    text-align: center;
}

.comments-load-more {
    margin-top: 25px;
    text-align: center;
}

.load-more-btn {
    padding: 12px 30px;
    background: #fff;
    color: #d32f2f;
    border: 2px solid #d32f2f;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s;
}

.load-more-btn:hover:not(:disabled) {
    background: #d32f2f;
    color: #fff;
}

.load-more-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-loading {
    display: inline-block;
}

@media (max-width: 768px) {
    .comments-section {
        margin: 15px 0;
    }
    
    .comments-section h3 {
        font-size: 16px;
        margin-bottom: 15px;
    }
    
    .rating-summary {
        padding: 12px;
        margin-bottom: 20px;
    }
    
    .rating-avg {
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 15px;
        padding-bottom: 12px;
    }
    
    .rating-number {
        font-size: 24px;
    }
    
    .rating-stars {
        font-size: 18px;
    }
    
    .rating-count {
        font-size: 12px;
    }
    
    .rating-row {
        font-size: 12px;
        gap: 8px;
    }
    
    .rating-row span:first-child {
        width: 28px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .comment-form {
        padding: 12px;
        margin-bottom: 20px;
    }
    
    .comment-form h4 {
        font-size: 15px;
        margin-bottom: 15px;
    }
    
    .form-group {
        margin-bottom: 15px;
    }
    
    .form-group input,
    .form-group textarea {
        padding: 8px 10px;
        font-size: 14px;
    }
    
    .rating-input .star {
        font-size: 22px;
    }
    
    .rating-text {
        font-size: 12px;
        margin-left: 6px;
    }
    
    .comment-form button {
        padding: 10px 20px;
        font-size: 13px;
        width: 100%;
    }
    
    .comments-list {
        margin-top: 20px;
    }
    
    .comment-item {
        padding: 12px 0;
    }
    
    .comment-user {
        gap: 8px;
    }
    
    .comment-avatar,
    .comment-reply-avatar {
        width: 32px;
        height: 32px;
        font-size: 14px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }
    
    .comment-info {
        flex: 1;
    }
    
    .comment-header {
        gap: 6px;
        margin-bottom: 4px;
    }
    
    .comment-name {
        font-size: 13px;
    }
    
    .comment-rating {
        font-size: 12px;
    }
    
    .comment-meta {
        margin-bottom: 6px;
    }
    
    .comment-date {
        font-size: 11px;
    }
    
    .comment-content {
        font-size: 13px;
        line-height: 1.6;
        margin-top: 2px;
    }
    
    .comment-reply {
        margin-top: 12px;
        margin-left: 40px;
        padding: 10px 12px;
        border-left-width: 3px;
    }
    
    .admin-badge {
        font-size: 8px;
        padding: 2px 3px;
        bottom: -2px;
        right: -2px;
        border-width: 1.5px;
    }
    
    .comment-reply-header {
        margin-bottom: 6px;
        gap: 6px;
    }
    
    .comment-reply-name {
        font-size: 13px;
    }
    
    .comment-reply-date {
        font-size: 11px;
    }
    
    .comment-reply-content {
        font-size: 13px;
        line-height: 1.6;
    }
    
    .comment-empty {
        padding: 30px 15px;
        font-size: 13px;
    }
    
    .comments-pagination {
        margin-top: 20px;
    }
}

@media (max-width: 480px) {
    .comments-section {
        margin: 10px 0;
    }
    
    .comments-section h3 {
        font-size: 15px;
        margin-bottom: 12px;
    }
    
    .rating-summary {
        padding: 10px;
    }
    
    .rating-avg {
        gap: 5px;
        margin-bottom: 12px;
        padding-bottom: 10px;
    }
    
    .rating-number {
        font-size: 20px;
    }
    
    .rating-stars {
        font-size: 16px;
    }
    
    .comment-form {
        padding: 10px;
    }
    
    .comment-item {
        padding: 10px 0;
    }
    
    .comment-avatar,
    .comment-reply-avatar {
        width: 28px;
        height: 28px;
        font-size: 12px;
    }
    
    .comment-reply {
        margin-left: 36px;
        padding: 8px 10px;
    }
    
    .admin-badge {
        font-size: 7px;
        padding: 1px 2px;
    }
    
    .comment-name,
    .comment-reply-name {
        font-size: 12px;
    }
    
    .comment-content,
    .comment-reply-content {
        font-size: 12px;
    }
    
    .comment-date,
    .comment-reply-date {
        font-size: 10px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('commentForm');
    if (!form) return;

    const ratingInputs = document.querySelectorAll('input[name="rating"]');
    const stars = document.querySelectorAll('.rating-input .star');
    const ratingText = document.getElementById('ratingText');
    const ratingError = document.getElementById('ratingError');
    const submitBtn = form.querySelector('button[type="submit"]');
    const contentTextarea = document.getElementById('commentContent');
    const charCount = document.getElementById('charCount');
    
    let selectedRating = 0;

    // Disable submit button ban đầu
    submitBtn.disabled = true;

    // Character counter cho textarea
    if (contentTextarea && charCount) {
        function updateCharCount() {
            const length = contentTextarea.value.length;
            charCount.textContent = length;
            if (length > 200) {
                charCount.style.color = '#d32f2f';
            } else if (length > 180) {
                charCount.style.color = '#ff9800';
            } else {
                charCount.style.color = '#666';
            }
        }
        
        contentTextarea.addEventListener('input', updateCharCount);
        updateCharCount(); // Initialize
    }

    // Xử lý click vào sao
    stars.forEach((star, index) => {
        const rating = index + 1;
        
        star.addEventListener('click', function() {
            selectedRating = rating;
            
            // Tô màu từ sao 1 đến sao được chọn
            stars.forEach((s, i) => {
                if (i < rating) {
                    s.classList.add('filled');
                } else {
                    s.classList.remove('filled');
                }
            });
            
            // Check radio button
            document.getElementById('rating' + rating).checked = true;
            
            // Cập nhật text
            ratingText.textContent = rating + ' sao';
            ratingError.style.display = 'none';
            
            // Enable submit button
            submitBtn.disabled = false;
        });

        // Hover effect
        star.addEventListener('mouseenter', function() {
            const hoverRating = rating;
            stars.forEach((s, i) => {
                if (i < hoverRating) {
                    s.style.opacity = '0.7';
                }
            });
        });

        star.addEventListener('mouseleave', function() {
            stars.forEach((s) => {
                s.style.opacity = '1';
            });
        });
    });

    // Form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Validate rating
        if (!selectedRating) {
            ratingError.textContent = 'Vui lòng chọn đánh giá';
            ratingError.style.display = 'block';
            return;
        }
        
        const formData = new FormData(form);
        submitBtn.disabled = true;
        submitBtn.textContent = 'Đang gửi...';

        try {
            const response = await fetch('{{ route("comments.store") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            const data = await response.json();

            if (data.success) {
                showCustomToast('Bình luận của bạn đã được gửi và đang chờ duyệt.');
                form.reset();
                selectedRating = 0;
                stars.forEach(star => {
                    star.classList.remove('filled');
                });
                ratingText.textContent = 'Chọn số sao';
                submitBtn.disabled = true;
                // Reset character counter
                if (charCount) {
                    charCount.textContent = '0';
                    charCount.style.color = '#666';
                }
                
                if (typeof window.reloadComments === 'function') {
                    window.reloadComments();
                } else {
                    location.reload();
                }
            } else {
                if (data.errors && data.errors.rating) {
                    ratingError.textContent = data.errors.rating[0];
                    ratingError.style.display = 'block';
                } else {
                    showCustomToast(data.message || 'Có lỗi xảy ra. Vui lòng thử lại.');
                }
                submitBtn.disabled = false;
                submitBtn.textContent = 'Gửi bình luận';
            }
        } catch (error) {
            console.error('Error:', error);
            showCustomToast('Có lỗi xảy ra. Vui lòng thử lại.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Gửi bình luận';
        }
    });

    // Load More Comments
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', async function() {
            const btn = this;
            const btnText = btn.querySelector('.btn-text');
            const btnLoading = btn.querySelector('.btn-loading');
            const commentsList = document.getElementById('commentsList');
            const type = btn.dataset.type;
            const objectId = btn.dataset.objectId;
            const offset = parseInt(btn.dataset.offset) || 0;

            btn.disabled = true;
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline';

            try {
                const response = await fetch(`{{ route('comments.load-more') }}?type=${type}&object_id=${objectId}&offset=${offset}`, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();

                if (data.success && data.data && data.data.length > 0) {
                    // Render new comments
                    data.data.forEach(comment => {
                        const commentHtml = renderComment(comment);
                        commentsList.insertAdjacentHTML('beforeend', commentHtml);
                    });

                    // Update offset
                    const newOffset = data.nextOffset;
                    if (newOffset !== null) {
                        btn.dataset.offset = newOffset;
                        const remaining = data.total - newOffset;
                        btnText.textContent = `Xem thêm bình luận (${remaining} còn lại)`;
                        btn.disabled = false;
                        btnText.style.display = 'inline';
                        btnLoading.style.display = 'none';
                    } else {
                        // No more comments
                        document.getElementById('commentsLoadMore')?.remove();
                    }
                } else {
                    // No more comments
                    document.getElementById('commentsLoadMore')?.remove();
                }
            } catch (error) {
                console.error('Error loading more comments:', error);
                showCustomToast('Có lỗi xảy ra khi tải thêm bình luận. Vui lòng thử lại.');
                btn.disabled = false;
                btnText.style.display = 'inline';
                btnLoading.style.display = 'none';
            }
        });
    }

    function renderComment(comment) {
        const userName = comment.account?.name || comment.name || 'Người dùng';
        const userInitial = userName.charAt(0).toUpperCase();
        const rating = comment.rating || 0;
        const ratingStars = '⭐'.repeat(rating);
        const date = new Date(comment.created_at).toLocaleDateString('vi-VN');
        
        let adminReplyHtml = '';
        if (comment.admin_reply) {
            const adminName = comment.admin_reply.account?.name || 'Admin';
            const adminInitial = adminName.charAt(0).toUpperCase();
            const adminDate = new Date(comment.admin_reply.created_at).toLocaleDateString('vi-VN');
            adminReplyHtml = `
                <div class="comment-reply">
                    <div class="comment-reply-avatar">
                        ${adminInitial}
                        <span class="admin-badge">QTV</span>
                    </div>
                    <div class="comment-reply-info">
                        <div class="comment-reply-header">
                            <strong class="comment-reply-name">Quản Trị Viên</strong>
                            <span class="comment-reply-date">${adminDate}</span>
                        </div>
                        <div class="comment-reply-content">${comment.admin_reply.content || ''}</div>
                    </div>
                </div>
            `;
        }

        return `
            <div class="comment-item" data-comment-id="${comment.id}">
                <div class="comment-user">
                    <div class="comment-avatar">
                        ${userInitial}
                    </div>
                    <div class="comment-info">
                        <div class="comment-header">
                            <strong class="comment-name">${userName}</strong>
                            ${rating > 0 ? `<span class="comment-rating">${ratingStars}</span>` : ''}
                        </div>
                        <div class="comment-meta">
                            <span class="comment-date">${date}</span>
                        </div>
                        <div class="comment-content">${comment.content || ''}</div>
                    </div>
                </div>
                ${adminReplyHtml}
            </div>
        `;
    }
});
</script>

