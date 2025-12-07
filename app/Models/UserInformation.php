<?php

namespace App\Models;

use App\Models\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class UserInformation extends Model
{
    use HasFactory, HasSlug;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'full_name',
        'slug',
        'address',
        'phone_number',
        'date_of_birth',
        'gender_id',
        'InsertAt',
        'InsertUserId',
        'UpdateBy',
        'UpdateUserId',
        'DeleteBy',
        'DeleteUserId',
        'IsActive',
    ];

    protected $casts = [
        'InsertAt' => 'datetime',
        'UpdateBy' => 'datetime',
        'DeleteBy' => 'datetime',
        'date_of_birth' => 'date',
        'IsActive' => 'integer',
        'phone_number' => 'encrypted',
        'address' => 'encrypted',
    ];

    protected $attributes = [
        'IsActive' => 1,
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new ActiveScope);

        static::creating(function ($userInfo) {
            if (is_null($userInfo->InsertAt)) {
                $userInfo->InsertAt = now();
            }
            if (is_null($userInfo->InsertUserId) && auth()->check()) {
                $userInfo->InsertUserId = auth()->id();
            }
        });

        static::updating(function ($userInfo) {
            $userInfo->UpdateBy = now();
            if (auth()->check()) {
                $userInfo->UpdateUserId = auth()->id();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
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

    public function gender()
    {
        return $this->belongsTo(Gender::class, 'gender_id');
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

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('full_name')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(255);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
