<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Support\Str;

class UserMenuService
{
    public function getForUser(User $user): array
    {
        return Menu::with([
                'children' => fn ($query) => $query->active()->orderBy('order'),
            ])
            ->parent()
            ->active()
            ->orderBy('order')
            ->get()
            ->map(fn (Menu $menu) => $this->transformMenu($menu, $user))
            ->filter()
            ->values()
            ->all();
    }

    private function transformMenu(Menu $menu, User $user): ?array
    {
        $children = $menu->children
            ->map(fn (Menu $child) => $this->transformMenu($child, $user))
            ->filter()
            ->values();

        if (!$menu->isAccessibleBy($user) && $children->isEmpty()) {
            return null;
        }

        $allowedActions = collect(['view', 'create', 'edit', 'delete', 'approve', 'export', 'upload'])
            ->filter(fn (string $action) => $menu->allowsAction($user, $action) || $menu->allowsAction($user, 'manage'))
            ->values();

        if ($allowedActions->isEmpty() && $menu->isAccessibleBy($user)) {
            $allowedActions = collect(['view']);
        }

        return [
            'id' => $menu->permission_name
                ? str_replace('_', '-', $menu->permission_name)
                : Str::slug($menu->name),
            'menu_id' => $menu->id,
            'name' => $menu->name,
            'icon' => $menu->icon,
            'path' => $menu->url ?: $menu->route,
            'route' => $menu->route,
            'url' => $menu->url,
            'permission_name' => $menu->permission_name,
            'permissions' => $allowedActions->all(),
            'children' => $children->all(),
        ];
    }
}
