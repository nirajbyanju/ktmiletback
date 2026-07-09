 <?php

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
use App\Http\Controllers\Api\V1\BatchTypeController;
use App\Http\Controllers\Api\V1\CourseCatalogController;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\CourseModuleController;
use App\Http\Controllers\Api\V1\EnrollmentController;
use App\Http\Controllers\Api\V1\AdditionalServiceController;
use App\Http\Controllers\Api\V1\SupportChannelController;
use App\Http\Controllers\Api\V1\SkillModuleController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\ExamBookingController;
use App\Http\Controllers\Api\V1\ContactMessageController;
use App\Http\Controllers\Api\V1\GoogleAuthController;
use App\Http\Controllers\Api\V1\TeacherController;
use App\Http\Controllers\Api\V1\MockTestSubscriptionController;
use App\Http\Controllers\Api\V1\MockTestEnrollmentController;
use App\Http\Controllers\Api\V1\TestimonialController;
use App\Http\Controllers\Api\V1\OfferController;
use App\Http\Controllers\Api\V1\OfferClaimController;
use App\Http\Controllers\Api\V1\DemoRequestController;

/*
|--------------------------------------------------------------------------
| API Routes v1
|--------------------------------------------------------------------------
*/

// Google OAuth — no v1 prefix, matches GOOGLE_REDIRECT_URI in .env
Route::get('/google/redirect', [GoogleAuthController::class, 'redirect']);
Route::get('/google/callback', [GoogleAuthController::class, 'callback']);

