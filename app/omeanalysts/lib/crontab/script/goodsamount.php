<?php
class omeanalysts_crontab_script_goodsamount{
    

    public function statistics(){
        @ini_set('memory_limit','256M');
        set_time_limit(0);
        $db = kernel::database();
        //base_kvstore::instance('omeanalysts_goodsamount')->fetch('goodsamount_time',$old_time);
        $old_time = app::get('omeanalysts')->getConf('old_time.goodsamount_time');//获取上次脚本执行时间
        $new_time = time();
        $ntime = date('Y-m-d',$new_time);
        //$t = mktime(0,30,0,date("m",$new_time),date("d",$new_time),date("Y",$new_time));
        $hour = date("H",$new_time);
        $minute = date("i",$new_time);
        //if($hour != 0 && $minute != 30){
            //return 'no time';
        //}
        if(!$old_time){
            $db->exec('DELETE FROM sdb_omeanalysts_ome_goodsamount');
            //$sqltime = 'SELECT MIN(sale_time) as time FROM sdb_ome_sales';
            //$frist_time = $db->select($sqltime);
            //base_kvstore::instance('omeanalysts_goodsamount')->store('goodsamount_time',$new_time);
            app::get('omeanalysts')->setConf('old_time.goodsamount_time', $new_time);
            //$old_time = $frist_time[0]['time'];
            $old_time=$new_time-86400;
        }else {
            //base_kvstore::instance('omeanalysts_goodsamount')->store('goodsamount_time',$new_time);
            app::get('omeanalysts')->setConf('old_time.goodsamount_time', $new_time);
        }
        $last_time = date("Y-m-d",($time_from - 86400));
        $old_time = strtotime(date('Y-m-d',$old_time));
        $new_time = strtotime(date('Y-m-d',$new_time));
        $val = ($new_time-$old_time)/86400;
        $times = array();
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
        
        $sql0 = "select count(*) From sdb_ome_sales_items";

        $count = $db->count($sql0);
        $page = 1;
        $limit = 1000;
        $pagecount = ceil($count/$limit);
        
    
        foreach($times as $time){
            $albn=array(); 
            for($page=1;$page<=$pagecount;$page++){
                    $datas = array();
                    $lim = ($page-1) * $limit;
                    $sql1 = 'select si.bn as bn, sum(si.nums) as nums from 
                        sdb_ome_sales_items as si, sdb_ome_sales as sa 
                        where si.sale_id=sa.sale_id  
                         and sa.sale_time>='.$time['from_time'].'  and sa.sale_time<'.$time['to_time'].' and si.nums>=0 GROUP BY si.bn  LIMIT '. $lim.','.$limit.' ';
                        
                    
                
                    
                    $sales_info = $db->select($sql1);
                    if(empty($sales_info)){
                        continue;
                    }else{
                        $sql_insert = 'insert into sdb_omeanalysts_ome_goodsamount (goods_bn,name,brand_id,sales_num,purchase_num,allocation_num,spec_info,store,createtime) values';
                        $value = count($sales_info);
                        foreach($sales_info as $k => $bn){
                        $goodsbn=$bn['bn'];
                        $albn[] = '\''.$goodsbn.'\'';
                        $sql='select p.name as name,p.goods_id as goods_id, p.spec_info as spec_info ,p.store as store From sdb_ome_products as p where p.bn= \''.$goodsbn.'\'';
                        $product_info = $db->select($sql);
                        $pro_info = $product_info[0];
                        $gsql='select g.brand_id as brand_id From sdb_ome_goods as g where g.goods_id= \''.$pro_info['goods_id'].'\'';  
                        $goods_info = $db->select($gsql);       
                        $gInfo = $goods_info[0];
                        $sql_insert0 ='';
                        $sales=array();
                                                    
                        $iostock = &app::get('ome')->model('iostock');
                        $purchase_num=kernel::database()->select('select sum(nums) as pnum from sdb_ome_iostock
                                                     where type_id=1 and bn=\''.$goodsbn.'\' and 
                                                    create_time>='.$time['from_time'].'  and 
                                                    create_time<'.$time['to_time']);            
                        $xnum = kernel::database()->select('select sum(nums) as xnum from 
                                                    sdb_ome_iostock where 
                                                    type_id=4 and bn=\''.$goodsbn.'\' and 
                                                    create_time>='.$time['from_time'].'  and 
                                                    create_time<'.$time['to_time']);
                                                    
                            $sales['goods_bn'] = $goodsbn;
                            $sales['name'] = $pro_info['name'];
                            $sales['brand']['brand_id'] = $gInfo['brand_id'];
                            $sales['sales_num'] = $bn['nums'];
                            $sales['purchase_num'] = ($purchase_num[0]['pnum'])?$purchase_num[0]['pnum']:0;
                            $sales['allocation_num'] = ($xnum[0]['xnum'])?$xnum[0]['xnum']:0;
                            $sales['spec_info'] = $pro_info['spec'];
                        //  $sales['shop']['shop_id'] = $data['shop_id'];
                            $sales['store'] = $pro_info['store'];
                            $sales['createtime'] = $time['from_time'];
                            $goodsamount_obj = &app::get('omeanalysts')->model('ome_goodsamount');
                        //  $r=$goodsamount_obj->insert($sales);
                            
                            if($value-1 != $k){
                                $sql_insert0 = "('".$sales['goods_bn']."','".$sales['name']."','".$sales['brand']['brand_id']."','".$sales['sales_num']."','".$sales['purchase_num']."','".$sales['allocation_num']."','".$sales['spec_info']."','".$sales['store']."','".$sales['createtime']."'),";
                                $sql_insert .= $sql_insert0;
                            }else{
                                $sql_insert0 = "('".$sales['goods_bn']."','".$sales['name']."','".$sales['brand']['brand_id']."','".$sales['sales_num']."','".$sales['purchase_num']."','".$sales['allocation_num']."','".$sales['spec_info']."','".$sales['store']."','".$sales['createtime']."')";
                                $sql_insert .= $sql_insert0;
                                kernel::database()->exec($sql_insert);
                            }
                        }
                    
                        
                    }
                    
            }
            $this->iostock_amount($albn,$time);
            
        }

        
    //  $datas=kernel::database()->select($sql);
        
    }

    public function iostock_amount($albn,$time){
        $db = kernel::database();
        $con = implode(',',$albn);  
        $ss= 'select bn as pbn, sum(nums) as pnum from sdb_ome_iostock
                                         where type_id=1 and 
                                        create_time>='.$time['from_time'].'  and 
                                        create_time<'.$time['to_time'];
        if(!empty($albn)){
            $ss.=' and bn not in ('.$con.')';
        }
        $ss.=' GROUP BY bn ';
        $p_bn = kernel::database()->select($ss);                                
        if(!empty($p_bn)){
            foreach($p_bn as $pbn){
                $goodsbn=$pbn['pbn'];
                $sql='select p.name as name,p.goods_id as goods_id, p.spec_info as spec_info ,p.store as store From sdb_ome_products as p where p.bn= \''.$goodsbn.'\'';
                $product_info = $db->select($sql);
                $pro_info = $product_info[0];
                $gsql='select g.brand_id as brand_id From sdb_ome_goods as g where g.goods_id= \''.$pro_info['goods_id'].'\'';  
                $goods_info = $db->select($gsql);       
                $gInfo = $goods_info[0];
                $sql_insert0 ='';
                $sales=array();

                    $sales['goods_bn'] = $goodsbn;
                    $sales['name'] = $pro_info['name'];
                    $sales['brand']['brand_id'] = $gInfo['brand_id'];
                    $sales['sales_num'] = $bn['nums'];
                    $sales['purchase_num'] = $pbn['pnum'];
                    $sales['allocation_num'] = 0;
                    $sales['spec_info'] = $pro_info['spec'];
                //  $sales['shop']['shop_id'] = $data['shop_id'];
                    $sales['store'] = $pro_info['store'];
                    $sales['createtime'] = $time['from_time'];
                    $goodsamount_obj = &app::get('omeanalysts')->model('ome_goodsamount');
                    $r=$goodsamount_obj->insert($sales);

                /*  if($value-1 != $k){
                        $sql_insert0 = "('".$sales['goods_bn']."','".$sales['name']."','".$sales['brand']['brand_id']."','".$sales['sales_num']."','".$sales['purchase_num']."','".$sales['allocation_num']."','".$sales['spec_info']."','".$sales['store']."','".$sales['createtime']."'),";
                        $sql_insert .= $sql_insert0;
                    }else{
                        $sql_insert0 = "('".$sales['goods_bn']."','".$sales['name']."','".$sales['brand']['brand_id']."','".$sales['sales_num']."','".$sales['purchase_num']."','".$sales['allocation_num']."','".$sales['spec_info']."','".$sales['store']."','".$sales['createtime']."')";
                        $sql_insert .= $sql_insert0;
                        kernel::database()->exec($sql_insert);
                    }*/


            }
        }
        $sq='select bn as xbn ,sum(nums) as xnum from 
                                        sdb_ome_iostock where 
                                        type_id=4 and 
                                        create_time>='.$time['from_time'].'  and 
                                        create_time<'.$time['to_time']; 
        if(!empty($albn)){
            $sq.=' and bn not in ('.$con.')';
        }
        $sq.=' GROUP BY bn ';           
        $x_bn = kernel::database()->select($sq);
        if(!empty($x_bn)){
            
            foreach($x_bn as $xbn){
                    $sql_x = 'SELECT * FROM sdb_omeanalysts_ome_goodsamount as g 
                    WHERE g.goods_bn=\''.$xbn.'\'   and 
                        createtime>='.$time['from_time'].'  and 
                        createtime<'.$time['to_time'];
                    $goodsamount = $db->select($sql_x); 
                    if(empty($goodsamount)){
                        $goodsbn=$xbn['xbn'];
                        $sql='select p.name as name,p.goods_id as goods_id, p.spec_info as spec_info ,p.store as store From sdb_ome_products as p where p.bn= \''.$goodsbn.'\'';
                        $product_info = $db->select($sql);
                        $pro_info = $product_info[0];
                        $gsql='select g.brand_id as brand_id From sdb_ome_goods as g where g.goods_id= \''.$pro_info['goods_id'].'\'';  
                        $goods_info = $db->select($gsql);       
                        $gInfo = $goods_info[0];
                        $sa=array();
                        $sa['goods_bn'] = $goodsbn;
                        $sa['name'] = $pro_info['name'];
                        $sa['brand']['brand_id'] = $gInfo['brand_id'];
                        $sa['sales_num'] = $bn['nums'];
                        $sa['purchase_num'] = $pbn['pnum'];
                        $sa['allocation_num'] = 0;
                        $sa['spec_info'] = $pro_info['spec'];
                        //$sales['shop']['shop_id'] = $data['shop_id'];
                        $sa['store'] = $pro_info['store'];
                        $sa['createtime'] = $time['from_time'];
                        $goodsamount_obj = &app::get('omeanalysts')->model('ome_goodsamount');
                        $r=$goodsamount_obj->insert($sales);
                    }else{
                        $goodsamount['allocation_num'] = $xbn['xnum'];
                        $goodsamount_obj = &app::get('omeanalysts')->model('ome_goodsamount');
                        $r=$goodsamount_obj->update($goodsamount);
                    }


            }
        }
    }

    
}
?>