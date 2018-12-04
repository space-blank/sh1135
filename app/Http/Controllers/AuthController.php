<?php

namespace App\Http\Controllers;

use App\Lib\Wxxcx\WXBizDataCrypt;
use App\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Transformers\UserTransformer;

class AuthController extends Controller
{
    /**
     * 微信API地址
     * @var string
     */
    protected $weChatLoginUrl = "https://api.weixin.qq.com/sns/jscode2session";

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct(){
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    /**
     * Get a JWT token via given credentials.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request){
        // 验证规则，由于业务需求，这里我更改了一下登录的用户名，使用手机号码登录
        $rules = [
            'email'   => 'required|email',
            'password' => 'required|string|min:6|max:20'
        ];

        // 验证参数，如果验证失败，则会抛出 ValidationException 的异常
        $this->validate($request, $rules);

        $credentials = request(['email', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * 微信小程序登陆
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function weChatLogin(Request $request){
        $rules = [
            'code' => 'required',
            'iv'   => 'required',
            'encrypt_data' => 'required'
        ];
        $this->validate($request, $rules);

        $code = $request->code;
        $iv = $request->iv;
        $encryptData = $request->encrypt_data;


        $appId = env('WE_CHAT_APP_ID');
        $secret = env('WE_CHAT_SECRET');
        $url = $this->weChatLoginUrl.'?appid='.$appId.'&secret='.$secret.'&js_code='.$code.'&grant_type=authorization_code';
        $http = new Client();
        $response = $http->get($url);
        $wxInfo = json_decode((string) $response->getBody(), true);

        if (!is_array($wxInfo) || !array_key_exists('openid', $wxInfo) || !array_key_exists('session_key', $wxInfo)){
            return $this->fail(20001);
        }
        $WxXcx = new WXBizDataCrypt($appId, $wxInfo['session_key']);
        $errCode = $WxXcx->decryptData($encryptData, $iv, $data);

        if($errCode != 0){
            return $this->fail(20002);
        }

        $info = json_decode($data,true);
        //TODO 插入用户数据
//        $user = User::firstOrCreate([], []);
//        $return['user'] = $user;

//        if($user->wasRecentlyCreated){
        if(1){
            $return['access_token'] = auth()->login($errCode);;
            $return['token_type'] = 'bearer';
            $return['user'] = $errCode;
//            $return['expires_in'] = auth()->factory()->getTTL() * 60;
        }
        return $this->success($return);
    }

    /**
     * 处理用户登出逻辑
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(){
        auth()->logout();
        return $this->success();
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(){
        return $this->success([
            'access_token' => auth()->refresh(),
            'token_type' => 'bearer'
        ]);
    }
}