<?php

namespace App\Models;

use App\Models\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookCategory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'Description',
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
        'IsActive' => 'integer',
    ];

    protected $attributes = [
        'IsActive' => 1,
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new ActiveScope);

        static::creating(function ($category) {
            if (is_null($category->InsertAt)) {
                $category->InsertAt = now();
            }
            if (is_null($category->InsertUserId) && auth()->check()) {
                $category->InsertUserId = auth()->id();
            }
        });

        static::updating(function ($category) {
            $category->UpdateBy = now();
            if (auth()->check()) {
                $category->UpdateUserId = auth()->id();
            }
        });
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
}
