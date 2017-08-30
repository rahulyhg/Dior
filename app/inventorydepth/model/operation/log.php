<?php
/**
 *
 * @category inventorydepth
 * @package model/operation
 * @author chenping<chenping@shopex.cn>
 * @version $Id: log.php 2013-11-20 14:57 Z
 */
class inventorydepth_mdl_operation_log extends dbeav_model
{
    private $operation = array(
        'stockup' => '库存回写',
        'stockset' => '库存回写设置',
        'approve' => '上下架',
    );

    public function get_operation_name($operation)
    {
        return $this->operation[$operation];
    }

    /**
     * 写操作日志
     *
     * @param String $obj_type 对象类型
     * @param String $obj_id   对象ID
     * @param String $operation 日志行为
     * @param String $memo  日志说明
     * @param Array $oper 操作人信息
     * @return void
     * @author 
     **/
    public function write_log($obj_type,$obj_id,$operation,$memo,$oper=array())
    {
        $operInfo = $oper ? $oper : kernel::single('inventorydepth_func')->getDesktopUser();

        $optLog = array(
            'obj_type'    => $obj_type,
            'obj_id'      => $obj_id,
            'memo'        => $memo,
            'create_time' => time(),
            'op_id'       => $operInfo['op_id'],
            'op_name'     => $operInfo['op_name'] ? $operInfo['op_name'] : $operInfo['login_name'],
            'operation'   => $operation,
        );

        return $this->insert($optLog);
    }

    /**
     * 写操作日志
     *
     * @param String $obj_type 对象类型
     * @param String $obj_id   对象ID集合
     * @param String $operation 日志行为
     * @param String $memo  日志说明
     * @param Array $oper 操作人信息     
     * @return void
     * @author 
     **/
    public function batch_write_logs($obj_type,$obj_ids,$operation,$memo,$oper=array())
    {
        $operInfo = $oper ? $oper : kernel::single('inventorydepth_func')->getDesktopUser();

        $optLogs = array();
        foreach ($obj_ids as $obj_id) {
            $optLogs[] = array(
                'obj_type'    => $obj_type,
                'obj_id'      => $obj_id,
                'memo'        => $memo,
                'create_time' => time(),
                'op_id'       => $operInfo['op_id'],
                'op_name'     => $operInfo['op_name'] ? $operInfo['op_name'] : $operInfo['login_name'],
                'operation'   => $operation,
            );
        }

        $sql = kernel::single('inventorydepth_func')->get_insert_sql($this,$optLogs);

        return $this->db->exec($sql);
    }
}