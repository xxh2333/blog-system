<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User; // 仅新增：引入User模型

class AuthController extends Controller
{
    // ====================== 【唯一修改】用数据库存储用户（替换原缓存逻辑） ======================
    private static function getUsers() {
        // 从数据库查询用户，转为数组（兼容原逻辑格式）
        $dbUsers = User::select('id', 'name', 'email', 'password')->get()->toArray();

        // 如果数据库无数据，初始化测试用户（保持原默认逻辑）
        if (empty($dbUsers)) {
            $testUser = [
                'id' => 1,
                'name' => 'test_user',
                'email' => '2633681826@qq.com',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
            ];
            // 写入数据库（仅首次初始化）
            User::create($testUser);
            return [$testUser];
        }

        return $dbUsers;
    }

    private static function saveUsers($users) {
        // 数据库无需批量保存，注册逻辑已改为直接创建用户，保留方法避免报错
        return true;
    }
    // ==========================================================================

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
            Mail::raw("【BlogSystem】你的注册验证码是：{$verifyCode}，5分钟内有效",
                function ($message) use ($email) {
                    $message->to($email)->subject('注册验证码');
                });
        } catch (\Exception $e) {}

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

        // ====================== 【修改】读取用户（底层换数据库，逻辑不变） ======================
        $users = self::getUsers();
        foreach ($users as $user) {
            if ($user['email'] === $email) {
                return response()->json([
                    'code' => 400,
                    'msg' => '该邮箱已注册',
                    'data' => []
                ], 400, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }
        }

        // ====================== 【核心修改】保存用户到数据库（替换原缓存追加逻辑） ======================
        $newUser = User::create([
            'name' => trim($request->input('name')),
            'email' => $email,
            'password' => Hash::make($request->input('password'))
        ]);

        // 保持原返回格式（兼容前端）
        $newUserArr = [
            'id' => $newUser->id,
            'name' => $newUser->name,
            'email' => $newUser->email,
            'password' => $newUser->password
        ];
        // ==========================================================================

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

        foreach (self::getUsers() as $user) {
            if ($user['email'] === $email) {
                if (Hash::check($password, $user['password'])) {

                    $token = Str::random(60);
                    cache()->put('login_token:' . $token, $user, 86400);

                    return response()->json([
                        'code' => 200,
                        'msg' => '登录成功',
                        'data' => [
                            'user' => [
                                'id' => $user['id'],
                                'name' => $user['name'],
                                'email' => $user['email']
                            ],
                            'token' => $token,
                            'token_type' => 'bearer'
                        ]
                    ], 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                } else {
                    return response()->json([
                        'code' => 400,
                        'msg' => '密码错误',
                        'data' => []
                    ], 400, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                }
            }
        }

        return response()->json([
            'code' => 400,
            'msg' => '该邮箱未注册',
            'data' => []
        ], 400, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    // ====================== ✅ 修改：登出使 Token 失效（完全保留原逻辑） ======================
    public function logout(Request $request): \Illuminate\Http\JsonResponse
    {
        $auth = $request->header('Authorization');
        if ($auth && str_starts_with($auth, 'Bearer ')) {
            $token = substr($auth, 7);
            cache()->forget('login_token:' . $token);
        }

        return response()->json([
            'code' => 200,
            'msg' => '退出成功'
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    // ====================== ✅ 修改：获取当前登录用户信息（完全保留原逻辑） ======================
    public function me(Request $request): \Illuminate\Http\JsonResponse
    {
        $auth = $request->header('Authorization');

        if (!$auth || !str_starts_with($auth, 'Bearer ')) {
            return response()->json([
                'code' => 401,
                'msg' => '请先登录',
                'data' => []
            ], 401, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        $token = substr($auth, 7);
        $user = cache()->get('login_token:' . $token);

        if (!$user) {
            return response()->json([
                'code' => 401,
                'msg' => '登录已过期，请重新登录',
                'data' => []
            ], 401, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        return response()->json([
            'code' => 200,
            'msg' => '获取用户信息成功',
            'data' => [
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email']
                ]
            ]
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    // 查看已注册账号（只修复读取用户，逻辑不变）
    public function getRegisteredUsers(): \Illuminate\Http\JsonResponse
    {
        $safeUsers = [];
        foreach (self::getUsers() as $user) {
            $safeUsers[] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email']
            ];
        }

        return response()->json([
            'code' => 200,
            'msg' => '已注册账号信息',
            'data' => ['users' => $safeUsers]
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}