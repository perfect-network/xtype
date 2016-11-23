<?php
namespace app\admin\controller;
use app\index\model\User;
use app\index\model\Admin;
use app\index\model\Config;
use think\Controller;
use think\Session;
use think\Cookie;

class Connect extends Controller
{
	public function login() {
		return $this->view->fetch('login');
	}

	public function deal($type = null) {
		if (empty($type)) {
			return $this->error('处理类型错误');
		}

		switch ($type) {
			case 'login':
				$this->loginTapped();
				break;
			case 'logout':
				$this->logoutTapped();
				break;
			default:
				return $this->error('没有这个处理类型');
				break;
		}
	}

	private function loginTapped() {
		$msg = [
		    'u.require' => '管理员不能为空',
		    'p.require'     => '密码不能为空',
		];

		$validate = new \think\Validate([
		    'u'  => 'require',
		    'p'  => 'require',
		] , $msg);

		if (!$validate->check($this->request->param())) {
			return $this->error($validate->getError());
		}

		$tmp = 	Admin::get(['username' => $this->request->param('u')]);

		if (empty($tmp)) {
			return $this->error('管理员不存在哦');
		}

		if ($tmp->password != md5($this->request->param('p')) ) {
			return $this->error('密码错了哦');
		}

		if ($this->request->param('r') == "on") {
			$v_time = time();
			Cookie::set('admin_username',$tmp->username,2678400);
        	Cookie::set('admin_passwd',$tmp->password,2678400);
        	Cookie::set('admin_time',$v_time,2678400);
        	Cookie::set('admin_code',md5($tmp->username . $tmp->password . $v_time . 'xtype'),2678400);
		}

		Session::set('admin_id',$tmp->id);
		
		return $this->success('登录成功',url('admin/index/index'));
	}

	private function logoutTapped() {
		Session::set('admin_id','');
		Session::delete('admin_id');
		Cookie::set('admin_username','');
        Cookie::set('admin_passwd','');
        Cookie::delete('admin_username');
        Cookie::delete('admin_passwd');
		return $this->success('退出成功',url('admin/connect/login'));
	}

}