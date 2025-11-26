<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class StudentDashboardController extends Controller
{
    public function overview(Request $request)
    {
        $user = $request->user();

        $enrollments = Enrollment::where('user_id', $user->id)
            ->with(['course' => function ($query) {
                $query->select('id', 'title', 'cover_image', 'total_duration_minutes');
            }])
            ->get();

        $stats = [
            'total_enrolled' => $enrollments->count(),
            'in_progress' => $enrollments->where('status', 'active')->count(),
            'completed' => $enrollments->where('status', 'completed')->count(),
            'certificates_earned' => Certificate::where('user_id', $user->id)->count(),
            'total_learning_time' => $enrollments->sum(function ($enrollment) {
                return $enrollment->lessonProgress()->sum('watch_time_seconds');
            }) / 3600, // Convert to hours
        ];

        $recentActivity = $enrollments
            ->sortByDesc('last_accessed_at')
            ->take(5)
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'recent_activity' => $recentActivity,
            ],
        ]);
    }

    public function myEnrollments(Request $request)
    {
        $query = Enrollment::where('user_id', $request->user()->id)
            ->with(['course.instructor']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $enrollments = $query->orderBy('last_accessed_at', 'desc')
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $enrollments,
        ]);
    }

    public function enrollmentDetail(Request $request, $enrollmentId)
    {
        $enrollment = Enrollment::where('user_id', $request->user()->id)
            ->with([
                'course.chapters.lessons',
                'lessonProgress',
            ])
            ->findOrFail($enrollmentId);

        return response()->json([
            'success' => true,
            'data' => $enrollment,
        ]);
    }

    public function myCertificates(Request $request)
    {
        $certificates = Certificate::where('user_id', $request->user()->id)
            ->with('course')
            ->orderBy('completion_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $certificates,
        ]);
    }

    public function downloadCertificate($certificateId)
    {
        $certificate = Certificate::findOrFail($certificateId);

        if (! $certificate->pdf_path) {
            return response()->json([
                'success' => false,
                'message' => 'Certificate PDF not available',
            ], 404);
        }

        return response()->download(storage_path('app/public/'.$certificate->pdf_path));
    }

    public function verifyCertificate($verificationCode)
    {
        $certificate = Certificate::where('verification_code', $verificationCode)
            ->with(['user', 'course'])
            ->first();

        if (! $certificate) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'is_valid' => $certificate->is_verified,
                'student_name' => $certificate->student_name,
                'course_title' => $certificate->course_title,
                'completion_date' => $certificate->completion_date,
                'certificate_number' => $certificate->certificate_number,
            ],
        ]);
    }
}
