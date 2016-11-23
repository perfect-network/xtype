<?php
namespace app\index\controller;

use app\index\model\User;
use app\index\model\Config;
use app\index\model\Acting;
use think\Controller;
use think\Session;
use think\Cookie;

class Connect extends Controller
{
	public function login() {
		$config = Config::get(1);
		return $this->view->fetch('login' , ['config' => $config]);
	}

	public function reg() {
		return $this->view->fetch('reg');
	}

	public function provisions() {
		return $this->view->fetch('provisions');
	}

	public function deal($type = null) {
		if (empty($type)) {
			return $this->error('处理类型错误');
		}

		switch ($type) {
			case 'login':
				$this->loginTapped();
				break;
			case 'reg':
				$this->regTapped();
				break;
			case 'logout':
				$this->logoutTapped();
				break;
			case 'send':
				$this->sendTapped();
				break;
			default:
				return $this->error('没有这个处理类型');
				break;
		}
	}

	private function loginTapped() {

		$config = Config::get(1);
		if(!$config->login) {
			return $this->error('暂时关闭了登录功能');
		}

		$msg = [
		    'u.require' => '用户名不能为空',
		    'p.require'     => '密码不能为空',
		];

		$validate = new \think\Validate([
		    'u'  => 'require',
		    'p'  => 'require',
		] , $msg);

		if (!$validate->check($this->request->param())) {
			return $this->error($validate->getError());
		}

		$tmp = 	User::get(['username' => $this->request->param('u')]);

		if (empty($tmp)) {
			return $this->error('用户名不存在哦');
		}

		if ($tmp->password != md5($this->request->param('p')) ) {
			return $this->error('密码错了哦');
		}

		if ($tmp->enabled == 0) {
			return $this->error('你已被管理员禁用');
		}

		$tmp->last_login_time = time();
		$tmp->save();

		Session::set('id',$tmp->id);
		Cookie::set('login_v2',time(),2678400);
		$v_time = time();
		Cookie::set('username',$tmp->username,2678400);
    	Cookie::set('passwd',$tmp->password,2678400);
    	Cookie::set('time',$v_time,2678400);
    	Cookie::set('code',md5($tmp->username . $tmp->password . $v_time . 'xtype'),2678400);

		return $this->success('登录成功，正在转跳',url('index/index/index'));
	}

	private function regTapped() {

		$config = Config::get(1);
		if(!$config->reg) {
			return $this->error('暂时关闭了注册功能');
		}

		$msg = [
		    'u.require' => '用户名不能为空',
		    'p.require'     => '密码不能为空',
		    'v.require'     => '验证码不能为空',
		    'u.alphaDash'	=> '用户名只能是字母数字下划线组合',
		];

		$validate = new \think\Validate([
		    'u'  => 'require|alphaDash',
		    'p'  => 'require',
		    'v'  => 'require',
		] , $msg);

		if (!$validate->check($this->request->param())) {
			return $this->error($validate->getError());
		}

		$tmp = User::get(['username' => $this->request->param('u') ]);
		if (!empty($tmp)) {
			return $this->error('已存在的用户名哦');
		}
		
		if ( !empty(Cookie::get('login_v2') ) ) {
			return $this->error('请您支持我们的业务，谢谢');
		}

		$tmp = 	new User();
		
		$tmp->username = $this->request->param('u');
		$tmp->password = md5($this->request->param('p'));
		$tmp->start_time = time();
		$tmp->end_time = time() + 86400;
		$tmp->quota_bytes = $config->free * 1073741824;
		$tmp->save();

		return $this->success( '注册成功，可以登录了' ,url('index/connect/login'));
	}

	private function logoutTapped() {
		Session::set('id','');
		Session::delete('id');
		Cookie::set('username','');
        Cookie::set('passwd','');

		return $this->success('退出成功',url('index/connect/login'));
	}

	private function sendTapped() {
		$user = User::get(Session::get('id'));

        // if (empty($user)) {
        //     return $this->error('需要登录');
        // }

        // if ($user->active == 1) {
        // 	return $this->error('您已激活');
        // }

		$validate = new \think\Validate([
		    'e|邮箱'  => 'require|email',
		]);

		if (!$validate->check($this->request->param())) {
			return $this->error($validate->getError());
		}

		$attr = explode('@',$this->request->param('e'));

		$f = array('qq.com','163.com','yeah.net','vip.qq.com','189.com' ,'live.com');

	    if ( !in_array($attr[1],$f) ) {
	      return $this->error("你输入的邮箱不支持");
	    }

		$tmp = User::get(['email' => $this->request->param('e') , 'active' => 1]);
		if (!empty($tmp)) {
			return $this->error('已存在此邮箱');
		}

        $code = md5(time().'16.47'.$user->id);

        $user->code = $code;
        $user->email = $this->request->param('e');
        $user->save();
        
        $url = $this->request->root(true) . url('index/connect/active' , [
        	'e' => $user->email ,
        	'u' => $user->username ,
        	'c' => $user->code ,
        	]);
        $c = "尊敬的客户:<br />您好！我们非常高兴您能来激活账户！！<br />请点击下面链接来激活您的账户: <br /><a href=\"" . $url . "\">" . $url . "</a>";

        if (!sendEmail($user->email,'云免用户，激活账户邮件',$c)) {
        	return $this->error('发送失败，请联系管理员');
        }

        return $this->success('发送成功，请注意查看。');
	}

	public function active() {
		$validate = new \think\Validate([
		    'e|邮箱'  => 'require|email',
		    'u|用户名'  => 'require',
		    'c|激活码'  => 'require',
		]);

		if (!$validate->check($this->request->param())) {
			return $this->error($validate->getError());
		}

		$user = User::get(['username' => $this->request->param('u') , 'code' => $this->request->param('c')]);

		if (empty($user)) {
			return $this->error('激活码错误',url('index/index/index'));
		}

		if ($user->active == 1) {
        	return $this->error('您已经激活了',url('index/index/index'));
        }

        $user->active = 1;
        $user->save();

        Session::set('id',$user->id);

        return $this->success('激活成功，可以使用了哦',url('index/index/index'));
	}

	public function downloadActing($id = null) {

        $tmp = Acting::get($id);
        if (empty($tmp)) {
            return $this->error('不存在此配置文件');
        }

        if (empty($tmp->content)) {
            return $this->error('抱歉，文件配置为空');
        }

        $file = DS .'static' .DS. 'config' .DS. $tmp->category_id .DS. $tmp->title . '.ovpn';

        $root_file = ROOT_PATH . 'public' .  $file;

        if (!file_exists($root_file)) {
        	$myfile = fopen($root_file, "w");
	        fwrite($myfile,$tmp->content);
	        fclose($myfile);
	    } else {
	    	$md5_db = md5($tmp->content);
        	$md5_file = md5(file_get_contents($root_file));

        	if ($md5_db != $md5_file) {
        		$myfile = fopen($root_file, "w");
		        fwrite($myfile,$tmp->content);
		        fclose($myfile);
        	}
	    }

	    $tmp->down_time += 1;
	    $tmp->save();

        $this->redirect($this->request->domain() . $file);
    }
}
