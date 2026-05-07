<?php

namespace App;

use Illuminate\Support\Facades\Auth;

trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
                // Ensure the model is saved with the updated_by field
                $model->saveQuietly(); // Use saveQuietly to prevent recursive events
            }
        });

        static::deleting(function ($model) {
            if (Auth::check()) {
                $model->deleted_by = Auth::id();
                // For soft deletes, we need to save before actually deleting
                if (method_exists($model, 'trashed') && !$model->isForceDeleting()) {
                    $model->saveQuietly();
                }
            }
        });

        // Handle force deletes
        static::forceDeleting(function ($model) {
            if (Auth::check()) {
                $model->deleted_by = Auth::id();
                $model->saveQuietly();
            }
        });
    }
}