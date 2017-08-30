<?php
/**
 * 修补订单obj上的price、amount、sale_price不正确问题
 *
 * @author yangminsheng@shopex.com
 * @version 1.0
 */

//php d:\wamp\www\prerelease20120904\script\update\script\fixOrderprice.php 192.168.132.54/prerelease20120904

$domain = $argv[1];

$order_id = $argv[2];

$host_id = $argv[3];

if (empty($domain)) {

    die('No Params');
}

set_time_limit(0);

require_once(dirname(__FILE__) . '/../../lib/init.php');

cachemgr::init(false);


$pre_time = '2012-11-01';//1349020800

$last_time = date('Y-m-d');//1352131200

fixOrderprice($pre_time,$last_time);


function fixOrderprice($pre_time,$last_time){

    $pre_time = strtotime($pre_time);

    $last_time = strtotime($last_time.' 23:59:59');

    $Oorderobj = app::get('ome')->model('order_objects');
    $Oorderitems = app::get('ome')->model('order_items');
    $Osales_items = app::get('ome')->model('sales_items');

    fixSaleprice($Osales_items,$pre_time,$last_time);

    $saledatas = array();

    while(getsale_data($Osales_items,&$tmp_data,$offset,$pre_time,$last_time)){
        $saledatas = $tmp_data;
        for($i=0;$i<count($saledatas);$i++){

            $bn = $saledatas[$i]['bn'];
            $sales_amount = $saledatas[$i]['sales_amount'];
            $order_id = $saledatas[$i]['order_id'];
            $sale_id = $saledatas[$i]['sale_id'];

            $order_obj = $Oorderobj->getList('amount,pmt_price,quantity,price,obj_id',array('bn'=>$bn,'order_id'=>$order_id));

            $order_items = $Oorderitems->getList('sum(pmt_price) as total_pmt_price',array('obj_id'=>$order_obj[0]['obj_id']));

    	    if($sales_amount == 0.000){//shopex订单 修复方法

                $sale_price = $order_obj[0]['amount'] - $order_obj[0]['pmt_price'] - $order_items[0]['total_pmt_price'];

                $sales_amount = $sale_price;//销售明细上

    		    //更新order_obj上的信息
    		    $Oorderobj->update(array('sale_price'=>$sale_price),array('bn'=>$bn,'order_id'=>$order_id));
    		    //更新sale_items上的信息
    		    $Osales_items->update(array('sales_amount'=>$sales_amount),array('bn'=>$bn,'sale_id'=>$sale_id));

    	    }elseif($sales_amount < 0){//C2C订单 修复方法

    	        $sale_price = $order_obj[0]['amount'];

    	        $amount = $order_obj[0]['quantity']*$order_obj[0]['price'];//订单obj一层上

                $sales_amount = $sale_price;//销售明细上

    		    //更新order_obj上的信息
    		    $Oorderobj->update(array('amount'=>$amount,'sale_price'=>$sale_price),array('bn'=>$bn,'order_id'=>$order_id));
    		    //更新sale_items上的信息
    		    $Osales_items->update(array('sales_amount'=>$sales_amount),array('bn'=>$bn,'sale_id'=>$sale_id));
    	    }

        }
    	$offset++;
    }
}


function getsale_data($Osales_items,&$data,$offset,$pre_time,$last_time){

    $limit = 1000;

    @ini_set('memory_limit','1024M');
    @set_time_limit(0);

    $sale_sql = 'select S.order_id,S.sale_id,SI.sales_amount,SI.bn from sdb_ome_sales S left join sdb_ome_sales_items SI on SI.sale_id = S.sale_id where S.order_create_time >= '.$pre_time.' and S.order_create_time < '.$last_time.' and SI.sales_amount <=0 limit '.$offset*$limit.','.$limit;

    $data = $Osales_items->db->select($sale_sql);


    if(!$data) return false;


    return true;
}


function fixSaleprice($Osales_items,$pre_time,$last_time){

    $sql1 = 'select si.bn,s.order_id,si.item_id from sdb_ome_sales_items si left join sdb_ome_sales s on si.sale_id = s.sale_id where si.sales_amount = 0 and s.order_create_time >= '.$pre_time.' and s.order_create_time < '.$last_time;

    $sales = $Osales_items->db->select($sql1);

    foreach ($sales as $k => $v) {

        $sql2 = 'select o.bn,o.obj_type from sdb_ome_order_objects o left join sdb_ome_order_items oi on o.obj_id = oi.obj_id where oi.bn="'.$v['bn'].'" and oi.order_id = '.$v['order_id'];

        $obj = $Osales_items->db->select($sql2);

        if($obj[0]['obj_type'] == 'pkg'){

            $Osales_items->db->exec('update sdb_ome_sales_items set bn="'.$obj[0]['bn'].'" where item_id= '.$v['item_id']);

        }

    }

}

/*

3cfenxiao

sale_items : sales_amount

order_obj : sale_price,amount,price

if order_obj.sale_price == 0
    说明是shopex订单:(20121107146159)
       order_obj.sale_price = order_obj.amount - order_obj.pmt_price - order_items.total_pmt_price  //订单一层上
       sales_items.sales_amount = order_obj.sale_price //销售明细上

else  58425  1352273992414234
    说明是C2C订单:(S20121107000185)
       order_obj.sale_price = order_obj.amount
       order_obj.amount = order_obj.quantity*order_obj.price //订单一层上
       sales_items.sales_amount = order_obj.sale_price //销售明细上
endif

*/