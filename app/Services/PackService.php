<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Pack;
use App\Models\PackEnrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PackService
{
    /**
     * Create a new pack
     */
    public function createPack(array $data, User $instructor)
    {
        $pack = Pack::create([
            'instructor_id' => $instructor->id,
            'title' => $data['title'],
            'slug' => $data['slug'] ?? null,
            'description' => $data['description'],
            'cover_image' => $data['cover_image'] ?? null,
            'price' => $data['price'],
            'currency' => $data['currency'] ?? 'USD',
            'is_active' => $data['is_active'] ?? true,
            'is_public' => $data['is_public'] ?? true,
            'max_enrollments' => $data['max_enrollments'] ?? null,
            'access_duration_days' => $data['access_duration_days'] ?? null,
            'enrollment_start_date' => $data['enrollment_start_date'] ?? null,
            'enrollment_end_date' => $data['enrollment_end_date'] ?? null,
            'has_certificate' => $data['has_certificate'] ?? false,
            'require_sequential_completion' => $data['require_sequential_completion'] ?? false,
            'recommended_order' => $data['recommended_order'] ?? null,
        ]);
        return $pack;
    }

    /**
     * Update an existing pack
     */
    public function updatePack(Pack $pack, array $data)
    {
        $pack->update($data);
        $pack->updateStatistics();
        return $pack->refresh();
    }

    /**
     * Add a course to a pack with access configuration
     */
    public function addCourseToPack(Pack $pack, Course $course, array $config = [])
    {
        if ($course->instructor_id !== $pack->instructor_id) {
            throw new \Exception('Course does not belong to the pack instructor');
        }
        $pack->courses()->attach($course->id, [
            'order' => $config['order'] ?? $pack->courses()->count(),
            'is_required' => $config['is_required'] ?? true,
            'access_config' => $config['access_config'] ?? null,
        ]);
        $pack->updateStatistics();
        return $pack->refresh(['courses']);
    }

    /**
     * Remove a course from a pack
     */
    public function removeCourseFromPack(Pack $pack, Course $course)
    {
        $pack->courses()->detach($course->id);
        $pack->updateStatistics();
        return $pack->refresh(['courses']);
    }

    /**
     * Validate that all courses in a pack belong to the pack instructor
     */
    public function validatePackCourses(Pack $pack)
    {
        $invalid = $pack->courses()
            ->where('instructor_id', '!=', $pack->instructor_id)
            ->count();
        if ($invalid > 0) {
            throw new \Exception('Some courses do not belong to the pack instructor');
        }
        return true;
    }

    /**
     * Update course configuration in a pack
     * Only updates fields that are present in the config array.
     */
    public function updatePackCourseConfig(Pack $pack, Course $course, array $config)
    {
        $attributes = [];
        if (array_key_exists('order', $config)) {
            $attributes['order'] = $config['order'];
        }
        if (array_key_exists('is_required', $config)) {
            $attributes['is_required'] = $config['is_required'];
        }
        if (array_key_exists('access_config', $config)) {
            $attributes['access_config'] = $config['access_config'];
        }
        if (!empty($attributes)) {
            $pack->courses()->updateExistingPivot($course->id, $attributes);
        }
        return $pack->refresh(['courses']);
    }

    /**
     * Enroll a user in a pack
     */
    public function enrollUserInPack(User $user, Pack $pack)
    {
        $existing = PackEnrollment::where('user_id', $user->id)
            ->where('pack_id', $pack->id)
            ->first();
        if ($existing) {
            throw new \Exception('User is already enrolled in this pack');
        }
        if (! $pack->isEnrollmentOpen()) {
            throw new \Exception('Enrollment is not open for this pack');
        }
        DB::beginTransaction();
        try {
            $expiresAt = null;
            if ($pack->access_duration_days) {
                $expiresAt = now()->addDays($pack->access_duration_days);
            }
            $packEnrollment = PackEnrollment::create([
                'user_id' => $user->id,
                'pack_id' => $pack->id,
                'status' => 'active',
                'enrolled_at' => now(),
                'expires_at' => $expiresAt,
                'total_courses' => $pack->courses()->count(),
            ]);
            $this->createCourseEnrollments($packEnrollment);
            $pack->increment('students_enrolled');
            DB::commit();
            return $packEnrollment->refresh(['courseEnrollments']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create course enrollments for all courses in a pack
     */
    public function createCourseEnrollments(PackEnrollment $packEnrollment)
    {
        $pack = $packEnrollment->pack;
        $courses = $pack->courses;
        foreach ($courses as $course) {
            $existing = $packEnrollment->user->enrollments()
                ->where('course_id', $course->id)
                ->first();
            if (! $existing) {
                $expiresAt = $packEnrollment->expires_at;
                $packEnrollment->user->enrollments()->create([
                    'course_id' => $course->id,
                    'pack_enrollment_id' => $packEnrollment->id,
                    'status' => 'active',
                    'enrolled_at' => now(),
                    'expires_at' => $expiresAt,
                    'total_lessons' => $course->total_lessons,
                ]);
                $course->increment('students_enrolled');
            }
        }
    }

    /**
     * Update pack enrollment progress
     */
    public function updatePackProgress(PackEnrollment $packEnrollment)
    {
        $packEnrollment->updateProgress();
        return $packEnrollment->refresh();
    }

    /**
     * Publish a pack
     */
    public function publishPack(Pack $pack)
    {
        if ($pack->courses()->count() === 0) {
            throw new \Exception('Cannot publish a pack without courses');
        }
        $pack->update([
            'is_active' => true,
            'published_at' => now(),
        ]);
        return $pack;
    }

    /**
     * Unpublish a pack
     */
    public function unpublishPack(Pack $pack)
    {
        $pack->update([
            'is_active' => false,
            'published_at' => null,
        ]);
        return $pack;
    }
}
