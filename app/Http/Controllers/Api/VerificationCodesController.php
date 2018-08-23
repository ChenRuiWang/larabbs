<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Cache;
use function GuzzleHttp\Psr7\str;
use Illuminate\Http\Request;
use Overtrue\EasySms\EasySms;
use App\Http\Requests\Api\VerificationCodeRequest;

class VerificationCodesController extends Controller
{
    public function store(VerificationCodeRequest $request, EasySms $easySms)
    {
        $captchaData = Cache::get($request->captcha_key);

        if(!$captchaData) {
            return $this->response->error('图片验证码已失效', 442);
        }

        if(!hash_equals($captchaData['code'], $request->captcha_code)) {
            // 验证错误就清除缓存
            Cache::forget($request->captcha_key);
            return $this->response->errorUnauthorized('验证码错误');
        }

        $phone = $captchaData['phone'];
        if(!app()->environment('production')) {
            $code = '1234';
        }else {
            // 生成四位随机验证码
            $code = str_pad(random_int(1, 999), 4, 0, STR_PAD_LEFT);

            try {
                $result = $easySms->send($phone, [
                    'content'  => '您的验证码为: '.$code,
                    'template' => 'SMS_139420027',
                    'data' => [
                        'code' => $code
                    ],
                ]);
            }catch (\Overtrue\EasySms\Exceptions\NoGatewayAvailableException $exception) {
                $message = $exception->getException('aliyun')->getMessage();
                return $this->response->errorInternal($message ?? '短信发送异常');
            }
        }
        // https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxa0bb5f321f4625bf&redirect_uri=http://larabbs.test&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect
        // https://api.weixin.qq.com/sns/oauth2/access_token?appid=wxa0bb5f321f4625bf&secret=a881f6191a14209b90c0dbe48dd41838&code=071FjvLI1Btsc60LAEMI1XGBLI1FjvL0&grant_type=authorization_code
        // https://api.weixin.qq.com/sns/userinfo?access_token=12_lQPnOm6vZ_ZknxRVPlyihzMK3E-6n62Y7yGjIXxNP2mCdbs4fiEF9tiY1Qp6xv50ZbO4EE30zO9mkVn56PmvCg&openid=oSfmy0_7vLm1CmCJj_MGsbR_vUuk&lang=zh_CN
$accessToken = '13_1WV0TrygJSt12UyAmGg4KNdboiW6H5DOSRCngdoh7fXh5G3LW4lBNWeEzZHwwAwnkV3Z82pL-XdZeKZpFdcBrg';
$openID = 'oSfmy0_7vLm1CmCJj_MGsbR_vUuk';
$driver = Socialite::driver('weixin');
$driver->setOpenId($openID);
$oauthUser = $driver->userFromToken($accessToken);
        061cEz6p1fGB0r0xT18p1F1v6p1cEz6W
        $key = 'verificationCode_'.str_random(15);
        $expiredAt = now()->addMinutes(10);
        // 缓存验证码 10分钟过期
        Cache::put($key, ['phone'=>$phone, 'code' => $code], $expiredAt);
        Cache::forget($request->captcha_key);

        return $this->response->array([
            'key' => $key,
            'expired_at' => $expiredAt->toDateTimeString(),
        ])->setStatusCode(201);
    }
}
