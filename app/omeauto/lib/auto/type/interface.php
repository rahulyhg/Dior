<?php
/**
 * 来源规则类型接口
 * 
 * @author hzjsq
 * @version 0.1
 */

interface omeauto_auto_type_interface {
    
    /**
     * 获取输入UI
     * 
     * @param mixed $val
     * @return String
     */
    public function getUI($val);
    
    /**
     * 获取用于输出的模板名
     * 
     * @param void
     * @return String
     */
    public function getTemplateName();
    
    /**
     * 检查输入的参数
     * 
     * @param Array $params
     * @returm mixed
     */
    public function checkParams($params);
    
    /**
     * 生成规则字串
     * 
     * @param Array $params
     * @return String
     */
    public function roleToString($params);
    
    /**
     * 检查订单数据是否符合要求
     * 
     * @param omeauto_auto_group_item $item
     * @return boolean
     */
    public function vaild($item);
}