<?php

use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ChapterController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\InstructorDashboardController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\MeetingRequestController;
use App\Http\Controllers\Api\PackController;
use App\Http\Controllers\Api\PackEnrollmentController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ResourceController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\StudentDashboardController;
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public routes
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);
Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');
Route::post('/forgot-password', [ForgotPasswordController::class, 'forgotPassword']);
Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword']);

Route::get('/media/{media}/serve', [MediaController::class, 'serve'])->name('media.serve');
Route::get('/media/{id}/thumbnail', [MediaController::class, 'thumbnail']);

// Public courses
Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/{id}', [CourseController::class, 'show']);
Route::get('/courses/slug/{slug}', [CourseController::class, 'getBySlug']);

// Public packs
Route::get('/packs', [PackController::class, 'index']);
Route::get('/packs/{id}', [PackController::class, 'show']);

// Categories
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);

// Certificate verification
Route::get('/certificates/verify/{code}', [StudentDashboardController::class, 'verifyCertificate']);

// Legal Pages (public)
Route::get('/legal/{slug}', [\App\Http\Controllers\Api\LegalPageController::class, 'show']);

// Currency (public)
Route::get('/currency/rates/{base}', [\App\Http\Controllers\Api\CurrencyController::class, 'getRates']);
Route::get('/currency/convert', [\App\Http\Controllers\Api\CurrencyController::class, 'convert']);

