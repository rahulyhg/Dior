<?php
/**
 * 插件接口类
 *
 * @author hzjsq@msn.com
 * @version 0.1b
 */

abstract class omeauto_auto_plugin_abstract {
    
    /**
     * 用于处理快速审单的数据
     */
    public $fastRoles = null;
    
    /**
     * 获取当前插件的简称
     *
     * @param void
     * @return string
     */
    public function _getPlugName() {

        $className = get_class($this);

        return preg_replace('/.*_([a-zA-Z0-9]+)$/is', '$1', $className);
    }

    /**
     * 获取状态编码
     *
     * @param void
     * @return Integer
     */
    public function getMsgFlag() {

        return  $this->__STATE_CODE;
    }
    
    /**
     * 是否可强制替换检查结果，用于批量审单
     * 
     * @param void
     * @return boolean
     */
    public function canReplaceRole() {
        
        return $this->__SUP_REP_ROLE;
    }
    
    /**
     * 设置用于强制替换的结果数据
     * 
     * @param Mixed $results
     * @return void
     */
    public function setResult($results = null) {
        
        if (!$this->canReplaceRole()) {
            
            return ; 
        }

        if (!empty($results)) {
            
            $this->fastRoles = $this->parseFromRequest();
        } else {
            
            $this->fastRoles = $results;
        }
    }
    
    /**
     * 从 $_REQUEST 数组中获取用于批量审单的数据 
     * 
     * @param void
     * @return void
     */
    private function parseFromRequest() {
        
        if (!$this->canReplaceRole()) {
            
            return ;
        }
    }
    
    /**
     * 获取用于快速审核的选项页，输出HTML代码
     * 
     * @param void
     * @return String
     */
    public function getInputUI() {
            
        return '';
    }
    
    /**
     * 获取备注信息
     * 
     * @param String $content 备注信息
     * @return String
     */
    public function getMark($content) {
        
        $ret = '';
        if ($content) {
            $text = kernel::single('ome_func')->format_memo($content);
            if (!empty($text)) {
                foreach ((array) $text as $k => $v) {
                    $ret .= $v['op_content'];
                }
            } else {

                $ret = '';
            }
        }
        
        return $ret;
    }

    /**
     * 获取订单状态
     */
    public function getStatus($status, $order) {
        return $order['auto_status'] & $this->getMsgFlag();
    }
}