Route::prefix('v1')->group(function () {

    // ==================== PUBLIC ROUTES ====================
    Route::prefix('public')->as('public.')->group(function () {

        // Authentication routes
        Route::prefix('auth')->as('auth.')->group(function () {
            Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1')->name('register');
            Route::post('/admin/register', [AuthController::class, 'adminRegister'])->middleware(['auth:sanctum', 'role:Admin|Super Admin'])->name('admin.register');
            Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login');
            Route::post('/refresh', [AuthController::class, 'refreshToken'])->middleware('throttle:20,1')->name('refresh');
            Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->middleware('throttle:5,1')->name('password.email');
            Route::get('/reset-password/validate', [AuthController::class, 'validateResetToken'])->name('password.validate');
            Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1')->name('password.reset');
        });

        Route::get('/course-catalog', [CourseCatalogController::class, 'index'])->name('course-catalog.index');
        Route::get('/course-catalog/{course}', [CourseCatalogController::class, 'show'])->name('course-catalog.show');
        Route::get('/exam-booking-plans', [ExamBookingController::class, 'userPlanIndex'])->name('public.exam-booking-plans.index');

        // Mock test subscription plans — public listing, no auth required
        Route::get('/mock-test-subscriptions', [MockTestSubscriptionController::class, 'userIndex'])->name('public.mock-test-subscriptions.index');
        Route::get('/mock-test-subscriptions/{id}', [MockTestSubscriptionController::class, 'show'])->name('public.mock-test-subscriptions.show');

        // Contact form — public, no auth
        Route::post('/contact', [ContactMessageController::class, 'store'])->middleware('throttle:10,1')->name('contact.store');

        // Testimonials — public listing
        Route::get('/testimonials', [TestimonialController::class, 'publicIndex'])->name('testimonials.public');

        // Offers — public listing (active & not expired)
        Route::get('/offers', [OfferController::class, 'publicIndex'])->name('offers.public');

        // Profile picture serving — public, no auth required (bypasses symlink issue on Windows)
        Route::get('/users/{userId}/profile-picture', [UserProfileController::class, 'serveProfilePicture'])->name('user.profile-picture.serve');
    });

    // ==================== AUTHENTICATED ROUTES ====================
    Route::middleware('auth:sanctum')->group(function () {

        // User profile
        Route::get('/user', [UserProfileController::class, 'show'])->name('user.profile');
        Route::match(['put', 'patch'], '/user', [UserProfileController::class, 'update'])->name('user.update');
        Route::post('/user/change-password', [UserProfileController::class, 'changePassword'])->name('user.change-password');
        Route::post('/user/set-password', [UserProfileController::class, 'setPassword'])->name('user.set-password');
        Route::post('/user/profile-picture', [UserProfileController::class, 'uploadProfilePicture'])->name('user.profile-picture.upload');
        Route::delete('/user/profile-picture', [UserProfileController::class, 'deleteProfilePicture'])->name('user.profile-picture.delete');
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

        // ==================== COURSES & BATCHES ====================
        Route::apiResource('courses', CourseController::class);
        Route::apiResource('batches', BatchController::class);

        // ==================== BATCH TYPES ====================
        Route::get('batch-types',                    [BatchTypeController::class, 'index'])->name('batch-types.index');
        Route::post('batch-types',                   [BatchTypeController::class, 'store'])->name('batch-types.store');
        Route::patch('batch-types/{batchType}',      [BatchTypeController::class, 'update'])->name('batch-types.update');
        Route::delete('batch-types/{batchType}',     [BatchTypeController::class, 'destroy'])->name('batch-types.destroy');

        // Course modules (nested under courses)
        Route::get('courses/{courseId}/modules', [CourseModuleController::class, 'index'])->name('courses.modules.index');
        Route::post('courses/{courseId}/modules', [CourseModuleController::class, 'store'])->name('courses.modules.store');
        Route::put('courses/{courseId}/modules/{id}', [CourseModuleController::class, 'update'])->name('courses.modules.update');
        Route::delete('courses/{courseId}/modules/{id}', [CourseModuleController::class, 'destroy'])->name('courses.modules.destroy');

        // Legacy support — keep old endpoints alive for backwards compat
        Route::apiResource('support-channels', SupportChannelController::class);
        Route::apiResource('skills-modules', SkillModuleController::class);
        Route::apiResource('additional-services', AdditionalServiceController::class);

        // ==================== TEACHERS ====================
        Route::apiResource('teachers', TeacherController::class);

        // Teacher self-service (requires Teacher role)
        Route::prefix('teacher')->as('teacher.')->middleware('role:Teacher|Admin|Super Admin')->group(function () {
            Route::get('/profile',          [TeacherController::class, 'myProfile'])->name('profile');
            Route::patch('/profile',        [TeacherController::class, 'updateMyProfile'])->name('profile.update');
            Route::post('/profile/photo',   [TeacherController::class, 'uploadPhoto'])->name('profile.photo.upload');
            Route::delete('/profile/photo', [TeacherController::class, 'deletePhoto'])->name('profile.photo.delete');

            // Courses, students & invoices visible to the teacher
            Route::get('/courses',                [TeacherController::class, 'myCourses'])->name('courses');
            Route::patch('/batches/{batchId}',    [TeacherController::class, 'updateMyBatch'])->name('batches.update');
            Route::get('/students',               [TeacherController::class, 'myStudents'])->name('students');
            Route::get('/student-invoices',       [TeacherController::class, 'myStudentInvoices'])->name('student-invoices');
        });

        // ==================== ENROLLMENTS (course) ====================
        Route::apiResource('enrollments', EnrollmentController::class);
        Route::patch('enrollments/{id}/change-batch', [EnrollmentController::class, 'changeBatch'])->name('enrollments.change-batch');
        Route::get('admin/enrollments/stats', [EnrollmentController::class, 'adminStats'])->name('admin.enrollments.stats');
        Route::get('admin/enrollments', [EnrollmentController::class, 'adminIndex'])->name('admin.enrollments.index');
        Route::patch('admin/enrollments/{enrollment}', [EnrollmentController::class, 'adminUpdate'])->name('admin.enrollments.update');

        // ==================== INVOICES ====================
        Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::post('invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::post('invoices/mock-test', [InvoiceController::class, 'storeForMockTest'])->name('invoices.mock-test.store');
        Route::post('invoices/switch-mock-plan', [InvoiceController::class, 'switchMockPlan'])->name('invoices.switch-mock-plan');
        Route::post('invoices/exam-booking', [InvoiceController::class, 'storeForExam'])->name('invoices.exam.store');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::patch('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid');
        Route::patch('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
        Route::patch('invoices/{invoice}/refund', [InvoiceController::class, 'refund'])->name('invoices.refund');
        Route::post('invoices/{invoice}/payment-screenshot', [InvoiceController::class, 'uploadScreenshot'])->name('invoices.screenshot.upload');
        Route::get('invoices/{invoice}/screenshot', [InvoiceController::class, 'serveScreenshot'])->name('invoices.screenshot.serve');
        Route::get('invoices/{invoice}/screenshots', [InvoiceController::class, 'getScreenshotHistory'])->name('invoices.screenshots.history');
        Route::patch('invoices/{invoice}/crm-status', [InvoiceController::class, 'updateCrmStatus'])->name('invoices.crm-status.update');

        // ==================== CONTACT MESSAGES (admin) ====================
        Route::get('admin/contact-messages', [ContactMessageController::class, 'adminIndex'])->name('admin.contact-messages.index');
        Route::get('admin/contact-messages/stats', [ContactMessageController::class, 'stats'])->name('admin.contact-messages.stats');
        Route::get('admin/contact-messages/{contactMessage}', [ContactMessageController::class, 'show'])->name('admin.contact-messages.show');
        Route::patch('admin/contact-messages/{contactMessage}/status', [ContactMessageController::class, 'updateStatus'])->name('admin.contact-messages.status');

        // ==================== EXAM BOOKING PLANS (admin CRUD) ====================
        Route::get('exam-booking-plans', [ExamBookingController::class, 'userPlanIndex'])->name('exam-booking-plans.index');
        Route::get('admin/exam-booking-plans', [ExamBookingController::class, 'adminPlanIndex'])->name('admin.exam-booking-plans.index');
        Route::post('admin/exam-booking-plans', [ExamBookingController::class, 'adminPlanStore'])->name('admin.exam-booking-plans.store');
        Route::patch('admin/exam-booking-plans/{id}', [ExamBookingController::class, 'adminPlanUpdate'])->name('admin.exam-booking-plans.update');
        Route::delete('admin/exam-booking-plans/{id}', [ExamBookingController::class, 'adminPlanDestroy'])->name('admin.exam-booking-plans.destroy');

        // ==================== EXAM BOOKING ENROLLMENTS ====================
        Route::get('exam-bookings', [ExamBookingController::class, 'userIndex'])->name('exam-bookings.user-index');
        Route::post('exam-bookings', [ExamBookingController::class, 'store'])->name('exam-bookings.store');
        Route::post('exam-bookings/{id}/update', [ExamBookingController::class, 'userUpdate'])->name('exam-bookings.user-update');
        Route::get('exam-bookings/{id}/passport', [ExamBookingController::class, 'downloadPassport'])->name('exam-bookings.passport');
        Route::get('admin/exam-bookings/stats', [ExamBookingController::class, 'adminStats'])->name('admin.exam-bookings.stats');
        Route::get('admin/exam-bookings', [ExamBookingController::class, 'adminIndex'])->name('admin.exam-bookings.index');
        Route::get('admin/exam-bookings/{id}', [ExamBookingController::class, 'adminShow'])->name('admin.exam-bookings.show');
        Route::patch('admin/exam-bookings/{id}', [ExamBookingController::class, 'adminUpdate'])->name('admin.exam-bookings.update');
        Route::delete('admin/exam-bookings/{id}', [ExamBookingController::class, 'adminDestroy'])->name('admin.exam-bookings.destroy');

        // ==================== MOCK TEST SUBSCRIPTIONS ====================
        Route::get('mock-test-subscriptions', [MockTestSubscriptionController::class, 'userIndex'])->name('mock-test-subscriptions.user-index');
        Route::get('mock-test-subscriptions/{id}', [MockTestSubscriptionController::class, 'show'])->name('mock-test-subscriptions.show');
        Route::get('admin/mock-test-subscriptions/stats', [MockTestSubscriptionController::class, 'adminStats'])->name('admin.mock-test-subscriptions.stats');
        Route::get('admin/mock-test-subscriptions', [MockTestSubscriptionController::class, 'adminIndex'])->name('admin.mock-test-subscriptions.index');
        Route::post('admin/mock-test-subscriptions', [MockTestSubscriptionController::class, 'store'])->name('admin.mock-test-subscriptions.store');
        Route::patch('admin/mock-test-subscriptions/{id}', [MockTestSubscriptionController::class, 'adminUpdate'])->name('admin.mock-test-subscriptions.update');
        Route::delete('admin/mock-test-subscriptions/{id}', [MockTestSubscriptionController::class, 'destroy'])->name('admin.mock-test-subscriptions.destroy');

        // ==================== OFFERS ====================
        Route::get('admin/offers', [OfferController::class, 'index'])->name('admin.offers.index');
        Route::post('admin/offers', [OfferController::class, 'store'])->name('admin.offers.store');
        Route::get('admin/offers/{id}', [OfferController::class, 'show'])->name('admin.offers.show');
        Route::put('admin/offers/{id}', [OfferController::class, 'update'])->name('admin.offers.update');
        Route::delete('admin/offers/{id}', [OfferController::class, 'destroy'])->name('admin.offers.destroy');

        // ==================== OFFER CLAIMS ====================
        Route::post('offer-claims', [OfferClaimController::class, 'store'])->name('offer-claims.store');
        Route::get('offer-claims', [OfferClaimController::class, 'userIndex'])->name('offer-claims.user-index');
        Route::get('admin/offer-claims', [OfferClaimController::class, 'adminIndex'])->name('admin.offer-claims.index');
        Route::delete('admin/offer-claims/{id}', [OfferClaimController::class, 'destroy'])->name('admin.offer-claims.destroy');

        // ==================== TESTIMONIALS ====================
        Route::get('admin/testimonials', [TestimonialController::class, 'index'])->name('admin.testimonials.index');
        Route::post('admin/testimonials', [TestimonialController::class, 'store'])->name('admin.testimonials.store');
        Route::get('admin/testimonials/{id}', [TestimonialController::class, 'show'])->name('admin.testimonials.show');
        Route::put('admin/testimonials/{id}', [TestimonialController::class, 'update'])->name('admin.testimonials.update');
        Route::delete('admin/testimonials/{id}', [TestimonialController::class, 'destroy'])->name('admin.testimonials.destroy');
        Route::post('admin/testimonials/{id}/photo', [TestimonialController::class, 'uploadPhoto'])->name('admin.testimonials.photo.upload');
        Route::delete('admin/testimonials/{id}/photo', [TestimonialController::class, 'deletePhoto'])->name('admin.testimonials.photo.delete');

        // ==================== MOCK TEST ENROLLMENTS ====================
        Route::get('mock-test-enrollments', [MockTestEnrollmentController::class, 'userIndex'])->name('mock-test-enrollments.user-index');
        Route::get('mock-test-enrollments/{id}', [MockTestEnrollmentController::class, 'show'])->name('mock-test-enrollments.show');
        Route::get('admin/mock-test-enrollments/stats', [MockTestEnrollmentController::class, 'adminStats'])->name('admin.mock-test-enrollments.stats');
        Route::get('admin/mock-test-enrollments', [MockTestEnrollmentController::class, 'adminIndex'])->name('admin.mock-test-enrollments.index');
        Route::post('admin/mock-test-enrollments', [MockTestEnrollmentController::class, 'store'])->name('admin.mock-test-enrollments.store');
        Route::patch('admin/mock-test-enrollments/{id}', [MockTestEnrollmentController::class, 'adminUpdate'])->name('admin.mock-test-enrollments.update');
        Route::delete('admin/mock-test-enrollments/{id}', [MockTestEnrollmentController::class, 'destroy'])->name('admin.mock-test-enrollments.destroy');

        // User menu
        Route::get('/user/menu', [MenusController::class, 'getMenu'])->name('user.menu');

        // ==================== DEMO REQUESTS ====================
        Route::get('demo-requests', [DemoRequestController::class, 'userIndex'])->name('demo-requests.index');
        Route::post('demo-requests', [DemoRequestController::class, 'store'])->name('demo-requests.store');
        Route::get('admin/demo-requests/stats', [DemoRequestController::class, 'stats'])->name('admin.demo-requests.stats');
        Route::get('admin/demo-requests', [DemoRequestController::class, 'adminIndex'])->name('admin.demo-requests.index');
        Route::get('admin/demo-requests/{demoRequest}', [DemoRequestController::class, 'adminShow'])->name('admin.demo-requests.show');
        Route::patch('admin/demo-requests/{demoRequest}', [DemoRequestController::class, 'adminUpdate'])->name('admin.demo-requests.update');
    });
});
