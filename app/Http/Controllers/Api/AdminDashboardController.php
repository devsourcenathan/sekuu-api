<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function overview()
    {
        $stats = [
            'total_users' => User::count(),
            'total_instructors' => User::whereHas('roles', function ($query) {
                $query->where('slug', 'instructor');
            })->count(),
            'total_students' => User::whereHas('roles', function ($query) {
                $query->where('slug', 'student');
            })->count(),
            'total_courses' => Course::count(),
            'published_courses' => Course::where('status', 'published')->count(),
            'total_enrollments' => Enrollment::count(),
            'total_revenue' => Payment::where('status', 'completed')->sum('amount'),
            'platform_revenue' => Payment::where('status', 'completed')->sum('platform_fee'),
        ];

        // User growth (last 12 months)
        $userGrowth = User::where('created_at', '>=', now()->subMonths(12))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Revenue growth
        $revenueGrowth = Payment::where('status', 'completed')
            ->where('created_at', '>=', now()->subMonths(12))
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('SUM(amount) as revenue')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Top courses
        $topCourses = Course::withCount('enrollments')
            ->orderBy('enrollments_count', 'desc')
            ->take(10)
            ->get();

        // Top instructors
        $topInstructors = User::whereHas('roles', function ($query) {
            $query->where('slug', 'instructor');
        })
            ->withCount(['instructedCourses as total_students' => function ($query) {
                $query->join('enrollments', 'courses.id', '=', 'enrollments.course_id');
            }])
            ->orderBy('total_students', 'desc')
            ->take(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'user_growth' => $userGrowth,
                'revenue_growth' => $revenueGrowth,
                'top_courses' => $topCourses,
                'top_instructors' => $topInstructors,
            ],
        ]);
    }

    public function users(Request $request)
    {
        $query = User::with('roles');

        // if ($request->has('role')) {
        //     $query->whereHas('roles', function ($q) use ($request) {
        //         $q->where('slug', $request->role);
        //     });
        // }

        // if ($request->has('search')) {
        //     $search = $request->search;
        //     $query->where(function ($q) use ($search) {
        //         $q->where('name', 'like', "%{$search}%")
        //             ->orWhere('email', 'like', "%{$search}%");
        //     });
        // }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    public function courses(Request $request)
    {
        $query = Course::with(['instructor', 'category'])
            ->withCount('enrollments');

        // if ($request->has('status')) {
        //     $query->where('status', $request->status);
        // }

        $courses = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $courses,
        ]);
    }

    public function payments(Request $request)
    {
        $query = Payment::with(['user', 'course']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->start_date);
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }
}
