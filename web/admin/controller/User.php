<?php
namespace app\admin\controller;

use app\index\model\User as MUser;
use app\index\model\Admin;
use app\index\model\Config;
use app\index\model\Package;
use app\index\model\Log;
use app\index\model\Acting;
use app\index\model\Ticket;
use think\Session;
use think\Cookie;
use think\Controller;

class User extends Common
{
	
	public function user() {

    	return $this->view->fetch('userList');
  	}

	// 显示在线用户列表
	public function online() {
	    $data = Log::all(['status' => 1]);

		return $this->view->fetch('online' , ['data' => $data ]);
	}

	// 连接详情
	public function status() {
		if (empty($this->request->param('id'))) {
			$log = Log::get(['username' => $this->request->param('username') , 'status' => 1]);
		} else {
			$log = Log::get(['id' => $this->request->param('id')]);
		}

		return $this->view->fetch('status' , ['log' => $log]);
	}

 	// 获取用户数据
	public function getUserData( $page = 1 , $rows = 10 , $desc = 1 , $where = '' ,$order = 'id') {
	    $f = array('id','start_time','bytes_sent','bytes_received', 'quota_bytes' ,'active' , 'money' , 'end_time' , 'last_login_time');

	    if ( !in_array($order,$f) ) {
	      return $this->error("获取失败");
	    }

	    if ( !empty($where) ){
	      $where = "%$where%";
	    }else{
	      $where = "%";
	    }

	    $desc = ($desc == 1)?'desc':'asc';

	    $data = MUser::where('email|username|qq','like',$where)->page($page,$rows)->order($order,$desc)->select();

	    $tmp = [];
	    foreach ($data as $key => $value) {
	      $tmp[] = $value->append(['bytes_received_deal','bytes_sent_deal','surplus','progress','active_text','user_url_text','end_time_text']);
	    }

	    return $this->success("获取成功", null , $tmp );
	}

	// 显示用户详情
	public function info() {

	    if ( !empty($this->request->param('username')) ) {
	      $data = MUser::get(['username' => $this->request->param('username')]);
	    } else if ( !empty($this->request->param('id')) ) {
	      $data = MUser::get(['id' => $this->request->param('id')]);
	    } else {
	      return $this->error('不存在');
	    }

	    $status = '未知';
        $log = Log::get(['username' => $data->username , 'status' => 1]);

        if (empty($log)) {
            $status = '离线';
        } else {
            $status = '在线';
        }

	    return $this->view->fetch('info',['status' => $status , 'data' => $data->append(['bytes_received_deal','bytes_sent_deal','surplus','progress','time_limit','starttime_text', 'last_login_time_text' ,'end_time_text','area_id_text','acting_id_text']) ]);
	}

	public function sendEmail() {
		return $this->view->fetch('sendEmail');
	}

	public function sendEmailOne($id = null) {
		$user = MUser::get($id);
		if (empty($user)) {
			return $this->error('用户不存在');
		}

		return $this->view->fetch('sendEmailOne',['data' => $user]);
	}

	public function sendEmailToAll($title = '' ,$c = '') {

		if (empty($title) || empty($c)) {
			return $this->error('内容或标题不能为空');
		}

		$allUser = MUser::all(['active' => 1]);

		foreach ($allUser as $key => $value) {
			sendEmail($value->email,$title,$c);
		}

		return $this->success('发送完成');
	}

	public function sendEmailById($id = null ,$title = '' ,$c = '') {
		$user = MUser::get($id);
		if (empty($user)) {
			return $this->error('用户不存在');
		}

		if ( empty($user->email) ) {
			return $this->error('此用户邮箱未填');
		}

		if (sendEmail($user->email,$title,$c)) {
			return $this->success('发送成功');
		}

		return $this->error('发送失败');
	}

	public function updateEnabled($id = null , $enabled = 0) {
		$user = MUser::get($id);
		if (empty($user)) {
			return $this->error('用户不存在');
		}

		$user->enabled = $enabled;
		$user->save();

		return $this->success('修改成功');
	}


	public function updatePower($id = null , $power = 0) {
		$user = MUser::get($id);
		if (empty($user)) {
			return $this->error('用户不存在');
		}

		$user->power = $power;
		$user->save();

		return $this->success('修改成功');
	}
}