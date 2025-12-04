<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'guard_name',
        'IsActive',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'IsActive' => 'integer',
        'deleted_at' => 'datetime',
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

        static::addGlobalScope(new \App\Models\Scopes\ActiveScope);
    }

    /**
     * Get only active permissions.
     */
    public function scopeActive($query)
    {
        return $query->where('IsActive', 1);
    }

    /**
     * Get only inactive (deleted) permissions.
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
        $this->deleted_at = now();
        $this->save();
    }

    /**
     * Restore: set IsActive to 1
     */
    public function restore()
    {
        $this->IsActive = 1;
        $this->deleted_at = null;
        $this->save();
    }
}

