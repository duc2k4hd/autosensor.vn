<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Banner;
use App\Models\Contact;
use App\Models\EmailAccount;
use App\Models\FlashSale;
use App\Models\Image;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class TrashController extends Controller
{
    /**
     * Danh sách model hỗ trợ khôi phục trong thùng rác.
     *
     * @var array<string, array>
     */
    protected array $trashables = [
        'accounts' => [
            'label' => 'Tài khoản',
            'model' => Account::class,
            'searchable' => ['name', 'email', 'phone'],
            'columns' => [
                'name' => 'Họ tên',
                'email' => 'Email',
                'phone' => 'Số điện thoại',
                'role' => 'Vai trò',
                'status' => 'Trạng thái',
            ],
        ],
        'posts' => [
            'label' => 'Bài viết',
            'model' => Post::class,
            'searchable' => ['title', 'slug', 'meta_title'],
            'columns' => [
                'title' => 'Tiêu đề',
                'slug' => 'Slug',
                'status' => 'Trạng thái',
                'published_at' => 'Xuất bản',
            ],
        ],
        'banners' => [
            'label' => 'Banner',
            'model' => Banner::class,
            'searchable' => ['title', 'position'],
            'columns' => [
                'title' => 'Tiêu đề',
                'position' => 'Vị trí',
                'is_active' => 'Kích hoạt',
                'start_at' => 'Bắt đầu',
                'end_at' => 'Kết thúc',
            ],
        ],
        'images' => [
            'label' => 'Hình ảnh',
            'model' => Image::class,
            'searchable' => ['title', 'alt', 'url'],
            'columns' => [
                'title' => 'Tiêu đề',
                'alt' => 'Alt text',
                'url' => 'Đường dẫn',
            ],
        ],
        'email_accounts' => [
            'label' => 'Tài khoản Email',
            'model' => EmailAccount::class,
            'searchable' => ['email', 'name', 'description'],
            'columns' => [
                'email' => 'Email',
                'name' => 'Tên hiển thị',
                'description' => 'Mô tả',
            ],
        ],
        'flash_sales' => [
            'label' => 'Flash Sale',
            'model' => FlashSale::class,
            'searchable' => ['title', 'tag', 'description'],
            'columns' => [
                'title' => 'Tiêu đề',
                'tag' => 'Tag',
                'start_time' => 'Bắt đầu',
                'end_time' => 'Kết thúc',
            ],
        ],
        'contacts' => [
            'label' => 'Liên hệ',
            'model' => Contact::class,
            'searchable' => ['name', 'email', 'phone', 'subject'],
            'columns' => [
                'name' => 'Họ tên',
                'email' => 'Email',
                'phone' => 'Số điện thoại',
                'subject' => 'Chủ đề',
                'status' => 'Trạng thái',
            ],
        ],
    ];

    /**
     * Trang danh sách thùng rác.
     */
    public function index(Request $request)
    {
        $type = $request->get('type', array_key_first($this->trashables));
        $trashable = $this->getTrashableByType($type);

        $query = $trashable['model']::onlyTrashed();

        // Tìm kiếm
        if ($request->filled('q')) {
            $keyword = trim($request->q);
            $columns = $trashable['searchable'] ?? [];
            if (! empty($columns)) {
                $query->where(function ($q) use ($columns, $keyword) {
                    foreach ($columns as $column) {
                        $q->orWhere($column, 'like', "%{$keyword}%");
                    }
                });
            }
        }

        // Lọc theo ngày xóa
        if ($request->filled('deleted_from')) {
            $query->whereDate('deleted_at', '>=', $request->deleted_from);
        }
        if ($request->filled('deleted_to')) {
            $query->whereDate('deleted_at', '<=', $request->deleted_to);
        }

        $items = $query->latest('deleted_at')
            ->paginate($request->get('per_page', 15))
            ->appends($request->only('type', 'q', 'deleted_from', 'deleted_to', 'per_page'));

        $stats = collect($this->trashables)->mapWithKeys(function ($config, $key) {
            /** @var \Illuminate\Database\Eloquent\Model $model */
            $model = $config['model'];

            return [$key => $model::onlyTrashed()->count()];
        });

        return view('admins.trash.index', [
            'trashables' => $this->trashables,
            'stats' => $stats,
            'currentType' => $type,
            'items' => $items,
            'search' => $request->q,
            'filters' => $request->only('deleted_from', 'deleted_to', 'per_page'),
        ]);
    }

    /**
     * Khôi phục một bản ghi.
     */
    public function restore(Request $request, string $type, int $id)
    {
        $trashable = $this->getTrashableByType($type);

        $model = $trashable['model']::withTrashed()->findOrFail($id);
        $model->restore();

        return back()->with('success', "{$trashable['label']} đã được khôi phục.");
    }

    /**
     * Xóa vĩnh viễn một bản ghi.
     */
    public function forceDelete(Request $request, string $type, int $id)
    {
        $trashable = $this->getTrashableByType($type);

        $model = $trashable['model']::withTrashed()->findOrFail($id);
        $model->forceDelete();

        return back()->with('success', "{$trashable['label']} đã bị xóa vĩnh viễn.");
    }

    /**
     * Lấy cấu hình trashable theo type.
     */
    protected function getTrashableByType(?string $type): array
    {
        $type = $type ?? array_key_first($this->trashables);
        $trashable = Arr::get($this->trashables, $type);

        abort_if(! $trashable, 404);

        return $trashable + ['type' => $type];
    }

    /**
     * Khôi phục toàn bộ bản ghi đã xóa cho 1 type.
     */
    public function restoreAll(Request $request, string $type)
    {
        $trashable = $this->getTrashableByType($type);

        $trashable['model']::onlyTrashed()->restore();

        return back()->with('success', "Toàn bộ {$trashable['label']} trong thùng rác đã được khôi phục.");
    }

    /**
     * Xóa vĩnh viễn toàn bộ bản ghi đã xóa cho 1 type.
     */
    public function forceDeleteAll(Request $request, string $type)
    {
        $trashable = $this->getTrashableByType($type);

        $count = $trashable['model']::onlyTrashed()->count();
        $trashable['model']::onlyTrashed()->forceDelete();

        return back()->with('success', "Đã xóa vĩnh viễn {$count} {$trashable['label']} trong thùng rác.");
    }

    /**
     * Khôi phục nhiều bản ghi cùng lúc.
     */
    public function bulkRestore(Request $request, string $type)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer',
        ]);

        $trashable = $this->getTrashableByType($type);

        $count = $trashable['model']::withTrashed()
            ->whereIn('id', $request->ids)
            ->onlyTrashed()
            ->restore();

        return back()->with('success', "Đã khôi phục {$count} {$trashable['label']}.");
    }

    /**
     * Xóa vĩnh viễn nhiều bản ghi cùng lúc.
     */
    public function bulkForceDelete(Request $request, string $type)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer',
        ]);

        $trashable = $this->getTrashableByType($type);

        $models = $trashable['model']::withTrashed()
            ->whereIn('id', $request->ids)
            ->onlyTrashed()
            ->get();

        $count = $models->count();
        foreach ($models as $model) {
            $model->forceDelete();
        }

        return back()->with('success', "Đã xóa vĩnh viễn {$count} {$trashable['label']}.");
    }
}
