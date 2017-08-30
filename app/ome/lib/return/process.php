<?php
class ome_return_process{
	
	function do_iostock($por_id,$io,&$msg){
    	//生成出入库明细
		$allow_commit = false;
        kernel::database()->exec('begin');
        $iostock_instance = kernel::service('ome.iostock');
        if ( method_exists($iostock_instance, 'set') ){
         	//存储出入库记录
            $iostock_data = $this->get_iostock_data($por_id,$type);
           
            //eval('$type='.get_class($iostock_instance).'::DIRECT_STORAGE;');
            $iostock_bn = $iostock_instance->get_iostock_bn($type);
            #error_log(var_export($iostock_data,1),3,'d:/test_log/iostock_data.txt');
            if ( $iostock_instance->set($iostock_bn, $iostock_data, $type, $iostock_msg, $io) ){
            	$allow_commit = true;
            }

        }
        if ($allow_commit == true){
            kernel::database()->commit();
            return true;
        }else{
            kernel::database()->rollBack();
            $msg = $iostock_msg;
            return false;
        }
    }
    
/**
     * 组织出库数据
     * @access public
     * @param String $iso_id 出入库ID
     * @return sdf 出库数据
     */
    public function get_iostock_data($pro_id,&$type){
        $pro_items_detail = $this->getProItems($pro_id);
        $iostock_data = array();
        $pro_detail = $this->getRetrunProcess($pro_id);
        $reship_id = $pro_detail['reship_id'];
        $oReship = &app::get('ome')->model('reship');
        $reship = $oReship->dump($reship_id,'reship_bn');
        $reship_bn = $reship['reship_bn'];
        $oper = kernel::single('desktop_user')->get_name();
        $operator = kernel::single('desktop_user')->get_name();
        $oper = $oper ? $oper : 'system';
        $operator = $operator ? $operator : 'system';
        if ($pro_items_detail){
            foreach ($pro_items_detail as $k=>$v){
                $iostock_data[$v['item_id']] = array(
                    'branch_id' => $v['branch_id'],
                    'original_bn' => $reship_bn,
                    'original_id' => $pro_id,
                    'original_item_id' => $v['item_id'],
                    'supplier_id' => 0,
                    'bn' => $v['bn'],
                    'iostock_price' => $v['need_money'],
                    'nums' => $v['num'],
                    'cost_tax' => 0,
                    'oper' => $oper,
                    'create_time' => $iso_detail['add_time'],
                    'operator' => $operator,
                    'settle_method' => '',
                    'settle_status' => '0',
                    'settle_operator' => '',
                    'settle_time' => '',
                    'settle_num' => '',
                    'settlement_bn' => '',
                    'settlement_money' => '',
                    'order_id'=>$v['order_id'],
                    //'memo' => $iso_detail['memo'],
                );
            }
        }
        $iostock_instance = kernel::service('ome.iostock');
        eval('$type='.get_class($iostock_instance).'::RETURN_STORAGE;');
        
        return $iostock_data;
    }
    
	function getProItems($pro_id){
		$objProItems = &app::get('ome')->model('return_process_items');
		$pro_items_detail = $objProItems->getList('*', array('por_id'=>$pro_id), 0, -1);
        
		return $pro_items_detail;

    }
    
    function getRetrunProcess($pro_id,$field='*'){
    	$db = kernel::database();
        $sql = 'SELECT '.$field.' FROM `sdb_ome_return_process` WHERE `por_id`=\''.$pro_id.'\'';
        return $db->selectrow($sql);
    }

}
