<?php
namespace app\index\model;
use app\index\model\Category;
use think\Model;

class Acting extends Model {
    protected $pk = 'id';

    public function category() {
        return $this->hasOne('Category','id','category_id');
    }
}