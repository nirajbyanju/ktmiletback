<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

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
                'order' => 0,
                'is_status' => 1,
                'permission_name' => 'dashboard',
            ],
            [
                'name' => 'Course Catalog',
                'icon' => 'FaGraduationCap',
                'route' => 'course-catalog',
                'url' => '/admin/courses',
                'order' => 7,
                'is_status' => 1,
                'permission_name' => 'course_catalog',
            ],
            [
                'name' => 'Invoices',
                'icon' => 'FaFileInvoiceDollar',
                'route' => 'invoices',
                'url' => '/admin/invoices',
                'order' => 5,
                'is_status' => 1,
                'permission_name' => 'invoices',
            ],
            [
                'name' => 'Course Enrollments',
                'icon' => 'FaUserGraduate',
                'route' => 'enrollments',
                'url' => '/admin/enrollments',
                'order' => 1,
                'is_status' => 1,
                'permission_name' => 'enrollments',
            ],
            [
                'name' => 'Mock Tests',
                'icon' => 'FaClipboardCheck',
                'route' => 'mock-tests',
                'url' => '/admin/mock-tests',
                'order' => 3,
                'is_status' => 1,
                'permission_name' => 'mock_tests',
            ],
            [
                'name' => 'Students',
                'icon' => 'FaClipboardList',
                'route' => 'students',
                'url' => '/admin/students',
                'order' => 6,
                'is_status' => 1,
                'permission_name' => 'students',
            ],
            [
                'name' => 'Exam Bookings',
                'icon' => 'FaPassport',
                'route' => 'exam-bookings',
                'url' => '/admin/exam-bookings',
                'order' => 2,
                'is_status' => 1,
                'permission_name' => 'exam_bookings',
            ],
            [
                'name' => 'Messages',
                'icon' => 'FaEnvelope',
                'route' => 'contact-messages',
                'url' => '/admin/contact-messages',
                'order' => 10,
                'is_status' => 1,
                'permission_name' => 'contact_messages',
            ],
            [
                'name' => 'Demo Requests',
                'icon' => 'FaVideo',
                'route' => 'demo-requests',
                'url' => '/admin/demo-requests',
                'order' => 4,
                'is_status' => 1,
                'permission_name' => 'demo_requests',
            ],
            [
                'name' => 'Message Templates',
                'icon' => 'FaCommentDots',
                'route' => 'message-templates',
                'url' => '/admin/message-templates',
                'order' => 11,
                'is_status' => 1,
                'permission_name' => 'message_templates',
            ],
            [
                'name' => 'Testimonials',
                'icon' => 'FaQuoteLeft',
                'route' => 'testimonials',
                'url' => '/admin/testimonials',
                'order' => 12,
                'is_status' => 1,
                'permission_name' => 'testimonials',
            ],
            [
                'name' => 'Offers',
                'icon' => 'FaTag',
                'route' => 'offers',
                'url' => '/admin/offers',
                'order' => 9,
                'is_status' => 1,
                'permission_name' => 'offers',
                'children' => [
                    [
                        'name' => 'All Offers',
                        'icon' => 'FaTag',
                        'route' => 'offers-all',
                        'url' => '/admin/offers',
                        'order' => 1,
                        'is_status' => 1,
                        'permission_name' => 'offers',
                    ],
                    [
                        'name' => 'Offer Claims',
                        'icon' => 'FaTicketAlt',
                        'route' => 'offer-claims',
                        'url' => '/admin/offer-claims',
                        'order' => 2,
                        'is_status' => 1,
                        'permission_name' => 'offer_claims',
                    ],
                    [
                        'name' => 'Refer a Friend',
                        'icon' => 'FaGift',
                        'route' => 'settings-referral',
                        'url' => '/admin/settings/referral',
                        'order' => 3,
                        'is_status' => 1,
                        'permission_name' => 'settings_referral',
                    ],
                ],
            ],
            [
                'name' => 'Access Control',
                'icon' => 'FaUserShield',
                'route' => 'access-control',
                'url' => '/admin/rbac',
                'order' => 14,
                'is_status' => 1,
                'permission_name' => 'access_control',
            ],
            [
                'name' => 'User Management',
                'icon' => 'FaUsers',
                'route' => 'user-management',
                'url' => '/admin/user-management',
                'order' => 13,
                'is_status' => 1,
                'permission_name' => 'user_management',
            ],
            [
                'name' => 'Settings',
                'icon' => 'FaCog',
                'route' => 'settings',
                'url' => '/admin/settings',
                'order' => 15,
                'is_status' => 1,
                'permission_name' => 'settings',
                'children' => [
                    [
                        'name' => 'Profile Settings',
                        'icon' => 'FaUserCog',
                        'route' => 'settings-profile',
                        'url' => '/admin/settings/profile',
                        'order' => 1,
                        'is_status' => 1,
                        'permission_name' => 'settings_profile',
                    ],
                    [
                        'name' => 'Billing & VAT',
                        'icon' => 'FaFileInvoiceDollar',
                        'route' => 'settings-billing',
                        'url' => '/admin/settings/billing',
                        'order' => 3,
                        'is_status' => 1,
                        'permission_name' => 'settings_billing',
                    ],
                    [
                        'name' => 'Website Content',
                        'icon' => 'FaEdit',
                        'route' => 'settings-content',
                        'url' => '/admin/settings/content',
                        'order' => 4,
                        'is_status' => 1,
                        'permission_name' => 'settings_content',
                    ],
                    [
                        'name' => 'Menu Manager',
                        'icon' => 'FaSitemap',
                        'route' => 'settings-menu',
                        'url' => '/admin/settings/menu',
                        'order' => 2,
                        'is_status' => 1,
                        'permission_name' => 'settings_menu',
                    ],
                ],
            ],
        ];

        foreach ($menus as $menuData) {
            $children = $menuData['children'] ?? [];
            unset($menuData['children']);

            $menu = Menu::updateOrCreate(
                [
                    'permission_name' => $menuData['permission_name'],
                    'parent_id' => null,
                ],
                $menuData
            );

            foreach ($children as $child) {
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
