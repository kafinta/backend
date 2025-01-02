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
      return $user->is_seller;
    }

    public function update(User $user, Product $product)
    {
      return $user->is_seller && $user->id === $product->user_id;
    }

    public function delete(User $user, Product $product)
    {
      return $user->is_seller && $user->id === $product->user_id;
    }
} 