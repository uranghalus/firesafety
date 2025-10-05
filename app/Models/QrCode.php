<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class QrCode extends Model
{
    //
    protected $fillable = ['token', 'title', 'target_url', 'created_by', 'expires_at', 'meta'];
    protected $casts = [
        'meta' => 'array',
        'expires_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->token) {
                $model->token = Str::uuid()->toString();
            }
        });
    }

    public function getUploadUrlAttribute()
    {
        return route('upload.show', ['token' => $this->token]);
    }
}
