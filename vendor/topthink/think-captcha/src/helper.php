<?php










Route::get('captcha/[:id]', "\\think\\captcha\\CaptchaController@index");

Validate::extend('captcha', function ($value, $id = '') {
    return captcha_check($value, $id);
});

Validate::setTypeMsg('captcha', ':attribute错误!');


function captcha($id = '', $config = [])
{
    $captcha = new \think\captcha\Captcha($config);
    return $captcha->entry($id);
}


function captcha_src($id = '')
{
    return Url::build('/captcha' . ($id ? "/{$id}" : ''));
}


function captcha_img($id = '')
{
    return '<img src="' . captcha_src($id) . '" alt="captcha" />';
}


function captcha_check($value, $id = '')
{
    $captcha = new \think\captcha\Captcha((array) Config::pull('captcha'));
    return $captcha->check($value, $id);
}
