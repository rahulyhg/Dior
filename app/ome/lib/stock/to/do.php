<?php
class ome_stock_to_do{
    function run(&$cursor_id,$params){
        $stock_clObj = &app::get('ome')->model('stock_change_log');
        $start = (int)$cursor_id;
        $limit = 500;
        $data = $params['sdfdata'];
        $branch_id = $data['branch_id'];
        $branch = app::get('ome')->model('branch')->dump($branch_id);
        $safe_time = $branch['stock_safe_time'];
        
        $sql = "SELECT product_id FROM sdb_ome_stock_change_log 
                                    WHERE branch_id=$branch_id 
                                        GROUP BY product_id 
                                        ORDER BY create_time asc,log_id asc 
                                    LIMIT $start,$limit ";
        $rows = $stock_clObj->db->select($sql);
        $num = $start;
        if ($rows){
            foreach ($rows as $v){
                $p_id = $v['product_id'];
                if ($branch['stock_safe_type'] == 'branch'){
                    $day = $branch['stock_safe_day'];
                }elseif ($branch['stock_safe_type'] == 'supplier') {
                    $sql = "SELECT MIN(s.arrive_days) AS 'days' FROM sdb_ome_products p 
                                            JOIN sdb_purchase_supplier_goods sg 
                                                ON p.goods_id=sg.goods_id 
                                            JOIN sdb_purchase_supplier s 
                                                ON sg.supplier_id=s.supplier_id 
                                            WHERE p.product_id='".$v['product_id']."'";
                    
                    $dd = $stock_clObj->db->selectrow($sql);
                    $day = $dd['days'];
                }
                if ($day == '' || $day == 0){
                    $num++;
                    continue;
                }
                
                $sql = "SELECT MIN(create_time) AS 'time' FROM sdb_ome_stock_change_log";
                $ddd = $stock_clObj->db->selectrow($sql);                
                
                if ($safe_time == 0){
                    $today = $data['time'];
                    $ago = $today - $day*24*3600;
                }else {
                    $today = $data['time']+$safe_time*3600;
                    $ago = $data['time'] - ($day-1)*24*3600;
                }
                if ($ddd['time'] == '' || $ddd['time'] == 0){
                    $tmp_day = 1;
                }else {
                    $tmp_day = ceil(($today - $ddd['time'])/(24*3600));
                }
                
                $r_day = $day > $tmp_day ? $tmp_day : $day;
                
                $sql = "SELECT SUM(store) AS 'total' FROM sdb_ome_stock_change_log 
                                            WHERE product_id=$p_id 
                                                AND branch_id=$branch_id 
                                                AND (create_time >= $ago 
                                                AND create_time <= $today)";
                
                $row = $stock_clObj->db->selectrow($sql);
                
                
                $bp['branch_id'] = $branch_id;
                $bp['product_id'] = $p_id;
                $bp['safe_store'] = ceil($row['total']/$r_day)*$day;
                
                app::get('ome')->model('branch_product')->save($bp);
                $num++;
            }
            if ($num == ($start+$limit)){
                $cursor_id = $num;
                return true;
            }
        }
        return false;
    }
}