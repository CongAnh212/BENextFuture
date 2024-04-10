<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\User;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function info(Request $request)
    {
        return response()->json([
            'infoAdmin'    => $request->user()
        ]);
    }
    public function signInAdmin(Request $request)
    {
        try {
            DB::beginTransaction();
            $admin = Admin::where('username', $request->username)
                ->first();
            if (!$admin || !Hash::check($request->password, $admin->password)) {
                return response()->json([
                    'status'    => 0,
                    'message'   => 'Invalid login information'
                ]);
            }
            Auth::guard('admin')->login($admin);
            $authenticatedUser = Auth::guard('admin')->user();
            $tokens = $authenticatedUser->tokens;
            $limit = 4;
            if ($tokens->count() >= $limit) {
                // Giữ lại giới hạn số lượng token
                $tokens->sortByDesc('created_at')->slice($limit)->each(function ($token) {
                    $token->delete();
                });
            }
            $token = $authenticatedUser->createToken('authToken', ['*'], now()->addDays(7));
            DB::commit();
            return response()->json([
                'status' => 1,
                'token' => $token->plainTextToken,
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status'    => 0,
                'message'   => 'Error in admin',
            ]);
        }
    }

    public function logOut(Request $request)
    {
        $client = $request->user();

        if ($client) {
            $client->currentAccessToken()->delete();
            return response()->json(['status' => 1]);
        } else {
            return response()->json(['status' => 0]);
        }
    }

    public function getAllPosts(Request $request)
    {
        $posts = Post::join('clients', 'clients.id', '=', 'posts.id_client')
            ->leftJoin('post_likes', 'post_likes.id_post', '=', 'posts.id')
            ->leftJoin('comments', 'comments.id_post', '=', 'posts.id')
            ->select(
                'posts.id',
                'clients.fullname',
                'clients.avatar',
                'posts.images',
                'posts.created_at',
                'posts.caption',
                DB::raw('COUNT(DISTINCT post_likes.id) as react'),
                DB::raw('COUNT(DISTINCT comments.id) as comment'),
                'posts.privacy'
            )
            ->groupBy('posts.id', 'clients.fullname', 'clients.avatar', 'posts.images', 'posts.created_at', 'posts.caption', 'posts.privacy')
            ->orderByDesc('posts.created_at')
            ->get()
            ->toArray();
        // Parse the images field
        foreach ($posts as &$post) {
            $post['images'] = json_decode($post['images']);
        }
        return response()->json([
            'status' => 1,
            'data' => $posts,
            'message' => 'oke',
        ]);
    }
    public function deletePost(Request $request)
    {
        $post = Post::find($request->id);
        if ($post) {
            $post->update(['privacy' => 0]);
            return response()->json([
                'status' => 1,
                'message' => 'Post privacy updated successfully!',
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'Update error!',
            ]);
        }
    }
    public function getAllAccounts(Request $request)
    {
        $accounts = Client::all()->toArray();
        return response()->json($accounts);
    }
    public function banAccount(Request $request)
    {
        $user = Client::find($request->id);
        if ($user) {
            $user->update(['status' => 0]);
            return response()->json([
                'status' => 1,
                'message' => 'Account banned successfully!',
            ]);
        } else {
            return response()->json([
                'status' => 0,
                'message' => 'Ban error: Account not found!',
            ]);
        }
    }
    public function unbanAccount(Request $request){
        $user = Client::find($request->id);
        if ($user) {
            $user->update(['status' => 1]);
            return response()->json([
                'status' => 1,
                'message' => 'Account unbanned successfully!',
            ]);
        }else{
            return response()->json([
                'status' => 0,
                'message' => 'Unban error: Account not found!',
            ]);
        }
    }
}
