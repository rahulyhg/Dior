<?php
/**
 * 导出数据存储的接口定义
 *
 * @author kamisama.xia@gmail.com
 * @version 0.1
 */
interface taskmgr_cache_interface {

    /**
     * 查询存储的数据内容 
     * 
     * @param string $key 唯一键
     * @param string $content 存储的数据内容
     * @return void
     */
    public function fetch($key, &$content);

    /**
     * 保存存储的数据内容 
     * 
     * @param string $key 唯一键
     * @param string $content 存储的数据内容
     * @param int $ttl 过期时间
     * @return void
     */
    public function store($key, $content, $ttl);

    /**
     * 追加存储数据
     * 
     * @param string $key 唯一键
     * @param string $content 存储的数据内容
     * @param int $ttl 过期时间
     * @return void
     */
    /*
    public function append($key, $content, $ttl);
    */

    /**
     * 删除存储数据
     * 
     * @param string $key 唯一键
     * @return void
     */
    public function delete($key);

    /**
     * 计数器
     * 
     * @param string $key 唯一键
     * @return void
     */
    public function increment($key);

}