<?php
/**
 * 插件接口
 *
 * @author hzjsq@msn.com
 * @version 0.1b
 */

interface omeauto_auto_plugin_interface {

    /**
     * 执行入口
     *
     * @param Array $group 要处理的订单组
     * @return Array
     */
    public function process(&$group, &$confirmRoles);

    /**
     * 获取提示信息
     *
     * @param Array $order 订单内容
     * @return Array
     */
    public function getAlertMsg(& $order);
    
    /**
     * 是否可强制替换检查结果，用于批量审单
     * 
     * @param void
     * @return boolean
     */
    public function canReplaceRole();
    
    /**
     * 设置用于强制替换的结果数据
     * 
     * @param Mixed $results 
     * @return void
     */
    public function setResult($results = null);
    
    /**
     * 获取用于快速审核的选项页，输出HTML代码
     * 
     * @param void
     * @return String
     */
    public function getInputUI();
}