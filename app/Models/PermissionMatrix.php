<?php
// app/Models/PermissionMatrix.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role;

class PermissionMatrix extends Model
{
    protected $fillable = [
        'feature_name',
        'permission_key',
        'can_view',
        'can_create',
        'can_edit',
        'can_delete',
        'can_approve',
        'can_export',
        'can_upload',
        'can_all',
        'role_id'
    ];

    protected $casts = [
        'can_view' => 'boolean',
        'can_create' => 'boolean',
        'can_edit' => 'boolean',
        'can_delete' => 'boolean',
        'can_approve' => 'boolean',
        'can_export' => 'boolean',
        'can_upload' => 'boolean',
        'can_all' => 'boolean',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function getPermissionsArray(): array
    {
        return [
            'view' => $this->can_view,
            'create' => $this->can_create,
            'edit' => $this->can_edit,
            'delete' => $this->can_delete,
            'approve' => $this->can_approve,
            'export' => $this->can_export,
            'upload' => $this->can_upload,
            'all' => $this->can_all,
        ];
    }
}