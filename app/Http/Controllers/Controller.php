<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @param array $data
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function success($data = [], $message = 'success'){
        return response()->json([
//            'status'  => true,
            'code'    => 200,
//            'message' => config('errorcode.code')[200],
            'message' => $message,
            'data'    => $data,
        ]);
    }

    /**
     * 请求失败返回JSON数据
     *
     * @param $code
     * @param array $data
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function fail($code, $data = [], $message = ''){
        return response()->json([
//            'status'  => false,
            'code'    => $code,
            'message' => $message ?: config('error,code.code')[(int) $code],
            'data'    => $data
        ]);
    }
}
