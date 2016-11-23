<?php
namespace app\index\controller;

use app\index\model\User;
use app\index\model\Config;
use app\index\model\Ticket;
use app\index\model\Log;
use app\index\model\Link;
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
    // 入口
    public function index() {
    	$c = $this->request->param('c');

    	if (empty($c)) {
    		return $this->error('请求错误');
    	}

    	switch ($c) {
    		case 'Appurl':
    			return $this->link();
    			break;
    		case 'LinesCata':
                return $this->category();
                break;
            case 'Lines':
                return $this->lines();
                break;
            case 'Note':
                return $this->note();
                break;
            case 'Recharge':
                return $this->recharge();
                break;
            case 'Login':
                return $this->login();
                break;
            case 'Reg':
                return $this->reg();
                break;    
    		default:
    			break;
    	}
    }

    // 获取官网等链接
    private function link() {
    	$data = Link::all();

    	return $this->success($data);
    }

    // 获取配置分类
    private function category() {
        $data = Category::all();

        return $this->success($data);
    }

    // 获取线路
    private function lines() {
        $cid = $this->request->param('cid');
        if (empty($cid)) {
            return $this->error('错误');
        }

        $data = Acting::order('title')->where('category_id' , $cid)->select();
        
        return $this->success($data);
    }

    // 获取公告
    private function note() {
        $config = Config::get(1);
        \think\Config::set('default_return_type','html');
        return $config->app_note;
    }

    // 充值
    private function recharge() {
        \think\Config::set('default_return_type','html');

        $validate = new \think\Validate([
            'user|用户名' => 'require',
            'pass|密码'   => 'require',
            'km|卡密'  => 'require',
        ]);

        if (!$validate->check($this->request->param())) {
            return $validate->getError();
        }

        $user = User::get([
            'username' => $this->request->param('user') ,
            'password' => md5($this->request->param('pass')) ,
            ]);

        if ($user->active == 0) {
            return '请您激活你的用户名哦';
        }

        $tmp = Ticket::get(['card' => $this->request->param('km')]);

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
        $user->save();

        $tmp->is_use = 1;
        $tmp->user = $user->username;
        $tmp->use_time = time();
        $tmp->save();

        return '充值成功';
    }

    // 登录和定时更新数据
    private function login() {
        \think\Config::set('default_return_type','html');
        $user = User::get(['username' => $this->request->param('user') , 'password' => md5( $this->request->param('pass') ) ]);

        if (empty($user)) {
            return '密码错误';
        }

        $data = $user->append(['bytes_received_deal','bytes_sent_deal','surplus','quota_bytes_text','starttime_text']);

        $str = "<p>账号:".$data['username'];
        $str = $str . "</p><p>发送:".$data['bytes_sent_deal'];
        $str = $str . "</p><p>接收:".$data['bytes_received_deal'];
        $str = $str . "</p><p>总量:".$data['quota_bytes_text'];
        $str = $str . "</p><p>剩余:". ( ($user->active != 1) ? '未激活' : $data['surplus'] );
        $str = $str . "</p><p>注册时间:". $data['starttime_text'];
        $str = $str . "</p><p>到期时间:". date('Y-m-d H:i',$data['end_time']);
        $str = $str . "</p><p>剩余天数:". round( ($data['end_time']-time()) / 86400  );
        $str = $str . "\n";
        return $str;
    }

    // 注册用户
    private function reg() {
        \think\Config::set('default_return_type','html');
        $config = Config::get(1);
        if(!$config->reg) {
            return '暂时关闭了注册功能';
        }

        $validate = new \think\Validate([
            'user|用户名'  => 'require|alphaDash',
            'pass|密码'  => 'require',
            'verifycode|邮箱地址'  => 'require|email',
        ]);

        if (!$validate->check($this->request->param())) {
            return $validate->getError();
        }

        $tmp = User::get(['username' => $this->request->param('user') ]);
        if (!empty($tmp)) {
            return '已存在的用户名哦';
        }

        $attr = explode('@',$this->request->param('verifycode'));

        $f = array('qq.com','163.com','yeah.net','vip.qq.com','189.com' ,'live.com');

        if ( !in_array($attr[1],$f) ) {
          return '你输入的邮箱不支持';
        }

        $tmp = User::get(['email' => $this->request->param('verifycode') , 'active' => 1]);
        if (!empty($tmp)) {
            return '已存在的邮箱';
        }
        
        if ( !empty(Cookie::get('login_v2') ) ) {
            return '请您支持我们的业务，谢谢';
        }

        $tmp =  new User();
        
        $tmp->username = $this->request->param('user');
        $tmp->password = md5($this->request->param('pass'));
        $tmp->email = $this->request->param('verifycode');
        $tmp->start_time = time();
        $tmp->end_time = time() + 86400;
        $tmp->quota_bytes = $config->free * 1073741824;
        $tmp->save();

        if ($this->sendTapped($tmp->id)) {
            return '好了，请到你的邮箱里面激活使用！';
        }

        return '好了，发送邮件失败，请登录我们的官网激活！';
    }

    // 发送激活邮件
    private function sendTapped($id) {
        $user = User::get($id);

        if (empty($user)) {
            return false;
        }

        if ($user->active == 1) {
            return false;
        }

        $code = md5(time().'16.47'.$user->id);
        
        $user->code = $code;
        $user->save();
        
        $url = 'http://xtype.cn/index/connect/active?e='.$user->email.'&u='.$user->username.'&c='.$user->code;

        $c = "尊敬的客户:<br />你好！我们非常高兴您能来激活账户！！好了，请点击下面链接来激活您的账户: <br /><a href=\"" . $url . "\">" . $url . "</a>";

        if (!sendEmail($user->email,'云免用户，激活账户邮件',$c)) {
            return false;
        }

        return true;
    }
}
