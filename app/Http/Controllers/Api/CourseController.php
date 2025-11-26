<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\CourseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    protected $courseService;

    public function __construct(CourseService $courseService)
    {
        $this->courseService = $courseService;
    }

    public function index(Request $request)
    {
        $query = Course::with(['instructor', 'category', 'tags'])
            ->where('status', 'published')
            ->where('is_public', true);

        // Filters
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        if ($request->has('is_free')) {
            $query->where('is_free', $request->boolean('is_free'));
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

        $courses = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $courses,
        ]);
    }

    public function store(Request $request)
    {
        // Convert boolean strings from FormData to actual booleans
        $data = $request->all();
        if (isset($data['is_free'])) {
            $data['is_free'] = filter_var($data['is_free'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }
        if (isset($data['is_public'])) {
            $data['is_public'] = filter_var($data['is_public'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }
        if (isset($data['requires_approval'])) {
            $data['requires_approval'] = filter_var($data['requires_approval'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }
        if (isset($data['allow_download'])) {
            $data['allow_download'] = filter_var($data['allow_download'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }
        if (isset($data['has_certificate'])) {
            $data['has_certificate'] = filter_var($data['has_certificate'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }
        if (isset($data['has_forum'])) {
            $data['has_forum'] = filter_var($data['has_forum'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $validator = Validator::make($data, [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'nullable|exists:categories,id',
            'level' => 'required|in:beginner,intermediate,advanced',
            'language' => 'required|string|max:10',
            'is_free' => 'required|boolean',
            'price' => 'required_if:is_free,false|nullable|numeric|min:0',
            'currency' => 'required_if:is_free,false|nullable|string|size:3',
            'cover_image' => 'nullable|image|max:2048',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $course = $this->courseService->createCourse(
                $data,
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Course created successfully',
                'data' => $course,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $course = Course::with([
            'instructor',
            'category',
            'tags',
            'chapters.lessons',
            'resources',
        ])->findOrFail($id);

        // Check access permissions
        // if (! $course->isPublished() &&
        //     (! auth()->check() ||
        //      (auth()->user()->id !== $course->instructor_id &&
        //       ! auth()->user()->hasRole('admin')))) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Course not found or not accessible',
        //     ], 404);
        // }

        return response()->json([
            'success' => true,
            'data' => $course,
        ]);
    }

    public function getBySlug($slug)
    {
        $course = Course::with([
            'instructor',
            'category',
            'tags',
            'chapters.lessons',
            'resources',
        ])->where('slug', $slug)->firstOrFail();

        // Check access permissions
        // if (! $course->isPublished() &&
        //     (! auth()->check() ||
        //      (auth()->user()->id !== $course->instructor_id &&
        //       ! auth()->user()->hasRole('admin')))) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Course not found or not accessible',
        //     ], 404);
        // }

        return response()->json([
            'success' => true,
            'data' => $course,
        ]);
    }

    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        // Check ownership or admin
        if ($course->instructor_id !== $request->user()->id &&
            ! $request->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Convert boolean strings from FormData to actual booleans
        $data = $request->all();
        if (isset($data['is_free'])) {
            $data['is_free'] = filter_var($data['is_free'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }
        if (isset($data['is_public'])) {
            $data['is_public'] = filter_var($data['is_public'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }
        if (isset($data['requires_approval'])) {
            $data['requires_approval'] = filter_var($data['requires_approval'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }
        if (isset($data['allow_download'])) {
            $data['allow_download'] = filter_var($data['allow_download'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }
        if (isset($data['has_certificate'])) {
            $data['has_certificate'] = filter_var($data['has_certificate'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }
        if (isset($data['has_forum'])) {
            $data['has_forum'] = filter_var($data['has_forum'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $validator = Validator::make($data, [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'category_id' => 'nullable|exists:categories,id',
            'level' => 'sometimes|required|in:beginner,intermediate,advanced',
            'is_free' => 'sometimes|required|boolean',
            'price' => 'required_if:is_free,false|nullable|numeric|min:0',
            'cover_image' => 'nullable|image|max:2048',
            'tags' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $course = $this->courseService->updateCourse($course, $data);

            return response()->json([
                'success' => true,
                'message' => 'Course updated successfully',
                'data' => $course,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        // Check ownership or admin
        if ($course->instructor_id !== $request->user()->id &&
            ! $request->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $this->courseService->deleteCourse($course);

            return response()->json([
                'success' => true,
                'message' => 'Course deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function publish(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        // Check ownership or admin
        if ($course->instructor_id !== $request->user()->id &&
            ! $request->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $course = $this->courseService->publishCourse($course);

            return response()->json([
                'success' => true,
                'message' => 'Course published successfully',
                'data' => $course,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function enroll(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        try {
            $enrollment = $this->courseService->enrollStudent(
                $course,
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Enrolled successfully',
                'data' => $enrollment,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function myInstructorCourses(Request $request)
    {
        $courses = Course::with(['category', 'tags'])
            ->where('instructor_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $courses,
        ]);
    }
}
