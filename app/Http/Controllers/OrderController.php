<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
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

            return $this->respondWithSuccess('Orders retrieved successfully', 200, [
                'orders' => $orders
            ]);
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
    public function show($id)
    {
        try {
            $order = $this->orderService->getOrder($id);

            // Check if the order belongs to the authenticated user
            if ($order->user_id !== Auth::id()) {
                return $this->respondWithError('You do not have permission to view this order', 403);
            }

            return $this->respondWithSuccess('Order retrieved successfully', 200, [
                'order' => $order,
                'items' => $order->orderItems
            ]);
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
    public function cancel($id)
    {
        try {
            $order = $this->orderService->getOrder($id);

            // Check if the order belongs to the authenticated user
            if ($order->user_id !== Auth::id()) {
                return $this->respondWithError('You do not have permission to cancel this order', 403);
            }

            // Check if the order can be cancelled
            if ($order->status !== 'pending') {
                return $this->respondWithError('Only pending orders can be cancelled', 400);
            }

            $order = $this->orderService->cancelOrder($id);

            return $this->respondWithSuccess('Order cancelled successfully', 200, [
                'order' => $order
            ]);
        } catch (\Exception $e) {
            return $this->respondWithError('Error cancelling order: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Return a success response
     *
     * @param string $message
     * @param int $statusCode
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithSuccess($message, $statusCode = 200, $data = [])
    {
        return response()->json([
            'status' => 'success',
            'status_code' => $statusCode,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Return an error response
     *
     * @param string|array $message
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithError($message, $statusCode = 400)
    {
        return response()->json([
            'status' => 'fail',
            'status_code' => $statusCode,
            'message' => $message
        ], $statusCode);
    }
}
