<?php
namespace app\index\model;
use app\index\model\Area;
use app\index\model\Acting;
use think\Model;

class User extends Model {
    protected $pk = 'id';

    public function getStarttimeTextAttr() {
        return date('Y-m-d',$this->start_time);
    }

    public function getLastLoginTimeTextAttr() {
        return date('Y-m-d H:i:s',$this->last_login_time);
    }

    public function getEndtimeTextAttr() {
        return $this->end_time <= time() ? '请充值' : date('Y-m-d H:i',$this->end_time);
    }

    public function getTimeLimitAttr() {
        $limit = $this->end_time - time();

        if ($limit <= 0) return -1;
        if ($limit <= 259200)  return 0;

        return 1;
    }

    public function getPowerTextAttr() {
        if ($this->power == 0) return '普通客户';
        if ($this->power == 1) return '代理客户';
    }

    public function getBytesReceivedDealAttr() {
        $value = $this->bytes_received;
    	if ($value < 1073741824) {
            return round( $value / 1048576.0 , 2 ) . 'MB';
        }

        return round($value / 1073741824.0 ,2) . 'GB';
    }

	public function getBytesSentDealAttr() {
        $value = $this->bytes_sent;
        if ($value < 1073741824) {
            return round( $value / 1048576.0 , 2 ) . 'MB';
        }

        return round($value / 1073741824.0 ,2) . 'GB';
    }

    public function getSurplusAttr() {
    	$tmp = ($this->quota_bytes - $this->bytes_sent - $this->bytes_received);

        if ($tmp < 0) {
            return '请充值';
        }

    	if ($tmp < 1073741824) {
            return round( $tmp / 1048576.0 , 2 ) . 'MB';
        }

        return round($tmp / 1073741824.0 ,2) . 'GB';
    }

    public function getProgressAttr() {
        if (empty($this->quota_bytes)) {
            return 0;
        }
    	$surplus = $this->quota_bytes - $this->bytes_sent - $this->bytes_received;

    	return round($surplus/$this->quota_bytes, 2 ) * 100;
    }

    public function getActiveTextAttr() {
        return ($this->active == 0) ? '未激活' : '已激活';
    }

    public function getUserUrlTextAttr() {
        return url('admin/user/info', ['id' => $this->id]);
    }

    public function getAreaIdTextAttr() {
        $tmp = Area::get($this->area_id);
        if (empty($tmp)) return '未选择';
        return $tmp->title;
    }

    public function getActingIdTextAttr() {
        $tmp = Acting::get($this->acting_id);
        if (empty($tmp)) return '未选择';
        return $tmp->title;
    }

    public function area() {
        return $this->hasOne('Area','id','area_id');
    }

    public function acting() {
        return $this->hasOne('Acting','id','acting_id');
    }
}