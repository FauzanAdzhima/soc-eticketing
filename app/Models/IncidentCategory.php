<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class IncidentCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        // 'description',
    ];

    protected static function boot()
{
    parent::boot();

    static::creating(function ($category) {
        // Automatically generate a slug if one wasn't provided
        if (! $category->slug) {
            $category->slug = Str::slug($category->name);
        }
    });
}
}
