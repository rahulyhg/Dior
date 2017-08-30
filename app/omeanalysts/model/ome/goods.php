<?php
class omeanalysts_mdl_ome_goods extends dbeav_model{
    public function get_sale($filter=null){
        //销售额
        $sql = 'SELECT sum(I.nums) as sale_num,sum(I.sale_price) as sale_amount,sum(I.amount) as subamount,sum(I.pmt_price) as subpmtprice FROM '.
            'sdb_ome_orders as O LEFT JOIN '.
            'sdb_ome_order_items as I ON O.order_id=I.order_id WHERE O.is_fail=\'false\' and I.delete=\'false\' and '.$this->_filter($filter);

        $row = $this->db->select($sql);
        $row[0]['sale_amount'] = $row[0]['subamount'] - $row[0]['subpmtprice'];
        return $row[0];
    }

    public function get_reship($filter=null){
        //退换货量
        $sql = 'SELECT sum(I.num) as reship_num FROM '.
            'sdb_ome_reship as R LEFT JOIN '.
            'sdb_ome_reship_items as I ON R.reship_id=I.reship_id WHERE '.$this->_rFilter($filter);

        $row = $this->db->select($sql);
        return intval($row[0]['reship_num']);
    }

    public function searchOptions(){
        $columns = array();
        foreach($this->_columns() as $k=>$v){
            if(isset($v['searchtype']) && $v['searchtype']){
                $columns[$k] = $v['label'];
            }
        }

        $ext_columns = array(
            'product_bn'=>$this->app->_('货品编号'),
        );

        return array_merge($columns, $ext_columns);
    }

    public function count($filter=null){
        if(isset($filter['report']) && $filter['report'] == 'month'){
            $time_from = strtotime($filter['time_from']);
            $filter['time_from'] = date('Y-m',$time_from).'-01';
            $time_to = explode('-',$filter['time_to']);
            $filter['time_to'] = date('Y-m',mktime(0, 0, 0, $time_to[1]+1, 1, $time_to[0]));
        }
        $sql = 'SELECT count(*) as _count FROM (SELECT P.goods_id as goods_id FROM '.
            'sdb_ome_orders as O LEFT JOIN '.
            'sdb_ome_order_items as I ON O.order_id=I.order_id LEFT JOIN '.
            'sdb_ome_products as P ON I.product_id=P.product_id LEFT JOIN '.
            'sdb_ome_goods as G ON P.goods_id=G.goods_id WHERE O.is_fail=\'false\' and I.delete=\'false\' and '.
            $this->_filter($filter).' GROUP BY P.goods_id) as tb';

        $row = $this->db->select($sql);
        return intval($row[0]['_count']);
    }

    public function getlist($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){
        if(isset($filter['report']) && $filter['report'] == 'month'){
            $time_from = strtotime($filter['time_from']);
            $filter['time_from'] = date('Y-m',$time_from).'-01';
            $time_to = explode('-',$filter['time_to']);
            $filter['time_to'] = date('Y-m-d',mktime(0, 0, 0, $time_to[1]+1, 0, $time_to[0]));
        }
        $sql = 'SELECT G.goods_id,G.bn,G.name,G.brand_id as brand,sum(I.nums) as sale_num,sum(I.sale_price) as sale_amount ,sum(I.amount) as subamount,sum(I.pmt_price) as subpmtprice FROM '.
            'sdb_ome_orders as O LEFT JOIN '.
            'sdb_ome_order_items as I ON O.order_id=I.order_id LEFT JOIN '.
            'sdb_ome_products as P ON I.product_id=P.product_id LEFT JOIN '.
            'sdb_ome_goods as G ON P.goods_id=G.goods_id WHERE O.is_fail=\'false\' and I.delete=\'false\' and '.
            $this->_filter($filter).' GROUP BY P.goods_id';

        if($orderType)$sql.=' ORDER BY '.(is_array($orderType)?implode($orderType,' '):$orderType);

        $rows = $this->db->selectLimit($sql,$limit,$offset);
        $this->tidy_data($rows, $cols);
        $dayNum = ((strtotime($filter['time_to'])-strtotime($filter['time_from']))/86400)+1;
        foreach($rows as $key=>$val){
            $rows[$key]['sale_amount'] = $rows[$key]['subamount'] - $rows[$key]['subpmtprice'];

            $rows[$key]['day_num'] = $dayNum?number_format($rows[$key]['sale_num']/$dayNum,2):0;
            $rows[$key]['day_amount'] = $dayNum?number_format($rows[$key]['sale_amount']/$dayNum,2):0;

            $productFilter = array(
                'goods_id'=>$rows[$key]['goods_id'],
                'time_from' => $filter['time_from'],
                'time_to' => $filter['time_to'],
            );
            $productObj = &app::get('omeanalysts')->model('ome_products');
		    $products = $productObj->getlist('*',$productFilter);
            $rows[$key]['reship_num'] = 0;
            foreach($products as $product){
                $rows[$key]['reship_num'] += $product['reship_num'];
            }

            $rows[$key]['reship_ratio'] = $rows[$key]['sale_num']?number_format($rows[$key]['reship_num']/$rows[$key]['sale_num'],2):0;
        }
        return $rows;
    }

