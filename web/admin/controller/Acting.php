<?php
namespace app\admin\controller;

use app\index\model\Acting as MActing;
use app\index\model\Category;
use think\Session;
use think\Cookie;
use think\Controller;

class Acting extends Common
{
	public function index($page = 1 , $rows = 10 ) {
		$data = MActing::order('category_id,title')->page($page,$rows)->select();

        return $this->view->fetch('list',[ 'data' => $data ,'page' => $page ]);
	}

	public function acting($id = 0) {
		$acting = MActing::get($id);
		if (empty($acting)) {
			return $this->error('不存在此条记录');
		}
		$category = Category::all();
		return $this->view->fetch('acting',['data' => $acting , 'category' => $category]);
	}

	public function type() {
		$data = Category::all();

		return $this->view->fetch('typeList',['data' => $data]);
	}

	public function update() {
		$acting = new MActing();

		$acting->allowField(true)->save($this->request->param() , ['id' => $this->request->param('id')]);

		return $this->success('修改成功');
	}

	public function del($id = null) {
		$acting = MActing::get($id);
		if (empty($acting)) {
			return $this->error('不存在此条记录');
		}

		$acting->delete();

		\think\Db::table('user')->where('acting_id', $id)->update(['acting_id' => 0]);

		return $this->success('删除成功');
	}

	public function add() {
		$data = Category::all();
		return $this->view->fetch('actingAdd',['data' => $data]);
	}

	public function addTapped() {
		$acting = new MActing();

		$acting->allowField(true)->save($this->request->param());

		return $this->success('添加成功');
	}
}