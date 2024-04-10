<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Check;
use App\Models\Comment;
use App\Models\Follower;
use App\Models\Post;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function test(Request $request)
    {
        $post = Comment::with('client')->find(3);
        // hoặc
        $post = Comment::find(3);
        $post->load('client');
        return response()->json([
            'message'   => $post,
        ]);
    }
}
