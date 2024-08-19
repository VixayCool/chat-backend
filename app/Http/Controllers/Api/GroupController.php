<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use App\Models\Group;
use App\Models\User;
use App\Models\Profile;  

class GroupController extends Controller
{
    public function create(Request $request){
        try{
            $validated = Validator::make($request->all(), [
                'name' => 'required',
                'users' => 'required|array',
            ]);
            if ($validated->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'validation error',
                    'data' => $validated->errors(),
                ], 422);
            }
            $user = Auth::user();
            DB::beginTransaction();
            $group = Group::create(['name'=>$request->name]);
            $group->users()->attach($user->id, ['role'=>'creator']);
            foreach($request->users as $user){
                $group->users()->attach($user["id"]);
            }
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'user created successfully',
                'data' => $group,
            ], 200);
        }
        catch(\Exception $e){
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => 'internal server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function get(){
        try{
            $user = Auth::user();
            $groups = $user->groups()->with(['latestMessage', 'profile', 'users'])->limit(5)->get();
            $groups->each(function ($group){
                $group->profile = $this->getProfile($group->profile);
                $group->users->each(function ($user){
                    if($user->profile){
                        $user->profile->profile_image = asset('profile/'.$user->profile->profile_image);
                    }
                }); 
                if($group->latestMessage !== null){             
                    if($group->latestMessage->message_type == "file"){
                        $position = strpos($group->latestMessage->content, ".");
                        if($position){
                            $group->latestMessage->content = substr($group->latestMessage->content, $position+1);
                        } 
                    }     
                } 
                        
            });
            return response()->json([
                'status' => 'success',
                'message' => 'group retrived successfully',
                'data' => $groups,
            ], 200);
        }
        catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'internal server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function getGroupDetail($id){
        try{
            $user = Auth::user();
            $group = Group::with(['users.profile', 'profile'])->find($id);
            $group->profile = $this->getProfile($group->profile);
            $group->users->map(function ($user){
                if($user->profile){
                    $user->profile->profile_image = asset('profile/'.$user->profile->profile_image);
                }
            });
            Log::Info($group);
            return response()->json([
                'status' => 'success',
                'message' => 'group detail retrived successfully',
                'data' => $group,
            ], 200);
        }
        catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'internal server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function updateGroupDetail(Request $request, $id){
        try{
            //need to validate
            $user = Auth::user();
            $group = Group::find($id);
            DB::beginTransaction();
            $group->update(['name'=>$request->name]);
            $group->profile()->updateOrCreate(['group_id'=> $id], ['bio'=>$request->bio ]);  
            DB::commit();         
            Log::Info($group);
            return response()->json([
                'status' => 'success',
                'message' => 'group detail updated',
                'data' => $group,
            ], 200);
        }
        catch(\Exception $e){
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'internal server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function leaveGroup($id){
        try{
            $user = Auth::user();
            $group = Group::find($id);
            $group->users()->detach($user->id);
            return response()->json([
                'status' => 'success',
                'message' => 'left group successfully',
                'data' => $group,
            ], 200);
                        }
        catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'internal server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function deleteMember($group_id, $friend_id){
        try{
            $user = Auth::user();
            $group = Group::find($group_id);
            $userPermission = $group->users()->where('user_id', $user->id)->first()->pivot->role;
            Log::Info($userPermission);
            if(!($userPermission == "creator" || $userPermission == "admin" )){
                return response()->json([
                    'status' => 'success',
                    'message' => 'you are not allow to add member',
                ], 403);
            }
            DB::beginTransaction();
            $group->users()->detach($friend_id);
            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'delete member from group successfully',
                'data' => $group,
            ], 200);
                        }
        catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'internal server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function addMember($group_id, $user_id){
        try{
            $user = Auth::user();
            $group = Group::find($group_id);
            $userPermission = $group->users()->where('user_id', $user->id)->first()->pivot->role;
            if(!($userPermission == "creator" || $userPermission == "admin" )){
                return response()->json([
                    'status' => 'success',
                    'message' => 'you are not allow to add member',
                ], 403);
            }
            DB::beginTransaction();
            $group->users()->attach($user_id, ["role"=>"member"]);
            DB::commit();        
            
            return response()->json([
                'status' => 'success',
                'message' => 'user added successfully',
            ], 200);
        }
        catch(\Exception $e){
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'internal server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function memberToAdmin(Request $request, $id){
        try{
            $validated = Validator::make($request->all(),[
                'friend_id' => 'required',
            ]);
            $user = Auth::user();
            //check user authority first
            $group = Group::find($id);
            $userPermission = $group->users()->where('user_id', $user->id)->first()->pivot->role;
            if(!($userPermission == "creator" || $userPermission == "admin" )){
                return response()->json([
                    'status' => 'success',
                    'message' => 'you are not allow to add member',
                ], 403);
            }
            $group->users()->updateExistingPivot($request->friend_id, ['role'=>'admin']);
            return response()->json([
                'status' => 'success',
                'message' => 'updated user to admin successfully',
            ], 200);
        }
        catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'internal server error',
                'error' => $e->getMessage(),
            ], 500);
        }
       

    }
    public function adminToMember(Request $request, $id){
        try{
            $validated = Validator::make($request->all(),[
                'friend_id' => 'required',
            ]);
            $user = Auth::user();
            //better check user authority first
            $group = Group::find($id);
            $userPermission = $group->users()->where('user_id', $user->id)->first()->pivot->role;
            if(!($userPermission == "creator" || $userPermission == "admin" )){
                return response()->json([
                    'status' => 'success',
                    'message' => 'you are not allow to add member',
                ], 403);
            }
            $group->users()->updateExistingPivot($request->friend_id, ['role'=>'member']);
            return response()->json([
                'status' => 'success',
                'message' => 'updated user to admin successfully',
            ], 200);
        }
        catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'internal server error',
                'error' => $e->getMessage(),
            ], 500);
        }

    }
    public function storeProfileImage(Request $request, $id){
        try{
            //validate later

            $user = Auth::user();
            $group = Group::find($id);  
            $groupProfile = $group->profile;
            if($groupProfile && $groupProfile->profile_image){
                $oldPath = public_path('profile/'. $groupProfile->profile_image);
                if(File::exists($oldPath)){ 
                    File::delete($oldPath);
                }
            }

            $imageData = $request->get('profile_image');
            $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $imageName = time() . '_profile.jpg';//get name later
            $filePath = public_path('profile/'.$imageName);
            File::put($filePath, base64_decode($imageData));
            DB::beginTransaction();
            $group->profile()->updateOrCreate(['group_id'=>$id], ['profile_image'=>$imageName]);
            DB::commit();
            return response()->json([
                'status'=>'success',
                'message'=>'image stored successfully',            
            ], 200);
        }
        catch(\Exception $e){
            DB::rollback();
            return response()->json([
                'status'=>'error',
                'message'=>'internal server error',     
                'error'=>$e->getMessage(),      
            ], 500);
        }

    }
    public function storeBackgroundImage(Request $request, $id){
        try{
            //validate later

            $user = Auth::user();
            $group = Group::find($id);
            $groupProfile = $group->profile;
            if($groupProfile && $groupProfile->background_image){
                $oldPath = public_path('background/'. $groupProfile->background_image);
                if(File::exists($oldPath)){ 
                    File::delete($oldPath);
                }
            }

            $imageData = $request->get('background_image');
            $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $imageName = time() . '_background.jpg';//get name later
            $filePath = public_path('background/'.$imageName);
            File::put($filePath, base64_decode($imageData));
            DB::beginTransaction();
            $group->profile()->updateOrCreate(['group_id'=>$id], ['background_image'=>$imageName]);
            DB::commit();
            return response()->json([
                'status'=>'success',
                'message'=>'image stored successfully',            
            ], 200);
        }
        catch(\Exception $e){
            DB::rollback();
            return response()->json([
                'status'=>'error',
                'message'=>'internal server error',     
                'error'=>$e->getMessage(),      
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
