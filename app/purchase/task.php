<?php
class purchase_task{

    function post_install(){
        
        //操作日志类型定义
        $operations = array(
            24   =>  '生成采购单',
            25   =>  '修改采购单',
            26   =>  '采购单入库取消',
            27   =>  '采购入库',
            28   =>  '采购退款',
            29   =>  '删除采购单',
            30   =>  '彻底删除采购单',
            31   =>  '恢复被删除的采购单',
            32   =>  '删除供应商',
            33   =>  '删除货位',
        );
        foreach ($operations as $key => $item){
            $data['operation_id'] = $key;
            $data['operation_name'] = $item;
            app::get('ome')->model('operations')->save($data);
            $data = null;
        }
        
    }

    function install_options(){
        return array(
                
            );
    }

}
