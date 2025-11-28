<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SessionRequest;
use App\Models\Session;
use App\Models\User;
use App\Services\LiveKitService;
use App\Services\SessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function __construct(
        protected SessionService $sessionService,
        protected LiveKitService $livekitService
    ) {}

    /**
     * List sessions
     */
    public function index(Request $request): JsonResponse
    {
        $query = Session::with(['instructor', 'course', 'participants']);

        // Filter by instructor
        if ($request->has('instructor_id')) {
            $query->byInstructor($request->instructor_id);
        }

        // Filter by course
        if ($request->has('course_id')) {
            $query->byCourse($request->course_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter upcoming/past
        if ($request->boolean('upcoming')) {
            $query->upcoming();
        } elseif ($request->boolean('past')) {
            $query->past();
        }

        $sessions = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }

    /**
     * Create a new session
     */
    public function store(SessionRequest $request): JsonResponse
    {
        $session = $this->sessionService->createSession(
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Session créée avec succès',
            'data' => $session,
        ], 201);
    }

    /**
     * Show session details
     */
    public function show(Session $session): JsonResponse
    {
        $session->load(['instructor', 'course', 'participants']);

        return response()->json([
            'success' => true,
            'data' => $session,
        ]);
    }

    /**
     * Update a session
     */
    public function update(SessionRequest $request, Session $session): JsonResponse
    {
        // Check authorization
        if ($session->instructor_id !== $request->user()->id && ! $request->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé',
            ], 403);
        }

        $session = $this->sessionService->updateSession($session, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Session mise à jour',
            'data' => $session,
        ]);
    }

    /**
     * Cancel a session
     */
    public function destroy(Session $session, Request $request): JsonResponse
    {
        // Check authorization
        if ($session->instructor_id !== $request->user()->id && ! $request->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé',
            ], 403);
        }

        $this->sessionService->cancelSession($session, $request->get('reason'));

        return response()->json([
            'success' => true,
            'message' => 'Session annulée',
        ]);
    }

    /**
     * Start a session
     */
    public function start(Session $session, Request $request): JsonResponse
    {
        if ($session->instructor_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Seul l\'instructeur peut démarrer la session',
            ], 403);
        }

        $this->sessionService->startSession($session);

        return response()->json([
            'success' => true,
            'message' => 'Session démarrée',
            'data' => $session->fresh(),
        ]);
    }

    /**
     * End a session
     */
    public function end(Session $session, Request $request): JsonResponse
    {
        if ($session->instructor_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Seul l\'instructeur peut terminer la session',
            ], 403);
        }

        $this->sessionService->endSession($session);

        return response()->json([
            'success' => true,
            'message' => 'Session terminée',
            'data' => $session->fresh(),
        ]);
    }

    /**
     * Generate LiveKit token to join session
     */
    public function generateToken(Session $session, Request $request): JsonResponse
    {
        try {
            $token = $this->sessionService->generateJoinToken($session, $request->user());

            return response()->json([
                'success' => true,
                'data' => [
                    'token' => $token,
                    'url' => $this->livekitService->getServerUrl(),
                    'room_name' => $session->livekit_room_name,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Get session participants
     */
    public function participants(Session $session): JsonResponse
    {
        $participants = $session->sessionParticipants()->with('user')->get();

        return response()->json([
            'success' => true,
            'data' => $participants,
        ]);
    }

    /**
     * Add participants to session
     */
    public function addParticipants(Request $request, Session $session): JsonResponse
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'role' => 'nullable|in:host,cohost,participant',
        ]);

        $this->sessionService->addParticipants(
            $session,
            $request->user_ids,
            $request->get('role', 'participant')
        );

        return response()->json([
            'success' => true,
            'message' => 'Participants ajoutés',
        ]);
    }

    /**
     * Remove a participant from session
     */
    public function removeParticipant(Session $session, User $user, Request $request): JsonResponse
    {
        if ($session->instructor_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé',
            ], 403);
        }

        $this->sessionService->removeParticipant($session, $user);

        return response()->json([
            'success' => true,
            'message' => 'Participant retiré',
        ]);
    }
}
