<?php

 interface omeftp_interface_ftp{
	/**
     * 将本地文件上传到存储服务器
     *
     * @params array $params 参数 array('local'=>'本地文件路径','remote'=>'远程文件路径')
     * @params string $msg 
     * @return bool
     */
     public function push($params, &$msg);

    /**
     * 将存储服务器中的文件下载到本地
     *
     * @params array $params 参数 array('local'=>'本地文件路径','remote'=>'远程文件路径','resume'=>'文件指针位置')
     * @params string $msg 
     * @return bool 
     */
    public function pull( $params, &$msg);
	
	/**
      * 获取传入文件在存储服务器中的大小
      *
      * @params string $filename 文件名称(无路径)
      * @return ini    文件存在则返回文件大小，文件不存在则返回 -1 或者 false
      */
    public function size($filename);

    /**
     * 根据传入文件名称参数删除存储服务器中的文件
     *
      * @params string $filename 文件名称(无路径)
      * @return bool
     */
    public function delete_ftp($filename);

	/**
     * 根据传入文件名称参数删除本地文件
     *
      * @params string $filename 文件名称(无路径)
      * @return bool
     */
    public function delete_loc($filename);



 }