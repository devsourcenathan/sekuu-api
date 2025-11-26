<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MediaController extends Controller
{
    protected $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * Upload media file
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:512000', // 500MB max
            'mediable_type' => 'required|in:App\Models\Course,App\Models\Lesson',
            'mediable_id' => 'required|integer',
            'collection' => 'required|string|in:cover,presentation,lesson_video,lesson_file',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Get mediable entity
            $mediableClass = $request->mediable_type;
            $mediable = $mediableClass::findOrFail($request->mediable_id);

            // Check ownership
            if ($mediable instanceof Course) {
                if ($mediable->instructor_id !== $request->user()->id && ! $request->user()->hasRole('admin')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized',
                    ], 403);
                }
            } elseif ($mediable instanceof Lesson) {
                $course = $mediable->chapter->course;
                if ($course->instructor_id !== $request->user()->id && ! $request->user()->hasRole('admin')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized',
                    ], 403);
                }
            }

            // Upload media
            $media = $this->mediaService->uploadMedia(
                $request->file('file'),
                $mediable,
                $request->collection,
                [
                    'title' => $request->title,
                    'description' => $request->description,
                    'is_public' => $request->boolean('is_public'),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Media uploaded successfully',
                'data' => $media,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload to Vimeo
     */
    public function uploadToVimeo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:mp4,mov,avi|max:2048000', // 2GB max
            'mediable_type' => 'required|in:App\Models\Course,App\Models\Lesson',
            'mediable_id' => 'required|integer',
            'collection' => 'required|string',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'privacy' => 'nullable|in:anybody,disable,unlisted',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $mediableClass = $request->mediable_type;
            $mediable = $mediableClass::findOrFail($request->mediable_id);

            // Check ownership
            if ($mediable instanceof Course) {
                if ($mediable->instructor_id !== $request->user()->id && ! $request->user()->hasRole('admin')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized',
                    ], 403);
                }
            } elseif ($mediable instanceof Lesson) {
                $course = $mediable->chapter->course;
                if ($course->instructor_id !== $request->user()->id && ! $request->user()->hasRole('admin')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized',
                    ], 403);
                }
            }

            // Upload to Vimeo
            $media = $this->mediaService->uploadToVimeo(
                $request->file('file'),
                $mediable,
                [
                    'title' => $request->title,
                    'description' => $request->description,
                    'collection' => $request->collection,
                    'privacy' => $request->privacy ?? 'disable',
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Video is being uploaded to Vimeo. Processing may take a few minutes.',
                'data' => $media,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Link YouTube video
     */
    public function linkYoutube(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'video_id' => 'required|string',
            'mediable_type' => 'required|in:App\Models\Course,App\Models\Lesson',
            'mediable_id' => 'required|integer',
            'collection' => 'required|string',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $mediableClass = $request->mediable_type;
            $mediable = $mediableClass::findOrFail($request->mediable_id);

            // Check ownership
            if ($mediable instanceof Course) {
                if ($mediable->instructor_id !== $request->user()->id && ! $request->user()->hasRole('admin')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized',
                    ], 403);
                }
            } elseif ($mediable instanceof Lesson) {
                $course = $mediable->chapter->course;
                if ($course->instructor_id !== $request->user()->id && ! $request->user()->hasRole('admin')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized',
                    ], 403);
                }
            }

            // Link YouTube video
            $media = $this->mediaService->linkYoutubeVideo(
                $request->video_id,
                $mediable,
                [
                    'title' => $request->title,
                    'description' => $request->description,
                    'collection' => $request->collection,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'YouTube video linked successfully',
                'data' => $media,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get media by ID
     */
    public function show($id)
    {
        $media = Media::with('mediable')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'media' => $media,
                'url' => $media->getUrl(),
                'embed_url' => $media->type === 'video' ? $media->getEmbedUrl() : null,
                'thumbnail_url' => $media->getThumbnailUrl(),
            ],
        ]);
    }

    /**
     * Get all media for a mediable entity
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mediable_type' => 'required|in:App\Models\Course,App\Models\Lesson',
            'mediable_id' => 'required|integer',
            'collection' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Media::where('mediable_type', $request->mediable_type)
            ->where('mediable_id', $request->mediable_id);

        if ($request->has('collection')) {
            $query->where('collection', $request->collection);
        }

        $media = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $media->map(function ($item) {
                return [
                    'media' => $item,
                    'url' => $item->getUrl(),
                    'thumbnail_url' => $item->getThumbnailUrl(),
                ];
            }),
        ]);
    }

    /**
     * Update media information
     */
    public function update(Request $request, $id)
    {
        $media = Media::findOrFail($id);

        // Check ownership
        $mediable = $media->mediable;
        if ($mediable instanceof Course) {
            if ($mediable->instructor_id !== $request->user()->id && ! $request->user()->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }
        } elseif ($mediable instanceof Lesson) {
            $course = $mediable->chapter->course;
            if ($course->instructor_id !== $request->user()->id && ! $request->user()->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $media->update($request->only(['title', 'description', 'is_public']));

        return response()->json([
            'success' => true,
            'message' => 'Media updated successfully',
            'data' => $media->fresh(),
        ]);
    }

    /**
     * Delete media
     */
    public function destroy(Request $request, $id)
    {
        $media = Media::findOrFail($id);

        // Check ownership
        $mediable = $media->mediable;
        if ($mediable instanceof Course) {
            if ($mediable->instructor_id !== $request->user()->id && ! $request->user()->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }
        } elseif ($mediable instanceof Lesson) {
            $course = $mediable->chapter->course;
            if ($course->instructor_id !== $request->user()->id && ! $request->user()->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }
        }

        try {
            $this->mediaService->deleteMedia($media);

            return response()->json([
                'success' => true,
                'message' => 'Media deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Serve private media file
     */
    public function serve(Request $request, $id)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired signature');
        }

        $media = Media::findOrFail($id);

        // Check if user has access
        if (! $media->is_public && auth()->check()) {
            $mediable = $media->mediable;

            if ($mediable instanceof Course) {
                if (! $mediable->canUserAccess($request->user())) {
                    abort(403, 'Unauthorized access');
                }
            } elseif ($mediable instanceof Lesson) {
                if (! $mediable->canUserAccess($request->user())) {
                    abort(403, 'Unauthorized access');
                }
            }
        }

        // Increment views
        $media->incrementViews();

        // Serve file
        $path = Storage::disk($media->disk)->path($media->file_path);

        if (! file_exists($path)) {
            abort(404, 'File not found');
        }

        return response()->file($path, [
            'Content-Type' => $media->mime_type,
            'Content-Disposition' => 'inline; filename="'.$media->file_name.'"',
        ]);
    }

    /**
     * Download media file
     */
    public function download(Request $request, $id)
    {
        $media = Media::findOrFail($id);

        // Check if user has access
        if (! $media->is_public && auth()->check()) {
            $mediable = $media->mediable;

            if ($mediable instanceof Course) {
                if (! $mediable->canUserAccess($request->user())) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized access',
                    ], 403);
                }
            } elseif ($mediable instanceof Lesson) {
                if (! $mediable->canUserAccess($request->user())) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized access',
                    ], 403);
                }
            }
        }

        // Increment downloads
        $media->incrementDownloads();

        // For external providers, return redirect URL
        if ($media->provider !== 'local') {
            return response()->json([
                'success' => true,
                'download_url' => $media->getUrl(),
            ]);
        }

        // For local files, return download
        $path = Storage::disk($media->disk)->path($media->file_path);

        if (! file_exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
            ], 404);
        }

        return response()->download($path, $media->file_name);
    }

    /**
     * Get signed URL for private media
     */
    public function getSignedUrl(Request $request, $id)
    {
        $media = Media::findOrFail($id);

        // Check if user has access
        if (! $media->is_public) {
            $mediable = $media->mediable;

            if ($mediable instanceof Course) {
                if (! $mediable->canUserAccess($request->user())) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized access',
                    ], 403);
                }
            } elseif ($mediable instanceof Lesson) {
                if (! $mediable->canUserAccess($request->user())) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized access',
                    ], 403);
                }
            }
        }

        $expirationMinutes = $request->get('expires_in', 60); // Default 1 hour
        $url = $media->getTemporaryUrl($expirationMinutes);

        return response()->json([
            'success' => true,
            'data' => [
                'signed_url' => $url,
                'expires_in' => $expirationMinutes,
                'expires_at' => now()->addMinutes($expirationMinutes)->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get thumbnail for media
     */
    public function thumbnail($id)
    {
        $media = Media::findOrFail($id);

        $thumbnailPath = $media->thumbnail ?? $media->thumbnails['medium'] ?? null;

        if (! $thumbnailPath) {
            abort(404, 'Thumbnail not found');
        }

        $path = Storage::disk($media->disk)->path($thumbnailPath);

        if (! file_exists($path)) {
            abort(404, 'Thumbnail file not found');
        }

        return response()->file($path);
    }
}
