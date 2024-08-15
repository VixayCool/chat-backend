<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Friendship;
use App\Models\User;
use App\Events\FriendshipAccepted;
class FriendshipController extends Controller
{
    public function friend_request(Request $request){
        try{
            $validated = Validator::make($request->all(), [
                "friend_id"=>"required",
            ]);
            $user_id = Auth::id();
            if($validated->fails()){
                return response()->json([
                    "status"=>"error",
                    "message"=>"validation error",
                    "data"=>$validated->errors(),
                ], 422);
            }
            DB::beginTransaction();
            $friendship = Friendship::create([
                "user_id"=>$user_id,
                "friend_id"=>$request->friend_id,
                "status"=>"pending",
            ]);
            DB::commit();
            return response()->json([
                "status"=>"success",
                "message"=>"friendship request sent successfully",
            ], 200);
        }
        catch(\Exception $e){
            DB::rollback();
            return response()->json([
                "status"=>"error",
                "message"=>"Internal server error",
                "errors"=>$e->getMessage(),
            ], 500);
        }
        
    }

    public function accept($friendship_id){
        try{
         
          //need to implement checking the requested user and that friendship status.
          DB::beginTransaction();
          $friendship = Friendship::find($friendship_id)->update(['status'=>"accepted"]);
          $friendship->$friendship_id = $friendship_id;
          DB::commit();
          broadcast(new FriendshipAccepted($friendship));
          return response()->json([
              "status"=>"success",
              "message0"=>"friendship is accepted",
          ],200);
        }
        catch(\Exception $e){
          DB::rollback();
          return response()->json([
              "status"=>"error",
              "message"=>"Internal server error",
              "errors"=>$e->getMessage(),
          ], 500);
        }
      }
      public function decline($friendship_id){
        try{
          
          //need to implement checking the requested user and that friendship status.
          DB::beginTransaction();
          $friendship = Friendship::find($friendship_id)->update(['status'=>"declined"]);
          DB::commit();
          return response()->json([
              "status"=>"success",
              "message0"=>"declined friendship",
          ],200);
        }
        catch(\Exception $e){
          DB::rollback();
          return response()->json([
              "status"=>"error",
              "message"=>"Internal server error",
              "errors"=>$e->getMessage(),
          ], 500);
        }
      }
      public function delete($friendship_id){
        try{
         
          //need to implement checking the requested user and that friendship status.
          DB::beginTransaction();
          $friendship = Friendship::find($friendship_id)->update(['status'=>"deleted"]);
          DB::commit();
          return response()->json([
              "status"=>"success",
              "message0"=>"deleted friendship",
          ],200);
        }
        catch(\Exception $e){
          DB::rollback();
          return response()->json([
              "status"=>"error",
              "message"=>"Internal server error",
              "errors"=>$e->getMessage(),
          ], 500);
        }
      }

      public function get_friendship_request(){
        try{
          $user = Auth::user();
          $friendships = Friendship::where(function($query) use ($user){
            $query->where('user_id', $user->id)->where('status', 'accepted');})
            ->orWhere(function($query) use ($user){
            $query->where('friend_id', $user->id)->where('status', 'accepted');
        })->get();

          $friends = $friendships->map(function ($friendship) use ($user){
            $friend_id = $friendship->user_id === $user->id? $friendship->friend_id : $friendship->user_id;
            $data = User::with("profile")->find($friend_id);
            $data->friendship_id = $friendship->id;
            $data->latest_message = $friendship->latestMessage()->first();
            if($data->profile){
              $data->profile = $this->getProfile($data->profile);
           }
           if($data->latest_message !== null){              
            if($data->latest_message->message_type == "file"){
                $position = strpos($data->latest_message->content, ".");
                if($position){
                    $data->latest_message->content = substr($data->latest_message->content, $position+1);
                } 
            }     
        } 

            return $data;
          }); 
       
          return response()->json([ 
              "status"=>"success",
              "data"=>$friends,
          ],200);
        }
        catch(\Exception $e){
          return response()->json([
              "status"=>"error",
              "message"=>"Internal server error",
              "errors"=>$e->getMessage(),
          ], 500);
        }
      }

      public function get_pending_friendship(){
        try{
          $user = Auth::user();
          $friendships = Friendship::where(function($query) use ($user){
            $query->where('user_id', $user->id)->where('status', 'pending');})
            ->orWhere(function($query) use ($user){
            $query->where('friend_id', $user->id)->where('status', 'pending');
        })->get();
          
          $friends = $friendships->map(function ($friendship) use ($user){
            $friend_id = $friendship->user_id === $user->id? $friendship->friend_id : $friendship->user_id;
           
            $data = User::with("profile")->find($friend_id);
            $data->friendship_id = $friendship->id;
            if($data->profile){
               $data->profile = $this->getProfile($data->profile);
            }
            return $data;
          }); 
       
          return response()->json([ 
              "status"=>"success",
              "data"=>$friends,
          ],200);
        }
        catch(\Exception $e){
          return response()->json([
              "status"=>"error",
              "message"=>"Internal server error",
              "errors"=>$e->getMessage(),
          ], 500);
        }
      }
      public function search_friendship(Request $request){
        try{
            $validated = Validator::make($request->all(),[
                "email"=>"required",
            ]);
            if($validated->fails()){
                return response()->json([
                    "status"=>"error",
                    "message"=>"validation error",
                    "data"=>$validated->errors(),
                ], 422);
            }
            $user=Auth::user();
          $friend = User::with("profile")->where('email', 'like', $request->email)->first();
          if(!$friend){
            return response()->json([
              "status"=>"friend not found",],
              404);}   

          $friendship = Friendship::where(function($query) use ($user, $friend){
                $query->where('user_id', $user->id)->where('friend_id', $friend->id);})
                ->orWhere(function($query) use ($user, $friend){
                  $query->where('user_id', $friend->id)->where('friend_id', $user->id);
            })->firstOrFail();

          if($friendship) $friend->friendship = $friendship;
         
          if($friend->profile){
            $friend->profile = $this->getProfile($friend->profile);
          }
         
          return response()->json([
              "status"=>"success",
              "data"=>$friend,
          ],200);
        }
        catch(\Exception $e){
          return response()->json([
              "status"=>"error",
              "message"=>"Internal server error",
              "errors"=>$e->getMessage(),
          ], 500);
        }
      }
      public function getProfile($profile){
        try{

            if($profile){
                if($profile->profile_image){
                    $profile->profile_image = asset( 'profile/'.$profile->profile_image);
                }
                if($profile->background_image){
                    $profile->background_image = asset('background/'.$profile->background_image);
                }
               }
            return $profile;
        }
        catch(\ModelNotFoundException $e){

        }
        catch(\Exception $e){
            return response()->json([
                'status'=>'error',
                'message'=>'internal server error',     
                'error'=>$e->getMessage(),      
            ], 500);
        }
    }
}
