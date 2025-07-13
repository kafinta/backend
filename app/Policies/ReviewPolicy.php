<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Review;

class ReviewPolicy
{
    /**
     * Determine if the user can delete the review.
     */
    public function delete(User $user, Review $review)
    {
        return $user->isAdmin() || $user->id === $review->user_id;
    }
} 