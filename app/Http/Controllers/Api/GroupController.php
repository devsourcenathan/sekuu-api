<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GroupRequest;
use App\Models\Group;
use App\Services\GroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function __construct(
        protected GroupService $groupService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Group::with(['instructor', 'course', 'members'])
            ->byInstructor($request->user()->id);

        if ($request->has('course_id')) {
            $query->byCourse($request->course_id);
        }

        $groups = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $groups,
        ]);
    }

    public function store(GroupRequest $request): JsonResponse
    {
        $group = $this->groupService->createGroup(
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Groupe créé avec succès',
            'data' => $group,
        ], 201);
    }

    public function show(Group $group): JsonResponse
    {
        $group->load(['instructor', 'course', 'members']);

        return response()->json([
            'success' => true,
            'data' => $group,
        ]);
    }

    public function update(GroupRequest $request, Group $group): JsonResponse
    {
        if ($group->instructor_id !== $request->user()->id && ! $request->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé',
            ], 403);
        }

        $group = $this->groupService->updateGroup($group, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Groupe mis à jour',
            'data' => $group,
        ]);
    }

    public function destroy(Group $group, Request $request): JsonResponse
    {
        if ($group->instructor_id !== $request->user()->id && ! $request->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé',
            ], 403);
        }

        $this->groupService->deleteGroup($group);

        return response()->json([
            'success' => true,
            'message' => 'Groupe supprimé',
        ]);
    }

    public function addMembers(Request $request, Group $group): JsonResponse
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $this->groupService->addMembers($group, $request->user_ids);

        return response()->json([
            'success' => true,
            'message' => 'Membres ajoutés',
        ]);
    }

    public function removeMembers(Request $request, Group $group): JsonResponse
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $this->groupService->removeMembers($group, $request->user_ids);

        return response()->json([
            'success' => true,
            'message' => 'Membres retirés',
        ]);
    }

    public function eligibleStudents(Request $request): JsonResponse
    {
        $students = $this->groupService->getEligibleStudents(
            $request->user(),
            $request->get('course_id')
        );

        return response()->json([
            'success' => true,
            'data' => $students,
        ]);
    }
}
