<?php
class omeanalysts_crontab_script_sale{
    
    function __construct(){
        $this->db = kernel::database();
        $shs = app::get('ome')->model('shop')->getlist('*');
        $shops = '';
        foreach($shs as $shop){
            $shops[] = $shop['shop_id'];
        }
        if(!$shops) die;
        $this->shops = $shops;
    }
    
    function statistics(){
        set_time_limit(0);
        
        
        //if(!$this->runtime()){
            //return 'runtime error!';
        //}
        
        $now = strtotime(date('Y-m-d',time()));
        $last_time = $this->last_record();
        //$last_time = $last_time ? $last_time : strtotime('2011-01-05 00:00:00');
        if($last_time == 0){
            return 'not find orders';
        }

        $days = $this->time_diff($now,$last_time);
        
        if(count($days) > 0){
            
            foreach($days as $day){
                $time_from = $day;
                $time_to = $day+86400;
                
                $where1 = " orders.createtime >= $day and orders.createtime < $time_to group by orders.shop_id ";
                $where2 = " d.delivery_time >= $day and d.delivery_time < $time_to and d.parent_id = 0 and d.status='succ' and o.ship_status = '1' group by o.shop_id ";
                
                foreach($this->shops as $shop_id){
                    $order_nums[$shop_id]           = 0;
                    $delivery_nums[$shop_id]        = 0;
                    $sale_totals[$shop_id]          = 0;
                    $minus_sale_totals[$shop_id]    = 0;
                    $return_totals[$shop_id]        = 0;
                    $ok_return_totals[$shop_id]     = 0;
                }

                
                $rows = $this->db->select("select orders.shop_id,count(*) as order_num from sdb_ome_orders as orders where $where1");
                if($rows){
                    foreach($rows as $row){
                        $order_nums[$row['shop_id']] = $row['order_num'];
                    }
                }
                
                $rows = $this->db->select("select o.shop_id,count(distinct o.order_id) as _count from 
                          sdb_ome_delivery as d 
                          inner join sdb_ome_delivery_order as do 
                          on do.delivery_id = d.delivery_id 
                          inner join sdb_ome_orders as o 
                          on do.order_id = o.order_id where $where2 
                          ");
                if($rows){
                    foreach($rows as $row){
                        $delivery_nums[$row['shop_id']] = $row['_count'];
                    }
                }
                
                $rows = $this->db->select("select shop_id,sum(sale_amount) as sale_total from sdb_ome_sales where sale_time >= $day and sale_time < $time_to and sale_amount>0 group by shop_id");
                if($rows){
                    foreach($rows as $row){
                        $sale_totals[$row['shop_id']] = $row['sale_total'];
                    }
                }
                
                $rows = $this->db->select("select shop_id,sum(sale_amount) as minus_sale_total from sdb_ome_sales where sale_time >= $day and sale_time < $time_to and sale_amount<0 group by shop_id");
                if($rows){
                    foreach($rows as $row){
                        $minus_sale_totals[$row['shop_id']] = abs($row['minus_sale_total']);
                    }
                }
                
                $rows = $this->db->select("select shop_id,count(*) as return_total from sdb_ome_return_product where add_time >= $day and add_time < $time_to group by shop_id");
                if($rows){
                    foreach($rows as $row){
                        $return_totals[$row['shop_id']] = $row['return_total'];
                    }
                }
                
                $rows = $this->db->select("select shop_id,count(*) as ok_return_total from sdb_ome_return_product where add_time >= $day and add_time < $time_to and status = '4' group by shop_id");
                if($rows){
                    foreach($rows as $row){
                        $ok_return_totals[$row['shop_id']] = $row['ok_return_total'];
                    }
                }
                foreach($this->shops as $shop_id){
                    $insertsql = "insert into sdb_omeanalysts_ome_salestatistics (shop_id,day,order_num,delivery_num,sale_total,minus_sale_total,return_total,ok_return_total,runtime) 
                            values('".$shop_id."','".$day."','".$order_nums[$shop_id]."','".$delivery_nums[$shop_id]."','".$sale_totals[$shop_id]."','".$minus_sale_totals[$shop_id]."','".$return_totals[$shop_id]."','".$ok_return_totals[$shop_id]."','".$now."')";
                    $this->db->exec($insertsql);
                }
                unset($order_nums,$delivery_nums,$sale_totals,$minus_sale_totals,$return_totals,$ok_return_totals);         
            }   
        }
        
        
    }
    
    function update_saleitems(){
        $rows = $this->db->select("select sales.sale_id as sale_id,sales.iostock_bn,saleitem.bn as product_bn,saleitem.nums as buycount,saleitem.item_id,sales.sale_time as createtime 
                from sdb_ome_sales_items as saleitem left join 
                sdb_ome_sales as sales on saleitem.sale_id = sales.sale_id");
        
        if($rows){
            foreach($rows as $row){
                $sql = "select orders.order_bn from sdb_ome_orders as orders left join 
                                sdb_ome_delivery_order as dod on orders.order_id = dod.order_id left join 
                                sdb_ome_delivery as delivery on dod.delivery_id = delivery.delivery_id left join 
                                sdb_ome_iostock as iostock on iostock.original_bn = delivery.delivery_bn left join 
                                sdb_ome_sales as sale on sale.iostock_bn = iostock.iostock_bn 
                                where sale.iostock_bn = '".$row['iostock_bn']."'";
                $row_order = $this->db->selectrow($sql);
                $order_bn = $row_order['order_bn'];
                $this->db->exec("update sdb_ome_sales_items set order_bn='".$order_bn."' where item_id=".$row['item_id']);
            }
        }
    }
    
    function runtime(){
        
        $timepart = date_parse(date('Y-m-d H:i:s',time()));
        return ($timepart['hour'] == '00' && $timepart['minute'] == '30') ? true : false;
    }
    
    
    function last_record(){ 
         $row = $this->db->selectrow("select max(runtime) as runtime from sdb_omeanalysts_ome_salestatistics");
         if($row['runtime']){
            return $row['runtime'];
         }else{
             //$row = $this->db->selectrow("select min(createtime) as runtime from sdb_ome_orders");
             /*if($row['runtime']){
                return mktime(0,0,0,date('m',$row['runtime']),date('d',$row['runtime']),date('Y',$row['runtime']));
             }else{
                return 0;
             }*/
             return time()-86400;
         }
    }
    
    function time_diff($time1,$time2){
        $days = array();
        if($time1 > $time2){
            $timeoff = $time1 - $time2;
            $daynum = $timeoff/(24*60*60);
            for($i = 0; $i < $daynum; $i++){
                $days[] = $time2+$i*(24*60*60);
            }
        }
        return $days;
    }
    
}
