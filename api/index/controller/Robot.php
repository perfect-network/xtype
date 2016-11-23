<?php
namespace app\index\controller;

use app\index\model\User;
use app\index\model\Config;
use app\index\model\Ticket;
use app\index\model\Log;
use app\index\model\Past;
use app\index\model\Acting;
use app\index\model\Admin;
use app\index\model\Category;
use app\index\model\Package;
use app\index\model\Area;
use think\Session;
use think\Cookie;
use think\Controller;

class Robot extends Controller
{
    private $qq = null;
    private $qun = null;

    public function _initialize() {
        $this->qq = $this->request->param('qq');
        $this->qun = $this->request->param('qun');
        $token = Admin::get(['password' => $this->request->param('token')]);
        if (empty($token)) {
            return 'Token 错误';
        }
    }

    public function index() {
        \think\Config::set('default_return_type','html');

    	$action = $this->request->param('c');

    	if (empty($action)) {
    		return '请求错误';
    	}
        $footer = "";
    	switch ($action) {
            case 'Menu':
                return $this->menu() . $footer;
                break;
            case 'Info':
                return $this->info() . $footer;
                break;
            case 'Bindings':
                return $this->bindings() . $footer;
                break;
            case 'Recharge':
                return $this->recharge() . $footer;
                break;
            case 'Past':
                return $this->past() . $footer;
                break;
            case 'Reg':
                return $this->reg() . $footer;
                break;
            case 'Online':
                return $this->online() . $footer;
                break;
            case 'RechargeByCode':
                return $this->rechargeByCode() . $footer;
                break;
            case 'RechargeById':
                return $this->rechargeById() . $footer;
                break;
            case 'GiveById':
                return $this->giveById() . $footer;
                break;
            case 'UpPassword':
                return $this->upPassword() . $footer;
                break;
    		default:
    			break;
    	}
    }

    private function menu() {
        $msg = "请回复指令继续: ";
        $msg .= "\n---------------";
        $msg .= "\n绑定+用户名+密码";
        $msg .= "\n注册";
        $msg .= "\n我的信息";
        $msg .= "\n修改密码+新密码";
        $msg .= "\n商品";
        $msg .= "\n充值+卡密";
        $msg .= "\n充值+商品序号";
        $msg .= "\n充值+商品序号+用户名";
        $msg .= "\n签到";
        $msg .= "\n---------------";
        $msg .= "\n回复 菜单 获取本信息";
        return $msg;
    }

    private function noBindings() {
        return "尊敬的客户，您的QQ还没有绑定异云账户呢！\n---------------\n请与我私聊 [绑定+用户名+密码] 来绑定已有账户;\n或者与我私聊 [注册] 以用您的QQ号注册一个新的账户。";
    }

    private function info() {
        $user = User::get(['qq' => $this->qq]);
        if (empty($user)) {
            return $this->noBindings();
        }

        $power = $user->power == 0 ? '客户' : 'VIP代理';
        $online = Log::get(['username' => $user->username , 'status' => 1]);
        $status = empty($online) ? '离线' : $online->type;

        $data = $user->append(['bytes_received_deal','bytes_sent_deal','surplus','time_limit','starttime_text','end_time_text','area_id_text','power_text']);
    
        $msg = "尊敬的" . $power . $data['username'] . "！";
        $msg .= "\n---------------";
        $msg .= "\n注册日期: " . $data['starttime_text'];
        $msg .= "\n地区: " . $data['area_id_text'];
        $msg .= "\n状态: " . $status;
        $msg .= "\n剩余量限: " . $data['surplus'];
        $msg .= "\n剩余时限: " . $data['endtime_text'];
        $msg .= "\n剩余异币: " . $data['money'] . '元';
        $msg .= "\n---------------";
        $msg .= "\n您可以回复 [商品] 来进行异币或流量充值哦！";

        return $msg;
    }

