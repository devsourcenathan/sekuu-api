<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\TestSubmission;
use App\Services\TestEvaluationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TestController extends Controller
{
    protected $evaluationService;

    public function __construct(TestEvaluationService $evaluationService)
    {
        $this->evaluationService = $evaluationService;
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'testable_type' => 'required|in:App\Models\Course,App\Models\Chapter,App\Models\Lesson',
            'testable_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'type' => 'required|in:formative,summative',
            'position' => 'required|in:after_lesson,after_chapter,end_of_course',
            'duration_minutes' => 'nullable|integer|min:1',
            'max_attempts' => 'required|integer|min:0',
            'passing_score' => 'required|integer|min:0|max:100',
            'validation_type' => 'required|in:automatic,manual,mixed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check ownership
        $testable = $request->testable_type::findOrFail($request->testable_id);

        $course = match ($request->testable_type) {
            'App\Models\Course' => $testable,
            'App\Models\Chapter' => $testable->course,
            'App\Models\Lesson' => $testable->chapter->course,
        };

        if ($course->instructor_id !== $request->user()->id &&
            ! $request->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $test = Test::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Test created successfully',
            'data' => $test,
        ], 201);
    }

    public function show($id)
    {
        $test = Test::with(['questions.options', 'testable'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $test,
        ]);
    }

    public function update(Request $request, $id)
    {
        $test = Test::findOrFail($id);
        $course = $test->getCourse();

        // Check ownership
        if ($course->instructor_id !== $request->user()->id &&
            ! $request->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'duration_minutes' => 'nullable|integer|min:1',
            'max_attempts' => 'sometimes|required|integer|min:0',
            'passing_score' => 'sometimes|required|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $test->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Test updated successfully',
            'data' => $test->fresh(),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $test = Test::findOrFail($id);
        $course = $test->getCourse();

        // Check ownership
        if ($course->instructor_id !== $request->user()->id &&
            ! $request->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $test->delete();

        return response()->json([
            'success' => true,
            'message' => 'Test deleted successfully',
        ]);
    }

    public function addQuestion(Request $request, $testId)
    {
        $test = Test::findOrFail($testId);
        $course = $test->getCourse();

        // Check ownership
        if ($course->instructor_id !== $request->user()->id &&
            ! $request->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'question_text' => 'required|string',
            'type' => 'required|in:multiple_choice,single_choice,true_false,short_answer,long_answer,audio,video,file_upload',
            'points' => 'required|integer|min:1',
            'order' => 'required|integer|min:0',
            'options' => 'required_if:type,multiple_choice,single_choice,true_false|array',
            'options.*.option_text' => 'required|string',
            'options.*.is_correct' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $question = $test->questions()->create([
            'question_text' => $request->question_text,
            'explanation' => $request->explanation,
            'type' => $request->type,
            'points' => $request->points,
            'order' => $request->order,
            'image_url' => $request->image_url,
            'requires_manual_grading' => in_array($request->type, ['short_answer', 'long_answer', 'audio', 'video', 'file_upload']),
        ]);

        // Add options if applicable
        if ($request->has('options')) {
            foreach ($request->options as $index => $optionData) {
                $question->options()->create([
                    'option_text' => $optionData['option_text'],
                    'is_correct' => $optionData['is_correct'],
                    'feedback' => $optionData['feedback'] ?? null,
                    'order' => $index,
                ]);
            }
        }

        // Update test statistics
        $test->updateStatistics();

        return response()->json([
            'success' => true,
            'message' => 'Question added successfully',
            'data' => $question->load('options'),
        ], 201);
    }

    public function startTest(Request $request, $testId)
    {
        $test = Test::findOrFail($testId);

        try {
            $submission = $this->evaluationService->startTest($test, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Test started successfully',
                'data' => $submission,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function saveDraft(Request $request, $submissionId)
    {
        $submission = TestSubmission::findOrFail($submissionId);

        if ($submission->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $submission = $this->evaluationService->saveDraft($submission, $request->answers);

            return response()->json([
                'success' => true,
                'message' => 'Draft saved',
                'data' => $submission,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function submitTest(Request $request, $submissionId)
    {
        $submission = TestSubmission::findOrFail($submissionId);

        if ($submission->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:questions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $submission = $this->evaluationService->submitTest($submission, $request->answers);

            return response()->json([
                'success' => true,
                'message' => 'Test submitted successfully',
                'data' => $submission,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function gradeSubmission(Request $request, $submissionId)
    {
        $submission = TestSubmission::findOrFail($submissionId);
        $course = $submission->test->getCourse();

        // Check if user is instructor or admin
        if ($course->instructor_id !== $request->user()->id &&
            ! $request->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'gradings' => 'required|array',
            'gradings.*.question_id' => 'required|exists:questions,id',
            'gradings.*.points_earned' => 'required|numeric|min:0',
            'gradings.*.feedback' => 'nullable|string',
            'gradings.comments' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $submission = $this->evaluationService->gradeManually(
                $submission,
                $request->user(),
                $request->gradings
            );

            return response()->json([
                'success' => true,
                'message' => 'Submission graded successfully',
                'data' => $submission,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function mySubmissions(Request $request, $testId)
    {
        $submissions = TestSubmission::where('test_id', $testId)
            ->where('user_id', $request->user()->id)
            ->with(['test', 'answers.question'])
            ->orderBy('attempt_number', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $submissions,
        ]);
    }

    public function pendingGradings(Request $request)
    {
        $submissions = $this->evaluationService->getPendingSubmissions($request->user());

        return response()->json([
            'success' => true,
            'data' => $submissions,
        ]);
    }

    public function getTestsByCourse($courseId)
    {
        $tests = Test::where('testable_type', 'App\\Models\\Course')
            ->where('testable_id', $courseId)
            // ->where('is_published', true)
            ->with(['questions'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tests,
        ]);
    }

    public function getTestsByChapter($chapterId)
    {
        $tests = Test::where('testable_type', 'App\\Models\\Chapter')
            ->where('testable_id', $chapterId)
            // ->where('is_published', true)
            ->with(['questions'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tests,
        ]);
    }

    public function getTestsByLesson($lessonId)
    {
        $tests = Test::where('testable_type', 'App\\Models\\Lesson')
            ->where('testable_id', $lessonId)
            // ->where('is_published', true)
            ->with(['questions'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tests,
        ]);
    }
}
