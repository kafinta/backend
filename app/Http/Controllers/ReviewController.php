<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Review;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;

class ReviewController extends Controller
{
    // List reviews for a product
    public function index($productId)
    {
        $product = Product::findOrFail($productId);
        $reviews = $product->reviews()->where('status', 'active')->with(['user', 'replies.user'])->latest()->paginate(10);
        return response()->json([
            'reviews' => $reviews->map(fn($r) => new \App\Http\Resources\ReviewResource($r)),
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ]
        ]);
    }

    // Create a review (only if purchased and not already reviewed)
    public function store(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);
        $user = Auth::user();
        // Check if user purchased and received the product
        if (!$this->userCanReview($user, $product)) {
            return response()->json(['message' => 'You can only review products you have purchased and received.'], 403);
        }
        // Prevent duplicate reviews
        if (Review::where('product_id', $product->id)->where('user_id', $user->id)->whereNull('parent_review_id')->exists()) {
            return response()->json(['message' => 'You have already reviewed this product.'], 409);
        }
        $validator = Validator::make($request->all(), [
            'value_for_money' => 'required|numeric|min:1|max:5',
            'true_to_description' => 'required|numeric|min:1|max:5',
            'product_quality' => 'required|numeric|min:1|max:5',
            'shipping' => 'required|numeric|min:1|max:5',
            'comment' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'string', // Assume base64 or URL, handle upload elsewhere
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $review = Review::create(array_merge($validator->validated(), [
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]));
        return new \App\Http\Resources\ReviewResource($review->load('user', 'replies.user'));
    }

    // Update own review
    public function update(Request $request, $id)
    {
        $review = Review::findOrFail($id);
        $user = Auth::user();
        if ($review->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $validator = Validator::make($request->all(), [
            'value_for_money' => 'sometimes|numeric|min:1|max:5',
            'true_to_description' => 'sometimes|numeric|min:1|max:5',
            'product_quality' => 'sometimes|numeric|min:1|max:5',
            'shipping' => 'sometimes|numeric|min:1|max:5',
            'comment' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'string',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $review->update($validator->validated());
        return new \App\Http\Resources\ReviewResource($review->fresh()->load('user', 'replies.user'));
    }

    // Delete own review
    public function destroy($id)
    {
        $review = Review::findOrFail($id);
        $user = Auth::user();
        $this->authorize('delete', $review);
        $review->delete();
        // TODO: Notify user/admin of deletion if needed
        return response()->json(['message' => 'Review deleted successfully.']);
    }

    // Reply to a review
    public function reply(Request $request, $reviewId)
    {
        $parent = Review::findOrFail($reviewId);
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'comment' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $reply = Review::create([
            'product_id' => $parent->product_id,
            'user_id' => $user->id,
            'parent_review_id' => $parent->id,
            'comment' => $request->comment,
            'value_for_money' => 0,
            'true_to_description' => 0,
            'product_quality' => 0,
            'shipping' => 0,
            'status' => 'active',
        ]);
        return new \App\Http\Resources\ReviewResource($reply->load('user'));
    }

    // Flag a review
    public function flag(Request $request, $id)
    {
        $review = Review::findOrFail($id);
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $review->update([
            'status' => 'flagged',
            'flagged_by' => $user->id,
            'flag_reason' => $request->reason,
        ]);
        return response()->json(['message' => 'Review flagged for moderation.']);
    }

    // Helpful voting
    public function helpful(Request $request, $id)
    {
        $review = Review::findOrFail($id);
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'vote' => 'required|in:up,down',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        // Upsert the vote
        $vote = $request->vote;
        $existing = $review->helpfulVotes()->where('user_id', $user->id)->first();
        if ($existing) {
            if ($existing->vote === $vote) {
                return response()->json(['message' => 'You have already voted.'], 409);
            }
            $existing->update(['vote' => $vote]);
        } else {
            $review->helpfulVotes()->create([
                'user_id' => $user->id,
                'vote' => $vote,
            ]);
        }
        // Optionally notify review author (stub)
        // $review->user->notify(...)
        return response()->json([
            'message' => 'Vote recorded.',
            'helpful_votes' => $review->fresh()->helpful_votes_count,
        ]);
    }

    // Helper: check if user can review (has purchased and received)
    protected function userCanReview($user, $product)
    {
        // Check if user has an order with this product, status delivered
        return $user->orders()
            ->whereHas('orderItems', function($q) use ($product) {
                $q->where('product_id', $product->id);
            })
            ->where('status', 'delivered')
            ->exists();
    }
}
