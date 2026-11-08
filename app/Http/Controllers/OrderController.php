<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ImprovedController;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class OrderController extends ImprovedController
{
    protected $orderService;

    /**
     * Create a new controller instance.
     *
     * @param OrderService $orderService
     */
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Display a listing of the user's orders.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $orders = $this->orderService->getUserOrders();

            // Load relationships for resource
            $orders->load(['orderItems.product', 'orderItems.variant', 'payment']);

            return $this->respondWithSuccess('Orders retrieved successfully', 200, OrderResource::collection($orders));
        } catch (\Exception $e) {
            return $this->respondWithError('Error retrieving orders: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified order.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $orderNumber)
    {
        try {
            $order = $this->orderService->getUserOrderByNumber($orderNumber, Auth::id());

            // Load relationships for resource
            $order->load(['orderItems.product', 'orderItems.variant', 'payment']);

            // Add computed fields
            $order->item_count = $order->orderItems->count();
            $order->total_quantity = $order->orderItems->sum('quantity');
            $order->can_cancel = $order->status === 'pending';

            return $this->respondWithSuccess('Order retrieved successfully', 200, new OrderResource($order));
        } catch (ModelNotFoundException $e) {
            return $this->respondWithError('Order not found', 404);
        } catch (\Exception $e) {
            return $this->respondWithError('Error retrieving order: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cancel the specified order.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(string $orderNumber)
    {
        try {
            $order = $this->orderService->getUserOrderByNumber($orderNumber, Auth::id());

            // Check if the order can be cancelled
            if ($order->status !== 'pending') {
                return $this->respondWithError('Only pending orders can be cancelled', 400);
            }

            $order = $this->orderService->cancelUserOrderByNumber($orderNumber, Auth::id());

            // Load relationships for resource
            $order->load(['orderItems.product', 'orderItems.variant', 'payment']);

            // Add computed fields
            $order->item_count = $order->orderItems->count();
            $order->total_quantity = $order->orderItems->sum('quantity');
            $order->can_cancel = false; // Just cancelled

            return $this->respondWithSuccess('Order cancelled successfully', 200, new OrderResource($order));
        } catch (ModelNotFoundException $e) {
            return $this->respondWithError('Order not found', 404);
        } catch (\Exception $e) {
            if (in_array($e->getMessage(), [
                'Only pending orders can be cancelled',
                'Paid orders cannot be cancelled until refunds are supported',
            ], true)) {
                return $this->respondWithError($e->getMessage(), 409);
            }

            return $this->respondWithError('Error cancelling order: ' . $e->getMessage(), 500);
        }
    }


}
