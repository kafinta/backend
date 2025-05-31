<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Services\SellerOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SellerOrderController extends ImprovedController
{
    protected $sellerOrderService;

    /**
     * Create a new controller instance.
     *
     * @param SellerOrderService $sellerOrderService
     */
    public function __construct(SellerOrderService $sellerOrderService)
    {
        $this->sellerOrderService = $sellerOrderService;
        $this->middleware('auth:sanctum');
        $this->middleware('role:seller');
    }

    /**
     * Display a listing of orders containing the seller's products.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $orders = $this->sellerOrderService->getSellerOrders();

            // Load additional relationships for resource
            $orders->load(['orderItems.product', 'orderItems.variant', 'user']);

            return $this->respondWithSuccess('Seller orders retrieved successfully', 200, OrderResource::collection($orders));
        } catch (\Exception $e) {
            return $this->respondWithError('Error retrieving seller orders: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified order with items sold by the seller.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $order = $this->sellerOrderService->getSellerOrder($id);

            // Load relationships for resource
            $order->load(['orderItems.product', 'orderItems.variant', 'user']);

            // Add computed fields
            $order->item_count = $order->orderItems->count();
            $order->total_quantity = $order->orderItems->sum('quantity');

            return $this->respondWithSuccess('Seller order retrieved successfully', 200, new OrderResource($order));
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'No query results for model')) {
                return $this->respondWithError('Order not found', 404);
            }

            return $this->respondWithError('Error retrieving seller order: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update the status of the seller's items in an order.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|string|in:processing,shipped,delivered,cancelled'
            ]);

            if ($validator->fails()) {
                return $this->respondWithError($validator->errors(), 422);
            }

            $order = $this->sellerOrderService->updateOrderStatus($id, $request->input('status'));

            // Load relationships for resource
            $order->load(['orderItems.product', 'orderItems.variant', 'user']);

            // Add computed fields
            $order->item_count = $order->orderItems->count();
            $order->total_quantity = $order->orderItems->sum('quantity');

            return $this->respondWithSuccess('Order status updated successfully', 200, new OrderResource($order));
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'No query results for model')) {
                return $this->respondWithError('Order not found', 404);
            }

            return $this->respondWithError('Error updating order status: ' . $e->getMessage(), 500);
        }
    }
}
