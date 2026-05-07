<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\PropertyController;
use App\Http\Controllers\Api\V1\MenuController;
use App\Http\Controllers\Api\V1\PermissionMatrixController;
use App\Http\Controllers\Api\V1\EmployeePermissionController;
use App\Http\Controllers\Api\V1\OptionController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\FrontController;
use App\Http\Controllers\Api\V1\InqueryController;
use App\Http\Controllers\Api\V1\InqueryFollowupController;
use App\Http\Controllers\Api\V1\FieldVisitsController;
use App\Http\Controllers\Api\V1\BlogController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\UserProfileController;
use App\Http\Controllers\Api\V1\Frontend\BlogsController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\UserAccessController;
use App\Http\Controllers\MenusController;

/*
|--------------------------------------------------------------------------
| API Routes v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ==================== PUBLIC ROUTES ====================
    Route::prefix('public')->as('public.')->group(function () {

        // Frontend/Tour routes
        Route::post('/tour', [FieldVisitsController::class, 'frontTour'])->name('tour.store');
        Route::post('/inquiry', [InqueryController::class, 'frontInquery'])->name('inquiry.store');

        // Property routes
        Route::prefix('properties')->as('properties.')->group(function () {
            Route::get('/summary', [FrontController::class, 'propertySummary'])->name('summary');
            Route::get('/list', [FrontController::class, 'propertyList'])->name('list');
            Route::get('/{slug}/details', [FrontController::class, 'propertyDetail'])->name('details');
        });

        // Authentication routes
        Route::prefix('auth')->as('auth.')->group(function () {
            Route::post('/register', [AuthController::class, 'register'])->name('register');
            Route::post('/admin/register', [AuthController::class, 'adminRegister'])->middleware('auth:sanctum')->name('admin.register');
            Route::post('/login', [AuthController::class, 'login'])->name('login');
            Route::post('/refresh', [AuthController::class, 'refreshToken'])->name('refresh');
            Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
            Route::get('/reset-password/validate', [AuthController::class, 'validateResetToken'])->name('password.validate');
            Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
        });

        // Options
        Route::get('/options/all', [OptionController::class, 'getAllOptions'])->name('options.all');

        // Frontend blog routes
        Route::prefix('blog')->as('blog.')->group(function () {
            Route::get('/', [BlogsController::class, 'view'])->name('index');
            Route::get('/list/{id}', [BlogsController::class, 'viewing'])->name('list');
            Route::get('/{slug}', [BlogsController::class, 'details'])->name('details')->middleware('throttle:30,1');
        });
    });

    // ==================== AUTHENTICATED ROUTES ====================
    Route::middleware('auth:sanctum')->group(function () {

        // User profile
        Route::get('/user', [UserProfileController::class, 'show'])->name('user.profile');
        Route::match(['put', 'patch'], '/user', [UserProfileController::class, 'update'])->name('user.update');
        Route::post('/user/profile-picture', [UserProfileController::class, 'updateProfilePicture'])->name('user.profile-picture.update');
        Route::delete('/user/profile-picture', [UserProfileController::class, 'deleteProfilePicture'])->name('user.profile-picture.delete');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/dashboard/summary', [DashboardController::class, 'summary'])->name('dashboard.summary');
        Route::get('/dashboard/recent-properties', [DashboardController::class, 'recentProperties'])->name('dashboard.recent-properties');
        Route::get('/dashboard/recent-activity', [DashboardController::class, 'recentActivity'])->name('dashboard.recent-activity');
        Route::get('/dashboard/performance', [DashboardController::class, 'performance'])->name('dashboard.performance');
        Route::get('/dashboard/report', [DashboardController::class, 'report'])->name('dashboard.report');

        Route::prefix('notifications')->as('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
            Route::patch('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
            Route::patch('/{notificationId}/read', [NotificationController::class, 'markAsRead'])->name('read');
        });

        // ==================== MENU MANAGEMENT ====================
        Route::prefix('menus')->as('menus.')->group(function () {
            Route::get('/accessible', [MenuController::class, 'getAccessibleMenus'])->name('accessible');
            Route::post('/reorder', [MenuController::class, 'reorder'])->name('reorder');
            Route::put('/{menu}/role-permissions', [MenuController::class, 'syncRolePermissions'])->name('role-permissions.sync');
            Route::apiResource('/', MenuController::class)->parameters(['' => 'menu']);
        });

        // ==================== RBAC MANAGEMENT ====================
        Route::prefix('rbac')->as('rbac.')->group(function () {
            Route::apiResource('roles', RoleController::class);
            Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');

            Route::prefix('users')->as('users.')->group(function () {
                Route::get('/', [UserAccessController::class, 'index'])->name('index');
                Route::post('/', [UserAccessController::class, 'store'])->name('store');
                Route::get('/{user}/access', [UserAccessController::class, 'show'])->name('show');
                Route::patch('/{user}/status', [UserAccessController::class, 'updateStatus'])->name('status.update');
                Route::put('/{user}/roles', [UserAccessController::class, 'syncRoles'])->name('roles.sync');
                Route::put('/{user}/permissions', [UserAccessController::class, 'syncPermissions'])->name('permissions.sync');
            });
        });

        // ==================== PERMISSION MATRIX ====================
        Route::prefix('permission-matrix')->as('permission-matrix.')->group(function () {
            Route::get('/', [PermissionMatrixController::class, 'index'])->name('index');
            Route::get('/grouped', [PermissionMatrixController::class, 'getMatrixGrouped'])->name('grouped');
            Route::post('/', [PermissionMatrixController::class, 'store'])->name('store');
            Route::put('/{permissionMatrix}', [PermissionMatrixController::class, 'update'])->name('update');
            Route::delete('/{permissionMatrix}', [PermissionMatrixController::class, 'destroy'])->name('destroy');
            Route::get('/employee/{roleId}', [PermissionMatrixController::class, 'getEmployeePermissions'])->name('employee.permissions');
            Route::post('/employee/bulk-update', [PermissionMatrixController::class, 'bulkUpdateForEmployee'])->name('employee.bulk-update');
        });

        // ==================== EMPLOYEE PERMISSIONS ====================
        Route::prefix('employee-permissions')->as('employee-permissions.')->group(function () {
            Route::get('/roles', [EmployeePermissionController::class, 'getEmployeeRoles'])->name('roles');
            Route::get('/matrix', [EmployeePermissionController::class, 'getEmployeePermissionMatrix'])->name('matrix');
            Route::post('/assign/{roleId}', [EmployeePermissionController::class, 'assignToEmployeeRole'])->name('assign');
            Route::get('/role/{roleId}/employees', [EmployeePermissionController::class, 'getEmployeesByRole'])->name('role.employees');
        });

        // ==================== OPTIONS MANAGEMENT ====================
        Route::prefix('options')->as('options.')->group(function () {
            Route::get('/', [OptionController::class, 'fetchOption'])->name('index');
            Route::post('/', [OptionController::class, 'store'])->name('store');
            Route::get('/types', [OptionController::class, 'types'])->name('types');
            Route::get('/catalog/{type}', [OptionController::class, 'catalog'])->name('catalog');
            Route::get('/dropdown/{slug}/{module?}', [OptionController::class, 'getDropdownOptions'])->name('dropdown');
            Route::get('/menu', [OptionController::class, 'optionMenu'])->name('menu');
            Route::get('/show', [OptionController::class, 'showOption'])->name('show');
            Route::get('/{id}', [OptionController::class, 'getOptionById'])->name('show');
            Route::put('/{id}', [OptionController::class, 'update'])->name('update');
            Route::delete('/{id}/{type}', [OptionController::class, 'destroy'])->name('destroy');
            Route::patch('/status/{id}', [OptionController::class, 'updateStatus'])->name('status.update');
        });

        // ==================== PROPERTY MANAGEMENT ====================
        Route::prefix('properties')->as('properties.')->group(function () {
            Route::get('/', [PropertyController::class, 'index'])->name('index');
            Route::post('/', [PropertyController::class, 'store'])->name('store');
            Route::get('/{property:id}', [PropertyController::class, 'show'])->name('show');
            Route::match(['put', 'patch'], '/{property:id}', [PropertyController::class, 'update'])->name('update');
            Route::delete('/{property:id}', [PropertyController::class, 'destroy'])->name('destroy');
            Route::patch('/{property:id}/status', [PropertyController::class, 'updateStatus'])->name('status.update');
        });

        // ==================== INQUIRY MANAGEMENT ====================
        Route::prefix('inquiries')->as('inquiries.')->group(function () {
            Route::apiResource('/', InqueryController::class)->parameters(['' => 'inquiry']);

            // Inquiry follow-ups
            Route::prefix('{inquiryId}/followups')->as('followups.')->group(function () {
                Route::get('/', [InqueryFollowupController::class, 'index'])->name('index');
                Route::post('/', [InqueryFollowupController::class, 'store'])->name('store');
                Route::get('/{inquiryFollowup}', [InqueryFollowupController::class, 'show'])->name('show');
                Route::put('/{inquiryFollowup}', [InqueryFollowupController::class, 'update'])->name('update');
                Route::delete('/{id}', [InqueryFollowupController::class, 'destroy'])->name('destroy');
            });
        });

        // ==================== FIELD VISITS ====================
        Route::get('/field-visits', [FieldVisitsController::class, 'index'])->name('field-visits.global.index');
        Route::prefix('field-visits')->as('field-visits.')->group(function () {
            Route::get('/', [FieldVisitsController::class, 'index'])->name('index');
            Route::post('/', [FieldVisitsController::class, 'store'])->name('store');
            Route::get('/{fieldVisit}', [FieldVisitsController::class, 'show'])->name('show');
            Route::put('/{fieldVisit}', [FieldVisitsController::class, 'update'])->name('update');
            Route::delete('/{fieldVisit}', [FieldVisitsController::class, 'destroy'])->name('destroy');
            Route::patch('/status/{fieldVisit}', [FieldVisitsController::class, 'updateStatus'])->name('status.update');
        });

        // ==================== BLOG MANAGEMENT ====================
        Route::prefix('blog')->as('blog.')->controller(BlogController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->middleware('throttle:10,1')->name('store');
            Route::get('/{blogPost}', 'show')->middleware('throttle:30,1')->name('show');
            Route::match(['put', 'patch'], '/{blogPost}', 'update')
                ->middleware('throttle:10,1')
                ->name('update');
            Route::patch('/status/{blogPost}', 'updateStatus')
                ->middleware('throttle:30,1')
                ->name('status.update');
            Route::delete('/{blogPost}', 'destroy')->middleware('throttle:30,1')->name('destroy');
        });

        // User menu
        Route::get('/user/menu', [MenusController::class, 'getMenu'])->name('user.menu');
    });
});
