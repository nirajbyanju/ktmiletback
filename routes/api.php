 <?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\MenuController;
use App\Http\Controllers\Api\V1\PermissionMatrixController;
use App\Http\Controllers\Api\V1\EmployeePermissionController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\UserProfileController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\UserAccessController;
use App\Http\Controllers\MenusController;
use App\Http\Controllers\Api\V1\BatchController;
use App\Http\Controllers\Api\V1\CourseCatalogController;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\EnrollmentController;
use App\Http\Controllers\Api\V1\AdditionalServiceController;
use App\Http\Controllers\Api\V1\SupportChannelController;
use App\Http\Controllers\Api\V1\SkillModuleController;
use App\Http\Controllers\Api\V1\InvoiceController;

/*
|--------------------------------------------------------------------------
| API Routes v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ==================== PUBLIC ROUTES ====================
    Route::prefix('public')->as('public.')->group(function () {


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

        Route::get('/course-catalog', [CourseCatalogController::class, 'index'])->name('course-catalog.index');
        Route::get('/course-catalog/{course}', [CourseCatalogController::class, 'show'])->name('course-catalog.show');


    });

    // ==================== AUTHENTICATED ROUTES ====================
    Route::middleware('auth:sanctum')->group(function () {

        // User profile
        Route::get('/user', [UserProfileController::class, 'show'])->name('user.profile');
        Route::match(['put', 'patch'], '/user', [UserProfileController::class, 'update'])->name('user.update');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

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
       
       

       

        // ==================== BLOG MANAGEMENT ====================
      
          Route::apiResource('courses', CourseController::class);
        Route::apiResource('batches', BatchController::class);
        Route::apiResource('support-channels', SupportChannelController::class);
        Route::apiResource('skills-modules', SkillModuleController::class);
        Route::apiResource('additional-services', AdditionalServiceController::class);
        Route::apiResource('enrollments', EnrollmentController::class);
        Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::patch('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');



        // User menu
        Route::get('/user/menu', [MenusController::class, 'getMenu'])->name('user.menu');
    });

    
});
