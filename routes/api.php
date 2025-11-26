<?php

use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ChapterController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\InstructorDashboardController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ResourceController;
use App\Http\Controllers\Api\StudentDashboardController;
use App\Http\Controllers\Api\TestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public routes
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'forgotPassword']);
Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword']);

Route::get('/media/{media}/serve', [MediaController::class, 'serve'])->name('media.serve');
Route::get('/media/{id}/thumbnail', [MediaController::class, 'thumbnail']);

// Public courses
Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/{id}', [CourseController::class, 'show']);
Route::get('/courses/slug/{slug}', [CourseController::class, 'getBySlug']);

// Categories
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);

// Certificate verification
Route::get('/certificates/verify/{code}', [StudentDashboardController::class, 'verifyCertificate']);

// Payment webhooks
Route::post('/webhooks/stripe', [PaymentController::class, 'webhookStripe']);
Route::post('/webhooks/paypal', [PaymentController::class, 'webhookPaypal']);

Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/me', [LoginController::class, 'me']);

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

    // ===== CHAPTERS -> LESSONS =====
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
});

Route::get('/status', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is running',
    ]);
});
