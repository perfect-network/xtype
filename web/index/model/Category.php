<?php
namespace app\index\model;

use think\Model;

class Category extends Model {
    protected $pk = 'id';

    public function acting() {
        return $this->hasMany('Acting');
    }
}