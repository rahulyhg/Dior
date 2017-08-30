<?php
class omeanalysts_crontab_script_rmatype{


    public function statistics(){ 
        @ini_set('memory_limit','256M');
        set_time_limit(0);
        $db = kernel::database();
        $old_time = app::get('omeanalysts')->getConf('old_time.rmatype_time');//获取上次脚本执行时间
        $new_time = time();
        $ntime = date('Y-m-d',$new_time);
        $hour = date("H",$new_time);
        $minute = date("i",$new_time);

        if(!$old_time){
            $db->exec('DELETE FROM sdb_omeanalysts_ome_rmatype');
            app::get('omeanalysts')->setConf('old_time.rmatype_time', $new_time);
            $old_time = $new_time - 86400;
        }else {
            app::get('omeanalysts')->setConf('old_time.rmatype_time', $new_time);
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


        $sql0 = "select count(*) From sdb_ome_reship";

        $count = $db->count($sql0);
        $page = 1;
        $limit = 1000;
        $pagecount = ceil($count/$limit);

        foreach($times as $time){
            for($page=1;$page<=$pagecount;$page++){
                        $datas = array();
                        $lim = ($page-1) * $limit;
                        $sql='select count(*) as rnum , r.problem_id as problem_id , r.shop_id as shop_id from sdb_ome_return_product_problem as pp
                            left join sdb_ome_reship as r on pp.problem_id=r.problem_id where r.t_begin>='.$time['from_time'].' and r.t_end<'.$time['to_time']. ' GROUP BY pp.problem_id , r.shop_id LIMIT '. $lim.','.$limit.' ';
                        $info = $db->select($sql);
                        if(empty($info)){
                            continue;
                        }else{
                                foreach($info as $k => $v){
                                    $datas[] = $v;
                                }


                                foreach($datas as $data){
                                    $rmatype['problem_id'] = $data['problem_id'];
                                    $rmatype['shop_id'] = $data['shop_id'];
                                    $rmatype['num'] = ($data['rnum']) ? $data['rnum'] : 0;
                                    $rmatype['createtime'] = $time['from_time'];

                                    $rmatype_obj = &app::get('omeanalysts')->model('ome_rmatype');
                                    $rmatype_obj->insert($rmatype);
                                }
                        }

             }

        }


    }


}