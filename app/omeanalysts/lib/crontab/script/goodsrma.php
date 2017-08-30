<?php
class omeanalysts_crontab_script_goodsrma{
	
    public function statistics(){ 
        @ini_set('memory_limit','256M');
        set_time_limit(0);
        $db = kernel::database();
        //base_kvstore::instance('omeanalysts_goodsrma')->fetch('goodsrma_time',$old_time);
        $old_time = app::get('omeanalysts')->getConf('old_time.goodsrma_time');//获取上次脚本执行时间
        $new_time = time();
        $ntime = date('Y-m-d',$new_time);
    //  $t = mktime(0,30,0,date("m",$new_time),date("d",$new_time),date("Y",$new_time));
        $hour = date("H",$new_time);
        $minute = date("i",$new_time);
        //if($hour != 0 && $minute != 30){
          //return 'no time';
        //}
        if(!$old_time){
            $db->exec('DELETE FROM sdb_omeanalysts_ome_goodsrma');
            //$sqltime = "SELECT MIN(add_time) as time FROM sdb_ome_return_product";
            //$frist_time = $db->select($sqltime);
            //base_kvstore::instance('omeanalysts_goodsrma')->store('goodsrma_time',$new_time);
            app::get('omeanalysts')->setConf('old_time.goodsrma_time', $new_time);
            //$old_time = $frist_time[0]['time'];
            $old_time=$new_time-86400;
        }else {
            //base_kvstore::instance('omeanalysts_goodsrma')->store('goodsrma_time',$new_time);
            app::get('omeanalysts')->setConf('old_time.goodsrma_time', $new_time);
        }
        $old_time = strtotime(date('Y-m-d',$old_time));
        $new_time = strtotime(date('Y-m-d',$new_time));
        $val = ($new_time-$old_time)/86400;
        if($val > 1){
            $te = $new_time;
            for($tim=0;$tim < $val;$tim++){

                $teo = $te-86400;
                $times[] = array('from_time'=>$teo,'to_time'=>$te);
                $te -= 86400;
            }
        }else{
            $times[0]=array('from_time'=>$old_time,'to_time'=>$new_time);
        }

        $db = kernel::database();
        foreach($times as $time){
                    //  $sql1 = "select DISTINCT pi.bn as bn From sdb_ome_return_product_items as pi,sdb_ome_return_product as rp
                    //              where pi.return_id=rp.return_id and rp.add_time>=".$time['from_time']."  and rp.add_time<".$time['to_time'];
                    //  $sql1 = "select DISTINCT pi.bn as bn From sdb_ome_return_product_items as pi,sdb_ome_return_product as rp
                    //              where pi.return_id=rp.return_id and rp.add_time>=".$time['from_time']."  and rp.add_time<".$time['to_time'];
                        $sql1 = "select i.bn as bn ,sum(i.nums) as nums From sdb_ome_iostock as i
                                    where (i.type_id=30 or i.type_id=31) and i.create_time>=".$time['from_time']."  and i.create_time<".$time['to_time'].' GROUP BY i.bn  ';
                        $bns = $db->select($sql1);

                        foreach($bns as $bn){
                            $goodsbn=$bn['bn'];
                            $sql='select p.name as name ,  g.brand_id as brand
                                ,p.store as store ,p.spec_info as spec from 
                                sdb_ome_iostock as i,sdb_ome_goods as g,sdb_ome_products as p 
                                where i.bn=p.bn and p.goods_id=g.goods_id and p.bn=\''.$goodsbn.'\'';
            
                            $info = $db->select($sql);
                            $data = $info[0];
                            $rma = array();
                            $sale_num=kernel::database()->select('select sum(si.nums) as sales_nums from sdb_ome_sales_items as si , sdb_ome_sales as s where si.sale_id=s.sale_id and si.nums>=0 and  s.sale_time>='.$time['from_time'].' and s.sale_time<'.$time['to_time']. ' and si.bn=\''.$goodsbn.'\' GROUP BY si.bn ');
                        //  $xnum = kernel::database()->select('select sum(nums) as xnum from sdb_ome_iostock where type_id=31 and bn='.$goodsbn);
                            $rma['goods_bn'] = $goodsbn;
                            $rma['name'] = $data['name'];
                            $rma['brand']['brand_id'] = $data['brand'];
                            $rma['sales_num'] = ($sale_num[0]['sales_nums'])?$sale_num[0]['sales_nums']:0;
                            $rma['back_change_num'] = ($bn['nums'])?$bn['nums']:0;
                        //  $rma['change_num'] = $pur['xnum'];
                            $rma['spec_info'] = $data['spec'];
                        //  $sales['shop']['shop_id'] = $data['shop_id'];
                            $rma['store'] = $data['store'];
                            $rma['createtime'] = $time['from_time'];
                            $goodsrma_obj = &app::get('omeanalysts')->model('ome_goodsrma');
                            $goodsrma_obj->insert($rma);
                        }





        }


    }


}