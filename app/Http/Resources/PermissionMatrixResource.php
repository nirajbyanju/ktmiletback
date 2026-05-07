<?php
// app/Http/Resources/PermissionMatrixResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PermissionMatrixResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'feature_name' => $this->feature_name,
            'permission_key' => $this->permission_key,
            'role' => [
                'id' => $this->role->id,
                'name' => $this->role->name,
            ],
            'permissions' => [
                'view' => $this->can_view,
                'create' => $this->can_create,
                'edit' => $this->can_edit,
                'delete' => $this->can_delete,
                'approve' => $this->can_approve,
                'export' => $this->can_export,
                'upload' => $this->can_upload,
                'all' => $this->can_all,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}