    private function bindings() {
        $tmp = User::get(['username' => $this->request->param('username')]);

        if (empty($tmp)) {
            return "绑定失败，用户名 " . $this->request->param('username') . " 不存在！请核对！";
        }

        if ($tmp->password == md5($this->request->param('password'))) {
            \think\Db::table('user')->where('qq',$this->qq)->update(['qq' => '']);
            $tmp->qq = $this->qq;
            if ($tmp->active == 0) {
                $tmp->active = 1;
                $tmp->email = $this->qq . "@qq.com";
            }
            $tmp->save();
            return "恭喜您，绑定成功！";
        }

        return "绑定失败，用户名和密码不匹配！"; 
    }

    private function recharge() {
        $data = Package::order('id')->where('enabled',1)->where('money',0)->select();
        if (empty($data)) {
            return "很抱歉，目前没有套餐可以充值！";
        }

        $msg = "目前有以下套餐可以充值: ";
        $msg .= "\n序号   套餐   价格";
        $tmp = User::get(['qq' => $this->qq]);

        if ( !empty($tmp) && $tmp->power == 1) {
            foreach ($data as $key => $value) {
                $msg .= "\n---------------\n" . $value->id . "   " . $value->name . "   " . $value->vip . "元";
            }
        } else {
            foreach ($data as $key => $value) {
                $msg .= "\n---------------\n" . $value->id . "   " . $value->name . "   " . $value->price . "元";
            }
        }

        $msg .= "\n---------------";

        $msg .= "\n请回复 [充值+序号] 来充值；\n或者请与我私聊 [充值+卡密] 来充值。";

        return $msg;
    }

    private function past() {
        $user = User::get(['qq' => $this->qq]);
        if (empty($user)) {
            return $this->noBindings();
        }

        $today = Past::whereTime('date', 'today')->where('user_id',$user->id)->count();
        if ( $today >= 1) {
            return "对不起，您今天已经签过了！再签就要扣了哦！";
        }

        $week = Past::whereTime('date', 'week')->where('user_id',$user->id)->count();
        if ($week >= 4) {
            return "对不起，您本周签到已经上限了！";
        }

        $tmp = new Past();
        $tmp->date = time();
        $tmp->data = rand(10485760,23068672);
        $tmp->user_id = $user->id;
        $tmp->save();

        $user->quota_bytes += $tmp->data;
        $user->save();

        return "尊敬的客户" . $user->username ."，您今天签到成功。\n获得了" . round($tmp->data / 1024 / 1024 , 2) . "MB流量哦。" ;
    }

    private function reg() {

        $config = Config::get(1);
        if(!$config->reg) {
            return '注册失败，暂时关闭了注册功能！';
        }

        if (empty($this->qq)) {
            return '接口调用失败！';
        }

        $tmp = User::get(['username' => $this->qq]);
        
        if (!empty($tmp)) {
            return '注册失败，您的QQ号为用户名的账号已存在！';
        }

        $tmp = User::get(['qq' => $this->qq]);

        if (!empty($tmp)) {
            return '注册失败，您的QQ号已绑定过用户' . $tmp->username . '！';
        }

        $tmp = User::get(['email' => $this->qq . "@qq.com"]);
        if (!empty($tmp)) {
            return '注册失败，您的QQ邮箱已经绑定过用户' . $tmp->username . '！';
        }

        $tmp_password = createRandomStr(6);
        $tmp =  new User();
        $tmp->username = $this->qq;
        $tmp->password = md5($tmp_password);
        $tmp->active = 1;
        $tmp->email = $this->qq . "@qq.com";
        $tmp->qq = $this->qq;
        $tmp->start_time = time();
        $tmp->end_time = time() + 86400;
        $tmp->quota_bytes = $config->free * 1073741824;
        $tmp->save();

        return "注册成功，已绑定此QQ号！\n 回复 [修改密码+新密码] 来修改密码。\n用户名: " . $this->qq . "\n密码: " . $tmp_password;
    }

    private function online() {
        $config = Config::get(1);

        if ($this->qq != $config->qq) {
            return "对不起，你无权查看！";
        }

        $onlineNum = Log::where('status',1)->count();

        return "您好，管理员！当前在线人数: " . $onlineNum;
    }

