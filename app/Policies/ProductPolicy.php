<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Product;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    public function create(User $user)
    {
      return $user->hasRole('seller');
    }

    public function update(User $user, Product $product)
    {
      return $user->hasRole('seller') && $user->id === $product->user_id;
    }

    public function delete(User $user, Product $product)
    {
        // Admins can delete any product, sellers can only delete their own
      return $user->hasRole('admin') || 
      ($user->hasRole('seller') && $user->id === $product->user_id);
  }
} 