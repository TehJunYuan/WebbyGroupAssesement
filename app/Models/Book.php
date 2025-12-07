<?php

namespace App\Models;

use App\Models\Scopes\ActiveScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Book extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, HasSlug;

    public $timestamps = false;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'price',
        'stock_quantity',
        'cover_image',
        'category_id',
        'seller_id',
        'InsertAt',
        'InsertUserId',
        'UpdateBy',
        'UpdateUserId',
        'DeleteBy',
        'DeleteUserId',
        'IsActive',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'InsertAt' => 'datetime',
        'UpdateBy' => 'datetime',
        'DeleteBy' => 'datetime',
        'IsActive' => 'integer',
    ];

    protected $attributes = [
        'IsActive' => 1,
        'stock_quantity' => 0,
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new ActiveScope);

        static::creating(function ($book) {
            if (is_null($book->InsertAt)) {
                $book->InsertAt = now();
            }
            if (is_null($book->InsertUserId) && auth()->check()) {
                $book->InsertUserId = auth()->id();
            }
        });

        static::updating(function ($book) {
            $book->UpdateBy = now();
            if (auth()->check()) {
                $book->UpdateUserId = auth()->id();
            }
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('standard')
            ->width(600)
            ->height(900)
            ->sharpen(10)
            ->performOnCollections('cover');

        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(450)
            ->sharpen(10)
            ->performOnCollections('cover');

        $this->addMediaConversion('preview')
            ->width(600)
            ->height(900)
            ->sharpen(10)
            ->performOnCollections('cover');
    }

    public function category()
    {
        return $this->belongsTo(BookCategory::class, 'category_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
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

    public function scopeBySeller($query, $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
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

    public function getCoverImageUrlAttribute()
    {
        if ($this->cover_image) {
            return Storage::disk('public')->url($this->cover_image);
        }
        
        return 'https://via.placeholder.com/600x800?text=No+Cover';
    }

    public function getCoverThumbnailUrlAttribute()
    {
        if ($this->cover_image) {
            return Storage::disk('public')->url($this->cover_image);
        }
        
        return 'https://via.placeholder.com/300x400?text=No+Cover';
    }
    
    public function getCoverImageUrl($conversion = null)
    {
        if ($this->cover_image) {
            return Storage::disk('public')->url($this->cover_image);
        }
        
        return 'https://via.placeholder.com/600x900?text=No+Cover';
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(255);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