    private function rechargeByCode() {
        $user = User::get(['qq' => $this->qq]);
        if (empty($user)) {
            return $this->noBindings();
        }

        $validate = new \think\Validate([
            'card|卡密'  => 'require',
        ]);

        if (!$validate->check($this->request->param())) {
            return $validate->getError();
        }

        if ($user->active == 0) {
            return '请您激活你的用户名哦';
        }

        $tmp = Ticket::get(['card' => $this->request->param('card')]);

        if (empty($tmp)) {
            return '卡密不存在，请检查一下哦';
        }

        if ($tmp->is_use == 1) {
            return '卡密已被'.$tmp->user.'使用';
        }

        $package = Package::get($tmp->package_id);
        if (empty($package)) {
            return '充值错误，请联系管理员';
        }

        if ( $user->end_time < time() ) {
            $user->end_time = time() + $package->date;
        } else {
            $user->end_time += $package->date;
        }

        $user->quota_bytes += $package->data;
        $user->money += $package->money;
        $user->save();

        // 更新卡密的信息
        $tmp->is_use = 1;
        $tmp->user = $user->username;
        $tmp->use_time = time();
        $tmp->save();

        return '恭喜您，充值' . $package->name . '成功！请发送 [我的信息] 来查看您的剩余流量。';
    }

    private function rechargeById() {
        $user = User::get(['qq' => $this->qq]);
        if (empty($user)) {
            return $this->noBindings();
        }

        $package = Package::get($this->request->param('id'));

        if (empty($package)) {
            return '不存在此商品，请发送 [充值] 来获取商品列表。';
        }

        if ($package->enabled == 0) {
            return '该商品不可用。';
        }

        if ($package->money > 0) {
            return '你不可以使用异币购买此商品。';
        }

        if ( ($user->power == 0 && $user->money - $package->price < 0) || ($user->power == 1 && $user->money - $package->vip < 0) ){
            return '您的余额' . $user->money . '元不足以购买' . $package->price . '元的商品，请联系管理员充值异币。';
        }

        if ($user->power == 0) {
            $user->money -= $package->price;
        }else {
            $user->money -= $package->vip;
        }

        if ( $user->end_time < time() ) {
            $user->end_time = time() + $package->date;
        } else {
            $user->end_time += $package->date;
        }

        $user->quota_bytes += $package->data;
        $user->money += $package->money;
        $user->save();

        return '充值' . $package->name .'成功！请发送 [我的信息] 来查看您的剩余流量。';
    }


    private function giveById() {
        $user = User::get(['qq' => $this->qq]);
        if (empty($user)) {
            return $this->noBindings();
        }

        $ob = User::get(['username' => $this->request->param('ob')]);

        if (empty($ob)) {
            return '赠送失败，赠送对象不存在！';
        }

        $package = Package::get($this->request->param('id'));

        if (empty($package)) {
            return '不存在此商品，请发送 [充值] 来获取商品列表。';
        }

        if ($package->enabled == 0) {
            return '该商品不可用。';
        }

        if ($package->money > 0) {
            return '你不可以使用异币购买此商品。';
        }

        if ( ($user->power == 0 && $user->money - $package->price < 0) || ($user->power == 1 && $user->money - $package->vip < 0) ){
            return '您的余额' . $user->money . '元不足以购买' . $package->price . '元的商品，请联系管理员充值异币。';
        }

        if ($user->power == 0) {
            $user->money -= $package->price;
        }else {
            $user->money -= $package->vip;
        }
        $user->save();

        if ( $ob->end_time < time() ) {
            $ob->end_time = time() + $package->date;
        } else {
            $ob->end_time += $package->date;
        }

        $ob->quota_bytes += $package->data;
        $ob->money += $package->money;
        $ob->save();

        return '购买' . $package->name .'给' . $ob->username .'成功！';
    }

    private function upPassword() {
        $user = User::get(['qq' => $this->qq]);
        if (empty($user)) {
            return $this->noBindings();
        }

        $tmp_password = $this->request->param('password');

        if (empty($tmp_password)) {
            return '修改密码失败！';
        }

        $user->password = md5($tmp_password);
        $user->save();

        return '密码修改成功！';
    }
}
