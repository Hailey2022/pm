<?php
namespace app\admin\controller;

use cmf\controller\RestBaseController;
use think\facade\Db;
use think\db\Query;

class WeChatController extends RestBaseController
{
    public function getUid()
    {
        $appId = "wx83e965d02ff3d258";
        $appSecret = "7c3321c41c3ccfe4459a240fe2fae66b";
        $code = $this->request->param('code');
        $response = cmf_curl_get("https://api.weixin.qq.com/sns/jscode2session?appid=$appId&secret=$appSecret&js_code=$code&grant_type=authorization_code");
        $response = json_decode($response, true);
        if (!empty($response['errcode'])) {
            $this->error('登入失败!');
        }
        $openid = $response['openid'];
        $sessionKey = $response['session_key'];
        if ($openid == null || $sessionKey == null) {
            $this->error("登入失败!!");
        }

        $findWechatUser = Db::name("wechat_user")
            ->where('openid', $openid)
            ->find();
        if ($findWechatUser) {
            $uid = $findWechatUser['uid'];
            $username = $findWechatUser['username'];
        } else {
            $uid = hash("sha256", $openid);
            $username = uniqid("新用戶");
            Db::name("wechat_user")->insert([
                'openid' => $openid,
                'uid' => $uid,
                'username' => $username
            ]);
        }
        $this->success("登入成功", ["uid" => $uid, "username" => $username]);
    }

    public function updateUsername()
    {
        $uid = $this->request->param('uid');
        $username = $this->request->param('username');
        Db::name("wechat_user")
            ->where('uid', $uid)
            ->update([
                'username' => $username
            ]);
        $this->success("updated");
    }

    public function uploadPicAndPunch()
    {
        $uid = $this->request->param('uid');
        $locationId = $this->request->param('locationId');
        $img = $this->request->file('image')->getInfo();
        move_uploaded_file($img['tmp_name'], WEB_ROOT . "upload/wechat_pics/" . $img['name']);
        $res = Db::name('wechat_punch_in_record')->insert([
            'img_url' => "wechat_pics/" . $img['name'],
            'time' => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
            'uid' => $uid,
            'locationId' => $locationId
        ]);
        if ($res !== false) {
            $this->success("ok");
        } else {
            $this->error("err");
        }
    }

    public function hasPunched()
    {
        $uid = $this->request->param('uid');
        if ($uid != null) {
            $this->error("uid not found");
        }
        $res = Db::name("wechat_punch_in_record")->where('uid', $uid)->whereTime('time', 'today')->select();
        // foreach ($res as $r){
        //     var_dump($r);
        // }
        // TODO: 区分时间 date('x-x-x xx:xx:xx')
        $this->success('yes', ['status' => false]);
    }

    public function getLocation()
    {
        $longitude = $this->request->param('longitude');
        $latitude = $this->request->param('latitude');
        $locations = Db::name('wechat_punch_in_location')->select();
        foreach ($locations as $location) {
            if (abs($location['longitude'] - $longitude) < 0.005 && abs($location['latitude'] - $latitude) < 0.005) {
                $this->success("ok", ['locId' => $location['id'], 'locName' => $location['location']]);
            }
        }
        $this->error("loc not found");
    }
    public function getLocations()
    {
        $uid = $this->request->param('uid');
        //TODO: 区分用户
        $locations = Db::name('wechat_punch_in_location')->select();
        $this->success("ok", ['locs' => $locations]);
    }
    public function getRecords()
    {
        $uid = $this->request->param('uid');
        $date = $this->request->param('date');
        if ($uid == null) {
            $this->error("not allowed");
        }

        //TODO: 区分用户
        $records = Db::name('wechat_punch_in_record')
            // ->where('uid', $uid)
            ->where(function (Query $query) use ($date, $uid) {
                if ($date != null) {
                    $query->whereBetweenTime('time', $date);
                } else {
                    $query->whereTime('time', 'today');
                }
                if ($uid != "all") {
                    $query->where('uid', $uid);
                }
            })
            // ->whereTime('time', 'today')
            ->select();

        $this->success("ok", ['records' => $records]);
    }
}