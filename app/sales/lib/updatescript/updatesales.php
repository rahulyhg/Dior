<?php
/**
 * 更新销售单
 * 说明: 老的销售单是依据发货单来生成销售单。新销售单将会以订单来生成销售单
 * @package default
 * @author
 **/
class sales_updatescript_updatesales
{

    /**
     * 修复销售单数据
     *
     * @return void
     * @author
     **/
    function updateSales($pre_time,$last_time){

        //清空销售单上的老数据 取的时间是以销售单系统升级时间为准
        $updatetime = time();
        $iostockbns = $this->truncate_sales($pre_time,$last_time);

        //以出入库单上面的original_id发货单号为依据，根据订单来重新生成销售单数据
        if(!$iostockbns) return false;

        $this->generate_sales($iostockbns);
        return true;
    }
/*
    function truncate_sales($time){
        $Osales = &app::get('sales')->model('sales');
        $Osales_items = kernel::single('ome_mdl_sales_items');

        $tmp_sales = $tmp_iostockbn = array();

        $get_sales = $Osales->getList('sale_id,iostock_bn',array('sale_time|sthan'=>$time));
        foreach($get_sales as $v){
            $tmp_sales[] = $v['sale_id'];
            $tmp_iostockbn[] = $v['iostock_bn'];//获取出入库单号
        }

        $Osales_items->delete(array('sale_id|in'=>$tmp_sales));
        $Osales->delete(array('sale_id|in'=>$tmp_sales));

        return $tmp_iostockbn;
    }
*/

    function getsale_data($Oiostock,&$data,$offset,$pre_time,$last_time){

        $limit = 1000;
        $where = '1';
        if($pre_time!=''&&$last_time!=''){
           $where = ' create_time >='.$pre_time.' and create_time<='.$last_time.' and type_id = 3';
        }else{
           $where = ' create_time<='.$last_time;
        }


        @ini_set('memory_limit','1024M');
        @set_time_limit(0);

        $sql = 'select iostock_bn from sdb_ome_iostock where '.$where.' limit '.$offset*$limit.','.$limit;

        $salesinfo = $Oiostock->db->select($sql);

        foreach($salesinfo as $k=>$v){
           //$data['sale_id'][] = $v['sale_id'];
           $iostocks[] = $v['iostock_bn'];
        }

        $get_orginal = $Oiostock->getList('distinct(original_id),iostock_bn',array('iostock_bn|in'=>$iostocks));


        foreach($get_orginal as $k=>$v){
           $data['iostock_bn'][$v['iostock_bn']] = $v['original_id'];
        }

        if(!$salesinfo) return false;

        return true;
    }

    function truncate_sales($pre_time,$last_time){

        $pre_time = strtotime($pre_time);
        $last_time = strtotime($last_time);
        $tmp_sales = $tmp_iostockbn = array();

        $offset = 0;
        $Oiostock = &app::get('ome')->model('iostock');
        $Osales = &app::get('sales')->model('sales');
        $Osales_items = kernel::single('ome_mdl_sales_items');

        while($this->getsale_data($Oiostock,$data,$offset,$pre_time,$last_time)){
            $offset++;
            $get_sales = $data;
        }
        if(!$get_sales['iostock_bn']) return false;
/*
        foreach ($get_sales['sale_id'] as $ssid) {
            $Osales_items->delete(array('sale_id'=>$ssid));
            $Osales->delete(array('sale_id'=>$ssid));
        }
*/
        return $get_sales['iostock_bn'];
    }



    function generate_sales($iostocks = array()){

        $Ome_sales = kernel::single('ome_sales');
        $Oiostocksales = kernel::single('ome_iostocksales');
        $sales_instance = kernel::service('ome.sales');
        $Osales_items = app::get("ome")->model("sales_items");
        $tgstockcostLib = kernel::single('tgstockcost_taog_instance');
        $sales_items = array();
        foreach ($iostocks as $k => $v) {

            $sales_data = $Oiostocksales->get_sales_data($v,true);

            foreach ($sales_data as $kk => $vv) {
                $sales_data[$kk]['iostock_bn'] = $k;
                $sales_data[$kk]['sale_id'] = $Ome_sales->gen_id();
                $sales_data[$kk]['sale_bn'] = $sales_instance->get_salse_bn();
                $sales_items = $sales_data[$kk]['sales_items'];
                unset($sales_data[$kk]['sales_items']);
                $tmp[$kk]['main'] = $sales_data[$kk];
                if($Ome_sales->_add_Sales($sales_data[$kk])){
                    $tmp[$kk]['items'] = $sales_items;
                    foreach ($sales_items as $_k => $_v) {
                        $sales_items[$_k]['sale_id'] = $sales_data[$kk]['sale_id'];
                        $tmp_info = $Osales_items->db->selectrow("select iostock_id from sdb_ome_iostock where original_item_id =".$_v['item_detail_id']." and branch_id =".$_v['branch_id']." and original_id =".$v);
                        $sales_items[$_k]['iostock_id'] = $tmp_info['iostock_id'] ? $tmp_info['iostock_id'] : '';
                        $Ome_sales->_add_Sales_Items($sales_items[$_k]);
                    }

                }
            }

            $data[0]['type_id'] = 3;
            $data[0]['original_id'] = $v;
            $tgstockcostLib->set_sales_iostock_cost(0,$data);
        }

    }
}


