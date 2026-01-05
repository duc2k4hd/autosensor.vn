<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Notification;

class NotificationService
{
    /**
     * Tạo thông báo cho một account cụ thể
     */
    public function create(
        ?int $accountId,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        ?string $icon = null,
        string $priority = Notification::PRIORITY_NORMAL,
        ?array $data = null
    ): Notification {
        return Notification::create([
            'account_id' => $accountId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'icon' => $icon,
            'priority' => $priority,
            'data' => $data,
            'is_read' => false,
        ]);
    }

    /**
     * Tạo thông báo cho tất cả admin
     */
    public function notifyAdmins(
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        ?string $icon = null,
        string $priority = Notification::PRIORITY_NORMAL,
        ?array $data = null
    ): Notification {
        return $this->create(null, $type, $title, $message, $link, $icon, $priority, $data);
    }

    /**
     * Tạo thông báo đơn hàng mới
     */
    public function notifyNewOrder(int $orderId, string $orderCode, float $total): Notification
    {
        return $this->notifyAdmins(
            Notification::TYPE_ORDER_NEW,
            'Đơn hàng mới',
            "Đơn hàng #{$orderCode} với tổng tiền ".number_format($total, 0, ',', '.').' đ',
            route('admin.orders.show', $orderId),
            'fa-shopping-cart',
            Notification::PRIORITY_HIGH,
            ['order_id' => $orderId, 'order_code' => $orderCode, 'total' => $total]
        );
    }

    /**
     * Tạo thông báo thay đổi trạng thái đơn hàng
     */
    public function notifyOrderStatusChange(int $accountId, int $orderId, string $orderCode, string $status): Notification
    {
        $statusLabels = [
            'pending' => 'Chờ xử lý',
            'processing' => 'Đang xử lý',
            'shipping' => 'Đang giao hàng',
            'completed' => 'Đã hoàn thành',
            'cancelled' => 'Đã hủy',
        ];

        $statusLabel = $statusLabels[$status] ?? $status;

        return $this->create(
            $accountId,
            Notification::TYPE_ORDER_STATUS,
            'Trạng thái đơn hàng thay đổi',
            "Đơn hàng #{$orderCode} của bạn đã chuyển sang trạng thái: {$statusLabel}",
            route('client.orders.show', ['code' => $orderCode]),
            'fa-shopping-cart',
            Notification::PRIORITY_NORMAL,
            ['order_id' => $orderId, 'order_code' => $orderCode, 'status' => $status]
        );
    }

    /**
     * Tạo thông báo comment mới cần duyệt
     */
    public function notifyNewComment(int $commentId, string $commentType, int $objectId, string $commenterName): Notification
    {
        $typeLabel = $commentType === 'product' ? 'sản phẩm' : 'bài viết';
        $route = $commentType === 'product'
            ? route('admin.products.edit', $objectId)
            : route('admin.posts.edit', $objectId);

        return $this->notifyAdmins(
            Notification::TYPE_COMMENT_NEW,
            'Bình luận mới cần duyệt',
            "{$commenterName} đã bình luận trên {$typeLabel} #{$objectId}",
            route('admin.comments.show', $commentId),
            'fa-comment',
            Notification::PRIORITY_NORMAL,
            ['comment_id' => $commentId, 'type' => $commentType, 'object_id' => $objectId]
        );
    }

    /**
     * Tạo thông báo comment đã được duyệt
     */
    public function notifyCommentApproved(int $accountId, int $commentId, string $commentType, int $objectId): Notification
    {
        $typeLabel = $commentType === 'product' ? 'sản phẩm' : 'bài viết';
        $route = $commentType === 'product'
            ? route('client.product.detail', ['slug' => 'slug'])
            : route('client.blog.show', ['post' => 'slug']);

        return $this->create(
            $accountId,
            Notification::TYPE_COMMENT_APPROVED,
            'Bình luận đã được duyệt',
            "Bình luận của bạn trên {$typeLabel} đã được duyệt và hiển thị",
            null, // Link sẽ được set sau khi có slug
            'fa-check-circle',
            Notification::PRIORITY_LOW,
            ['comment_id' => $commentId, 'type' => $commentType, 'object_id' => $objectId]
        );
    }

    /**
     * Tạo thông báo contact mới
     */
    public function notifyNewContact(int $contactId, string $contactName, string $subject): Notification
    {
        return $this->notifyAdmins(
            Notification::TYPE_CONTACT_NEW,
            'Liên hệ mới',
            "{$contactName} đã gửi liên hệ: {$subject}",
            route('admin.contacts.show', $contactId),
            'fa-envelope',
            Notification::PRIORITY_NORMAL,
            ['contact_id' => $contactId]
        );
    }

    /**
     * Tạo thông báo voucher mới
     */
    public function notifyNewVoucher(int $accountId, string $voucherCode, float $discount): Notification
    {
        return $this->create(
            $accountId,
            Notification::TYPE_VOUCHER_NEW,
            'Voucher mới',
            "Bạn có voucher mới: {$voucherCode} giảm ".number_format($discount, 0, ',', '.').' đ',
            route('client.shop.index'),
            'fa-tag',
            Notification::PRIORITY_NORMAL,
            ['voucher_code' => $voucherCode, 'discount' => $discount]
        );
    }

