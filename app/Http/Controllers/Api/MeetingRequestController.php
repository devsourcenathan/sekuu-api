<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MeetingRequestRequest;
use App\Models\MeetingRequest;
use App\Services\MeetingRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeetingRequestController extends Controller
{
    public function __construct(
        protected MeetingRequestService $meetingRequestService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = MeetingRequest::with(['student', 'instructor', 'formation', 'session']);

        // Si instructeur, voir les demandes reçues
        if ($user->hasRole('instructor')) {
            $query->byInstructor($user->id);
        } else {
            // Si étudiant, voir les demandes envoyées
            $query->byStudent($user->id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    public function store(MeetingRequestRequest $request): JsonResponse
    {
        try {
            $meetingRequest = $this->meetingRequestService->createRequest(
                $request->validated(),
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Demande de meeting envoyée',
                'data' => $meetingRequest,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function show(MeetingRequest $meetingRequest): JsonResponse
    {
        $meetingRequest->load(['student', 'instructor', 'formation', 'session']);

        return response()->json([
            'success' => true,
            'data' => $meetingRequest,
        ]);
    }

    public function accept(Request $request, MeetingRequest $meetingRequest): JsonResponse
    {
        if ($meetingRequest->instructor_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé',
            ], 403);
        }

        $request->validate([
            'datetime_start' => 'required|date|after:now',
            'datetime_end' => 'required|date|after:datetime_start',
            'title' => 'nullable|string|max:255',
        ]);

        try {
            $session = $this->meetingRequestService->acceptRequest(
                $meetingRequest,
                $request->all()
            );

            return response()->json([
                'success' => true,
                'message' => 'Demande acceptée et session créée',
                'data' => [
                    'meeting_request' => $meetingRequest->fresh(),
                    'session' => $session,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function reject(Request $request, MeetingRequest $meetingRequest): JsonResponse
    {
        if ($meetingRequest->instructor_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé',
            ], 403);
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->meetingRequestService->rejectRequest(
                $meetingRequest,
                $request->reason
            );

            return response()->json([
                'success' => true,
                'message' => 'Demande refusée',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function cancel(MeetingRequest $meetingRequest, Request $request): JsonResponse
    {
        if ($meetingRequest->student_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé',
            ], 403);
        }

        try {
            $this->meetingRequestService->cancelRequest($meetingRequest);

            return response()->json([
                'success' => true,
                'message' => 'Demande annulée',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function eligibleInstructors(Request $request): JsonResponse
    {
        $instructors = $this->meetingRequestService->getEligibleInstructors($request->user());

        return response()->json([
            'success' => true,
            'data' => $instructors,
        ]);
    }
}
