<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    private static function getUsers() {
        // 1. 先查询数据库
        $dbUsers = User::select('id', 'name', 'email', 'password')->get()->toArray();

        // 2. 如果数据库为空，初始化测试用户
        if (empty($dbUsers)) {
            $testUser = [
                'id' => 1,
                'name' => 'test_user',
                'email' => '2633681826@qq.com',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
            ];
            // 写入数据库
            User::create($testUser);

            // 3. 【关键修复】写入后，重新从数据库查回来！
            // 这样保证返回的数据结构永远是一致的（数据库类型）
            return User::select('id', 'name', 'email', 'password')->get()->toArray();
        }

        return $dbUsers;
    }

    private static function saveUsers($users) {
        // 数据库无需批量保存，注册逻辑已改为直接创建用户，保留方法避免报错
        return true;
    }

    // 发送验证码 → 发邮件 → 存缓存（完全保留原逻辑）
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
            ], 400, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        $email = trim($request->input('email'));
        $verifyCode = rand(100000, 999999);

        cache()->put("verify_code:$email", [
            'code' => (string)$verifyCode,
            'expire' => time() + 300
        ], 300);

        try {
            Mail::raw("【BlogSystem】你的注册验证码是：{$verifyCode}，5 分钟内有效",
                function ($message) use ($email) {
                    $message->to($email)->subject('注册验证码');
                });
            
            \Illuminate\Support\Facades\Log::info("验证码邮件已发送至：{$email}");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("验证码发送失败：{$e->getMessage()}");
            return response()->json([
                'code' => 500,
                'msg' => '邮件发送失败：' . $e->getMessage(),
                'data' => []
            ], 500, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        return response()->json([
            'code' => 200,
            'msg' => '验证码已发送至邮箱，请查收',
            'data' => []
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    // 注册：必须用邮箱收到的验证码才能通过（仅替换保存用户逻辑）
    public function register(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6|confirmed',
            'code' => 'required|numeric|digits:6'//需要写明验证码的位数
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'msg' => '参数验证失败',
                'data' => $validator->errors()->first()
            ], 400, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        $email = trim($request->input('email'));
        $code = (string)$request->input('code');

        $codeData = cache()->get("verify_code:$email");

        if (!$codeData) {
            return response()->json([
                'code' => 400,
                'msg' => '请先获取注册验证码',
                'data' => []
            ], 400, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        if ($codeData['code'] !== $code) {
            return response()->json([
                'code' => 400,
                'msg' => '验证码错误',
                'data' => []
            ], 400, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        cache()->forget("verify_code:$email");

        $users = self::getUsers();
        foreach ($users as $user) {
            if ($user['email'] === $email) { // ✅ 改回数组访问
                return response()->json([
                    'code' => 400,
                    'msg' => '该邮箱已注册',
                    'data' => []
                ], 400, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }
        }

        $newUser = User::create([
            'name' => trim($request->input('name')),
            'email' => $email,
            'password' => Hash::make($request->input('password'))
        ]);

        // 保持原返回格式（兼容前端）- 不返回密码
        $newUserArr = [
            'id' => $newUser->id,
            'name' => $newUser->name,
            'email' => $newUser->email
        ];

        return response()->json([
            'code' => 200,
            'msg' => '注册成功！',
            'data' => ['user' => $newUserArr]
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function login(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'msg' => '参数验证失败',
                'data' => $validator->errors()->first()
            ], 400, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        $email = trim($request->input('email'));
        $password = $request->input('password');

        $user = User::where('email', $email)->first();
        
        if (!$user) {
            return response()->json([
                'code' => 400,
                'msg' => '该邮箱未注册',
                'data' => []
            ], 400, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        if (!Hash::check($password, $user->password)) {
            return response()->json([
                'code' => 400,
                'msg' => '密码错误',
                'data' => []
            ], 400, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        // 生成 JWT Token
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'code' => 200,
            'msg' => '登录成功',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ],
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl', 60)
            ]
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function logout(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            
            return response()->json([
                'code' => 200,
                'msg' => '退出成功'
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 400,
                'msg' => '退出失败',
                'data' => $e->getMessage()
            ], 400, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
    }

    public function me(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = JWTAuth::user();
            
            if (!$user) {
                return response()->json([
                    'code' => 401,
                    'msg' => '请先登录',
                    'data' => []
                ], 401, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }

            return response()->json([
                'code' => 200,
                'msg' => '获取成功',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email
                    ]
                ]
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 401,
                'msg' => '请先登录',
                'data' => []
            ], 401, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
    }

    // 查看已注册账号（只修复读取用户，逻辑不变）
    public function getRegisteredUsers(): \Illuminate\Http\JsonResponse
    {
        $users = User::select('id', 'name', 'email')->get();

        return response()->json([
            'code' => 200,
            'msg' => '已注册账号信息',
            'data' => ['users' => $users]
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}