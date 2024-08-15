<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Services\AzureTranslatorService;
use App\Services\AzureSummarizationService;

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
                'file'=>'nullable',
                'receiver_id'=>'required',
                'destination_id'=>'required',
                'destination_type'=>'required|in:user,group',
                'destination_id'=>'required',
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
            Log::Info($request->all());

            $message_type ="message";
            $message_content = $request->content;

            if($request->hasFile('file')){
                $file_name = time().".".$request->file('file')->getClientOriginalName();
                $filePath = $request->file('file')->move(public_path('file_message'), $file_name);
                $message_type ="file";
                $message_content = $file_name;
            }
            if($request->room_type==="group"){
                $group = Group::find($request->destination_id);
                $message = $group->messages()->create([
                    'sender_id'=>$user->id,
                    'content'=>$message_content, 
                    'destination_id'=>$request->destination_id,
                    'destination_type'=>$request->destination_type,
                    'message_type'=>$message_type,              
                ]);
            }
            else{
                $friendship = Friendship::find($request->destination_id);
                $message = $friendship->messages()->create([
                    'sender_id'=>$user->id,
                    'content'=>$message_content,  
                    'destination_id'=>$request->destination_id,
                    'destination_type'=>$request->destination_type,
                    'message_type'=>$message_type,                         
                ]);  
            }
            $message->load("sender");
            if($request->hasFile('file')){
                $message->file = asset('file_message/' . $message->content);
                $position = strpos($message->content, ".");
                if($position){
                    $message->content = substr($message->content, $position+1);
                }
            }
            DB::commit();
            broadcast(new MessageSent($message, $request->receiver_id))->toOthers();
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
                $room = Group::find($id);
            }
            else{
                $room = Friendship::find($id);
            }
            $messages = $room->messages()->orderBy("id","desc")->limit(50)->with('sender')->get();
            $messages->map(function($message){
                if($message->message_type == "file"){
                    $message->file = asset('file_message/' . $message->content);
                    $position = strpos($message->content, ".");
                    if($position){
                        $message->content = substr($message->content, $position+1);
                    }
                    
                }
                return $message;
            });
            return response()->json([
                'status'=>"success",
                'data'=>$messages,
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
    public function downloadFile($fileName){
        try{
            $filePath = public_path("file_message/".$fileName);
            if (file_exists($filePath)){
                return response()->download($filePath, $fileName, ['Content-Type'=>'application/octet-stream']);
            }
            return response()->json([
                'status' => 'error',
                'message' => 'File not found',
            ], 404);
        }
        catch(\Exception $e){
            return response()->json([
                'status'=>"error",
                'message'=>"Internal server error",
                'error'=>$e->getMessage(),
            ],500);
        }
    }


    // maybe better as seperate controller
    public function translateMessage(Request $request, AzureTranslatorService $translator){
        try{
            $translated_content = $translator->translate($request->content, $request->destination_language);
            
            Log::Info($translated_content);
            return response()->json([
                'status'=>"message translated successfully",
                'data'=>$translated_content,
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

    public function summarizeConversation(Request $request, $room_type, $id, AzureSummarizationService $summarizer){
        try{
            
            if($room_type == 'group'){
                $room = Group::find($id);
            }
            else{
                $room = Friendship::find($id);
            }
            $startDate = Carbon::createFromFormat('m/d/Y', $request->query('startDate'))->startOfDay();
            $endDate = Carbon::createFromFormat('m/d/Y', $request->query('endDate'))->endOfDay();
            $messages = $room->messages()->whereBetween('created_at', [$startDate, $endDate])->with("sender")->get();
            $arranged_messages = "";    
            $messages->each(function($message) use (&$arranged_messages){
                if($message->message_type !="file"){
                    $arranged_messages .= $message->sender->name.":".$message->content.",";
                }
            });
            Log::Info($arranged_messages);
            $summarized_content = $summarizer->summarize($request->query('lang'), $arranged_messages);
            Log::Info($summarized_content);
            return response()->json([
                'status'=>"message translated successfully",
                'data'=>$summarized_content,
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

}
