<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'seller_approval_status',
        'seller_approved_at',
        'seller_approved_by',
        'seller_rejection_reason',
        'seller_applied_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'seller_approval_status' => 'boolean',
            'seller_approved_at' => 'datetime',
            'seller_applied_at' => 'datetime',
        ];
    }
    
    /**
     * Get the user who approved this seller.
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'seller_approved_by');
    }
    
    /**
     * Check if user is an approved seller.
     */
    public function isApprovedSeller(): bool
    {
        return $this->hasRole('Seller') && $this->seller_approval_status == 1;
    }
    
    public function isPendingSeller(): bool
    {
        return $this->hasRole('Seller') && $this->seller_approval_status == 0 && empty($this->seller_rejection_reason);
    }
    
    public function isRejectedSeller(): bool
    {
        return $this->hasRole('Seller') && $this->seller_approval_status == 0 && !empty($this->seller_rejection_reason);
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function sellerInformation()
    {
        return $this->hasOne(SellerInformation::class, 'user_id');
    }

    public function hasSellerInformation(): bool
    {
        return $this->sellerInformation()->exists();
    }

    public function userInformation()
    {
        return $this->hasOne(UserInformation::class, 'user_id');
    }

    public function hasUserInformation(): bool
    {
        return $this->userInformation()->exists();
    }

    public function cartItems()
    {
        return $this->hasMany(Cart::class, 'user_id');
    }

    public function cart()
    {
        return $this->hasMany(Cart::class, 'user_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }
}