// Payment webhooks
Route::post('/webhooks/stripe', [PaymentController::class, 'webhookStripe']);
Route::post('/webhooks/paypal', [PaymentController::class, 'webhookPaypal']);

Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/me', [LoginController::class, 'me']);

    // User Settings
    Route::put('/user/settings/currency', [\App\Http\Controllers\Api\CurrencyController::class, 'updatePreferredCurrency']);
    Route::put('/user/profile', [\App\Http\Controllers\Api\UserSettingsController::class, 'updateProfile']);
    Route::put('/user/password', [\App\Http\Controllers\Api\UserSettingsController::class, 'updatePassword']);

    // Payment Methods
    Route::prefix('payment-methods')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\PaymentMethodController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\PaymentMethodController::class, 'store']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\PaymentMethodController::class, 'destroy']);
        Route::put('/{id}/default', [\App\Http\Controllers\Api\PaymentMethodController::class, 'setDefault']);
    });

    // Instructor Payout
    Route::prefix('instructor')->middleware('permission:courses.view')->group(function () {
        Route::get('/earnings', [\App\Http\Controllers\Api\InstructorPayoutController::class, 'getEarnings']);
        Route::put('/payout-settings', [\App\Http\Controllers\Api\InstructorPayoutController::class, 'updatePayoutSettings']);
        Route::post('/request-payout', [\App\Http\Controllers\Api\InstructorPayoutController::class, 'requestPayout']);
    });

    // ===== COURSES =====
    Route::prefix('courses')->group(function () {
        Route::post('/', [CourseController::class, 'store'])->middleware('permission:courses.create');
        Route::get('/my-courses', [CourseController::class, 'myInstructorCourses'])->middleware('permission:courses.view');
        Route::put('/{id}', [CourseController::class, 'update'])->middleware('permission:courses.edit');
        Route::delete('/{id}', [CourseController::class, 'destroy'])->middleware('permission:courses.delete');
        Route::post('/{id}/publish', [CourseController::class, 'publish'])->middleware('permission:courses.publish');
        Route::post('/{id}/enroll', [CourseController::class, 'enroll']);

        // Tests
        Route::get('/{courseId}/tests', [TestController::class, 'getTestsByCourse']);

        // Chapters
        Route::get('/{courseId}/chapters', [ChapterController::class, 'index']);
        Route::post('/{courseId}/chapters', [ChapterController::class, 'store'])->middleware('permission:chapters.manage');
        Route::get('/{courseId}/chapters/{id}', [ChapterController::class, 'show']);
        Route::put('/{courseId}/chapters/{id}', [ChapterController::class, 'update'])->middleware('permission:chapters.manage');
        Route::delete('/{courseId}/chapters/{id}', [ChapterController::class, 'destroy'])->middleware('permission:chapters.manage');
    });

    // ===== PACKS =====
    Route::prefix('packs')->group(function () {
        // Instructor pack management
        Route::post('/', [PackController::class, 'store'])->middleware('permission:courses.create');
        Route::put('/{id}', [PackController::class, 'update'])->middleware('permission:courses.edit');
        Route::delete('/{id}', [PackController::class, 'destroy'])->middleware('permission:courses.delete');
        Route::post('/{id}/publish', [PackController::class, 'publish'])->middleware('permission:courses.publish');
        Route::post('/{id}/unpublish', [PackController::class, 'unpublish'])->middleware('permission:courses.publish');
        Route::get('/{id}/statistics', [PackController::class, 'statistics'])->middleware('permission:courses.view');
        
        // Course management in pack
        Route::post('/{id}/courses', [PackController::class, 'addCourse'])->middleware('permission:courses.edit');
        Route::delete('/{packId}/courses/{courseId}', [PackController::class, 'removeCourse'])->middleware('permission:courses.edit');
        Route::put('/{packId}/courses/{courseId}', [PackController::class, 'updateCourseConfig'])->middleware('permission:courses.edit');
        
        // Student enrollment
        Route::post('/{id}/enroll', [PackEnrollmentController::class, 'enroll']);
    });

    // Instructor packs
    Route::get('/instructor/packs', [PackController::class, 'myPacks'])->middleware('permission:courses.view');

    // Student pack enrollments
    Route::prefix('student')->group(function () {
        Route::get('/pack-enrollments', [PackEnrollmentController::class, 'myPackEnrollments']);
        Route::get('/pack-enrollments/{id}', [PackEnrollmentController::class, 'show']);
        Route::get('/pack-enrollments/{id}/progress', [PackEnrollmentController::class, 'progress']);
        Route::post('/pack-enrollments/{id}/cancel', [PackEnrollmentController::class, 'cancel']);
    });

    // Admin pack management
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/packs', [PackController::class, 'adminIndex']);
        Route::get('/packs/statistics', [PackController::class, 'adminStatistics']);
        Route::put('/packs/{id}/status', [PackController::class, 'adminUpdateStatus']);
        Route::delete('/packs/{id}/force', [PackController::class, 'adminForceDelete']);
    });


    // ===== CHAPTERS -> LESSONS ===== LESSONS =====
    Route::prefix('chapters')->group(function () {
        // Tests
        Route::get('/{chapterId}/tests', [TestController::class, 'getTestsByChapter']);
    });

    Route::prefix('chapters/{chapterId}/lessons')->group(function () {
        Route::get('/', [LessonController::class, 'index']);
        Route::post('/', [LessonController::class, 'store'])->middleware('permission:lessons.manage');
        Route::get('/{id}', [LessonController::class, 'show']);
        Route::put('/{id}', [LessonController::class, 'update'])->middleware('permission:lessons.manage');
        Route::delete('/{id}', [LessonController::class, 'destroy'])->middleware('permission:lessons.manage');
        Route::post('/{id}/complete', [LessonController::class, 'markAsComplete']);
        Route::post('/{id}/progress', [LessonController::class, 'updateProgress']);
    });

    // ===== LESSONS =====
    Route::prefix('lessons')->group(function () {
        // Tests
        Route::get('/{lessonId}/tests', [TestController::class, 'getTestsByLesson']);
    });

    // ===== CATEGORIES (Admin only) =====
    Route::prefix('categories')->group(function () {
        Route::post('/', [CategoryController::class, 'store'])->middleware('role:admin');
        Route::put('/{id}', [CategoryController::class, 'update'])->middleware('role:admin');
        Route::delete('/{id}', [CategoryController::class, 'destroy'])->middleware('role:admin');
    });

    // ===== RESOURCES =====
    Route::prefix('resources')->group(function () {
        Route::post('/', [ResourceController::class, 'store']);
        Route::get('/{id}/download', [ResourceController::class, 'download']);
        Route::delete('/{id}', [ResourceController::class, 'destroy']);
    });

    // ===== TESTS =====
    Route::prefix('tests')->group(function () {
        Route::post('/', [TestController::class, 'store'])->middleware('permission:tests.create');
        Route::get('/{id}', [TestController::class, 'show']);
        Route::put('/{id}', [TestController::class, 'update'])->middleware('permission:tests.edit');
        Route::delete('/{id}', [TestController::class, 'destroy'])->middleware('permission:tests.delete');
        Route::post('/{testId}/questions', [TestController::class, 'addQuestion'])->middleware('permission:tests.create');
        Route::post('/{testId}/start', [TestController::class, 'startTest']);
        Route::get('/{testId}/my-submissions', [TestController::class, 'mySubmissions']);
        Route::get('/pending-gradings', [TestController::class, 'pendingGradings'])->middleware('permission:tests.evaluate');
    });

    // ===== TEST SUBMISSIONS =====
    Route::prefix('submissions')->group(function () {
        Route::post('/{submissionId}/draft', [TestController::class, 'saveDraft']);
        Route::post('/{submissionId}/submit', [TestController::class, 'submitTest']);
        Route::post('/{submissionId}/grade', [TestController::class, 'gradeSubmission'])->middleware('permission:tests.evaluate');
    });

    // ===== PAYMENTS =====
    Route::prefix('payments')->group(function () {
        Route::get('/calculate/{courseId}', [PaymentController::class, 'calculateTotal']);
        Route::post('/create', [PaymentController::class, 'createPayment']);
        Route::post('/{paymentId}/complete', [PaymentController::class, 'completePayment']);
        Route::get('/my-payments', [PaymentController::class, 'myPayments']);
        Route::post('/{paymentId}/refund', [PaymentController::class, 'requestRefund']);
    });

    // ===== STUDENT DASHBOARD =====
    Route::prefix('student/dashboard')->group(function () {
        Route::get('/overview', [StudentDashboardController::class, 'overview']);
        Route::get('/enrollments', [StudentDashboardController::class, 'myEnrollments']);
        Route::get('/enrollments/{enrollmentId}', [StudentDashboardController::class, 'enrollmentDetail']);
        Route::get('/certificates', [StudentDashboardController::class, 'myCertificates']);
        Route::get('/certificates/{certificateId}/download', [StudentDashboardController::class, 'downloadCertificate']);
    });

    // ===== INSTRUCTOR DASHBOARD =====
    Route::prefix('instructor/dashboard')->group(function () {
        Route::get('/overview', [InstructorDashboardController::class, 'overview']);
        Route::get('/courses', [InstructorDashboardController::class, 'myCourses']);
        Route::get('/courses/{courseId}/analytics', [InstructorDashboardController::class, 'courseAnalytics']);
        Route::get('/students', [InstructorDashboardController::class, 'myStudents']);
        Route::get('/revenue', [InstructorDashboardController::class, 'revenue']);
    });

    // ===== ADMIN DASHBOARD =====
    Route::prefix('admin/dashboard')->group(function () {
        Route::get('/overview', [AdminDashboardController::class, 'overview']);
        Route::get('/users', [AdminDashboardController::class, 'users']);
        Route::get('/courses', [AdminDashboardController::class, 'courses']);
        Route::get('/payments', [AdminDashboardController::class, 'payments']);
    });

    // ===== ADMIN LEGAL PAGES =====
    Route::prefix('admin/legal')->middleware('role:admin')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\LegalPageController::class, 'index']);
        Route::put('/{slug}', [\App\Http\Controllers\Api\LegalPageController::class, 'upsert']);
    });

    // Media management
    Route::prefix('media')->group(function () {
        Route::post('/upload', [MediaController::class, 'upload']);
        Route::post('/upload-vimeo', [MediaController::class, 'uploadToVimeo']);
        Route::post('/link-youtube', [MediaController::class, 'linkYoutube']);
        Route::get('/', [MediaController::class, 'index']);
        Route::get('/{id}', [MediaController::class, 'show']);
        Route::put('/{id}', [MediaController::class, 'update']);
        Route::delete('/{id}', [MediaController::class, 'destroy']);
        Route::get('/{id}/signed-url', [MediaController::class, 'getSignedUrl']);
        Route::get('/{id}/download', [MediaController::class, 'download']);
    });

    // Tests management
    Route::post('/tests', [TestController::class, 'store'])->middleware('permission:tests.create');
    Route::get('/tests/{id}', [TestController::class, 'show']);
    Route::put('/tests/{id}', [TestController::class, 'update'])->middleware('permission:tests.edit');
    Route::delete('/tests/{id}', [TestController::class, 'destroy'])->middleware('permission:tests.delete');

    // Questions
    Route::post('/tests/{testId}/questions', [TestController::class, 'addQuestion'])->middleware('permission:tests.create');

    // Taking tests
    Route::post('/tests/{testId}/start', [TestController::class, 'startTest']);
    Route::post('/submissions/{submissionId}/draft', [TestController::class, 'saveDraft']);
    Route::post('/submissions/{submissionId}/submit', [TestController::class, 'submitTest']);
    Route::get('/tests/{testId}/my-submissions', [TestController::class, 'mySubmissions']);

    // Grading
    Route::post('/submissions/{submissionId}/grade', [TestController::class, 'gradeSubmission'])->middleware('permission:tests.evaluate');
    Route::get('/tests/pending-gradings', [TestController::class, 'pendingGradings'])->middleware('permission:tests.evaluate');

    // ===== ROLES & PERMISSIONS =====
    Route::prefix('roles')->middleware('role:admin')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\RoleController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\RoleController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\Api\RoleController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\Api\RoleController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\RoleController::class, 'destroy']);
    });

    Route::get('/permissions', [\App\Http\Controllers\Api\PermissionController::class, 'index'])->middleware('role:admin');

    // ===== USERS MANAGEMENT =====
    Route::prefix('users')->middleware('role:admin')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{userId}/permissions', [UserController::class, 'getUserPermissions']);
        Route::post('/{userId}/permissions', [UserController::class, 'assignPermission']);
        Route::delete('/{userId}/permissions/{permissionId}', [UserController::class, 'revokePermission']);
        Route::get('/{userId}/effective-permissions', [UserController::class, 'getEffectivePermissions']);
    });

    // ===== SESSIONS (Visioconférence) =====
    Route::prefix('sessions')->group(function () {
        Route::get('/', [SessionController::class, 'index']);
        Route::post('/', [SessionController::class, 'store'])->middleware('permission:courses.view');
        Route::get('/{session}', [SessionController::class, 'show']);
        Route::put('/{session}', [SessionController::class, 'update'])->middleware('permission:courses.view');
        Route::delete('/{session}', [SessionController::class, 'destroy'])->middleware('permission:courses.view');
        Route::post('/{session}/start', [SessionController::class, 'start'])->middleware('permission:courses.view');
        Route::post('/{session}/end', [SessionController::class, 'end'])->middleware('permission:courses.view');
        Route::post('/{session}/token', [SessionController::class, 'generateToken']);
        Route::get('/{session}/participants', [SessionController::class, 'participants']);
        Route::post('/{session}/participants', [SessionController::class, 'addParticipants'])->middleware('permission:courses.view');
        Route::delete('/{session}/participants/{user}', [SessionController::class, 'removeParticipant'])->middleware('permission:courses.view');
    });

    // ===== GROUPS (Groupes d'encadrement) =====
    Route::prefix('groups')->middleware('permission:courses.view')->group(function () {
        Route::get('/', [GroupController::class, 'index']);
        Route::post('/', [GroupController::class, 'store']);
        Route::get('/eligible-students', [GroupController::class, 'eligibleStudents']);
        Route::get('/{group}', [GroupController::class, 'show']);
        Route::put('/{group}', [GroupController::class, 'update']);
        Route::delete('/{group}', [GroupController::class, 'destroy']);
        Route::post('/{group}/members', [GroupController::class, 'addMembers']);
        Route::delete('/{group}/members', [GroupController::class, 'removeMembers']);
    });

    // ===== MEETING REQUESTS =====
    Route::prefix('meeting-requests')->group(function () {
        Route::get('/', [MeetingRequestController::class, 'index']);
        Route::post('/', [MeetingRequestController::class, 'store']);
        Route::get('/eligible-instructors', [MeetingRequestController::class, 'eligibleInstructors']);
        Route::get('/{meetingRequest}', [MeetingRequestController::class, 'show']);
        Route::post('/{meetingRequest}/accept', [MeetingRequestController::class, 'accept'])->middleware('permission:courses.view');
        Route::post('/{meetingRequest}/reject', [MeetingRequestController::class, 'reject'])->middleware('permission:courses.view');
        Route::post('/{meetingRequest}/cancel', [MeetingRequestController::class, 'cancel']);
    });

    // ===== SUBSCRIPTIONS =====
    // Admin subscription plan management
    Route::prefix('admin/subscription-plans')->middleware('role:admin')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\SubscriptionPlanController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\SubscriptionPlanController::class, 'store']);
        Route::get('/{plan}', [\App\Http\Controllers\Api\SubscriptionPlanController::class, 'show']);
        Route::put('/{plan}', [\App\Http\Controllers\Api\SubscriptionPlanController::class, 'update']);
        Route::delete('/{plan}', [\App\Http\Controllers\Api\SubscriptionPlanController::class, 'destroy']);
        Route::put('/{plan}/limits', [\App\Http\Controllers\Api\SubscriptionPlanController::class, 'setLimits']);
        Route::put('/{plan}/features', [\App\Http\Controllers\Api\SubscriptionPlanController::class, 'setFeatures']);
    });

    // User subscription endpoints
    Route::prefix('subscription')->group(function () {
        Route::get('/current', [\App\Http\Controllers\Api\UserSubscriptionController::class, 'current']);
        Route::get('/plans', [\App\Http\Controllers\Api\UserSubscriptionController::class, 'availablePlans']);
        Route::get('/usage', [\App\Http\Controllers\Api\UserSubscriptionController::class, 'usage']);
        Route::get('/history', [\App\Http\Controllers\Api\UserSubscriptionController::class, 'history']);
    });

    // Subscription upgrade/downgrade
    Route::prefix('subscription')->group(function () {
        Route::get('/upgrade/preview/{plan}', [\App\Http\Controllers\Api\SubscriptionUpgradeController::class, 'preview']);
        Route::post('/upgrade', [\App\Http\Controllers\Api\SubscriptionUpgradeController::class, 'upgrade']);
        Route::post('/downgrade', [\App\Http\Controllers\Api\SubscriptionUpgradeController::class, 'downgrade']);
        Route::post('/cancel', [\App\Http\Controllers\Api\SubscriptionUpgradeController::class, 'cancel']);
    });
});

Route::get('/status', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is running',
    ]);
});
