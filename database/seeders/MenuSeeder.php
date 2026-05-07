<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            [
                'name' => 'Dashboard',
                'icon' => 'FaTachometerAlt',
                'route' => 'dashboard',
                'url' => '/admin/dashboard',
                'order' => 1,
                'is_status' => 1,
                'permission_name' => 'dashboard'
            ],
            [
                'name'=>'Property Management',
                'icon'=>'FaBuilding',
                'route'=>'property',
                'url'=>'/admin/property',
                'order'=>2,
                'is_status'=>1,
                'permission_name'=>'property',
            ],
            [
                'name'=>'Field Visit',
                'icon'=>'FaMapMarkedAlt',
                'route'=>'field-visit',
                'url'=>'/admin/fieldVisit',
                'order'=>3,
                'is_status'=>1,
                'permission_name'=>'field_visit'
            ],
            [
                'name'=>'Property Inquiry',
                'icon'=>'FaClipboardList',
                'route'=>'property-inquiry',
                'url'=>'/admin/propertyInquery',
                'order'=>4,
                'is_status'=>1,
                'permission_name'=>'property_inquiry'
            ],
            [
                'name'=>'Blog',
                'icon'=>'FaNewspaper',
                'route'=>'blog',
                'url'=>'/admin/blog',
                'order'=>5,
                'is_status'=>1,
                'permission_name'=>'blog'
            ],
            [
                'name'=>'Access Control',
                'icon'=>'FaUserShield',
                'route'=>'access-control',
                'url'=>'/admin/rbac',
                'order'=>6,
                'is_status'=>1,
                'permission_name'=>'access_control'
            ],
            [
                'name'=>'Settings',
                'icon'=>'FaCog',
                'route'=>'settings',
                'url'=>'/admin/settings',
                'order'=>7,
                'is_status'=>1,
                'permission_name'=>'settings',
                'children'=>[
                    [
                        'name'=>'Option Manager',
                        'icon'=>'FaList',
                        'route'=>'settings-option',
                        'url'=>'/admin/settings/option',
                        'order'=>1,
                        'is_status'=>1,
                        'permission_name'=>'settings_option'
                    ],
                    [
                        'name'=>'Menu Manager',
                        'icon'=>'FaSitemap',
                        'route'=>'settings-menu',
                        'url'=>'/admin/settings/menu',
                        'order'=>2,
                        'is_status'=>1,
                        'permission_name'=>'settings_menu'
                    ],
                ]
            ],
        ];

        foreach ($menus as $menuData){
            $children = $menuData['children'] ?? [];
            unset($menuData['children']);

            $menu = Menu::updateOrCreate(
                [
                    'permission_name' => $menuData['permission_name'],
                    'parent_id' => null,
                ],
                $menuData
            );

            foreach($children as $child){
                $child['parent_id'] = $menu->id;
                Menu::updateOrCreate(
                    [
                        'permission_name' => $child['permission_name'],
                        'parent_id' => $menu->id,
                    ],
                    $child
                );
            }
        }
    }
}
