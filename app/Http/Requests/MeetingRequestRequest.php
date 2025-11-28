<?php

namespace App\Http\Requests;

use App\Models\Enrollment;
use Illuminate\Foundation\Http\FormRequest;

class MeetingRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller/service
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'instructor_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'message' => 'required|string|max:1000',
            'datetime_proposed' => 'nullable|date|after:now',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Check if student is enrolled in the course
            $enrollment = Enrollment::where('user_id', auth()->id())
                ->where('course_id', $this->course_id)
                ->whereIn('status', ['active', 'completed'])
                ->first();

            if (! $enrollment) {
                $validator->errors()->add(
                    'course_id',
                    'You must be enrolled in this course to request a meeting'
                );
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'datetime_proposed.after' => 'Proposed date must be in the future',
            'message.max' => 'Message cannot exceed 1000 characters',
        ];
    }
}
