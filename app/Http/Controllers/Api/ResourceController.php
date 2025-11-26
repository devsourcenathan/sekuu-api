<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ResourceController extends Controller
{
    protected $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'resourceable_type' => 'required|in:App\Models\Course,App\Models\Chapter,App\Models\Lesson',
            'resourceable_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'required|file|max:51200', // 50MB
            'is_free' => 'boolean',
            'is_downloadable' => 'boolean',
            'download_limit' => 'nullable|integer|min:1',
            'order' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check ownership
        $resourceable = $request->resourceable_type::findOrFail($request->resourceable_id);

        $course = match ($request->resourceable_type) {
            'App\Models\Course' => $resourceable,
            'App\Models\Chapter' => $resourceable->course,
            'App\Models\Lesson' => $resourceable->chapter->course,
        };

        if ($course->instructor_id !== $request->user()->id &&
            ! $request->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $fileData = $this->mediaService->uploadFile($request->file('file'), 'resources');

        $resource = Resource::create([
            'resourceable_type' => $request->resourceable_type,
            'resourceable_id' => $request->resourceable_id,
            'title' => $request->title,
            'description' => $request->description,
            'file_path' => $fileData['path'],
            'file_name' => $fileData['name'],
            'file_type' => $fileData['type'],
            'file_size' => $fileData['size'],
            'is_free' => $request->boolean('is_free'),
            'is_downloadable' => $request->boolean('is_downloadable', true),
            'download_limit' => $request->download_limit,
            'order' => $request->order,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Resource uploaded successfully',
            'data' => $resource,
        ], 201);
    }

    public function download(Request $request, $id)
    {
        $resource = Resource::findOrFail($id);

        // Check download limit
        if (! $resource->canDownload()) {
            return response()->json([
                'success' => false,
                'message' => 'Download limit reached',
            ], 403);
        }

        // Check if user has access
        if (! $resource->is_free && auth()->check()) {
            $course = match (get_class($resource->resourceable)) {
                'App\Models\Course' => $resource->resourceable,
                'App\Models\Chapter' => $resource->resourceable->course,
                'App\Models\Lesson' => $resource->resourceable->chapter->course,
            };

            if (! $course->canUserAccess($request->user())) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this resource',
                ], 403);
            }
        }

        // Increment download count
        $resource->incrementDownload();

        // Generate download URL
        $url = Storage::disk('public')->url($resource->file_path);

        return response()->json([
            'success' => true,
            'data' => [
                'download_url' => $url,
                'file_name' => $resource->file_name,
            ],
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $resource = Resource::findOrFail($id);

        // Check ownership
        $course = match (get_class($resource->resourceable)) {
            'App\Models\Course' => $resource->resourceable,
            'App\Models\Chapter' => $resource->resourceable->course,
            'App\Models\Lesson' => $resource->resourceable->chapter->course,
        };

        if ($course->instructor_id !== $request->user()->id &&
            ! $request->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Delete file
        $this->mediaService->deleteFile($resource->file_path);

        $resource->delete();

        return response()->json([
            'success' => true,
            'message' => 'Resource deleted successfully',
        ]);
    }
}
