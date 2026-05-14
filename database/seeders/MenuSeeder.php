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
                'name'=>'Course Catalog',
                'icon'=>'FaGraduationCap',
                'route'=>'course-catalog',
                'url'=>'/admin/courses',
                'order'=>1,
                'is_status'=>1,
                'permission_name'=>'course_catalog',
            ],
            [
                'name'=>'Invoices',
                'icon'=>'FaFileInvoiceDollar',
                'route'=>'invoices',
                'url'=>'/admin/invoices',
                'order'=>2,
                'is_status'=>1,
                'permission_name'=>'invoices',
            ],
            [
                'name'=>'Enrollments',
                'icon'=>'FaUserGraduate',
                'route'=>'enrollments',
                'url'=>'/admin/courses?tab=enrollments',
                'order'=>3,
                'is_status'=>1,
                'permission_name'=>'enrollments',
            ],
            [
                'name'=>'Access Control',
                'icon'=>'FaUserShield',
                'route'=>'access-control',
                'url'=>'/admin/rbac',
                'order'=>4,
                'is_status'=>1,
                'permission_name'=>'access_control'
            ],
            [
                'name'=>'User Management',
                'icon'=>'FaUsers',
                'route'=>'user-management',
                'url'=>'/admin/user-management',
                'order'=>5,
                'is_status'=>1,
                'permission_name'=>'user_management',
            ],
            [
                'name'=>'Settings',
                'icon'=>'FaCog',
                'route'=>'settings',
                'url'=>'/admin/settings',
                'order'=>6,
                'is_status'=>1,
                'permission_name'=>'settings',
                'children'=>[
                    [
                        'name'=>'Profile Settings',
                        'icon'=>'FaUserCog',
                        'route'=>'settings-profile',
                        'url'=>'/admin/settings/profile',
                        'order'=>1,
                        'is_status'=>1,
                        'permission_name'=>'settings_profile'
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
