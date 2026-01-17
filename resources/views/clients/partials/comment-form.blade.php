{{-- Comment Form Component --}}
<div class="comment-form-section mb-4">
    <h4 class="mb-3">💬 Viết bình luận</h4>
    <form id="commentForm" class="border p-4 rounded">
        @csrf
        <input type="hidden" name="type" value="{{ $type }}">
        <input type="hidden" name="object_id" value="{{ $objectId }}">

        {{-- Rating --}}
        <div class="mb-3">
            <label class="form-label">Đánh giá <span class="text-danger">*</span></label>
            <div class="rating-input d-flex gap-2 align-items-center">
                @for($i = 5; $i >= 1; $i--)
                    <input type="radio" name="rating" id="rating{{ $i }}" value="{{ $i }}" required>
                    <label for="rating{{ $i }}" class="rating-star" data-rating="{{ $i }}">
                        ⭐
                    </label>
                @endfor
                <span class="ms-2 text-muted" id="ratingText">Chọn số sao</span>
            </div>
            <div class="text-danger small mt-1" id="ratingError"></div>
        </div>

        {{-- Name --}}
        <div class="mb-3">
            <label class="form-label">Tên của bạn <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required
                   value="{{ auth('web')->user()?->name ?? old('name') }}">
            <div class="text-danger small mt-1" id="nameError"></div>
        </div>

        {{-- Email --}}
        <div class="mb-3">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" required
                   value="{{ auth('web')->user()?->email ?? old('email') }}">
            <div class="text-danger small mt-1" id="emailError"></div>
        </div>

        {{-- Content --}}
        <div class="mb-3">
            <label class="form-label">Nội dung bình luận <span class="text-danger">*</span></label>
            <textarea name="content" class="form-control" rows="5" required
                      placeholder="Nhập nội dung bình luận của bạn (tối thiểu 10 ký tự)..."></textarea>
            <div class="text-danger small mt-1" id="contentError"></div>
        </div>

        {{-- Submit --}}
        <button type="submit" class="btn btn-primary" id="submitBtn">
            <span class="spinner-border spinner-border-sm d-none" id="submitSpinner"></span>
            Gửi bình luận
        </button>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('commentForm');
    const ratingInputs = document.querySelectorAll('input[name="rating"]');
    const ratingText = document.getElementById('ratingText');
    const submitBtn = document.getElementById('submitBtn');
    const submitSpinner = document.getElementById('submitSpinner');

    // Rating stars interaction
    ratingInputs.forEach(input => {
        input.addEventListener('change', function() {
            const rating = this.value;
            ratingText.textContent = rating + ' sao';
            
            // Update star colors
            document.querySelectorAll('.rating-star').forEach((star, index) => {
                if (5 - index <= rating) {
                    star.style.color = '#ffc107';
                } else {
                    star.style.color = '#ccc';
                }
            });
        });
    });

    // Form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Clear previous errors
        document.querySelectorAll('.text-danger').forEach(el => el.textContent = '');
        submitBtn.disabled = true;
        submitSpinner.classList.remove('d-none');

        const formData = new FormData(form);

        try {
            const response = await fetch('{{ route("comments.store", ['lang' => app()->getLocale()]) }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            const data = await response.json();

            if (data.success) {
                showCustomToast(data.message || 'Bình luận của bạn đã được gửi và đang chờ duyệt.');
                form.reset();
                ratingText.textContent = 'Chọn số sao';
                document.querySelectorAll('.rating-star').forEach(star => {
                    star.style.color = '#ccc';
                });
                
                // Reload comments if callback exists
                if (typeof window.reloadComments === 'function') {
                    window.reloadComments();
                }
            } else {
                // Display errors
                if (data.errors) {
                    Object.keys(data.errors).forEach(key => {
                        const errorEl = document.getElementById(key + 'Error');
                        if (errorEl) {
                            errorEl.textContent = data.errors[key][0];
                        }
                    });
                } else {
                    showCustomToast(data.message || 'Có lỗi xảy ra. Vui lòng thử lại.');
                }
            }
        } catch (error) {
            console.error('Error:', error);
            showCustomToast('Có lỗi xảy ra. Vui lòng thử lại.');
        } finally {
            submitBtn.disabled = false;
            submitSpinner.classList.add('d-none');
        }
    });
});
</script>

<style>
.rating-input input[type="radio"] {
    display: none;
}

.rating-star {
    font-size: 2rem;
    color: #ccc;
    cursor: pointer;
    transition: color 0.2s;
}

.rating-star:hover,
.rating-input input[type="radio"]:checked + .rating-star {
    color: #ffc107;
}
</style>
@endpush

