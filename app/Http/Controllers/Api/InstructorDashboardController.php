<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\TestSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstructorDashboardController extends Controller
{
    public function overview(Request $request)
    {
        $user = $request->user();

        $courses = Course::where('instructor_id', $user->id)->get();
        $courseIds = $courses->pluck('id');

        $stats = [
            'total_courses' => $courses->count(),
            'published_courses' => $courses->where('status', 'published')->count(),
            'total_students' => Enrollment::whereIn('course_id', $courseIds)
                ->where('status', 'active')
                ->distinct('user_id')
                ->count(),
            'total_revenue' => Payment::whereIn('course_id', $courseIds)
                ->where('status', 'completed')
                ->sum('instructor_amount'),
            'pending_reviews' => TestSubmission::whereHas('test', function ($query) use ($user) {
                $query->whereHasMorph('testable', ['App\Models\Course', 'App\Models\Chapter', 'App\Models\Lesson'],
                    function ($q) use ($user) {
                        $q->where('instructor_id', $user->id);
                    });
            })
                ->where('status', 'submitted')
                ->whereHas('answers', function ($query) {
                    $query->where('requires_manual_review', true);
                })
                ->count(),
        ];

        // Revenue chart (last 12 months)
        $revenueByMonth = Payment::whereIn('course_id', $courseIds)
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subMonths(12))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('SUM(instructor_amount) as revenue')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Top performing courses
        $topCourses = Course::where('instructor_id', $user->id)
            ->withCount('enrollments')
            ->orderBy('enrollments_count', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'revenue_chart' => $revenueByMonth,
                'top_courses' => $topCourses,
            ],
        ]);
    }

    public function myCourses(Request $request)
    {
        $query = Course::where('instructor_id', $request->user()->id)
            ->withCount(['enrollments', 'chapters']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $courses = $query->orderBy('created_at', 'desc')->paginate(12);

        return response()->json([
            'success' => true,
            'data' => $courses,
        ]);
    }

    public function courseAnalytics($courseId)
    {
        $course = Course::where('instructor_id', auth()->id())
            ->withCount('enrollments')
            ->findOrFail($courseId);

        $enrollments = Enrollment::where('course_id', $courseId)->get();

        $analytics = [
            'total_enrollments' => $enrollments->count(),
            'active_students' => $enrollments->where('status', 'active')->count(),
            'completed_students' => $enrollments->where('status', 'completed')->count(),
            'average_progress' => round($enrollments->avg('progress_percentage'), 2),
            'completion_rate' => $enrollments->count() > 0
                ? round(($enrollments->where('status', 'completed')->count() / $enrollments->count()) * 100, 2)
                : 0,
            'total_revenue' => Payment::where('course_id', $courseId)
                ->where('status', 'completed')
                ->sum('instructor_amount'),
            'average_rating' => $course->average_rating,
            'total_reviews' => $course->total_reviews,
        ];

        // Enrollment trend (last 30 days)
        $enrollmentTrend = Enrollment::where('course_id', $courseId)
            ->where('enrolled_at', '>=', now()->subDays(30))
            ->select(
                DB::raw('DATE(enrolled_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'analytics' => $analytics,
                'enrollment_trend' => $enrollmentTrend,
            ],
        ]);
    }

    public function myStudents(Request $request)
    {
        $courseIds = Course::where('instructor_id', $request->user()->id)
            ->pluck('id');

        $query = Enrollment::whereIn('course_id', $courseIds)
            ->with(['user', 'course']);

        if ($request->has('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $students = $query->orderBy('enrolled_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $students,
        ]);
    }

    public function revenue(Request $request)
    {
        $query = Payment::whereHas('course', function ($q) use ($request) {
            $q->where('instructor_id', $request->user()->id);
        })
            ->where('status', 'completed')
            ->with('course');

        // Date filter
        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->end_date);
        }

        $payments = $query->orderBy('created_at', 'desc')
            ->paginate(20);

        $summary = [
            'total_revenue' => $query->sum('instructor_amount'),
            'total_transactions' => $query->count(),
            'average_transaction' => $query->avg('instructor_amount'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'payments' => $payments,
                'summary' => $summary,
            ],
        ]);
    }
}
