<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChapterController extends Controller
{
    public function index($courseId)
    {
        $course = Course::findOrFail($courseId);
        $chapters = $course->chapters()->with('lessons')->get();

        return response()->json([
            'success' => true,
            'data' => $chapters,
        ]);
    }

    public function store(Request $request, $courseId)
    {
        $course = Course::findOrFail($courseId);

        // Check ownership
        if ($course->instructor_id !== $request->user()->id &&
            ! $request->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'required|integer|min:0',
            'is_free' => 'boolean',
            'is_published' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $chapter = $course->chapters()->create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Chapter created successfully',
            'data' => $chapter,
        ], 201);
    }

    public function show($courseId, $id)
    {
        $chapter = Chapter::with(['lessons', 'resources'])
            ->where('course_id', $courseId)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $chapter,
        ]);
    }

    public function update(Request $request, $courseId, $id)
    {
        $chapter = Chapter::where('course_id', $courseId)->findOrFail($id);
        $course = $chapter->course;

        // Check ownership
        if ($course->instructor_id !== $request->user()->id &&
            ! $request->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'order' => 'sometimes|required|integer|min:0',
            'is_free' => 'boolean',
            'is_published' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $chapter->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Chapter updated successfully',
            'data' => $chapter->fresh(),
        ]);
    }

    public function destroy(Request $request, $courseId, $id)
    {
        $chapter = Chapter::where('course_id', $courseId)->findOrFail($id);
        $course = $chapter->course;

        // Check ownership
        if ($course->instructor_id !== $request->user()->id &&
            ! $request->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $chapter->delete();

        return response()->json([
            'success' => true,
            'message' => 'Chapter deleted successfully',
        ]);
    }
}
