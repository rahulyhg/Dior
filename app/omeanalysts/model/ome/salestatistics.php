<?php
class omeanalysts_mdl_ome_salestatistics extends dbeav_model{
          
    public function count($filter=null){
        return $this->_get_count($filter);
    }

    public function _get_count($filter=null){
        if($filter['report'] == 'day'){
            $sql = "select count(*) as _count from sdb_omeanalysts_ome_salestatistics as a 
                    join sdb_ome_shop as b on a.shop_id = b.shop_id 
                    where ".$this->_filter($filter);
            $row = $this->db->select($sql);
            return intval($row[0]['_count']);
        }else{
            $sql = "select a.day,b.name as shop_name,a.order_num,a.delivery_num,a.sale_total,a.minus_sale_total,a.return_total,a.ok_return_total from sdb_omeanalysts_ome_salestatistics as a   
                    join sdb_ome_shop as b on a.shop_id = b.shop_id 
                    where ".$this->_filter($filter);
            $data = array();
            $rows = $this->db->selectLimit($sql,-1,0);
            if($rows){
                foreach($rows as $row){
                    $key = date('Y-m',$row['day']);
                    $data[$key] = $key; 
                }
            }
            return count($data);
        }
    }
    
    public function getlist($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){

        $sql = "select a.day,b.name as shop_name,a.order_num,a.delivery_num,a.sale_total,a.minus_sale_total,a.return_total,a.ok_return_total from sdb_omeanalysts_ome_salestatistics as a   
                    join sdb_ome_shop as b on a.shop_id = b.shop_id 
                    where 1";
        if(trim($this->_filter($filter))){
            $sql .= ' AND '.$this->_filter($filter);
        }
            $sql .= ' order by a.day';
        

        if($filter['report'] == 'month'){
            $rows = $this->db->selectLimit($sql,-1,0);
        }else{
            $rows = $this->db->selectLimit($sql,$limit,$offset);
        }
        $data = array();
        if($rows){

            if($filter['report'] == 'day'){
                foreach($rows as $key=>$row){
                    $data[$key]['day']                  = date('Y-m-d',$row['day']);
                    $data[$key]['shop_name']            = $row['shop_name'];
                    $data[$key]['order_num']            = $row['order_num'];
                    $data[$key]['delivery_num']         = $row['delivery_num'];
                    $data[$key]['sale_total']           = $row['sale_total'];
                    $data[$key]['minus_sale_total']     = $row['minus_sale_total'];
                    $data[$key]['return_total']         = $row['return_total'];
                    $data[$key]['ok_return_total']      = $row['ok_return_total'];
                }

            }elseif($filter['report'] == 'month'){
                foreach($rows as $row){
                    $key = date('Y-m',$row['day']);
                    if(isset($filter['type_id']) && $filter['type_id']){
                        $shopname = $row['shop_name'];
                    }else{
                        $shopname = '所有店铺';
                    }
                    $data[$key]['day'] = $key;
                    $data[$key]['shop_name'] = $shopname;
                    $data[$key]['order_num'] += $row['order_num'];
                    $data[$key]['delivery_num'] += $row['delivery_num'];
                    $data[$key]['sale_total'] += $row['sale_total'];
                    $data[$key]['minus_sale_total'] += $row['minus_sale_total'];
                    $data[$key]['return_total'] += $row['return_total'];
                    $data[$key]['ok_return_total'] += $row['ok_return_total'];
                }       
                if($limit == -1)
                    return array_slice($data,0,1024);
                else 
                    return array_slice($data,$offset,$limit);
            }
        }

        return $data;
    }
    
    public function _filter($filter,$tableAlias=null,$baseWhere=null){

        if(isset($filter['time_from']) && $filter['time_from']){
            $where[] = ' a.day >='.strtotime($filter['time_from']);
        }
        if(isset($filter['time_to']) && $filter['time_to']){
            $where[] = ' a.day <'.(strtotime($filter['time_to'])+86400);
        }
        if(isset($filter['type_id']) && $filter['type_id']){
            $where[] = ' b.shop_id =\''.addslashes($filter['type_id']).'\'';
        }
       return " ".implode($where,' AND ');
    }
    
    public function get_schema(){
        $schema = array (
            'columns' => array (
                'day' => array (
                    'type' => 'varchar(20)',
                    'pkey' => true,
                    'label' => '日期',
                    'width' => 210,
                    'order'=>1,
                    'orderby' => false,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
                'shop_name' => array (
                    'type' => 'varchar(50)',
                    'required' => true,
                    'default' => 0,
                    'label' => '店铺',
                    'order'=>2,
                    'orderby' => false,
                    'width' => 120,
                    'editable' => true,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
                'order_num' => array (
                    'type' => 'number',
                    'required' => true,
                    'label' => '下单量',
                    'order'=>3,
                    'orderby' => false,
                    'width' => 110,
                    'editable' => true,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
                'delivery_num' => array (
                    'type' => 'number',
                    'label' => '发货量',
                    'width' => 110,
                    'orderby' => false,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
                'sale_total' => array (
                    'type' => 'number',
                    'label' => '销售额',
                    'order'=>4,
                    'width' => 130,
                    'orderby' => false,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
                'minus_sale_total' => array (
                    'type' => 'number',
                    'label' => '负销售额',
                    'width' => 130,
                    'order'=>5,
                    'orderby' => false,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
                 'return_total' => array (
                    'type' => 'number',
                    'label' => '售后量',
                    'width' => 130,
                    'orderby' => false,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
                 'ok_return_total' => array (
                    'type' => 'number',
                    'label' => '完成售后量',
                    'width' => 130,
                    'orderby' => false,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                ),
            ),
            'idColumn' => 'day',
            'in_list' => array (
                0 => 'day',
                1 => 'shop_name',
                2 => 'order_num',
                3 => 'delivery_num',
                4 => 'sale_total',
                5 => 'minus_sale_total',
                6 => 'return_total',
                7 => 'ok_return_total',
            ),
            'default_in_list' => array (
                0 => 'day',
                1 => 'shop_name',
                2 => 'order_num',
                3 => 'delivery_num',
                4 => 'sale_total',
                5 => 'minus_sale_total',
                6 => 'return_total',
                7 => 'ok_return_total',
            ),
        );
        return $schema;
    }

    //配置信息
    public function export_params(){

        $filter = $this->export_filter;
        if($filter['isSelectedAll'] == '_ALL_'){
            $filter = array();
        }
        $data = $_SESSION['data'];
        $filter = $data;


        $params = array(
            'filter' => $filter,
            'single'=> array(
                '1'=> array(
                    'method' => 'get_export_main',
                    'offset' => 0,
                    'limit' => 5000,
                    'filename' => 'salestatisticsContent',
                ),
            ),
        );
        return $params;

    }

    //商品销量统计title
    public function get_export_main_title(){
        $title = array(
            'col:日期',
            'col:店铺',
            'col:下单量',
            'col:发货量',
            'col:销售额',
            'col:负销售额',
            'col:售后量',
            'col:完成售后量',
        );
        return $title;
    }

    //商品销量统计
    public function get_export_main($filter,$offset,$limit,&$data){
        $data = $this->getlist('*',$filter,$offset,$limit);
    }

}