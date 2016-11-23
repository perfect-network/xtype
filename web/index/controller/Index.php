<?php
namespace app\index\controller;

use app\index\model\User;
use app\index\model\Config;
use app\index\model\Ticket;
use app\index\model\Log;
use app\index\model\Past;
use app\index\model\Acting;
use app\index\model\Category;
use app\index\model\Package;
use app\index\model\Area;
use think\Session;
use think\Cookie;
use think\Controller;

class Index extends Controller
{
    private $id = null;

    protected $beforeActionList = [
        'access' =>  ['except'=>'help'],
    ];

    protected function access() {
        $this->id = Session::get('id');
        if (empty($this->id)) {
            $passwd = Cookie::get('passwd');
            $username = Cookie::get('username');
            $time = Cookie::get('time');
            if (empty($passwd) || empty($time)) {
                return $this->success('登录开启免流时代',url('index/connect/login'));
            }

            $code = md5($username . $passwd . $time . 'xtype');
            if ($code != Cookie::get('code')) {
                return $this->error('身份已失效',url('index/connect/login'));
            }

            $tmp = User::get(['username' => $username , 'password' => $passwd]);
            if (empty($tmp)) {
                return $this->error('身份已失效',url('index/connect/login'));
            }
            $this->id = $tmp->id;
            Session::set('id',$tmp->id);
        }   
    }

    // 修改密码界面
    public function upPass() {
        return $this->view->fetch('pass');
    }

    // 绑定邮箱界面
    public function email() {
        $data = User::get($this->id);

        return $this->view->fetch('email' , ['data' => $data]);
    }

    // 关于页面
    public function about() {
        $config = Config::get(1);
        return $this->view->fetch('about',['config' => $config]);
    }

    // 地区
    public function area() {
        $user = User::get($this->id);
        $area = Area::order('title')->select();
        $acting = Acting::order('title')->select();

        return $this->view->fetch('area',['user' => $user , 'area' => $area , 'acting' => $acting]);
    }

    public function areaOrther($page = 1 , $rows = 10 ) {
        $data = User::order('area_id','acting_id')->where('area_id','<>',0)->where('acting_id','<>',0)->page($page,$rows)->select();

        return $this->view->fetch('area_orther',[ 'data' => $data ,'page' => $page ]);
    }

    // 主界面
    public function index() {

    	$data = User::get($this->id);
        $config = Config::get(1);

        return $this->view->fetch('index',[
            'config' => $config ,
            'data' => $data->append(['bytes_received_deal','bytes_sent_deal','surplus','time_limit','starttime_text','end_time_text','area_id_text','power_text']) ,
        ]);
    }

    // 线路页面
    public function actingList() {
        $data = Category::order('sortby')->select();

        return $this->view->fetch('acting',[ 'data' => $data ]);
    }

    public function past() {
        $week = Past::whereTime('date', 'week')->where('user_id',$this->id)->count();
        $today = Past::whereTime('date', 'today')->where('user_id',$this->id)->count();
        $enabled = 1;
        $msg = '立即签到';

        if ($week >= 4 ) {
            $enabled = 0;
            $msg = '本周签到已上限';
        }else if ($today >= 1) {
            $enabled = 0;
            $msg = '今日已签到';
        }

        return $this->view->fetch('past',['enabled' => $enabled , 'msg' => $msg]);
    }

    public function pastTapped() {
        $week = Past::whereTime('date', 'week')->where('user_id',$this->id)->count();
        $today = Past::whereTime('date', 'today')->where('user_id',$this->id)->count();

        if ($week >= 4 || $today >= 1) {
            return $this->error('签到失败');
        }

        $tmp = new Past();
        $tmp->date = time();
        $tmp->data = rand(15728640,23068672);
        $tmp->user_id = $this->id;
        $tmp->save();

        $user = User::get($this->id);
        $user->quota_bytes += $tmp->data;

        $user->save();

        return $this->success('签到成功');
    }

    // 支付页面
    public function recharge() {
        $config = Config::get(1);
        $package = Package::order('sort')->where('enabled',1)->select();

        return $this->view->fetch('recharge',['config' => $config , 'package' => $package]);
    }

    // 帮助界面
    public function help() {
        $config = Config::get(1);
        return $this->view->fetch('help',['config' => $config]);
    }

    // 充值成功界面
    public function pay() {
        return $this->view->fetch('pay');
    }

    public function download() {
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);

        $is_pc = (strpos($agent, 'windows nt')) ? true : false;   
        $is_iphone = (strpos($agent, 'iphone')) ? true : false;   
        $is_ipad = (strpos($agent, 'ipad')) ? true : false;   
        $is_android = (strpos($agent, 'android')) ? true : false;

        if($is_pc){
            return $this->error('尚不支持PC');
        }
        if($is_iphone){
            return $this->view->fetch('ios');
        }   
        if($is_ipad){
            return $this->view->fetch('ios');
        }
        if($is_android){
            return $this->view->fetch('android');
        }
    }

    // 充值触发，靠这个吃饭
    public function verify() {
        
        $validate = new \think\Validate([
            'card|卡密'  => 'require',
        ]);

        if (!$validate->check($this->request->param())) {
            return $this->error($validate->getError());
        }

        $user = User::get($this->id);

        if ($user->active == 0) {
            return $this->error('请您激活你的用户名哦');
        }

        $tmp = Ticket::get(['card' => $this->request->param('card')]);

        if (empty($tmp)) {
            return $this->error('卡密不存在，请检查一下哦');
        }

        if ($tmp->is_use == 1) {
            return $this->error('卡密已被'.$tmp->user.'使用');
        }

        $package = Package::get($tmp->package_id);
        if (empty($package)) {
            return $this->error('充值错误，请联系管理员');
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

		return $this->success('正在转跳到结果界面',url('index/index/pay'));
    }

    public function status() {
        $user = User::get($this->id);
        $log = Log::get(['username' => $user->username , 'status' => 1]);

        return $this->view->fetch('status' , ['log' => $log]);
    }

    // 修改密码触发
    public function upPassTapped() {
        $msg = [
            'p.require'     => '密码不能为空',
        ];

        $validate = new \think\Validate([
            'p'  => 'require',
        ] , $msg);

        if (!$validate->check($this->request->param())) {
            return $this->error($validate->getError());
        }

        $tmp = User::get($this->id);

        $tmp->password = md5($this->request->param('p'));
        $tmp->save();

        return $this->success('修改成功了',url('index/index/index'));
    }

    // 修改地区
    public function upAreaTapped($area_id = 0 , $acting_id = 0) {
        $user = User::get($this->id);

        $user->area_id = $area_id;
        $user->acting_id = $acting_id;
        $user->save();

        return $this->success('修改成功，感谢支持');
    }

    public function proxy() {
        $user = User::get($this->id);

        return $this->view->fetch('proxy',['user' => $user]);
    }

    public function qq() {
        $data = User::get($this->id);

        return $this->view->fetch('qq' , ['data' => $data]);
    }

    public function rechargeById() {
        $user = User::get($this->id);

        $package = Package::get($this->request->param('id'));

        if (empty($package)) {
            return $this->error('不存在此商品。');
        }

        if ($package->enabled == 0) {
            return $this->error('该商品不可用。');
        }

        if ($package->money > 0) {
            return $this->error('你不可以使用异币购买此商品。');
        }

        if ($user->power == 0 && $user->money - $package->price < 0) {
            return $this->error('异币余额不足！');
        }else if ($user->power == 1 && $user->money - $package->vip < 0) {
            return $this->error('异币余额不足！');
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

        return $this->success('恭喜您，充值成功！',url('index/index/index'));
    }
}
