<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller/policy
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'course_id' => 'nullable|exists:courses,id',
            'datetime_start' => 'required|date|after:now',
            'datetime_end' => 'required|date|after:datetime_start',
            'type' => 'required|in:course,mentoring,meeting',
            'participant_ids' => 'nullable|array',
            'participant_ids.*' => 'exists:users,id',
            'group_ids' => 'nullable|array',
            'group_ids.*' => 'exists:groups,id',
            'max_participants' => 'nullable|integer|min:2',
            'recording_enabled' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'datetime_start.after' => 'Start date must be in the future',
            'datetime_end.after' => 'End date must be after start date',
            'type.in' => 'Type must be: course, mentoring or meeting',
        ];
    }
}
