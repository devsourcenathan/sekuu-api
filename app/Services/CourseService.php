<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CourseService
{
    public function createCourse(array $data, User $instructor)
    {
        return DB::transaction(function () use ($data, $instructor) {
            $data['instructor_id'] = $instructor->id;

            // Handle cover image upload
            if (isset($data['cover_image'])) {
                $data['cover_image'] = $this->uploadCoverImage($data['cover_image']);
            }

            $course = Course::create($data);

            // Attach tags if provided
            if (isset($data['tags'])) {
                $course->tags()->sync($data['tags']);
            }

            return $course->load(['instructor', 'category', 'tags']);
        });
    }

    public function updateCourse(Course $course, array $data)
    {
        return DB::transaction(function () use ($course, $data) {
            // Handle cover image upload
            if (isset($data['cover_image'])) {
                // Delete old image
                if ($course->cover_image) {
                    Storage::disk('public')->delete($course->cover_image);
                }
                $data['cover_image'] = $this->uploadCoverImage($data['cover_image']);
            }

            $course->update($data);

            // Update tags if provided
            if (isset($data['tags'])) {
                $course->tags()->sync($data['tags']);
            }

            return $course->fresh(['instructor', 'category', 'tags']);
        });
    }

    public function publishCourse(Course $course)
    {
        if ($course->status === 'published') {
            return $course;
        }

        // Validate course has minimum content
        $totalLessons = $course->chapters()->withCount('lessons')->get()->sum('lessons_count');

        if ($totalLessons < 1) {
            throw new \Exception('Course must have at least one lesson to be published');
        }

        $course->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return $course;
    }

    public function enrollStudent(Course $course, User $student, ?string $paymentId = null)
    {
        if (! $course->isEnrollmentOpen()) {
            throw new \Exception('Enrollment is not open for this course');
        }

        // Check if already enrolled
        $existingEnrollment = Enrollment::where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existingEnrollment) {
            throw new \Exception('Student is already enrolled in this course');
        }

        $enrollmentData = [
            'user_id' => $student->id,
            'course_id' => $course->id,
            'status' => $course->requires_approval ? 'pending' : 'active',
            'enrolled_at' => now(),
        ];

        // Set expiration date if access is limited
        if ($course->access_duration_days) {
            $enrollmentData['expires_at'] = now()->addDays($course->access_duration_days);
        }

        $enrollment = Enrollment::create($enrollmentData);

        // Update course enrollment count
        $course->increment('students_enrolled');

        return $enrollment->load(['user', 'course']);
    }

    public function calculateCourseDuration(Course $course)
    {
        $totalMinutes = $course->chapters()
            ->with('lessons')
            ->get()
            ->flatMap->lessons
            ->sum('duration_minutes');

        $course->update(['total_duration_minutes' => $totalMinutes]);

        return $totalMinutes;
    }

    private function uploadCoverImage($file)
    {
        $path = $file->store('courses/covers', 'public');

        return Storage::url($path);
    }

    public function deleteCourse(Course $course)
    {
        DB::transaction(function () use ($course) {
            // Delete cover image
            if ($course->cover_image) {
                Storage::disk('public')->delete($course->cover_image);
            }

            // Delete all related media files
            foreach ($course->chapters as $chapter) {
                foreach ($chapter->lessons as $lesson) {
                    if ($lesson->file_path) {
                        Storage::disk('public')->delete($lesson->file_path);
                    }
                }
            }

            $course->delete();
        });
    }
}
