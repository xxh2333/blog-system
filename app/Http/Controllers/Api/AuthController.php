<?php
//认证控制器

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

// 全局内存模拟数据（无数据库核心）
global $mockUsers, $codeCache;
// 初始化模拟用户（默认测试账号）
$mockUsers = [
    [
        'id' => 1,
        'name' => 'test_user',
        'email' => '2633681826@qq.com',
        'password' => bcrypt('123456') // 预加密密码
    ]
];
// 初始化验证码缓存
$codeCache = [];

class AuthController extends Controller
{
    /**
     * 发送注册验证码
     */
    public function sendCode(Request $request)
    {
        global $codeCache;

        // 1. 参数校验
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'msg' => '参数错误',
                'data' => $validator->errors()->first()
            ], 400);
        }

        $email = $request->input('email');
        // 2. 生成6位验证码
        $code = rand(100000, 999999);
        // 3. 存入内存缓存（标记过期时间：5分钟）
        $codeCache[$email] = [
            'code' => $code,
            'expire' => time() + 300
        ];

        try {
            // 4. 发送验证码邮件
            Mail::raw(
                "【BlogSystem】你的注册验证码是：{$code}，5分钟内有效，请勿泄露！",
                function ($message) use ($email) {
                    $message->to($email)
                        ->subject('BlogSystem注册验证码');
                }
            );

            return response()->json([
                'code' => 200,
                'msg' => '验证码发送成功，请查收邮箱',
                'data' => []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'msg' => '邮件发送失败：'.$e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * 用户注册（无数据库版）
     */
    public function register(Request $request)
    {
        global $mockUsers, $codeCache;

        // 1. 参数校验
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed',
            'code' => 'required|numeric'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'msg' => '参数错误',
                'data' => $validator->errors()->first()
            ], 400);
        }

        $email = $request->input('email');
        $code = $request->input('code');

        // 2. 校验验证码
        if (!isset($codeCache[$email])) {
            return response()->json([
                'code' => 400,
                'msg' => '请先获取验证码',
                'data' => []
            ], 400);
        }
        if ($codeCache[$email]['expire'] < time()) {
            unset($codeCache[$email]); // 清除过期验证码
            return response()->json([
                'code' => 400,
                'msg' => '验证码已过期，请重新获取',
                'data' => []
            ], 400);
        }
        if ($codeCache[$email]['code'] != $code) {
            return response()->json([
                'code' => 400,
                'msg' => '验证码错误',
                'data' => []
            ], 400);
        }

        // 3. 模拟创建用户（加入内存数组）
        $newUserId = count($mockUsers) + 1;
        $newUser = [
            'id' => $newUserId,
            'name' => $request->input('name'),
            'email' => $email,
            'password' => bcrypt($request->input('password'))
        ];
        $mockUsers[] = $newUser;

        // 4. 清除已使用的验证码
        unset($codeCache[$email]);

        // 5. 生成JWT Token
        $token = JWTAuth::customClaims(['sub' => $newUserId])
            ->fromUser((object)$newUser);

        return response()->json([
            'code' => 200,
            'msg' => '注册成功',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $newUserId,
                    'name' => $newUser['name'],
                    'email' => $newUser['email']
                ]
            ]
        ]);
    }

    /**
     * 用户登录
     */
    public function login(Request $request)
    {
        global $mockUsers;

        // 1. 参数校验
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'msg' => '参数错误',
                'data' => $validator->errors()->first()
            ], 400);
        }

        $email = $request->input('email');
        $password = $request->input('password');

        // 2. 模拟校验用户（遍历内存用户列表）
        $user = null;
        foreach ($mockUsers as $u) {
            if ($u['email'] == $email && Hash::check($password, $u['password'])) {
                $user = $u;
                break;
            }
        }

        if (!$user) {
            return response()->json([
                'code' => 401,
                'msg' => '邮箱或密码错误',
                'data' => []
            ], 401);
        }

        // 3. 生成JWT Token
        $token = JWTAuth::customClaims(['sub' => $user['id']])
            ->fromUser((object)$user);

        return response()->json([
            'code' => 200,
            'msg' => '登录成功',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email']
                ]
            ]
        ]);
    }

    /**
     * 退出登录（使Token失效）
     */
    public function logout(Request $request)
    {
        try {
            // 使当前Token失效
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json([
                'code' => 200,
                'msg' => '退出登录成功',
                'data' => []
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'msg' => '退出失败：'.$e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * 获取当前登录用户信息
     */
    public function me(Request $request)
    {
        try {
            // 获取Token中的用户ID
            $userId = JWTAuth::user()->id ?? 0;
            global $mockUsers;
            // 从内存中查找用户
            $user = null;
            foreach ($mockUsers as $u) {
                if ($u['id'] == $userId) {
                    $user = $u;
                    break;
                }
            }

            if (!$user) {
                return response()->json([
                    'code' => 404,
                    'msg' => '用户不存在',
                    'data' => []
                ], 404);
            }

            // 隐藏密码字段
            unset($user['password']);
            return response()->json([
                'code' => 200,
                'msg' => '获取用户信息成功',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 401,
                'msg' => '未登录或Token失效',
                'data' => []
            ], 401);
        }
    }
}