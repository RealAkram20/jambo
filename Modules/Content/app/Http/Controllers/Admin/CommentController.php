<?php

namespace Modules\Content\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\Content\app\Models\Comment;

class CommentController extends Controller
{
    public function toggleApproved(Comment $comment): RedirectResponse
    {
        $comment->update(['is_approved' => ! $comment->is_approved]);

        $status = $comment->is_approved ? 'approved' : 'unapproved';

        return redirect()->back()->with('success', "Comment #{$comment->id} {$status}.");
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        $comment->delete();

        return redirect()->back()->with('success', 'Comment deleted.');
    }
}
