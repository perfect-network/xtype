<?php
namespace app\index\model;

use think\Model;

class Ticket extends Model {
    protected $pk = 'id';

    public function package() {
        return $this->hasOne('Package','id','package_id');
    }

    public function getUseTimeAttr($value) {
        return date('Y-m-d H:i',$value);
    }
}