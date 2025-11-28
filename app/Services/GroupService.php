<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GroupService
{
    /**
     * Create a new group
     */
    public function createGroup(array $data, User $instructor): Group
    {
        return DB::transaction(function () use ($data, $instructor) {
            $group = Group::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'instructor_id' => $instructor->id,
                'course_id' => $data['course_id'] ?? null,
                'is_active' => true,
            ]);

            // Add initial members
            if (! empty($data['member_ids'])) {
                $this->addMembers($group, $data['member_ids']);
            }

            return $group->fresh(['members', 'instructor', 'course']);
        });
    }

    /**
     * Update an existing group
     */
    public function updateGroup(Group $group, array $data): Group
    {
        $group->update([
            'name' => $data['name'] ?? $group->name,
            'description' => $data['description'] ?? $group->description,
            'course_id' => $data['course_id'] ?? $group->course_id,
            'is_active' => $data['is_active'] ?? $group->is_active,
        ]);

        return $group->fresh(['members', 'instructor', 'course']);
    }

    /**
     * Delete a group
     */
    public function deleteGroup(Group $group): void
    {
        $group->delete();
    }

    /**
     * Add members to a group
     */
    public function addMembers(Group $group, array $userIds): void
    {
        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user) {
                $group->addMember($user);
            }
        }
    }

    /**
     * Remove members from a group
     */
    public function removeMembers(Group $group, array $userIds): void
    {
        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user) {
                $group->removeMember($user);
            }
        }
    }

    /**
     * Get eligible students for an instructor
     * (Students enrolled in instructor's courses)
     */
    public function getEligibleStudents(User $instructor, ?int $courseId = null): Collection
    {
        $query = User::query()
            ->whereHas('roles', function ($q) {
                $q->where('slug', 'student');
            })
            ->whereHas('enrollments', function ($q) use ($instructor, $courseId) {
                $q->whereHas('course', function ($courseQuery) use ($instructor) {
                    $courseQuery->where('instructor_id', $instructor->id);
                });

                if ($courseId) {
                    $q->where('course_id', $courseId);
                }

                $q->whereIn('status', ['active', 'completed']);
            })
            ->with(['enrollments.course'])
            ->distinct();

        return $query->get();
    }
}
