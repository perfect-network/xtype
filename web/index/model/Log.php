<?php
namespace app\index\model;
use app\index\model\Server;
use think\Model;

class Log extends Model {
    protected $pk = 'id';

    public function getTypeAttr($value) {
    	$tmp = Server::get(['ip' => $this->server_ip]);
    	return empty($tmp->title)?'未知':$tmp->title;
    }
}