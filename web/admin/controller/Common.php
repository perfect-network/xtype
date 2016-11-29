<?php
namespace app\admin\controller;
use app\index\model\Admin;
use think\Session;
use think\Cookie;
use think\Controller;

class Common extends Controller
{
	private $id = null;

  	public function _initialize() {
      	$this->id = Session::get('admin_id');
      	if (empty($this->id)) {
            $passwd = Cookie::get('admin_passwd');
            $username = Cookie::get('admin_username');
            $time = Cookie::get('admin_time');
            if (empty($passwd) || empty($time)) {
                return $this->redirect('admin/connect/login');
            }
            $code = md5($username . $passwd . $time . 'xtype');
            if ($code != Cookie::get('admin_code')) {
              return $this->redirect('admin/connect/login');
            }
            $tmp = Admin::get(['username' => $username , 'password' => $passwd]);
            if (empty($tmp)) {
                return $this->redirect('admin/connect/login');
            }
            $this->id = $tmp->id;
            Session::set('admin_id',$tmp->id);
        }
  	}
}