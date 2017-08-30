<?php
/**
 * 使用数据库来生成自增GUID
 */

class ome_concurrent_guid {
	
    /**
     * 单例对像
     */
    static public $_instance = null;

    /**
     * 获取单例对像
     *
     * @param void
     * @return ome_concurrent_guid
     */
    static public function instance() {

        if (!is_object(self::$_instance)) {

            self::$_instance = new ome_concurrent_guid();
        }

        return self::$_instance;
    }

	/**
	 * 获取自增ID
     *
     * @param $type
     * @param $prefix
     * @param $length
     * @param $fix
     * @return Integer
	 */
	public function increment($type, $prefix ,$length, $fixed) {

        if ($fixed) {

            $fix = $prefix . $this->getUUIDFix();
            $sql = "SELECT id,`current_time` FROM sdb_ome_concurrent WHERE `current_time`<=".time()." and type='$type' and id like '{$fix}%' order by id desc limit 0,1";
        } else {

            $sql = "SELECT id,`current_time` FROM sdb_ome_concurrent WHERE `current_time`<=".time()." and type='$type' order by id desc limit 0,1";
        }

        $ret = kernel::database()->select($sql);
        //默认值设定
        $num = 1;

        if (is_array($ret)) {
            $ret = $ret[0];
            //检查是否当天的ID
            if (date('y-m-d', $ret['current_time']) == date('y-m-d', time())) {
                //是今天的
                $num = substr($ret['id'], 0 - $length);
                $num = intval($num)+1;
            }
        }

        return $num;
	} 

	/**
	 * 保存并校验指定类型的GUID是否可用
	 *
	 * @return Boolean
	 */
	public function _vaildGUID($type, $guid) {

        if(kernel::database()->exec('INSERT INTO sdb_ome_concurrent(`id`,`type`,`current_time`)VALUES("'.$guid.'","'.$type.'","'.time().'")')){
           
            return true;
        }else{
           
            return false;
        }
	}

    /**
     * 返回本插件的UID识别字段值
     *
     * @return String
     */
    public function getUUIDFix() {

        return '0';
    }
}