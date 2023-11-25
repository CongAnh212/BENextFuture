<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{


    public function data(Request $request){
        $data = Comment::join('clients', 'clients.id', 'comments.id_client')
        ->select('comments.*', 'clients.fullname', 'clients.avatar')
        ->get();

        return response()->json([
            'dataComment'    => $data,
        ]);
    }

    public function store(Request $request)
    {

        $client = $request->user();
        $data = $request->all();
        $data['id_client'] = $client->id;
        $check = Comment::Create($data);
        if($check){
            return response()->json([
                'status'    => 1,
                'message'   => 'commented successfully!',
            ]);
        }else{
            return response()->json([
                'status'    => 0,
                'message'   => 'comment error!',
            ]);
        }
    }

}
