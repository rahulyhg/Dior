<?php
/**
 * 导出数据存储基类
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */

abstract class taskmgr_cache_abstract {

    //saas定义的是用户域名
    private function kvprefix() {
        return (defined('KV_PREFIX')) ? KV_PREFIX : 'default';
    }

    public function create_key($key) 
    {
        return md5($this->kvprefix() . $key);
    }
}