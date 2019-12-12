<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class UserController extends Controller
{
    public function login(Request $request){
        $credentials = $request->only('email', 'password');

        try {
            if(!$token = JWTAuth::attempt($credentials)){
                return response()->json([
                    'logged'  =>  false,
                    'message' =>  'Invalid Email and Password'
                ]);
            }
        } catch(JWTException $e){
            return response()->json([
                'logged'   =>  false,
                'message'  =>  'Generate Token Failed'
            ]);
        }

        return response()->json([
            "logged"   => true,
            "token"    => $token,
            "message"  => "Login Berhasil"
        ]);
    }

	public function register(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'name'     => 'required|string|max:255',
			'email'    => 'required|string|email|max:255|unique:users',
			'password' => 'required|string|min:6',
			'role'     => 'required|in:petugas',
		]);

		if($validator->fails()){
			return response()->json([
				'status'	=> 0,
				'message'	=> $validator->errors()->toJson()
			]);
		}

		$user = new User();
		$user->name 	= $request->name;
		$user->email 	= $request->email;
		$user->role 	= $request->role;
		$user->password = Hash::make($request->password);
		$user->save();

		$token = JWTAuth::fromUser($user);

		return response()->json([
			'status'	=> '1',
			'message'	=> 'Petugas berhasil ter-registrasi'
		], 201);
	}

	public function index($limit = 10, $offset = 0)
	{
		$data["count"] = User::count();
		$user = array();

		foreach(User::take($limit)->skip($offset)->get() as $p) {
			$item = [
				'id'           => $p->id,
				'name'         => $p->name,
				'email'        => $p->email,
				'role'         => $p->role,
				'created_at'   => $p->created_at,
				'updated_at'   => $p->updated_at,
			];
			array_push($user, $item);
		}
		$data["user"] = $user;
		$data["status"] = 1;
		return response($data);
	}

	public function update($id, Request $request)
	{
		$user = User::where('id', $id)->first();

		$user->name       = $request->name;
		$user->email      = $request->email;
		$user->role       = $request->role;
		$user->updated_at = now()->timestamp;

		$user->save();

		return response($user);
	}

	public function destroy($id)
	{
		$user = User::where('id', $id)->first();

		$user->delete();

		return response()->json([
			'status'  =>  '1',
			'message' =>  'Delete Data Berhasil'
		]);
	}

	public function getAuthenticatedUser(){
		try {
			if(!$user = JWTAuth::parseToken()->authenticate()){
				return response()->json([
						'auth' 		=> false,
						'message'	=> 'Invalid token'
					]);
			}
		} catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e){
			return response()->json([
						'auth' 		=> false,
						'message'	=> 'Token expired'
					], $e->getStatusCode());
		} catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e){
			return response()->json([
						'auth' 		=> false,
						'message'	=> 'Invalid token'
					], $e->getStatusCode());
		} catch (Tymon\JWTAuth\Exceptions\JWTException $e){
			return response()->json([
						'auth' 		=> false,
						'message'	=> 'Token absent'
					], $e->getStatusCode());
		}

		 return response()->json([
		 		"auth"    => true,
                "user"    => $user
		 ], 201);
	}

	
}
