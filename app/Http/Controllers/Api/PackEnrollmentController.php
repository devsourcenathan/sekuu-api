<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pack;
use App\Models\PackEnrollment;
use App\Services\PackService;
use Illuminate\Http\Request;

class PackEnrollmentController extends Controller
{
    protected $packService;

    public function __construct(PackService $packService)
    {
        $this->packService = $packService;
    }

    /**
     * Enroll in a pack
     */
    public function enroll(Request $request, $id)
    {
        $pack = Pack::findOrFail($id);

        // $this->authorize('enroll', $pack);

        try {
            $packEnrollment = $this->packService->enrollUserInPack(
                $request->user(),
                $pack
            );

            return response()->json([
                'success' => true,
                'message' => 'Enrolled in pack successfully',
                'data' => $packEnrollment,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get user's pack enrollments
     */
    public function myPackEnrollments(Request $request)
    {
        $enrollments = PackEnrollment::with(['pack.courses', 'pack.instructor'])
            ->where('user_id', $request->user()->id)
            ->orderBy('enrolled_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $enrollments,
        ]);
    }

    /**
     * Get pack enrollment details
     */
    public function show($id)
    {
        $enrollment = PackEnrollment::with([
            'pack.courses',
            'courseEnrollments.course',
        ])->findOrFail($id);

        // Check if user owns this enrollment
        if ($enrollment->user_id !== auth()->id() && ! auth()->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $enrollment,
        ]);
    }

    /**
     * Get pack enrollment progress
     */
    public function progress($id)
    {
        $enrollment = PackEnrollment::with([
            'pack.courses',
            'courseEnrollments.course',
        ])->findOrFail($id);

        // Check if user owns this enrollment
        if ($enrollment->user_id !== auth()->id() && ! auth()->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Update progress
        $enrollment = $this->packService->updatePackProgress($enrollment);

        $progress = [
            'pack_id' => $enrollment->pack_id,
            'status' => $enrollment->status,
            'progress_percentage' => $enrollment->progress_percentage,
            'completed_courses' => $enrollment->completed_courses,
            'total_courses' => $enrollment->total_courses,
            'enrolled_at' => $enrollment->enrolled_at,
            'expires_at' => $enrollment->expires_at,
            'completed_at' => $enrollment->completed_at,
            'certificate_issued' => $enrollment->certificate_issued,
            'courses' => $enrollment->courseEnrollments->map(function ($courseEnrollment) {
                return [
                    'course_id' => $courseEnrollment->course_id,
                    'course_title' => $courseEnrollment->course->title,
                    'status' => $courseEnrollment->status,
                    'progress_percentage' => $courseEnrollment->progress_percentage,
                    'completed_lessons' => $courseEnrollment->completed_lessons,
                    'total_lessons' => $courseEnrollment->total_lessons,
                ];
            }),
        ];

        return response()->json([
            'success' => true,
            'data' => $progress,
        ]);
    }

    /**
     * Cancel pack enrollment
     */
    public function cancel($id)
    {
        $enrollment = PackEnrollment::findOrFail($id);

        // Check if user owns this enrollment
        if ($enrollment->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $enrollment->update(['status' => 'cancelled']);

            // Also cancel all course enrollments
            $enrollment->courseEnrollments()->update(['status' => 'cancelled']);

            return response()->json([
                'success' => true,
                'message' => 'Pack enrollment cancelled successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
