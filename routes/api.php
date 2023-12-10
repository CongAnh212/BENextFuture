<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ConnectionController;
use App\Http\Controllers\FollowerController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StoriesController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\NotificationController;

Route::post('/sign-up', [ClientController::class, 'register']);
Route::post('/sign-in', [ClientController::class, 'login']);



Route::middleware('auth:sanctum')->group(function () {

    Route::get('/sign-out', [ClientController::class, 'signOut']);

    Route::group(['prefix' => '/story'], function () {
        Route::get('/data', [StoriesController::class, "getStory"]);
        Route::get('/data-all', [StoriesController::class, "getAllStory"]);
        Route::get('/{id}', [StoriesController::class, 'detailStory']);
        Route::post('/create', [StoriesController::class, 'store']);
    });
    Route::group(['prefix' => '/follower'], function () {
        Route::post('/add-friend', [FollowerController::class, "addFriend"]);
        Route::post('/cancel-friend', [FollowerController::class, "cancelFriend"]);

        Route::get('/request-friend', [FollowerController::class, "requestFriend"]);
        Route::post('/request-friend-limit', [FollowerController::class, "requestFriendLimit"]);
        Route::post('/accept-friend', [FollowerController::class, "acceptFriend"]);
        Route::post('/delete-friend', [FollowerController::class, "deleteFriend"]);
    });

    Route::group(['prefix' => '/{username}'], function () {
        Route::get('/data-info', [ClientController::class, "getInfo"]);
    });
    Route::group(['prefix' => '/profile'], function () {
        Route::get('/data', [ClientController::class, "getProfile"]);
    });

    Route::get('/dataFull', [ClientController::class, "getAllData"]);
    Route::get('/data-all-friend', [FriendController::class, "getAllFriend"]);
    Route::post('/delete-friend', [FriendController::class, "delFriend"]);


    Route::group(['prefix' => '/post'], function () {
        Route::post('/create', [PostController::class, "create"]);
        Route::get('/data', [PostController::class, "dataPost"]);
    });

    Route::group(['prefix' => '/comment'], function () {
        Route::get('/data', [CommentController::class, 'data']);
        Route::post('/create', [CommentController::class, 'store']);
    });

    Route::group(['prefix' => '/groups'], function () {
        Route::post('/create', [GroupController::class, 'createGroup']);                                // tạo nhóm mới
        Route::get('/data-discover', [GroupController::class, 'data_all_group']);                       // data tất cả nhóm
        Route::get('/data-your-group', [GroupController::class, 'data_your_group']);                    // data nhóm bạn quản lý
        Route::get('/data-group-participated', [GroupController::class, 'data_group_participated']);    // data nhóm đang tham gia không bao gồm nhóm admin
        Route::get('/data-all-group-participated', [GroupController::class, 'dataAllGroupParticipated']);  // data toàn bộ nhóm đang tham gia
        Route::post('/data-invite', [GroupController::class, 'dataInvite']);                            // list bạn khi tạo nhóm
        Route::post('/data-invited', [GroupController::class, 'dataInvited']);                          // list lời mời của bạn
        Route::get('/{id_group}', [GroupController::class, 'infoGroup']);                               // trang chủ nhón dựa vào id
        Route::post('/data-invite-detail', [GroupController::class, 'dataInviteDetail']);               // list bạn để mời vào nhóm trừ những người đã trong nhóm
        Route::post('/send-invite', [GroupController::class, 'sendInvite']);                            // mời bạn vào nhóm
        Route::post('/come-in-group', [GroupController::class, 'comeInGroup']);                         // xin vào nhóm
        Route::post('/current-group', [GroupController::class, 'getData']);                             // lấy thông tin nhóm hiện tại thông qua id
        Route::post('/update-privacy', [GroupController::class, 'updatePrivacy']);                      // cập nhật quyền riêng tư nhóm
        Route::post('/update-display', [GroupController::class, 'updateDisplay']);                      // cập nhật quyền hiển thị nhóm
        Route::post('/update-join-approval', [GroupController::class, 'updateJoinApproval']);           // cập nhật duyệt vào nhóm
        Route::post('/update-post-approval', [GroupController::class, 'updatePostApproval']);           // cập nhật duyệt đăng bài
    });

    Route::group(['prefix' => '/notification'], function () {
        Route::get('/data', [NotificationController::class, 'getData']);
    });


    Route::post('/upload-file', [ImageController::class, 'upload']);
    Route::post('/upload-image', [ImageController::class, 'uploadImage']);
});
