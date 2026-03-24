<?php

namespace App\helpers;


//统一响应助手
class ApiResponse
{
    public static function success($data = [], $message = '操作成功', $code = 200)
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    public static function error($message = '操作失败', $code = 400, $data = [])
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data
        ], $code);
    }
}