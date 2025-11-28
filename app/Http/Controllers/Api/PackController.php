<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Pack;
use App\Services\PackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PackController extends Controller
{
    protected $packService;

    public function __construct(PackService $packService)
    {
        $this->packService = $packService;
    }

    /**
     * Get list of packs
     */
    public function index(Request $request)
    {
        $query = Pack::with(['instructor', 'courses']);
            // ->where('is_active', true)
            // ->where('is_public', true)
            // ->whereNotNull('published_at');

        // Filters
        if ($request->has('instructor_id')) {
            $query->where('instructor_id', $request->instructor_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $packs = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $packs,
        ]);
    }

    /**
     * Create a new pack
     */
    public function store(Request $request)
    {
        $this->authorize('create', Pack::class);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'cover_image' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'is_public' => 'nullable|boolean',
            'max_enrollments' => 'nullable|integer|min:1',
            'access_duration_days' => 'nullable|integer|min:1',
            'enrollment_start_date' => 'nullable|date',
            'enrollment_end_date' => 'nullable|date|after:enrollment_start_date',
            'has_certificate' => 'nullable|boolean',
            'require_sequential_completion' => 'nullable|boolean',
            'recommended_order' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $pack = $this->packService->createPack(
                $request->all(),
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Pack created successfully',
                'data' => $pack,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get pack details
     */
    public function show($id)
    {
        $pack = Pack::with([
            'instructor',
            'courses.category',
            'courses.tags',
            'courses.chapters.lessons',
        ])->findOrFail($id);

        // $this->authorize('view', $pack);

        return response()->json([
            'success' => true,
            'data' => $pack,
        ]);
    }

    /**
     * Update a pack
     */
    public function update(Request $request, $id)
    {
        $pack = Pack::findOrFail($id);

        $this->authorize('update', $pack);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'price' => 'sometimes|required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'cover_image' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'is_public' => 'nullable|boolean',
            'max_enrollments' => 'nullable|integer|min:1',
            'access_duration_days' => 'nullable|integer|min:1',
            'enrollment_start_date' => 'nullable|date',
            'enrollment_end_date' => 'nullable|date|after:enrollment_start_date',
            'has_certificate' => 'nullable|boolean',
            'require_sequential_completion' => 'nullable|boolean',
            'recommended_order' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $pack = $this->packService->updatePack($pack, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Pack updated successfully',
                'data' => $pack,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a pack
     */
    public function destroy($id)
    {
        $pack = Pack::findOrFail($id);

        $this->authorize('delete', $pack);

        try {
            $pack->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pack deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Publish a pack
     */
    public function publish($id)
    {
        $pack = Pack::findOrFail($id);

        $this->authorize('publish', $pack);

        try {
            $pack = $this->packService->publishPack($pack);

            return response()->json([
                'success' => true,
                'message' => 'Pack published successfully',
                'data' => $pack,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Unpublish a pack
     */
    public function unpublish($id)
    {
        $pack = Pack::findOrFail($id);

        $this->authorize('publish', $pack);

        try {
            $pack = $this->packService->unpublishPack($pack);

            return response()->json([
                'success' => true,
                'message' => 'Pack unpublished successfully',
                'data' => $pack,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Add a course to a pack
     */
    public function addCourse(Request $request, $id)
    {
        $pack = Pack::findOrFail($id);

        $this->authorize('update', $pack);

        $validator = Validator::make($request->all(), [
            'course_id' => 'required|exists:courses,id',
            'order' => 'nullable|integer|min:0',
            'is_required' => 'nullable|boolean',
            'access_config' => 'nullable|array',
            'access_config.include_chapters' => 'nullable|array',
            'access_config.include_lessons' => 'nullable|array',
            'access_config.include_tests' => 'nullable|boolean',
            'access_config.include_resources' => 'nullable|boolean',
            'access_config.allow_download' => 'nullable|boolean',
            'access_config.include_certificate' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $course = Course::findOrFail($request->course_id);
            $pack = $this->packService->addCourseToPack($pack, $course, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Course added to pack successfully',
                'data' => $pack,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove a course from a pack
     */
    public function removeCourse($packId, $courseId)
    {
        $pack = Pack::findOrFail($packId);
        $course = Course::findOrFail($courseId);

        $this->authorize('update', $pack);

        try {
            $pack = $this->packService->removeCourseFromPack($pack, $course);

            return response()->json([
                'success' => true,
                'message' => 'Course removed from pack successfully',
                'data' => $pack,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update course configuration in a pack
     */
    public function updateCourseConfig(Request $request, $packId, $courseId)
    {
        $pack = Pack::findOrFail($packId);
        $course = Course::findOrFail($courseId);

        $this->authorize('update', $pack);

        $validator = Validator::make($request->all(), [
            'order' => 'nullable|integer|min:0',
            'is_required' => 'nullable|boolean',
            'access_config' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $pack = $this->packService->updatePackCourseConfig($pack, $course, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Course configuration updated successfully',
                'data' => $pack,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get instructor's packs
     */
    public function myPacks(Request $request)
    {
        $packs = Pack::with(['courses.chapters.lessons'])
            ->where('instructor_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $packs,
        ]);
    }

    /**
     * Get pack statistics
     */
    public function statistics($id)
    {
        $pack = Pack::with([
            'packEnrollments.user',
            'courses',
        ])->findOrFail($id);

        $this->authorize('view', $pack);

        $stats = [
            'total_enrollments' => $pack->students_enrolled,
            'active_enrollments' => $pack->packEnrollments()->where('status', 'active')->count(),
            'completed_enrollments' => $pack->packEnrollments()->where('status', 'completed')->count(),
            'total_revenue' => $pack->students_enrolled * $pack->price,
            'average_progress' => $pack->packEnrollments()->avg('progress_percentage') ?? 0,
            'total_courses' => $pack->total_courses,
            'enrollments_by_month' => $pack->packEnrollments()
                ->selectRaw('DATE_FORMAT(enrolled_at, "%Y-%m") as month, COUNT(*) as count')
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->limit(12)
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    // ===== ADMIN METHODS =====

    /**
     * Admin: Get all packs with advanced filters
     */
    public function adminIndex(Request $request)
    {
        $query = Pack::with(['instructor', 'courses']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('instructor_id')) {
            $query->where('instructor_id', $request->instructor_id);
        }

        if ($request->has('status')) {
            switch ($request->status) {
                case 'active':
                    $query->where('is_active', true);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'published':
                    $query->where('is_active', true)->whereNotNull('published_at');
                    break;
            }
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $packs = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $packs,
        ]);
    }

    /**
     * Admin: Get global pack statistics
     */
    public function adminStatistics()
    {
        $totalPacks = Pack::count();
        $activePacks = Pack::where('is_active', true)->count();
        $publishedPacks = Pack::where('is_active', true)->whereNotNull('published_at')->count();
        
        $totalStudents = Pack::sum('students_enrolled');
        $totalRevenue = Pack::selectRaw('SUM(students_enrolled * price) as total')->value('total') ?? 0;

        $packsByMonth = Pack::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();

        $topPacks = Pack::with('instructor')
            ->orderBy('students_enrolled', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($pack) {
                return [
                    'id' => $pack->id,
                    'title' => $pack->title,
                    'instructor' => $pack->instructor->name ?? 'Unknown',
                    'students' => $pack->students_enrolled,
                    'revenue' => $pack->students_enrolled * $pack->price,
                    'courses' => $pack->total_courses,
                ];
            });

        $stats = [
            'total_packs' => $totalPacks,
            'active_packs' => $activePacks,
            'published_packs' => $publishedPacks,
            'total_students' => $totalStudents,
            'total_revenue' => $totalRevenue,
            'packs_by_month' => $packsByMonth,
            'top_packs' => $topPacks,
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Admin: Update pack status
     */
    public function adminUpdateStatus(Request $request, $id)
    {
        $pack = Pack::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $pack->update([
                'is_active' => $request->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pack status updated successfully',
                'data' => $pack->fresh(['instructor', 'courses']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Force delete a pack (even with enrollments)
     */
    public function adminForceDelete($id)
    {
        $pack = Pack::withTrashed()->findOrFail($id);

        try {
            $pack->packEnrollments()->delete();
            $pack->courses()->detach();
            $pack->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'Pack permanently deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}