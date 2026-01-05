@extends('admins.layouts.master')

@section('title', 'Chi tiết liên hệ')
@section('page-title', '📨 Chi tiết liên hệ')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <strong>Thông tin liên hệ</strong>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-3">Họ tên</dt>
                            <dd class="col-sm-9">{{ $contact->name ?? 'Không tên' }}</dd>

                            <dt class="col-sm-3">Email</dt>
                            <dd class="col-sm-9">{{ $contact->email }}</dd>

                            <dt class="col-sm-3">Số điện thoại</dt>
                            <dd class="col-sm-9">{{ $contact->phone }}</dd>

                            <dt class="col-sm-3">Tiêu đề</dt>
                            <dd class="col-sm-9">{{ $contact->subject }}</dd>

                            <dt class="col-sm-3">Nội dung</dt>
                            <dd class="col-sm-9">
                                <pre class="mb-0" style="white-space: pre-wrap">{{ $contact->message }}</pre>
                            </dd>

                            <dt class="col-sm-3">Tệp đính kèm</dt>
                            <dd class="col-sm-9">
                                @if($contact->attachment_path)
                                    <a href="{{ route('admin.contacts.attachment', $contact) }}">
                                        Tải xuống
                                    </a>
                                @else
                                    <span class="text-muted">Không có</span>
                                @endif
                            </dd>

                            <dt class="col-sm-3">IP</dt>
                            <dd class="col-sm-9">{{ $contact->ip }}</dd>

                            <dt class="col-sm-3">Thời gian gửi</dt>
                            <dd class="col-sm-9">
                                {{ optional($contact->created_at)->format('d/m/Y H:i') }}
                            </dd>
                        </dl>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header">
                        <strong>Gửi email phản hồi</strong>
                    </div>
                    <div class="card-body">
                        <form id="contact-reply-form" action="{{ route('admin.contacts.reply', $contact) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Nội dung</label>
                                <textarea id="contact-reply-editor" name="message" class="form-control" rows="8">{{ old('message') }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Đính kèm (tùy chọn)</label>
                                <input type="file" name="attachment" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-primary">Gửi phản hồi</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <strong>Trạng thái & phân loại</strong>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.contacts.update-status', $contact) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Trạng thái</label>
                                <select name="status" class="form-select">
                                    <option value="new" @selected($contact->status === 'new')>Mới</option>
                                    <option value="processing" @selected($contact->status === 'processing')>Đang xử lý</option>
                                    <option value="done" @selected($contact->status === 'done')>Đã xử lý</option>
                                    <option value="spam" @selected($contact->status === 'spam')>Spam</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ghi chú nội bộ</label>
                                <textarea name="note" class="form-control" rows="3">{{ old('note', $contact->admin_note) }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-success">Lưu trạng thái</button>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <strong>Thông tin hệ thống</strong>
                    </div>
                    <div class="card-body">
                        <p class="mb-1">
                            <strong>Trạng thái:</strong>
                            <span class="{{ $contact->status_badge_class }}">{{ $contact->status_label }}</span>
                        </p>
                        <p class="mb-1">
                            <strong>Nguồn:</strong> {{ $contact->source ?? 'contact_form' }}
                        </p>
                        <p class="mb-1">
                            <strong>Đã đọc:</strong> {{ $contact->is_read ? 'Có' : 'Chưa' }}
                        </p>
                        <p class="mb-1">
                            <strong>Lần trả lời cuối:</strong>
                            {{ optional($contact->last_replied_at)->format('d/m/Y H:i') ?? 'Chưa có' }}
                        </p>
                        <p class="mb-0">
                            <strong>Số lần trả lời:</strong> {{ $contact->reply_count ?? 0 }}
                        </p>
                    </div>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <strong>Lịch sử phản hồi</strong>
                    </div>
                    <div class="card-body">
                        @if($contact->replies->isEmpty())
                            <p class="text-muted mb-0">Chưa có phản hồi nào.</p>
                        @else
                            <div class="list-group list-group-flush">
                                @foreach($contact->replies->sortByDesc('created_at') as $reply)
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between mb-1">
                                            <strong>{{ $reply->account->name ?? 'Hệ thống' }}</strong>
                                            <small class="text-muted">{{ optional($reply->created_at)->format('d/m/Y H:i') }}</small>
                                        </div>
                                        <div class="small" style="white-space: pre-wrap;">
                                            {!! $reply->message !!}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const initializeEditor = () => {
                if (!window.tinymce) {
                    return;
                }

                tinymce.init({
                    selector: '#contact-reply-editor',
                    menubar: false,
                    height: 320,
                    plugins: 'link lists image code table media',
                    toolbar: 'undo redo | styles | link image table | bold italic underline forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | code media',
                    language: 'vi',
                    image_dimensions: false,
                    images_upload_handler: function (blobInfo, progress) {
                        return new Promise(function (resolve, reject) {
                            const formData = new FormData();
                            formData.append('file', blobInfo.blob(), blobInfo.filename());
                            formData.append('_token', '{{ csrf_token() }}');

                            fetch('{{ route('admin.contacts.editor.upload-image') }}', {
                                method: 'POST',
                                body: formData,
                                credentials: 'same-origin',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            })
                                .then(function (response) {
                                    if (!response.ok) {
                                        throw new Error('Upload failed');
                                    }

                                    return response.json();
                                })
                                .then(function (data) {
                                    if (data && data.location) {
                                        resolve(data.location);
                                    } else {
                                        reject('Không thể tải ảnh lên.');
                                    }
                                })
                                .catch(function () {
                                    reject('Không thể tải ảnh lên.');
                                });
                        });
                    },
                    setup(editor) {
                        editor.on('change keyup', () => editor.save());
                    },
                });
            };

            initializeEditor();

            const replyForm = document.getElementById('contact-reply-form');
            if (replyForm) {
                replyForm.addEventListener('submit', function (event) {
                    if (!window.tinymce) {
                        return;
                    }

                    const editor = tinymce.get('contact-reply-editor');
                    if (!editor) {
                        return;
                    }

                    const plain = editor.getContent({ format: 'text' }).trim();
                    if (plain.length === 0) {
                        event.preventDefault();
                        editor.focus();
                        alert('Vui lòng nhập nội dung phản hồi.');
                        return;
                    }

                    editor.save();
                });
            }
        });
    </script>
@endpush

