<?php

namespace App\Http\Controllers\Api\Questions;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Quizze;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QuestionController extends Controller
{
    public function index()
    {
        try {
            $questions = Question::with('quizze')->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Questions retrieved successfully',
                'data' => $questions
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving questions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'answers' => 'required',
            'right_answer' => 'required|string|max:255',
            'score' => 'required',
            'quizze_id' => 'required|exists:quizzes,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $question = new Question();
            $question->title = $request->title;
            $question->answers = $request->answers;
            $question->right_answer = $request->right_answer;
            $question->score = $request->score;
            $question->quizze_id = $request->quizze_id;
            $question->save();

            return response()->json([
                'success' => true,
                'message' => 'Question created successfully',
                'data' => $question->load('quizze')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating question',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $question = Question::with('quizze')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Question retrieved successfully',
                'data' => $question
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Question not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id = null)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'answers' => 'required',
            'right_answer' => 'required|string|max:255',
            'score' => 'required',
            'quizze_id' => 'required|exists:quizzes,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $question = Question::findOrFail($request->input('id', $id));
            $question->title = $request->title;
            $question->answers = $request->answers;
            $question->right_answer = $request->right_answer;
            $question->score = $request->score;
            $question->quizze_id = $request->quizze_id;
            $question->save();

            return response()->json([
                'success' => true,
                'message' => 'Question updated successfully',
                'data' => $question->load('quizze')
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating question',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id = null)
    {
        try {
            $question = Question::findOrFail($request->input('id', $id));
            $question->delete();

            return response()->json([
                'success' => true,
                'message' => 'Question deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting question',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
