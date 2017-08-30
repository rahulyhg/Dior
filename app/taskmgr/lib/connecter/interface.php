<?php
/**
 * 数据访问插件的接口定义
 *
 * @author hzjsq@foxmail.com
 * @version 0.1b
 */
interface taskmgr_connecter_interface {

    /**
     * 初始化数据访问对像
     *
     * @param string $task 任务标识
     * @return void
     */
    public function load($task,$config);
    
	/**
	 * 连接数据源
	 * 
	 * @param array $cfg 数据源配置信息
	 * @return void
	 */
	public function connect($cfg);

	/**
	 * 关闭链接 
	 * 
	 * @param void
	 * @return void
	 */
	public function disconnect();

	/**
	 * 回调方法名
	 * 
	 * @param callback $fName 回调方法名
	 * @return void
	 */
	public function consume($fName);


	/**
	 * 获取队列长度
	 * 
	 * @param void
	 * @return integer
	 */
	public function length();


	/**
	 * 确认消费完成
	 * 
	 * @param mixed
	 * @return void
	 */
	public function ack($tagId);

	/**
	 * 退回队列
	 * 
	 * @param mixed
	 * @return void
	 */
	public function nack($tagId);
}