<?php
class wms_iostockorder{
    var $iostockorder_bn;

    /**
    * 出入库调入类和方法
    *
    */
    function  iostock_method($type_id){
        
        $iostock = array(
            '4'=>array('class'=>'allocate','method'=>'in_storage','info'=>'调拨入库'),
            '40'=>array('class'=>'allocate','method'=>'out_storage','info'=>'调拨出库'),
            '7'=>array('class'=>'otheroutstorage','method'=>'out_storage','info'=>'直接出库'),
            '70'=>array('class'=>'otherinstorage','method'=>'in_storage','info'=>'直接入库'),
            '5'=>array('class'=>'otheroutstorage','method'=>'out_storage','info'=>'残损出库'),
            '50'=>array('class'=>'otherinstorage','method'=>'in_storage','info'=>'残损入库'),
            '100'=>array('class'=>'otheroutstorage','method'=>'out_storage','info'=>'赠品出库'),
            '200'=>array('class'=>'otherinstorage','method'=>'in_storage','info'=>'赠品入库'),
            '300'=>array('class'=>'otheroutstorage','method'=>'out_storage','info'=>'样品出库'),
            '400'=>array('class'=>'otherinstorage','method'=>'in_storage','info'=>'样品入库'),
        );
        if($iostock[$type_id]){
            return $iostock[$type_id];
        }else{
            return false;
        }
    } 
    
    /**
    * 出入库单确认
    *
    */
    function check_iostockorder($data,&$msg){
        $iso_id = $data['iso_id'];
        $type = $data['type_id'];
        $oper = $data['operator'];//经手人
        $iostockorderObj = kernel::single('console_iostockorder');
        if($iostockorderObj->confirm_iostockorder($iso_id,$type,$msg)){
            $objIoStockOrder = &app::get('taoguaniostockorder')->model('iso');
            $data = array(
                        'iso_id'   => $iso_id,
                        'confirm'  => 'Y',
                        'oper'     => $oper,//经手人
                        'operator' => kernel::single('desktop_user')->get_name(),//操作员
                        'iso_status'=>'3'
            );
            $result = $objIoStockOrder->save($data);
            if ($result){
                $db = kernel::database();
                $db->exec('UPDATE sdb_taoguaniostockorder_iso_items SET normal_num=nums WHERE iso_id='.$iso_id);
            }
            return true;
        }else{
            return false;
        }
    }

   
    function get_create_iso_type($io=1,$isReturnId=false){
        $iostock_instance = kernel::service('ome.iostock');
        if(!$iostock_instance)return array();

        $iso_types = array();
        foreach($iostock_instance->get_iostock_types() as $id=>$type){
            if(isset($type['is_new']) && $type['io'] == $io){
                $iso_types[$id] = $type['info'];
            }
        }

        if($isReturnId){
            return array_keys($iso_types);
        }else{
            return $iso_types;
        }
    }
}
