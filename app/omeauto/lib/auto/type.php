<?php
/**
 * 
 */

class omeauto_auto_type {
    
    /**
     * 所有支持的类型
     */
    static $TYPE_LIST = array('address','shop','money','platform','sku','cod','skunum','itemnum');
    
    /**
     * 执行插件的指定方法
     * 
     * @param String $tagName
     * @param String $method
     * @param Array $params
     * @return Mixed
     */
    public function doMethod($tagName, $method, $params) {
        
        $tagName = strtolower($tagName);
        if (in_array($tagName, self::$TYPE_LIST)) {
            
            $obj = kernel::single(sprintf('omeauto_auto_type_%s',$tagName));
            if (method_exists($obj, $method)) {
                
                return $obj->$method($params);
            } else {
                
                return sprintf("方法 {$method} 不存在。");
            }
        } else {
            
            return sprintf("未知类型 {$tagName}。");
        }
    }
    
    /**
     * 执行数据保存操作
     * 
     * @param void
     * @return mixed
     */
    function createRole() {
        //参数多使用 POST 来传送
        if (empty($_POST)) {
            
            return '请使用 POST 方式进行数据提交！';
        }
        $tagName = strtolower(trim($_REQUEST['type_id']));
        $method = 'createRole';
        return $this->doMethod($tagName, $method, $_POST);
    }
    
    /**
     * 获取所有用于审单的订单分组
     * 
     * @param void
     * @return mixed
     */
    function getAutoOrderTypes() {
        
       return kernel::database()->select("SELECT ot.* FROM sdb_omeauto_order_type as ot LEFT JOIN sdb_omeauto_autoconfirm as at ON ot.oid=at.oid WHERE ot.oid > 0 AND ot.disabled = 'false' AND at.disabled='false' ORDER BY weight DESC, tid ASC");  
    }
    
    /**
     * 获取所有用于仓库选择的订单分组
     * 
     * @param void
     * @return mixed
     */
    function getAutoBranchTypes() { 
        
       return kernel::database()->select("SELECT ot.tid,ot.oid,ot.did,ot.config,ot.name,ot.memo,ot.weight,ot.delivery_group,ot.group_type,ot.disabled,bt.bid,bt.is_default FROM sdb_omeauto_order_type as ot LEFT JOIN sdb_omeauto_autobranch as bt ON ot.tid=bt.tid WHERE ot.group_type in ('branch') AND ot.disabled = 'false'   ORDER BY bt.is_default,ot.weight,bt.weight DESC, ot.tid ASC");  
    }
    
    /**
     * 获取所有用于分派选择的订单分组
     * 
     * @param void
     * @return mixed
     */
    function getAutoDispatchTypes() {
        
       return kernel::database()->select("SELECT ot.* FROM sdb_omeauto_order_type as ot LEFT JOIN sdb_omeauto_autodispatch as bt ON ot.did=bt.oid WHERE ot.did > 0 AND ot.disabled = 'false' AND bt.disabled='false' ORDER BY ot.weight DESC, ot.tid ASC");  
    }
    
    /**
     * 获取所有用于打印的订单分组
     * 
     * @param void
     * @return mixed
     */
    function getDeliveryGroupTypes() {
        
       return kernel::database()->select("SELECT * FROM sdb_omeauto_order_type WHERE disabled = 'false' AND delivery_group='true' ORDER BY weight DESC, tid ASC");  
    }

    /**
     * 获取所有用于短信发送的订单分组
     * 
     * @param void
     * @return mixed
     */
    function getAutoSendSmsTypes() {

       return kernel::database()->select("SELECT * FROM sdb_omeauto_order_type WHERE disabled = 'false' AND group_type='sms' ORDER BY weight DESC, tid ASC"); 
        
    }
}