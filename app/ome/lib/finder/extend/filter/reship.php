<?php
class ome_finder_extend_filter_reship{
    function get_extend_colums(){

        switch($_GET['ctl']){
            case 'admin_return_rchange':
                $type = array(
                    'return' => '退货',
                    'change' => '换货',
                  );
            break;
            case 'admin_delivery_refuse':
                $type = array(
                    'refuse' => '拒收退货',
                  );
            break;
            default:
                $type = array(
                    'return' => '退货',
                    'change' => '换货',
                    'refuse' => '拒收退货',
                  );
            break;           
        }

        $db['reship']=array (
            'columns' => array (
                'order_bn' => array (
                    'type' => 'varchar(30)',
                    'label' => '订单号',
                    'width' => 130,
                    'filtertype' => 'normal',
                    'filterdefault' => true,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
                'return_type' =>
                array (
                  'type' => $type,
                  'default' => 'return',
                  'required' => true,
                  'comment' => '退换货状态',
                  'editable' => false,
                  'label' => '退换货状态',
                  'width' =>65,
                  'in_list' => true,
                  'default_in_list' => true,
                  'filtertype' => true,
                  'filterdefault' => true,
                ),                
            )
        );
        return $db;
    }
}
