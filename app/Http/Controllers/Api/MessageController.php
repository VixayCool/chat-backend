<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Events\MessageSent;
use App\Events\MessageDeleted;
use App\Models\Message;
use App\Models\Group;
use App\Models\Friendship;

use Carbon\Carbon;
class MessageController extends Controller
{
    public function send(Request $request){
        try{
            $validated = Validator::make($request->all(), [
                'receiver_id'=>'required',
                'destination_id'=>'required',
                'destination_type'=>'required|in:user,group',
                'content'=>'required',
            ]);
            if($validated->fails()){
                return response()->json([
                    'status'=>'error',
                    'message'=>'validation error',
                    'data'=>$validated->errors()
                ],422);
            }

            DB::beginTransaction();
            $user = Auth::user();
            if($request->room_type==="group"){
                $group = Group::find($request->destination_id);
                $message = $group->messages()->create([
                    'sender_id'=>$user->id,
                    'content'=>$request->content, 
                    'destination_id'=>$request->destination_id,
                    'destination_type'=>$request->destination_type,              
                ]);
            }
            else{
                $friendship = Friendship::find($request->destination_id);
                $message = $friendship->messages()->create([
                    'sender_id'=>$user->id,
                    'content'=>$request->content,  
                    'destination_id'=>$request->destination_id,
                    'destination_type'=>$request->destination_type,                 
                ]);
               
            }
            $message->load("sender");
            DB::commit();
            broadcast(new MessageSent($message))->toOthers();
            return response()->json([
                'status'=>'success',
                'data'=>$message,
            ], 200);
        }
        catch(\Exception $e){
            DB::rollback();
            return response()->json([
                'status'=>'error',
                'message'=>'Internal server error',
                'data'=>$e->getMessage(),
            ],500);
        }
       
    }
    public function getMessages($id, Request $request){
        try{

            if($request->query('type')== 'group'){
                $group = Group::find($id);
                $message = $group->messages()->orderBy("id","desc")->limit(50)->with('sender')->get();
            }
            else{
                $friendship = Friendship::find($id);
                $message = $friendship->messages()->orderBy("id","desc")->limit(50)->with('sender')->get();
            }

            return response()->json([
                'status'=>"success",
                'data'=>$message,
            ], 200);
        }
        catch(\Exception $e){
            return response()->json([
                'status'=>"error",
                'message'=>"Internal server error",
                'error'=>$e->getMessage(),
            ],500);
        }
    }
    public function deleteMessage($id){
        try{
            $userId = Auth::id();
            $message = Message::find($id);
            Log::Info($message);
            DB::beginTransaction();
            $message->delete();
            DB::commit();
            broadcast(new MessageDeleted($message))->toOthers();
            return response()->json([
                'status'=>"message deleted",
                'data'=>$message,
            ], 200);
        }
        catch(\Exception $e){
            DB::rollback();
            return response()->json([
                'status'=>"error",
                'message'=>"Internal server error",
                'error'=>$e->getMessage(),
            ],500);
        }
    } 
}
