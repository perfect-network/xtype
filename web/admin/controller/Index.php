<?php
namespace app\admin\controller;

use app\index\model\User;
use app\index\model\Admin;
use app\index\model\Config;
use app\index\model\Link;
use app\index\model\Package;
use app\index\model\Log;
use app\index\model\Past;
use app\index\model\Server;
use app\index\model\Acting;
use app\index\model\Ticket;
use think\Session;
use think\Cookie;
use think\Controller;

class Index extends Common
{

  // 显示配置页面
  public function webConfig() {
    $config = Config::get(1);
    return $this->view->fetch('webConfig',['config' => $config]);
  }

  // 显示APP配置
  public function appConfig() {
    $config = Link::all();
    return $this->view->fetch('appConfig',['config' => $config]);
  }

  public function log() {
    return $this->view->fetch('log');
  }

  // 显示管理主页
  public function index() {
    $onlineNum = Log::where('status',1)->count();
    $userNum = User::count();
    $configNum = Acting::count();
    $activeNum = User::where('active',1)->count();
    $cardNum = Ticket::count();
    $activeCardNum = Ticket::where('is_use',1)->count();
    $sumData = User::sum('bytes_received') + User::sum('bytes_sent');
    $surplus = \think\Db::table('user')->field('sum(quota_bytes)-sum(bytes_received)-sum(bytes_sent) as surplus')->where('active',1)->where('quota_bytes','>=',1048576000)->select();
    $serverNum = Server::count();
    $packageNum = Package::count();
    $allPast = Past::count();
    $todayPast = Past::whereTime('date', 'today')->count();

    return $this->view->fetch('index' , ['allPast' => $allPast , 'todayPast' => $todayPast , 'serverNum' => $serverNum , 'packageNum' => $packageNum,'onlineNum' => $onlineNum , 'userNum' => $userNum , 'configNum' => $configNum , 'activeNum' => $activeNum , 'cardNum' => $cardNum , 'activeCardNum' => $activeCardNum , 'sumData' => $sumData , 'surplus' => $surplus[0]['surplus']]);
  }

  // 显示在线用户列表
  public function online() {
    $data = $this->getOnlinenUser();
    foreach ($data as $key => $value) {
      $tmp = User::get( [ 'username' => $value[0] ] );
      if (!empty($tmp))
        $data[$key][5] = $tmp->surplus;
    }

    return $this->view->fetch('online' , ['data' => $data ]);
  }

  // 获取日志数据
  public function getLogData( $page = 1 , $rows = 10 , $desc = 1 , $where = '' ,$order = 'id') {
    $f = array('username','id');

    if ( !in_array($order,$f) )
      return $this->error("获取失败");

    if ( !empty($where) ){
      $where = "%$where%";
    }else{
      $where = "%";
    }

    $desc = ($desc == 1)?'desc':'asc';

    $data = Log::where('username','like',$where)->page($page,$rows)->order($order,$desc)->select();

    return $this->success("获取成功", null , $data );
  }

  public function webSetting() {
    $config = new Config();
    $config->allowField(true)->save($this->request->param(), ['id' => 1] );
    
    return $this->success('保存成功');
  }


  public function appSetting() {
    $config = Link::get(1);
    $config->url = $this->request->param('c_url');
    $config->save();

    $config = Link::get(2);
    $config->url = $this->request->param('t_url');
    $config->save();

    $config = Link::get(3);
    $config->url = $this->request->param('p_url');
    $config->save();

    $config = Link::get(4);
    $config->url = $this->request->param('h_url');
    $config->save();
    
    return $this->success('保存成功');
  }
  
}