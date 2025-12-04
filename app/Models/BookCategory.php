<?php

namespace App\Models;

use App\Models\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookCategory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'InsertAt' => 'datetime',
        'UpdateBy' => 'datetime',
        'DeleteBy' => 'datetime',
        'IsActive' => 'integer',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'IsActive' => 1,
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new ActiveScope);

        // Automatically set InsertAt and InsertUserId when creating
        static::creating(function ($category) {
            if (is_null($category->InsertAt)) {
                $category->InsertAt = now();
            }
            if (is_null($category->InsertUserId) && auth()->check()) {
                $category->InsertUserId = auth()->id();
            }
        });

        // Automatically set UpdateBy and UpdateUserId when updating
        static::updating(function ($category) {
            $category->UpdateBy = now();
            if (auth()->check()) {
                $category->UpdateUserId = auth()->id();
            }
        });
    }

    /**
     * Get the user who inserted this category.
     */
    public function insertedBy()
    {
        return $this->belongsTo(User::class, 'InsertUserId');
    }

    /**
     * Get the user who last updated this category.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'UpdateUserId');
    }

    /**
     * Get the user who deleted this category.
     */
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'DeleteUserId');
    }

    /**
     * Get only active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('IsActive', 1);
    }

    /**
     * Get only inactive (deleted) categories.
     */
    public function scopeInactive($query)
    {
        return $query->where('IsActive', -1);
    }

    /**
     * Soft delete: set IsActive to -1
     */
    public function softDelete()
    {
        $this->IsActive = -1;
        $this->DeleteBy = now();
        if (auth()->check()) {
            $this->DeleteUserId = auth()->id();
        }
        $this->save();
    }

    /**
     * Restore: set IsActive to 1
     */
    public function restore()
    {
        $this->IsActive = 1;
        $this->DeleteBy = null;
        $this->DeleteUserId = null;
        $this->save();
    }
}
