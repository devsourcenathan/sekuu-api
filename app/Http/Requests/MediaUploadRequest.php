<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MediaUploadRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $maxSize = config('media.max_file_size.video', 512000);

        return [
            'file' => [
                'required',
                'file',
                "max:{$maxSize}",
                function ($attribute, $value, $fail) {
                    $mimeType = $value->getMimeType();
                    $allowedTypes = array_merge(
                        config('media.allowed_mime_types.image', []),
                        config('media.allowed_mime_types.video', []),
                        config('media.allowed_mime_types.document', []),
                        config('media.allowed_mime_types.audio', [])
                    );

                    if (! in_array($mimeType, $allowedTypes)) {
                        $fail('The file type is not allowed.');
                    }
                },
            ],
            'mediable_type' => 'required|in:App\Models\Course,App\Models\Lesson',
            'mediable_id' => 'required|integer|exists:'.$this->getTableFromType(),
            'collection' => 'required|string',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
        ];
    }

    protected function getTableFromType()
    {
        $type = $this->input('mediable_type');

        return match ($type) {
            'App\Models\Course' => 'courses,id',
            'App\Models\Lesson' => 'lessons,id',
            default => 'courses,id',
        };
    }
}
