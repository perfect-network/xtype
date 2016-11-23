<?php
namespace app\admin\controller;

use app\index\model\Server as MServer;
use think\Session;
use think\Cookie;
use think\Controller;

class Server extends Common
{
	public function index() {
		$data = MServer::all();

		return $this->view->fetch('list',['data' => $data]);
	}

	public function server($id = 0) {
		$server = MServer::get($id);
		if (empty($server)) {
			return $this->error('不存在此服务器');
		}

		return $this->view->fetch('server',['data' => $server]);
	}

	public function update() {
		$server = new MServer();

		$server->allowField(true)->save($this->request->param() , ['id' => $this->request->param('id')]);

		return $this->success('修改成功');
	}

	public function add() {
		return $this->view->fetch();
	}

	public function addTapped() {
		$server = new MServer();

		$server->allowField(true)->save($this->request->param());

		return $this->success('添加成功');
	}

	public function del($id = null) {
		$server = new MServer($id);

		if (empty($server)) {
			return $this->error('服务器不存在');
		}

		$server->delete();

		return $this->success('删除成功');
	}

}