    public function _filter($filter,$tableAlias=null,$baseWhere=null){
        $config = app::get('eccommon')->getConf('analysis_config');
        $filter['order_status'] = $config['filter']['order_status'];
        $where = array(1);
        if(isset($filter['time_from']) && $filter['time_from']){
            $where[] = ' O.createtime >='.strtotime($filter['time_from']);
        }
        if(isset($filter['time_to']) && $filter['time_to']){
            $where[] = ' O.createtime <'.(strtotime($filter['time_to'])+86400);
        }
        if(isset($filter['type_id']) && $filter['type_id']){
            $where[] = ' O.shop_id =\''.addslashes($filter['type_id']).'\'';
        }
        if(isset($filter['bn']) && $filter['bn']){
            $where[] = ' G.bn LIKE \''.addslashes($filter['bn']).'%\'';
        }
        if(isset($filter['name']) && $filter['name']){
            $where[] = ' G.name LIKE \''.addslashes($filter['name']).'%\'';
        }
        if(isset($filter['product_bn']) && $filter['product_bn']){
            $where[] = ' P.bn LIKE \''.addslashes($filter['product_bn']).'%\'';
        }
        if(isset($filter['order_status']) && $filter['order_status']){
            if($filter['order_status']=='confirmed'){
                $where[] = ' O.process_status != \'unconfirmed\'';
                $where[] = ' O.process_status != \'cancel\'';
            }
            if($filter['order_status']=='pay'){
                $where[] = ' O.pay_status IN (\'1\',\'3\',\'4\')';
            }
            if($filter['order_status']=='ship'){
                $where[] = ' O.ship_status IN (\'1\',\'2\',\'3\')';
            }
        }
        return implode($where,' AND ');
    }

    public function _rFilter($filter,$tableAlias=null,$baseWhere=null){
        $where = array(1);
        if(isset($filter['time_from']) && $filter['time_from']){
            $where[] = ' t_end >='.strtotime($filter['time_from']);
        }
        if(isset($filter['time_to']) && $filter['time_to']){
            $where[] = ' t_end <'.(strtotime($filter['time_to'])+86400);
        }
        if(isset($filter['type_id']) && $filter['type_id']){
            $where[] = ' shop_id =\''.$filter['type_id'].'\'';
        }
        return implode($where,' AND ');
    }

    public function get_schema(){
        $schema = array (
            'columns' => array (
                'goods_id' => array (
                    'type' => 'int unsigned',
                    'required' => true,
                    'pkey' => true,
                    'extra' => 'auto_increment',
                    'label' => 'ID',
                    'width' => 110,
                    'hidden' => true,
                    'editable' => false,
                ),
                'bn' => array (
                    'type' => 'varchar(50)',
                    'required' => true,
                    'default' => 0,
                    'label' => '商品编号',
                    'width' => 120,
                    'searchtype' => 'has',
                    'editable' => true,
                    'filtertype' => 'normal',
                    'filterdefault' => 'true',
                    'in_list' => true,
                    'default_in_list' => true,
                    'realtype' => 'varchar(50)',
                ),
                'name' => array (
                    'type' => 'varchar(200)',
                    'pkey' => true,
                    'label' => '商品名称',
                    'width' => 210,
                    'searchtype' => 'has',
                    'editable' => false,
                    'filtertype' => 'normal',
                    'filterdefault' => 'true',
                    'in_list' => true,
                    'default_in_list' => true,
                    'realtype' => 'varchar(200)',
                ),
                'brand' => array (
                    'type' => 'table:brand@ome',
                    'pkey' => true,
                    'label' => '品牌',
                    'width' => 110,
                    'editable' => false,
                    'filtertype' => 'normal',
                    'filterdefault' => 'true',
                    'in_list' => true,
                    'default_in_list' => true,
                    'realtype' => 'varchar(200)',
                ),
                'sale_num' => array (
                    'type' => 'number',
                    'label' => '销售量',
                    'width' => 75,
                    'editable' => true,
                    'in_list' => true,
                    'is_title' => true,
                    'default_in_list' => true,
                    'realtype' => 'mediumint(8) unsigned',
                ),
                'sale_amount' => array (
                    'type' => 'money',
                    'default' => 0,
                    'required' => true,
                    'label' => '销售额',
                    'width' => 110,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'realtype' => 'mediumint(8) unsigned',
                ),
                'day_num' => array (
                    'type' => 'number',
                    'label' => '日均销售量',
                    'width' => 75,
                    'orderby' => false,
                    'editable' => true,
                    'in_list' => true,
                    'is_title' => true,
                    'default_in_list' => true,
                    'realtype' => 'mediumint(8) unsigned',
                ),
                'day_amount' => array (
                    'type' => 'money',
                    'default' => 0,
                    'required' => true,
                    'label' => '日均销售额',
                    'width' => 110,
                    'orderby' => false,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'realtype' => 'mediumint(8) unsigned',
                ),
                'reship_num' => array (
                    'type' => 'number',
                    'default' => 1,
                    'required' => true,
                    'label' => '退换货量',
                    'orderby' => false,
                    'width' => 110,
                    'editable' => true,
                    'in_list' => true,
                    'default_in_list' => true,
                    'realtype' => 'mediumint(8) unsigned',
                ),
                'reship_ratio' => array (
                    'type' => 'varchar(200)',
                    'label' => '退换货率',
                    'width' => 110,
                    'orderby' => false,
                    'editable' => false,
                    'in_list' => true,
                    'default_in_list' => true,
                    'realtype' => 'varchar(50)',
                ),
            ),
            'idColumn' => 'goods_id',
            'in_list' => array (
                0 => 'bn',
                1 => 'name',
                2 => 'brand',
                3 => 'sale_num',
                4 => 'sale_amount',
                5 => 'day_num',
                6 => 'day_amount',
                7 => 'reship_num',
                8 => 'reship_ratio',
            ),
            'default_in_list' => array (
                0 => 'bn',
                1 => 'name',
                2 => 'brand',
                3 => 'sale_num',
                4 => 'sale_amount',
                5 => 'day_num',
                6 => 'day_amount',
                7 => 'reship_num',
                8 => 'reship_ratio',
            ),
        );
        return $schema;
    }
}