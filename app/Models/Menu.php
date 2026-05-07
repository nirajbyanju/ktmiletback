<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Auditable;
use App\Models\User;

class Menu extends Model
{
   use HasFactory, SoftDeletes, Auditable;

    protected $table = 'menus';
    
    protected $fillable = [
        'name',
        'icon',
        'route',
        'url',
        'parent_id',
        'order',
        'is_status',
        'permission_name',
        'created_by',
        'is_public', // Make sure this field exists
    ];

    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('order');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query)
    {
        return $query->where('is_status', 1);
    }

    public function scopeParent(Builder $query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeWithPermission(Builder $query, $user)
    {
        if ($user->hasRole('Super Admin')) {
            return $query;
        }

        return $query->where(function($q) use ($user) {
            $q->whereNull('permission_name')
              ->orWhereIn('permission_name', $user->getAllPermissions()->pluck('name'));
        });
    }

    public function permissionVariants(?string $action = null): array
    {
        if (empty($this->permission_name)) {
            return [];
        }

        $base = trim($this->permission_name);
        $variants = [$base];

        if ($action !== null) {
            $action = trim($action);
            $variants[] = "{$action}_{$base}";
            $variants[] = "{$action} {$base}";
        }

        return array_values(array_unique($variants));
    }

    public function allowsAction(User $user, string $action): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        foreach ($this->permissionVariants($action) as $permissionName) {
            if ($user->can($permissionName)) {
                return true;
            }
        }

        return false;
    }
    
    // Helper method to check if menu is accessible
    public function isAccessibleBy(User $user): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if (!empty($this->permission_name)) {
            if ($this->allowsAction($user, 'manage') || $this->allowsAction($user, 'view')) {
                return true;
            }
        }

        return (bool) ($this->is_public ?? false);
    }
}
