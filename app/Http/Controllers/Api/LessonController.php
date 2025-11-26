<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LessonController extends Controller
{
    protected $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    public function index($chapterId)
    {
        $chapter = Chapter::findOrFail($chapterId);
        $lessons = $chapter->lessons()->with('resources')->get();

        return response()->json([
            'success' => true,
            'data' => $lessons,
        ]);
    }

    public function store(Request $request, $chapterId)
    {
        $chapter = Chapter::findOrFail($chapterId);
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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content_type' => 'required|in:video,text,pdf,audio,quiz,slides',
            'content' => 'required_if:content_type,text|nullable|string',
            'video_url' => 'required_if:content_type,video|nullable|url',
            'video_provider' => 'required_if:content_type,video|nullable|in:youtube,vimeo,local',
            'video_id' => 'nullable|string',
            'file' => 'nullable|file|max:51200', // 50MB
            'order' => 'required|integer|min:0',
            'duration_minutes' => 'required|integer|min:0',
            'is_free' => 'boolean',
            'is_preview' => 'boolean',
            'is_downloadable' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->except(['file']);

        // Handle file upload
        if ($request->hasFile('file')) {
            $fileData = $this->mediaService->uploadFile(
                $request->file('file'),
                'lessons'
            );

            $data['file_path'] = $fileData['path'];
            $data['file_type'] = $fileData['type'];
            $data['file_size'] = $fileData['size'];
        }

        // Get video info if YouTube or Vimeo
        if ($request->content_type === 'video' && $request->video_id) {
            try {
                if ($request->video_provider === 'youtube') {
                    $videoInfo = $this->mediaService->getYoutubeVideoInfo($request->video_id);
                    $data['video_duration_seconds'] = $videoInfo['duration'];
                } elseif ($request->video_provider === 'vimeo') {
                    $videoInfo = $this->mediaService->getVimeoVideoInfo($request->video_id);
                    $data['video_duration_seconds'] = $videoInfo['duration'];
                }
            } catch (\Exception $e) {
                // Continue without video info
            }
        }

        $lesson = $chapter->lessons()->create($data);

        // Update chapter duration
        $chapter->updateDuration();

        return response()->json([
            'success' => true,
            'message' => 'Lesson created successfully',
            'data' => $lesson,
        ], 201);
    }

    public function show($chapterId, $id)
    {
        $lesson = Lesson::with(['resources', 'chapter.course'])
            ->where('chapter_id', $chapterId)
            ->findOrFail($id);

        // Check access
        if (auth()->check() && ! $lesson->canUserAccess(auth()->user())) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this lesson',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $lesson,
        ]);
    }

    public function update(Request $request, $chapterId, $id)
    {
        $lesson = Lesson::where('chapter_id', $chapterId)->findOrFail($id);
        $course = $lesson->chapter->course;

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
            'content' => 'nullable|string',
            'video_url' => 'nullable|url',
            'file' => 'nullable|file|max:51200',
            'order' => 'sometimes|required|integer|min:0',
            'duration_minutes' => 'sometimes|required|integer|min:0',
            'is_free' => 'boolean',
            'is_preview' => 'boolean',
            'is_downloadable' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->except(['file']);

        // Handle file upload
        if ($request->hasFile('file')) {
            // Delete old file
            if ($lesson->file_path) {
                $this->mediaService->deleteFile($lesson->file_path);
            }

            $fileData = $this->mediaService->uploadFile(
                $request->file('file'),
                'lessons'
            );

            $data['file_path'] = $fileData['path'];
            $data['file_type'] = $fileData['type'];
            $data['file_size'] = $fileData['size'];
        }

        $lesson->update($data);

        // Update chapter duration
        $lesson->chapter->updateDuration();

        return response()->json([
            'success' => true,
            'message' => 'Lesson updated successfully',
            'data' => $lesson->fresh(),
        ]);
    }

    public function destroy(Request $request, $chapterId, $id)
    {
        $lesson = Lesson::where('chapter_id', $chapterId)->findOrFail($id);
        $course = $lesson->chapter->course;

        // Check ownership
        if ($course->instructor_id !== $request->user()->id &&
            ! $request->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Delete file if exists
        if ($lesson->file_path) {
            $this->mediaService->deleteFile($lesson->file_path);
        }

        $chapter = $lesson->chapter;
        $lesson->delete();

        // Update chapter duration
        $chapter->updateDuration();

        return response()->json([
            'success' => true,
            'message' => 'Lesson deleted successfully',
        ]);
    }

    public function markAsComplete(Request $request, $chapterId, $id)
    {
        $lesson = Lesson::where('chapter_id', $chapterId)->findOrFail($id);

        // Get user's enrollment
        $enrollment = $request->user()->enrollments()
            ->where('course_id', $lesson->chapter->course_id)
            ->where('status', 'active')
            ->firstOrFail();

        // Find or create progress
        $progress = LessonProgress::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'lesson_id' => $lesson->id,
            ],
            [
                'enrollment_id' => $enrollment->id,
                'started_at' => now(),
            ]
        );

        $progress->markAsCompleted();

        return response()->json([
            'success' => true,
            'message' => 'Lesson marked as complete',
            'data' => $progress,
        ]);
    }

    public function updateProgress(Request $request, $chapterId, $id)
    {
        $validator = Validator::make($request->all(), [
            'progress_percentage' => 'required|integer|min:0|max:100',
            'watch_time_seconds' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $lesson = Lesson::where('chapter_id', $chapterId)->findOrFail($id);

        // Get user's enrollment
        $enrollment = $request->user()->enrollments()
            ->where('course_id', $lesson->chapter->course_id)
            ->where('status', 'active')
            ->firstOrFail();

        // Find or create progress
        $progress = LessonProgress::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'lesson_id' => $lesson->id,
            ],
            [
                'enrollment_id' => $enrollment->id,
                'started_at' => now(),
            ]
        );

        $progress->update([
            'progress_percentage' => $request->progress_percentage,
            'watch_time_seconds' => $request->watch_time_seconds ?? $progress->watch_time_seconds,
            'last_accessed_at' => now(),
        ]);

        // Auto complete if threshold reached
        if ($lesson->auto_complete &&
            $request->progress_percentage >= $lesson->completion_threshold &&
            ! $progress->is_completed) {
            $progress->markAsCompleted();
        }

        return response()->json([
            'success' => true,
            'message' => 'Progress updated',
            'data' => $progress,
        ]);
    }
}
