<?php

namespace App\Http\Controllers;

use App\Company;
use App\Http\Requests\RegisterAuthRequest;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Validator;

class ApiController extends Controller
{
    public $loginAfterSignUp = true;
    protected const COMPANY = 2;

    public function register(RegisterAuthRequest $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'email' => 'required|email|unique:users',
                'password' => 'required|string|min:6|max:10'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors'=>array($validator->errors()->all())], 500);
            }

            $user = DB::transaction(function () use($request){
                $user = new User();
                $user->name = $request->name;
                $user->email = $request->email;
                $user->password = bcrypt($request->password);
                if (!is_null($request->role_id)){
                    $user->save();
                    $user->roles()->attach($request->role_id);
                }else{
                    return response()->json([
                        'success' => false,
                        'data' => 'cannot save user! give a role!'
                    ], 504);
                }

                if ($request->role_id == self::COMPANY && !is_null($request->company_id)){
                    $user->company()->attach($request->company_id);
                }else{
                    return response()->json([
                        'success' => false,
                        'data' => 'cannot save user! it is not company role or company_id is null!'
                    ], 504);
                }

                if ($this->loginAfterSignUp) {
                    return $this->login($request);
                }
                return $user;
            });

            return response()->json([
                'success' => true,
                'data' => $user
            ], 200);

        }catch (\Exception $exception){
            dd($exception);
            return response()->json([
                'success' => false,
                'data' => 'there are some errors!'
            ], 504);
        }
    }

    public function login(Request $request)
    {
        $input = $request->only('email', 'password');
        $jwt_token = null;

        if (!$jwt_token = JWTAuth::attempt($input)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Email or Password',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'token' => $jwt_token,
        ]);
    }

    public function logout(Request $request)
    {
        $this->validate($request, [
            'token' => 'required'
        ]);

        try {
            JWTAuth::invalidate($request->token);

            return response()->json([
                'success' => true,
                'message' => 'User logged out successfully'
            ]);
        } catch (JWTException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, the user cannot be logged out'
            ], 500);
        }
    }

    public function getAuthUser(Request $request)
    {
        $this->validate($request, [
            'token' => 'required'
        ]);

        $user = JWTAuth::authenticate($request->token);

        return response()->json(['user' => $user]);
    }
}
