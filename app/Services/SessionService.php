<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Session;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SessionService
{
    public function __construct(
        protected LiveKitService $livekitService
    ) {}

    /**
     * Create a new session
     */
    public function createSession(array $data, User $instructor): Session
    {
        return DB::transaction(function () use ($data, $instructor) {
            // Créer la session
            $session = Session::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'instructor_id' => $instructor->id,
                'course_id' => $data['course_id'] ?? null,
                'datetime_start' => Carbon::parse($data['datetime_start']),
                'datetime_end' => Carbon::parse($data['datetime_end']),
                'type' => $data['type'],
                'recording_enabled' => $data['recording_enabled'] ?? Setting::get('session_recording_enabled_default', true),
                'max_participants' => $data['max_participants'] ?? Setting::get('session_max_participants'),
            ]);

            // Ajouter l'instructeur comme host
            $session->addParticipant($instructor, 'host');

            // Ajouter les participants individuels
            if (! empty($data['participant_ids'])) {
                foreach ($data['participant_ids'] as $userId) {
                    $user = User::find($userId);
                    if ($user) {
                        $session->addParticipant($user, 'participant');
                    }
                }
            }

            // Ajouter les participants des groupes
            if (! empty($data['group_ids'])) {
                foreach ($data['group_ids'] as $groupId) {
                    $group = Group::find($groupId);
                    if ($group) {
                        $this->addGroupParticipants($session, $group);
                    }
                }
            }

            // TODO: Envoyer des notifications aux participants

            return $session->fresh(['participants', 'instructor', 'course']);
        });
    }

    /**
     * Update an existing session
     */
    public function updateSession(Session $session, array $data): Session
    {
        return DB::transaction(function () use ($session, $data) {
            $session->update([
                'title' => $data['title'] ?? $session->title,
                'description' => $data['description'] ?? $session->description,
                'course_id' => $data['course_id'] ?? $session->course_id,
                'datetime_start' => isset($data['datetime_start']) ? Carbon::parse($data['datetime_start']) : $session->datetime_start,
                'datetime_end' => isset($data['datetime_end']) ? Carbon::parse($data['datetime_end']) : $session->datetime_end,
                'type' => $data['type'] ?? $session->type,
                'recording_enabled' => $data['recording_enabled'] ?? $session->recording_enabled,
                'max_participants' => $data['max_participants'] ?? $session->max_participants,
            ]);

            // TODO: Notifier les participants des changements

            return $session->fresh(['participants', 'instructor', 'course']);
        });
    }

    /**
     * Cancel a session
     */
    public function cancelSession(Session $session, string $reason = null): void
    {
        $session->cancel($reason);

        // TODO: Notifier tous les participants de l'annulation
    }

    /**
     * Add participants to a session
     */
    public function addParticipants(Session $session, array $userIds, string $role = 'participant'): void
    {
        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user && $session->canAddParticipant()) {
                $session->addParticipant($user, $role);
                // TODO: Notifier le participant
            }
        }
    }

    /**
     * Add all members of a group as participants
     */
    public function addGroupParticipants(Session $session, Group $group): void
    {
        $memberIds = $group->getMemberIds();
        $this->addParticipants($session, $memberIds);
    }

    /**
     * Remove a participant from a session
     */
    public function removeParticipant(Session $session, User $user): void
    {
        $session->removeParticipant($user);
        // TODO: Notifier le participant
    }

    /**
     * Start a session
     */
    public function startSession(Session $session): void
    {
        $session->start();
        // TODO: Notifier les participants que la session a démarré
    }

    /**
     * End a session
     */
    public function endSession(Session $session): void
    {
        $session->end();
        // TODO: Notifier les participants que la session est terminée
    }

    /**
     * Generate a LiveKit token for a user to join a session
     */
    public function generateJoinToken(Session $session, User $user): string
    {
        if (! $session->canUserJoin($user)) {
            throw new \Exception('User is not authorized to join this session');
        }

        return $this->livekitService->generateToken($session, $user);
    }
}
