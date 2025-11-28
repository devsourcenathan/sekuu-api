<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\MeetingRequest;
use App\Models\Session;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MeetingRequestService
{
    public function __construct(
        protected SessionService $sessionService
    ) {}

    /**
     * Create a new meeting request
     */
    public function createRequest(array $data, User $student): MeetingRequest
    {
        // Verify student is enrolled in the course
        $enrollment = Enrollment::where('user_id', $student->id)
            ->where('course_id', $data['course_id'])
            ->whereIn('status', ['active', 'completed'])
            ->first();

        if (! $enrollment) {
            throw new \Exception('Student must be enrolled in this course to request a meeting');
        }

        return MeetingRequest::create([
            'student_id' => $student->id,
            'instructor_id' => $data['instructor_id'],
            'course_id' => $data['course_id'],
            'message' => $data['message'],
            'datetime_proposed' => isset($data['datetime_proposed']) ? Carbon::parse($data['datetime_proposed']) : null,
            'status' => 'pending',
        ]);
    }

    /**
     * Accept a meeting request and create a session
     */
    public function acceptRequest(MeetingRequest $request, array $data): Session
    {
        if (! $request->canBeAccepted()) {
            throw new \Exception('This meeting request cannot be accepted');
        }

        return DB::transaction(function () use ($request, $data) {
            $session = $this->sessionService->createSession([
                'title' => $data['title'] ?? "Meeting with {$request->student->name}",
                'description' => $request->message,
                'course_id' => $request->course_id,
                'datetime_start' => $data['datetime_start'],
                'datetime_end' => $data['datetime_end'],
                'type' => 'meeting',
                'participant_ids' => [$request->student_id],
            ], $request->instructor);

            $request->accept($session);

            // TODO: Notify student

            return $session;
        });
    }

    /**
     * Reject a meeting request
     */
    public function rejectRequest(MeetingRequest $request, string $reason): void
    {
        if (! $request->canBeAccepted()) {
            throw new \Exception('This meeting request cannot be rejected');
        }

        $request->reject($reason);

        // TODO: Notify student
    }

    /**
     * Cancel a meeting request (by student)
     */
    public function cancelRequest(MeetingRequest $request): void
    {
        if (! $request->canBeCancelled()) {
            throw new \Exception('This meeting request cannot be cancelled');
        }

        $request->cancel();

        // TODO: Notify instructor
    }

    /**
     * Get eligible instructors for a student
     * (Instructors of courses the student is enrolled in)
     */
    public function getEligibleInstructors(User $student): Collection
    {
        return User::query()
            ->whereHas('roles', function ($q) {
                $q->where('slug', 'instructor');
            })
            ->whereHas('instructedCourses', function ($q) use ($student) {
                $q->whereHas('enrollments', function ($enrollmentQuery) use ($student) {
                    $enrollmentQuery->where('user_id', $student->id)
                        ->whereIn('status', ['active', 'completed']);
                });
            })
            ->with(['instructedCourses' => function ($q) use ($student) {
                $q->whereHas('enrollments', function ($enrollmentQuery) use ($student) {
                    $enrollmentQuery->where('user_id', $student->id)
                        ->whereIn('status', ['active', 'completed']);
                });
            }])
            ->distinct()
            ->get();
    }
}
