<?php
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\FriendshipController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\GroupController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
Route::controller(UserController::class)->group(function(){
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::get('/user/{id}', 'getUser')->middleware('auth:sanctum');
    Route::get('/self', 'getSelf')->middleware('auth:sanctum');
    Route::put('/update/user', 'update')->middleware('auth:sanctum');
    Route::get('/user/profile', 'getProfile')->middleware('auth:sanctum');
    Route::post('/user/profile', 'storeProfileImage')->middleware('auth:sanctum');
    Route::post('/user/background', 'storeBackgroundImage')->middleware('auth:sanctum');
});
Route::controller(FriendshipController::class)->group(function(){
    Route::post('/friends/search', 'search_friendship')->middleware('auth:sanctum');
    Route::post('/friendship/request', 'friend_request')->middleware('auth:sanctum');
    Route::get('/friends', 'get_friendship_request')->middleware('auth:sanctum');
    Route::get('/friendship/pending', 'get_pending_friendship')->middleware('auth:sanctum');
    Route::post('/friendship/accept/{friendship_id}', 'accept')->middleware('auth:sanctum');
    Route::post('/friendship/decline/{friendship_id}', 'decline')->middleware('auth:sanctum');
    Route::delete('/friendship/delete/{friendship_id}', 'delete')->middleware('auth:sanctum');
   
});

Route::controller(MessageController::class)->group(function(){
    Route::post('/message', 'send')->middleware('auth:sanctum');
    Route::get('/message/{id}', 'getMessages')->middleware('auth:sanctum');
    Route::delete('/message/{id}', 'deleteMessage')->middleware('auth:sanctum');
});

Route::controller(GroupController::class)->group(function(){
    Route::post('/group/create', 'create')->middleware('auth:sanctum');
    Route::post('/group/{group_id}/member/add/{user_id}', 'addMember')->middleware('auth:sanctum');
    Route::post('/group/profile/{id}', 'storeProfileImage')->middleware('auth:sanctum');
    Route::post('/group/background/{id}', 'storeBackgroundImage')->middleware('auth:sanctum');
    
    Route::get('/groups', 'get')->middleware('auth:sanctum');
    Route::get('/group/{id}', 'getGroupDetail')->middleware('auth:sanctum');

    Route::put('/group/member/admin/{id}', 'memberToAdmin')->middleware('auth:sanctum');
    Route::put('/group/admin/member/{id}', 'adminToMember')->middleware('auth:sanctum');
    Route::put('/group/detail/{id}', 'updateGroupDetail')->middleware('auth:sanctum');

    Route::delete('/group/{group_id}/member/delete/{friend_id}', 'deleteMember')->middleware('auth:sanctum');
    Route::delete('/group/leave/{id}', 'leaveGroup')->middleware('auth:sanctum');
});


Route::middleware('auth:sanctum')->post('/broadcasting/auth', function (Request $request) {
    return true;//change later
}); 
Broadcast::routes(['middleware' => ['auth:sanctum']]);

require base_path('routes\channels.php');
// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
// Route::get('/user', )
