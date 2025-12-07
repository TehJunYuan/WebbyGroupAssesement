<?php

namespace App\Models;

use App\Models\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'book_id',
        'quantity',
        'InsertAt',
        'InsertUserId',
        'UpdateBy',
        'UpdateUserId',
        'DeleteBy',
        'DeleteUserId',
        'IsActive',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'InsertAt' => 'datetime',
        'UpdateBy' => 'datetime',
        'DeleteBy' => 'datetime',
        'IsActive' => 'integer',
    ];

    protected $attributes = [
        'IsActive' => 1,
        'quantity' => 1,
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new ActiveScope);

        static::creating(function ($cart) {
            if (is_null($cart->InsertAt)) {
                $cart->InsertAt = now();
            }
            if (is_null($cart->InsertUserId) && auth()->check()) {
                $cart->InsertUserId = auth()->id();
            }
        });

        static::updating(function ($cart) {
            $cart->UpdateBy = now();
            if (auth()->check()) {
                $cart->UpdateUserId = auth()->id();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function book()
    {
        return $this->belongsTo(Book::class, 'book_id');
    }

    public function insertedBy()
    {
        return $this->belongsTo(User::class, 'InsertUserId');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'UpdateUserId');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'DeleteUserId');
    }

    public function scopeActive($query)
    {
        return $query->where('IsActive', 1);
    }

    public function scopeInactive($query)
    {
        return $query->where('IsActive', -1);
    }

    public function softDelete()
    {
        $this->IsActive = -1;
        $this->DeleteBy = now();
        if (auth()->check()) {
            $this->DeleteUserId = auth()->id();
        }
        $this->save();
    }

    public function restore()
    {
        $this->IsActive = 1;
        $this->DeleteBy = null;
        $this->DeleteUserId = null;
        $this->save();
    }

    public function getSubtotalAttribute()
    {
        return $this->quantity * $this->book->price;
    }
}
