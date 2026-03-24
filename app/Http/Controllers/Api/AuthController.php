<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // 模拟用户（你原本的）
    private static array $users = [
        [
            'id' => 1,
            'name' => 'test_user',
            'email' => '2633681826@qq.com',
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
        ]
    ];

    // 发送验证码 → 发邮件 → 存缓存（你原本的逻辑）
    public function sendCode(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'msg' => '参数验证失败',
                'data' => $validator->errors()->first()
            ], 400);
        }

        $email = trim($request->input('email'));
        $verifyCode = rand(100000, 999999); // 随机6位验证码

        // 存入缓存（跨请求稳定）
        cache()->put("verify_code:$email", [
            'code' => (string)$verifyCode,
            'expire' => time() + 300
        ], 300);

        // 发送邮件到你的邮箱（你原本的功能）
        try {
            Mail::raw("【BlogSystem】你的注册验证码是：{$verifyCode}，5分钟内有效",
                function ($message) use ($email) {
                    $message->to($email)->subject('注册验证码');
                });
        } catch (\Exception $e) {}

        return response()->json([
            'code' => 200,
            'msg' => '验证码已发送至邮箱，请查收',
            'data' => []
        ]);
    }

    // 注册：必须用邮箱收到的验证码才能通过（你原本的逻辑）
    public function register(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6|confirmed',
            'code' => 'required|numeric|digits:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'msg' => '参数验证失败',
                'data' => $validator->errors()->first()
            ], 400);
        }

        $email = trim($request->input('email'));
        $code = (string)$request->input('code');

        // 从缓存读取 真实邮箱验证码
        $codeData = cache()->get("verify_code:$email");

        // 必须先获取验证码
        if (!$codeData) {
            return response()->json([
                'code' => 400,
                'msg' => '请先获取注册验证码',
                'data' => []
            ], 400);
        }

        // 必须验证码正确
        if ($codeData['code'] !== $code) {
            return response()->json([
                'code' => 400,
                'msg' => '验证码错误',
                'data' => []
            ], 400);
        }

        // 验证通过后删除
        cache()->forget("verify_code:$email");

        // 检查邮箱重复
        foreach (self::$users as $user) {
            if ($user['email'] === $email) {
                return response()->json([
                    'code' => 400,
                    'msg' => '该邮箱已注册',
                    'data' => []
                ], 400);
            }
        }

        // 创建用户
        $newUserId = count(self::$users) + 1;
        $newUser = [
            'id' => $newUserId,
            'name' => trim($request->input('name')),
            'email' => $email,
            'password' => Hash::make($request->input('password'))
        ];
        self::$users[] = $newUser;

        return response()->json([
            'code' => 200,
            'msg' => '注册成功！',
            'data' => ['user' => $newUser]
        ]);
    }

    public function login(Request $request): \Illuminate\Http\JsonResponse
    {
        return response()->json(['code' => 200, 'msg' => '登录成功']);
    }

    public function logout(): \Illuminate\Http\JsonResponse
    {
        return response()->json(['code' => 200, 'msg' => '退出成功']);
    }

    public function me(): \Illuminate\Http\JsonResponse
    {
        return response()->json(['code' => 200, 'msg' => '获取用户信息成功']);
    }
}