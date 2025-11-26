<?php

namespace App\Policies;

use App\Models\Pack;
use App\Models\User;

class PackPolicy
{
    /**
     * Determine if the user can view any packs
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine if the user can view the pack
     */
    public function view(User $user, Pack $pack)
    {
        // Admin can view all packs
        if ($user->hasRole('admin')) {
            return true;
        }

        // Instructor can view their own packs
        if ($pack->instructor_id === $user->id) {
            return true;
        }

        // Public packs can be viewed by anyone
        if ($pack->is_public && $pack->isPublished()) {
            return true;
        }

        // Students can view packs they're enrolled in
        return $pack->packEnrollments()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Determine if the user can create packs
     */
    public function create(User $user)
    {
        return $user->hasRole('instructor') || $user->hasRole('admin');
    }

    /**
     * Determine if the user can update the pack
     */
    public function update(User $user, Pack $pack)
    {
        // Admin can update any pack
        if ($user->hasRole('admin')) {
            return true;
        }

        // Instructor can only update their own packs
        return $pack->instructor_id === $user->id;
    }

    /**
     * Determine if the user can delete the pack
     */
    public function delete(User $user, Pack $pack)
    {
        // Admin can delete any pack
        if ($user->hasRole('admin')) {
            return true;
        }

        // Instructor can only delete their own packs if no active enrollments
        if ($pack->instructor_id === $user->id) {
            $activeEnrollments = $pack->packEnrollments()
                ->whereIn('status', ['active', 'completed'])
                ->count();

            return $activeEnrollments === 0;
        }

        return false;
    }

    /**
     * Determine if the user can publish the pack
     */
    public function publish(User $user, Pack $pack)
    {
        // Admin can publish any pack
        if ($user->hasRole('admin')) {
            return true;
        }

        // Instructor can only publish their own packs
        return $pack->instructor_id === $user->id;
    }

    /**
     * Determine if the user can enroll in the pack
     */
    public function enroll(User $user, Pack $pack)
    {
        // Cannot enroll in own pack
        if ($pack->instructor_id === $user->id) {
            return false;
        }

        // Pack must be published and enrollment must be open
        if (! $pack->isPublished() || ! $pack->isEnrollmentOpen()) {
            return false;
        }

        // Check if already enrolled
        $existingEnrollment = $pack->packEnrollments()
            ->where('user_id', $user->id)
            ->exists();

        return ! $existingEnrollment;
    }
}
