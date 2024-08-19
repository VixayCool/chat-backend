<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Models\User;  
use App\Models\Profile;     

class UserController extends Controller
{
    public function signup(Request $request)
    {
        try {
            $validated = Validator::make($request->all(), [
                'name' => 'required',
                'email' => 'required',
                'password' => 'required|min:8',
            ]);
            if ($validated->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'validation error',
                    'data' => $validated->errors(),
                ], 422);
            }
            $email_existed = User::where("email", $request->email)->first();
            if($email_existed) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'email is already used',
                    'data' => $validated->errors(),
                ], 422);
            }

            //check  if email is used
            DB::beginTransaction();
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'user created successfully',
                'token' => $user->createToken('auth-token')->plainTextToken,
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => 'internal server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $validated = Validator::make($request->all(),
                [
                    'email' => 'required',
                    'password' => 'required',
                ]);
            if ($validated->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'validation error',
                    'error' => $validated->errors(),
                ], 422);
            }
            if (!Auth::attempt($request->only(['email', 'password']))) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'please check your email and password',
                ], 401);
            }

            $user = User::with("profile")->where('email', $request->email)->first();
            $user->profile = $this->getProfile($user->profile);
            return response()->json([
                'status' => 'success',
                'message' => 'user logged in successfully',
                'data'=>$user,
                'token' => $user->createToken('auth-token')->plainTextToken,
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => 'internal server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function getSelf(){
        try{
            $user = Auth::user();
            $user->profile = $this->getProfile($user->profile);
            return response()->json([
                'status' => 'success',
                'message' => 'user updated successfully',
                'data'=> $user,
            ], 200);       }
        catch(\Exception $e){

        }
    }
    public function getUser($id){
        try{
            $user = User::with("profile")->find($id);
            $user->profile = $this->getProfile($user->profile);
            return response()->json([
                'status' => 'success',
                'message' => 'user updated successfully',
                'data'=> $user,
            ], 200);

        }
        catch(\Exception $e){

        }
    }
    public function update(Request $request)
    {
        try {
            $validated = Validator::make($request->all(), [
                'name' => 'required',
                'password' => 'required|min:8',
            ]);
            if ($validated->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'validation error',
                    'data' => $validated->errors(),
                ], 422);
            }
            //check  if email is used
            $userId = Auth()::id();
            DB::beginTransaction();
            //model not find exception
            $user = User::find($userId)->firstOrFail();
            $user->update([
                'name' => $request->name,
                'password' => Hash::make($request->password),
            ]);
            $user->save();
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'user updated successfully',
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => 'internal server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function delete(Request $request)
    {
        try {
            $userId = Auth()::id();
            DB::beginTransaction();
            //model not find exception
            $user = User::find($userId)->firstOrFail();
            $user->delete();
            DB::commit();

            return response()->json([
                'status' => 'account deleted',
                'message' => 'user updated successfully',
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => 'internal server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeProfileImage(Request $request){
        try{
            //validate later

            $user = Auth::user();
            $userProfile = $user->profile;
            if($userProfile && $userProfile->profile_image){
                $oldPath = public_path('profile/'. $userProfile->profile_image);
                if(File::exists($oldPath)){ 
                    File::delete($oldPath);
                }
            }

            $imageData = $request->get('profile_image');
            $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $imageName = time() . '_profile.jpg';//get name later
            // $imageName = time().'_'.$request->profile_image->getClientOriginalName();
            // $request->file('profile_image')->move(public_path("profile"), $imageName);

            $filePath = public_path('profile/'.$imageName);
            // \Storage::disk('public')->put('profile/'.$imageName, base64_decode($imageData));
            File::put($filePath, base64_decode($imageData));
            DB::beginTransaction();
            $profile = $user->profile()->updateOrCreate(['user_id'=>$user->id], ['profile_image'=>$imageName]);
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
    public function storeBackgroundImage(Request $request){
        try{
            // $validated = Validator::make($request->all(),[
            //     'background_image'=>'required|image|mimes:jpg, png, jpeg',
            // ]);
            // if ($validated->fails()) {
            //     return response()->json([
            //         'status' => 'error',
            //         'message' => 'validation error',
            //         'data' => $validated->errors(),
            //     ], 422);
            // }
            $user = Auth::user();
            $userProfile = $user->profile;
            if($userProfile && $userProfile->background_image){
                $oldPath = public_path("background/".$userProfile->background_image);
                if(File::exists($oldPath)){
                    File::delete($oldPath);
                }
            }
            // $imageName = time().'_'.$request->background_image->getClientOriginalName();
            $imageData = $request->get('background_image');
            $imageData = str_replace('data:image/jpeg;base64,', '',$imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $imageName = time().'_background.jpg';
            $filePath =  public_path('background/'.$imageName);
            File::put($filePath, base64_decode($imageData));
            DB::beginTransaction();
            $profile = $user->profile()->updateOrCreate(['user_id'=>$user->id], ['background_image'=>$imageName]);
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

