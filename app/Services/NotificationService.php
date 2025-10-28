<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Cart;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Create and send a notification
     *
     * @param int $userId
     * @param string $type
     * @param string $title
     * @param string $message
     * @param array $data
     * @return Notification|null
     */
    public function createNotification(int $userId, string $type, string $title, string $message, array $data = []): ?Notification
    {
        try {
            // Check if app notifications are enabled for this user and type
            if (!NotificationPreference::isAppEnabled($userId, $type)) {
                Log::info('App notification disabled for user', [
                    'user_id' => $userId,
                    'type' => $type
                ]);
                return null;
            }

            // Create the notification
            $notification = Notification::create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
                'sent_at' => now(),
            ]);

            // Send email notification if enabled
            if (NotificationPreference::isEmailEnabled($userId, $type)) {
                $this->sendEmailNotification($notification);
            }

            Log::info('Notification created', [
                'notification_id' => $notification->id,
                'user_id' => $userId,
                'type' => $type
            ]);

            return $notification;
        } catch (\Exception $e) {
            Log::error('Failed to create notification', [
                'user_id' => $userId,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Send email notification
     *
     * @param Notification $notification
     * @return bool
     */
    protected function sendEmailNotification(Notification $notification): bool
    {
        try {
            $user = $notification->user;
            if (!$user || !$user->email) {
                return false;
            }

            // Send notification email using EmailService
            return $this->emailService->sendNotification(
                $user,
                $notification->title,
                'notification',
                [
                    'message' => $notification->message,
                    'type' => $notification->type,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to send email notification', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Create order placed notification for customer
     *
     * @param Order $order
     * @return Notification|null
     */
    public function notifyOrderPlaced(Order $order): ?Notification
    {
        $title = "Order Placed Successfully";
        $message = "Your order #{$order->order_number} has been placed successfully. Total: ₦{$order->total}";

        $data = [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'total' => $order->total,
            'action_url' => "/orders/{$order->id}"
        ];

        return $this->createNotification(
            $order->user_id,
            Notification::TYPE_ORDER_PLACED,
            $title,
            $message,
            $data
        );
    }

    /**
     * Create order status change notification
     *
     * @param Order $order
     * @param string $oldStatus
     * @param string $newStatus
     * @return Notification|null
     */
    public function notifyOrderStatusChange(Order $order, string $oldStatus, string $newStatus): ?Notification
    {
        $statusMessages = [
            'confirmed' => 'Your order has been confirmed and is being prepared.',
            'processing' => 'Your order is now being processed.',
            'shipped' => 'Your order has been shipped and is on its way to you.',
            'delivered' => 'Your order has been delivered successfully.',
            'cancelled' => 'Your order has been cancelled.'
        ];

        $statusTypes = [
            'confirmed' => Notification::TYPE_ORDER_CONFIRMED,
            'processing' => Notification::TYPE_ORDER_PROCESSING,
            'shipped' => Notification::TYPE_ORDER_SHIPPED,
            'delivered' => Notification::TYPE_ORDER_DELIVERED,
            'cancelled' => Notification::TYPE_ORDER_CANCELLED,
        ];

        $title = "Order Status Updated";
        $message = "Order #{$order->order_number}: " . ($statusMessages[$newStatus] ?? "Status updated to {$newStatus}");
        $type = $statusTypes[$newStatus] ?? Notification::TYPE_ORDER_CONFIRMED;

        $data = [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'action_url' => "/orders/{$order->id}"
        ];

        return $this->createNotification(
            $order->user_id,
            $type,
            $title,
            $message,
            $data
        );
    }

    /**
     * Create new order notification for seller
     *
     * @param Order $order
     * @param int $sellerId
     * @return Notification|null
     */
    public function notifySellerNewOrder(Order $order, int $sellerId): ?Notification
    {
        $title = "New Order Received";
        $message = "You have received a new order #{$order->order_number}. Please review and confirm the order.";

        $data = [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->shipping_name,
            'action_url' => "/seller/orders/{$order->id}"
        ];

        return $this->createNotification(
            $sellerId,
            Notification::TYPE_SELLER_NEW_ORDER,
            $title,
            $message,
            $data
        );
    }

    /**
     * Create abandoned cart notification
     *
     * @param Cart $cart
     * @return Notification|null
     */
    public function notifyAbandonedCart(Cart $cart): ?Notification
    {
        if (!$cart->user_id) {
            return null; // Can't notify guest users
        }

        $itemCount = $cart->cartItems->count();
        $title = "Don't forget your cart!";
        $message = "You have {$itemCount} item(s) waiting in your cart. Complete your purchase before they're gone!";

        $data = [
            'cart_id' => $cart->id,
            'item_count' => $itemCount,
            'action_url' => "/cart"
        ];

        return $this->createNotification(
            $cart->user_id,
            Notification::TYPE_CART_ABANDONED,
            $title,
            $message,
            $data
        );
    }

    /**
     * Create low stock notification for seller
     *
     * @param Product $product
     * @param int $currentStock
     * @param int $threshold
     * @return Notification|null
     */
    public function notifyLowStock(Product $product, int $currentStock, int $threshold = 5): ?Notification
    {
        $title = "Low Stock Alert";
        $message = "Your product '{$product->name}' is running low on stock. Current stock: {$currentStock}";

        $data = [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'current_stock' => $currentStock,
            'threshold' => $threshold,
            'action_url' => "/seller/products/{$product->id}/edit"
        ];

        return $this->createNotification(
            $product->user_id,
            Notification::TYPE_PRODUCT_LOW_STOCK,
            $title,
            $message,
            $data
        );
    }

    /**
     * Create out of stock notification for seller
     *
     * @param Product $product
     * @return Notification|null
     */
    public function notifyOutOfStock(Product $product): ?Notification
    {
        $title = "Product Out of Stock";
        $message = "Your product '{$product->name}' is now out of stock. Please restock to continue selling.";

        $data = [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'action_url' => "/seller/products/{$product->id}/edit"
        ];

        return $this->createNotification(
            $product->user_id,
            Notification::TYPE_PRODUCT_OUT_OF_STOCK,
            $title,
            $message,
            $data
        );
    }

    /**
     * Create product approval notification for seller
     *
     * @param Product $product
     * @param bool $approved
     * @return Notification|null
     */
    public function notifyProductApproval(Product $product, bool $approved): ?Notification
    {
        if ($approved) {
            $title = "Product Approved";
            $message = "Your product '{$product->name}' has been approved and is now live on the marketplace.";
            $type = Notification::TYPE_SELLER_PRODUCT_APPROVED;
        } else {
            $title = "Product Denied";
            $message = "Your product '{$product->name}' has been denied. Please review and resubmit.";
            $type = Notification::TYPE_SELLER_PRODUCT_DENIED;
        }

        $data = [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'approved' => $approved,
            'action_url' => "/seller/products/{$product->id}"
        ];

        return $this->createNotification(
            $product->user_id,
            $type,
            $title,
            $message,
            $data
        );
    }

    /**
     * Create first product submission notification for seller
     *
     * @param Product $product
     * @return Notification|null
     */
    public function notifyFirstProductSubmitted(Product $product): ?Notification
    {
        $title = "First Product Submitted!";
        $message = "Congratulations! You've submitted your first product '{$product->name}'. It's now under review by our team.";

        $data = [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'is_first_product' => true,
            'action_url' => "/seller/products/{$product->id}"
        ];

        return $this->createNotification(
            $product->user_id,
            Notification::TYPE_SELLER_PRODUCT_APPROVED, // We'll use the same type but with different data
            $title,
            $message,
            $data
        );
    }

    /**
     * Create product denied notification with detailed feedback
     *
     * @param Product $product
     * @param string $reason
     * @return Notification|null
     */
    public function notifyProductDenied(Product $product, string $reason = ''): ?Notification
    {
        $title = "Product Denied";
        $baseMessage = "Your product '{$product->name}' has been denied and needs revision before it can be approved.";

        if ($reason) {
            $message = $baseMessage . " Reason: {$reason}";
        } else {
            $message = $baseMessage . " Please review our product guidelines and resubmit.";
        }

        $data = [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'denial_reason' => $reason,
            'action_url' => "/seller/products/{$product->id}/edit",
            'guidelines_url' => "/seller/guidelines"
        ];

        return $this->createNotification(
            $product->user_id,
            Notification::TYPE_SELLER_PRODUCT_DENIED,
            $title,
            $message,
            $data
        );
    }

    /**
     * Get user notifications with pagination
     *
     * @param int $userId
     * @param int $perPage
     * @param bool $unreadOnly
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUserNotifications(int $userId, int $perPage = 20, bool $unreadOnly = false)
    {
        $query = Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        if ($unreadOnly) {
            $query->unread();
        }

        return $query->paginate($perPage);
    }

    /**
     * Mark notification as read
     *
     * @param int $notificationId
     * @param int $userId
     * @return bool
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if (!$notification) {
            return false;
        }

        return $notification->markAsRead();
    }

    /**
     * Mark all notifications as read for user
     *
     * @param int $userId
     * @return int Number of notifications marked as read
     */
    public function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Delete notification
     *
     * @param int $notificationId
     * @param int $userId
     * @return bool
     */
    public function deleteNotification(int $notificationId, int $userId): bool
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();

        if (!$notification) {
            return false;
        }

        return $notification->delete();
    }

    /**
     * Get unread notification count for user
     *
     * @param int $userId
     * @return int
     */
    public function getUnreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->unread()
            ->count();
    }
}
