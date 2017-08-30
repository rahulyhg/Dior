<?php
/**
 * 用来解决大数据量KV存储的时候，不存储到数据库
 * @author ome
 * @copyright 2011.2.11 shopex.cn
 */
class ome_kvstore{
    
    /**
     * @var array $_instance
     * @access static private
     */
    static private $_instance = array();
    
    /**
     * @var string $_prefix
     * @access private
     */
    private $_prefix = null;
    
    /**
     * @var array $_kvstorage
     * @access private
     */
    private $_kvstorage = array();
    
    /**
     * 构造
     * @param string $prefix
     * @access public
     * @return void
     */
    public function __construct($prefix){
        $this->set_kvstorage();
        $this->set_prefix($prefix);
    }
    
    /**
     * 获取当前KVSTORAGE类型
     * @access void
     */
    public function set_kvstorage(){
        $kvstorage = array(
            'base_kvstore_memcache',
            'base_kvstore_tokyotyrant',
            'base_kvstore_filesystem'
        );
        if (defined(KVSTORE_STORAGE) && in_array(constant(KVSTORE_STORAGE),$kvstorage)){
            $this->_kvstorage = KVSTORE_STORAGE;
        }else{
            $this->_kvstorage = 'base_kvstore_filesystem';
        }
    }
    
    /**
     * 设置KV前缀
     * @param string $prefix
     * @access public
     * @return void
     */
    public function set_prefix($prefix){
        $this->_prefix = $prefix;
    }
    
    /**
     * 实例一个kvstore
     * @param string $prefix
     * @access static public
     * @return object
     */
    static public function instance($prefix){
        if(!isset($_instance[$prefix])){
            self::$_instance[$prefix] = new ome_kvstore($prefix);
        }
        return self::$_instance[$prefix];
    }
    
    /**
     * 存储key内容
     * 根据config配置文件的KVSTORE值来选择存储KEY内容的方式
     * @param string $key 名称
     * @param mixed $value 内容
     * @access public 
     * @return void
     */
    public function store($key,$value){
        kernel::single($this->_kvstorage, $this->_prefix)->store($key,$value);
    }
    
    /**
     * 获取key内容
     * 根据config配置文件的KVSTORE值来选择获取KEY的内容
     * @param string $key
     * @param mixed &$value
     * @access public 
     * @return void
     */
    public function fetch($key,&$value){
        kernel::single($this->_kvstorage, $this->_prefix)->fetch($key,$value);
    }
    
    /**
     * 删除key内容
     * 根据config配置文件的KVSTORE值来选择删除KEY的内容
     * @param string $key
     * @access public
     * @return void
     */
    public function delete($key){
        kernel::single($this->_kvstorage, $this->_prefix)->delete($key);
    }
    
    
}