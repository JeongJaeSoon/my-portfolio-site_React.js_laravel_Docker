<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use App\User;
use Auth;
use Validator;
use Hash;

class ApiAuthController extends Controller
{

    /**
     * Register
     *
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response([
                'errors' => $validator->errors()->all()
            ], 422);
        }

        $request['password'] = Hash::make($request['password']);
        $request['remember_token'] = Str::random(10);
        $user = User::create($request->toArray());

        $token = $user->createToken('Laravel Password Grant Client')->accessToken;
        return response([
            "msg" => "회원가입에 성공하였습니다.",
            'token' => $token
        ], 201);
    }

    /**
     * Login
     *
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response([
                'errors' => $validator->errors()->all()
            ], 422);
        }

        $user = User::where('email', $request->input('email'))->first();

        // 유저 정보가 있을 경우
        if ($user) {
            // 정확한 password
            if (Hash::check($request->input('password'), $user->password)) {
                $token = $user->createToken('Laravel Password Grant Client')->accessToken;
                return response([
                    "msg" => "로그인에 성공하였습니다.",
                    'token' => $token
                ], 201);
            }

            // 잘못된 password
            return response([
                "msg" => "패스워드가 일치하지 않습니다."
            ], 401);
        }

        // 유저 정보 없을 경우
        return response([
            "msg" => '존재하지 않는 사용자입니다.'
        ], 401);
    }

    /**
     * Logout
     *
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public function logout(Request $request)
    {
        $token = $request->user()->token();
        $token->revoke();
        return response([
            'msg' => '로그아웃에 성공하였습니다.'
        ], 200);
    }

    public function auth()
    {
        if (Auth::guard('api')->check()) {
            return response([
                'msg' => '인증에 성공하였습니다.',
                'isAuth' => true
            ], 201);
        }
    }
}
