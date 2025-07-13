<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'user_id',
        'value_for_money',
        'true_to_description',
        'product_quality',
        'shipping',
        'comment',
        'images',
        'parent_review_id',
        'status',
        'flagged_by',
        'flag_reason',
    ];

    protected $casts = [
        'images' => 'array',
        'value_for_money' => 'float',
        'true_to_description' => 'float',
        'product_quality' => 'float',
        'shipping' => 'float',
        'status' => 'string',
    ];

    public function product() {
        return $this->belongsTo(\App\Models\Product::class);
    }

    public function user() {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function replies() {
        return $this->hasMany(self::class, 'parent_review_id');
    }

    public function parent() {
        return $this->belongsTo(self::class, 'parent_review_id');
    }

    public function flaggedBy() {
        return $this->belongsTo(\App\Models\User::class, 'flagged_by');
    }

    public function helpfulVotes()
    {
        return $this->hasMany(\App\Models\ReviewHelpfulVote::class);
    }

    public function getHelpfulVotesCountAttribute()
    {
        $up = $this->helpfulVotes()->where('vote', 'up')->count();
        $down = $this->helpfulVotes()->where('vote', 'down')->count();
        return $up - $down;
    }
}
