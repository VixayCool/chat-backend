<?php
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\FriendshipController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\GroupController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::post('/signup', [UserController::class, 'signup']);
Route::post('/login', [UserController::class, 'login']);
Route::get('/user/{id}', [UserController::class, 'getUser'])->middleware('auth:sanctum');
Route::get('/self', [UserController::class, 'getSelf'])->middleware('auth:sanctum');
Route::put('/update/user', [UserController::class, 'update'])->middleware('auth:sanctum');
Route::get('/user/profile', [UserController::class, 'getProfile'])->middleware('auth:sanctum');
Route::post('/user/profile', [UserController::class, 'storeProfileImage'])->middleware('auth:sanctum');
Route::post('/user/background', [UserController::class, 'storeBackgroundImage'])->middleware('auth:sanctum');

Route::post('/friends/search', [FriendshipController::class, 'search_friendship'])->middleware('auth:sanctum');
Route::post('/friendship/request', [FriendshipController::class, 'request'])->middleware('auth:sanctum');
Route::get('/friends', [FriendshipController::class, 'get_friendship_request'])->middleware('auth:sanctum');
Route::get('/friendship/pending', [FriendshipController::class, 'get_pending_friendship'])->middleware('auth:sanctum');
Route::post('/friendship/accept/{friendship_id}', [FriendshipController::class, 'accept'])->middleware('auth:sanctum');
Route::post('/friendship/decline/{friendship_id}', [FriendshipController::class, 'decline'])->middleware('auth:sanctum');
Route::delete('/friendship/delete/{friendship_id}', [FriendshipController::class, 'delete'])->middleware('auth:sanctum');


Route::post('/message', [MessageController::class, 'send'])->middleware('auth:sanctum');
Route::delete('/message/{id}', [MessageController::class, 'deleteMessage'])->middleware('auth:sanctum');
Route::get('/room/{id}/messages', [MessageController::class, 'getMessages'])->middleware('auth:sanctum');
Route::get('/message/download/{fileName}', [MessageController::class, 'downloadFile'])->middleware('auth:sanctum');
Route::post('/message/translate', [MessageController::class, 'translateMessage'])->middleware('auth:sanctum');
Route::get('/room/{room_type}/{id}/messages/summarize', [MessageController::class, 'summarizeConversation'])->middleware('auth:sanctum');

Route::post('/group/create', [GroupController::class, 'create'])->middleware('auth:sanctum');
Route::post('/group/{group_id}/member/add/{user_id}', [GroupController::class, 'addMember'])->middleware('auth:sanctum');
Route::post('/group/profile/{id}', [GroupController::class, 'storeProfileImage'])->middleware('auth:sanctum');
Route::post('/group/background/{id}', [GroupController::class, 'storeBackgroundImage'])->middleware('auth:sanctum');

Route::get('/groups', [GroupController::class, 'get'])->middleware('auth:sanctum');
Route::get('/group/{id}', [GroupController::class, 'getGroupDetail'])->middleware('auth:sanctum');

Route::put('/group/member/admin/{id}', [GroupController::class, 'memberToAdmin'])->middleware('auth:sanctum');
Route::put('/group/admin/member/{id}', [GroupController::class, 'adminToMember'])->middleware('auth:sanctum');
Route::put('/group/detail/{id}', [GroupController::class, 'updateGroupDetail'])->middleware('auth:sanctum');

Route::delete('/group/{group_id}/member/delete/{friend_id}', [GroupController::class, 'deleteMember'])->middleware('auth:sanctum');
Route::delete('/group/leave/{id}', [GroupController::class, 'leaveGroup'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->post('/broadcasting/auth', function (Request $request) {
    $authData = Broadcast::auth($request);
    \Log::info('Broadcasting auth response:', [$authData]);
    return $authData;
});


Broadcast::routes(['middleware' => ['auth:sanctum']]);

require base_path('routes/channels.php');

