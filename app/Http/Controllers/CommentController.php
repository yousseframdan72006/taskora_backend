<?php

namespace App\Http\Controllers;

use App\Events\CommentAdded;
use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Task;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, Task $task)
    {
        $comment = $task->comments()->create([
            'content' => $request->content,
            'user_id' => $request->user()->id,
        ]);

        // Dispatch event → notifies all task participants (except commenter)
        CommentAdded::dispatch($task, $comment, $request->user()->id);

        return response()->json([
            'success' => true,
            'data'    => $comment->load('user'),
            'message' => 'Comment added successfully.',
        ], 201);
    }

    public function destroy(Comment $comment)
    {
        Gate::authorize('delete', $comment);

        $comment->delete();

        return response()->json([
            'success' => true,
            'data'    => [],
            'message' => 'Comment deleted successfully.',
        ]);
    }
}