    /**
     * Tạo thông báo flash sale bắt đầu
     */
    public function notifyFlashSaleStart(int $accountId, string $flashSaleName, string $startTime): Notification
    {
        return $this->create(
            $accountId,
            Notification::TYPE_FLASH_SALE_START,
            'Flash Sale bắt đầu',
            "Flash Sale '{$flashSaleName}' đã bắt đầu lúc {$startTime}",
            route('client.flash-sale.index'),
            'fa-bolt',
            Notification::PRIORITY_HIGH,
            ['flash_sale_name' => $flashSaleName, 'start_time' => $startTime]
        );
    }

    /**
     * Thông báo sản phẩm sắp hết hàng / hết hàng cho admin
     */
    public function notifyStockAlert(int $productId, string $sku, string $name, int $stock, bool $outOfStock = false): Notification
    {
        $type = $outOfStock ? Notification::TYPE_STOCK_OUT : Notification::TYPE_STOCK_LOW;
        $title = $outOfStock ? 'Sản phẩm đã hết hàng' : 'Sản phẩm sắp hết hàng';
        $message = $outOfStock
            ? "Sản phẩm [{$sku}] {$name} đã hết hàng (tồn kho: 0)."
            : "Sản phẩm [{$sku}] {$name} sắp hết hàng (tồn kho: {$stock}).";

        return $this->notifyAdmins(
            $type,
            $title,
            $message,
            route('admin.products.inventory', $productId),
            'fa-box-open',
            $outOfStock ? Notification::PRIORITY_HIGH : Notification::PRIORITY_NORMAL,
            [
                'product_id' => $productId,
                'sku' => $sku,
                'name' => $name,
                'stock' => $stock,
                'out_of_stock' => $outOfStock,
            ]
        );
    }

    /**
     * Thông báo biến thể sản phẩm sắp hết hàng / hết hàng cho admin
     */
    public function notifyVariantStockAlert(int $variantId, int $productId, string $sku, string $productName, string $variantName, int $stock, bool $outOfStock = false): Notification
    {
        $type = $outOfStock ? Notification::TYPE_STOCK_OUT : Notification::TYPE_STOCK_LOW;
        $title = $outOfStock ? 'Biến thể đã hết hàng' : 'Biến thể sắp hết hàng';
        $fullName = "{$productName} - {$variantName}";
        $message = $outOfStock
            ? "Biến thể [{$sku}] {$fullName} đã hết hàng (tồn kho: 0)."
            : "Biến thể [{$sku}] {$fullName} sắp hết hàng (tồn kho: {$stock}).";

        return $this->notifyAdmins(
            $type,
            $title,
            $message,
            route('admin.products.inventory', $productId),
            'fa-box-open',
            $outOfStock ? Notification::PRIORITY_HIGH : Notification::PRIORITY_NORMAL,
            [
                'variant_id' => $variantId,
                'product_id' => $productId,
                'sku' => $sku,
                'product_name' => $productName,
                'variant_name' => $variantName,
                'stock' => $stock,
                'out_of_stock' => $outOfStock,
            ]
        );
    }

    /**
     * Đánh dấu thông báo đã đọc
     */
    public function markAsRead(int $notificationId, ?int $accountId = null): bool
    {
        $notification = Notification::findOrFail($notificationId);

        // Kiểm tra quyền: chỉ account sở hữu hoặc admin mới đọc được
        if ($accountId && $notification->account_id && $notification->account_id !== $accountId) {
            return false;
        }

        return $notification->markAsRead();
    }

    /**
     * Đánh dấu tất cả thông báo đã đọc
     */
    public function markAllAsRead(?int $accountId = null): int
    {
        $query = Notification::where('is_read', false);

        if ($accountId) {
            $query->where('account_id', $accountId);
        } else {
            $query->whereNull('account_id');
        }

        return $query->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Xóa thông báo
     */
    public function delete(int $notificationId, ?int $accountId = null): bool
    {
        $notification = Notification::findOrFail($notificationId);

        // Kiểm tra quyền
        if ($accountId && $notification->account_id && $notification->account_id !== $accountId) {
            return false;
        }

        return $notification->delete();
    }

    /**
     * Xóa tất cả thông báo đã đọc
     */
    public function deleteRead(?int $accountId = null): int
    {
        $query = Notification::where('is_read', true);

        if ($accountId) {
            $query->where('account_id', $accountId);
        } else {
            $query->whereNull('account_id');
        }

        return $query->delete();
    }

    /**
     * Lấy số lượng thông báo chưa đọc
     */
    public function getUnreadCount(?int $accountId = null): int
    {
        $query = Notification::where('is_read', false);

        if ($accountId) {
            $query->where('account_id', $accountId);
        } else {
            $query->whereNull('account_id');
        }

        return $query->count();
    }

    /**
     * Lấy danh sách thông báo
     */
    public function getNotifications(?int $accountId = null, int $limit = 20, bool $unreadOnly = false)
    {
        $query = Notification::query();

        if ($accountId) {
            $query->where('account_id', $accountId);
        } else {
            $query->whereNull('account_id');
        }

        if ($unreadOnly) {
            $query->where('is_read', false);
        }

        return $query->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
