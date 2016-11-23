<?php
namespace app\admin\controller;

use app\index\model\Package;
use app\index\model\Ticket;
use app\index\model\Admin;
use think\Session;
use think\Cookie;
use think\Controller;

class Card extends Common
{

	// 显示卡密按照条件
	public function showCardWhere($package = 1 , $is_use = true) {
		$data = Ticket::all(['package_id' => $package , 'is_use' => $is_use ? 1 : 0]);

		$str = '';
		foreach ($data as $key => $value) {
			$str .= $value->card. (empty($value->user)?'':'->'.$value->user) ."\n";
		}

		return $this->view->fetch('showCardWhere',['str' => $str]);
	}

	// 删除卡密按照条件
	public function delCardWhere($package = 1 , $is_use = true) {

		if ($is_use == -1) {
			Ticket::destroy(['package_id' => $package]);
		} else {
			Ticket::destroy(['package_id' => $package ,'is_use' => $is_use]);
		}

		return $this->success('删除成功');
	}

	public function createCardWhere($package = 1 , $num = 1) {
		$package = Package::get(['id' => $package]);
		if (empty($package)) {
			return $this->error('错误');
		}
		$str = '';
		for ($i=0; $i < $num; $i++) { 
			$tmp = new Ticket();
			$tmp->card = createRandomStr(18);
			$str .= $tmp->card."\n";
			$tmp->add_time = time();
			$tmp->package_id = $package->id;
			$tmp->save();
		}

		return $this->view->fetch('showCardWhere',['str' => $str]);
	}

	public function log($page = 1 , $rows = 10 ) {
		$card = Ticket::where('is_use',1)->page($page,$rows)->order('use_time','desc')->select();

		return $this->view->fetch('log',['data' => $card , 'page' => $page]);
	}

	public function packageList() {
		$package = Package::order('sort')->select();

		return $this->view->fetch('packageList' , ['package' => $package]);
	}

	public function manageCard() {
		$package = Package::order('sort')->select();

		return $this->view->fetch('manageCard' , ['package' => $package]);
	}

	public function createCard() {
		$package = Package::order('sort')->select();

		return $this->view->fetch('createCard' , ['package' => $package]);
	}

	public function package($id = null) {
		if (empty($id)) {
			return $this->view->fetch('packageAdd'); 
		}

		$package = Package::get($id);

		if (empty($package)) {
		return $this->error('不存在');
		}

		return $this->view->fetch('package' , ['package' => $package]);
	}

	public function packageSave($id = null) {

		if (empty($id)) {
			$package = new Package();
			$package->save($this->request->param());
		} else {
			$package = Package::get($id);
			$package->save($this->request->param());
		}

		return $this->success('保存成功');
	}

	public function getCardData( $page = 1 , $rows = 10 , $desc = 1 , $where = '' ,$order = 'id') {
	    $f = array('id','is_use');

	    if ( !in_array($order,$f) )
	      return $this->error("获取失败");

	    if ( !empty($where) ){
	      $where = "%$where%";
	    }else{
	      $where = "%";
	    }

	    $desc = ($desc == 1)?'desc':'asc';

	    $data = AuthTicket::where('user','like',$where)->page($page,$rows)->order($order,$desc)->select();

	    return $this->success("获取成功", null , $data );
	}

	public function del($id = null) {
		$package = Package::get($id);
		if (empty($package)) {
			return $this->error('不存在此条记录');
		}

		$count = Ticket::where('package_id',$package->id)->count();

		if ($count != 0 ) {
			return $this->error('目前套餐还有卡密，不能删除');
		}

		$package->delete();

		return $this->success('删除成功');
	}
}