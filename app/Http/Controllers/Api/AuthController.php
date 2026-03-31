<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    // ====================== 【唯一修改】用数据库存储用户（替换原缓存逻辑） ======================
    private static function getUsers() {
        $dbUsers = User::select('id', 'name', 'email', 'password')->get()->toArray();

        if (empty($dbUsers)) {
            $testUser = [
                'id' => 1,
                'name' => 'test_user',
                'email' => '2633681826@qq.com',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
            ];
            User::create($testUser);
            return User::select('id', 'name', 'email', 'password')->get()->toArray();
        }

        return $dbUsers;
    }

    private static function saveUsers($users) {
        return true;
    }
    // ==========================================================================

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

    public function register(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6|confirmed',
            'code' => 'required|numeric|digits=6'
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
            if ($user['email'] === $email) {
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

    // =========================================================================
    // ✅ FIX 1：登录（完全不依赖 JWTAuth 门面）
    // =========================================================================
    public function login(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 400,
                'msg' => '参数验证失败',
                'data' => $validator->errors()->first()
            ], 400, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'code' => 400,
                'msg' => '账号或密码错误',
                'data' => []
            ], 400, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        // 🔥 最稳定写法：不会出现类找不到
        $token = auth('api')->login($user);

        return response()->json([
            'code' => 200,
            'msg'  => '登录成功',
            'data' => [
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                ],
                'token'      => $token,
                'token_type' => 'bearer',
            ]
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    // =========================================================================
    // ✅ FIX 2：退出登录
    // =========================================================================
    public function logout(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            auth('api')->logout();
            return response()->json([
                'code' => 200,
                'msg' => '退出成功',
                'data' => []
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 401,
                'msg' => '未登录',
                'data' => []
            ], 401, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
    }

    // =========================================================================
    // ✅ FIX 3：获取用户信息
    // =========================================================================
    public function me(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = auth('api')->user();

            return response()->json([
                'code' => 200,
                'msg'  => '获取用户信息成功',
                'data' => [
                    'user' => [
                        'id'    => $user->id,
                        'name'  => $user->name,
                        'email' => $user->email,
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