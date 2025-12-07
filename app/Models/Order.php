<?php

namespace App\Models;

use App\Models\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'total_amount',
        'payment_status',
        'shipping_address',
        'InsertAt',
        'InsertUserId',
        'UpdateBy',
        'UpdateUserId',
        'DeleteBy',
        'DeleteUserId',
        'IsActive',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'shipping_address' => 'encrypted',
        'InsertAt' => 'datetime',
        'UpdateBy' => 'datetime',
        'DeleteBy' => 'datetime',
        'IsActive' => 'integer',
    ];

    protected $attributes = [
        'IsActive' => 1,
        'payment_status' => 'pending',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new ActiveScope);

        static::creating(function ($order) {
            if (is_null($order->InsertAt)) {
                $order->InsertAt = now();
            }
            if (is_null($order->InsertUserId) && auth()->check()) {
                $order->InsertUserId = auth()->id();
            }
        });

        static::updating(function ($order) {
            $order->UpdateBy = now();
            if (auth()->check()) {
                $order->UpdateUserId = auth()->id();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
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

    public function scopePendingPayment($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
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
}
