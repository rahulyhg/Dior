<?php

//swise 平均成本

class tgstockcost_crontab_swise
{

    function set()
    {
        $offset = 0;

        $database = app::get("ome")->model("iostock");

        $ext_row = $database->db->select('select count(id) from sdb_ome_dailystock');

        while($this->_generate_dailystock($offset,$database)){
            $offset++;
        }

        return true;
    }

    function _generate_dailystock($offset,$database){

        $limit = 10000;

        $stockcost_install_time = app::get("ome")->getConf("tgstockcost_install_time");

        $ext_row = $database->db->select('select id,stock_date,branch_id,product_id,product_bn,stock_num from sdb_ome_dailystock limit '.$offset*$limit.','.$limit);
        
        if(!$ext_row) return false;

        foreach($ext_row as $k=>$v){
            
            $branch_p_data = $database->db->selectrow("select unit_cost from sdb_ome_branch_product where branch_id=".intval($v['branch_id'])." and product_id=".intval($v['product_id']));

            if( $v['stock_date'] == date('Y-m-d',$stockcost_install_time) ){//成本应用开启时的商品成本价格
                $data['unit_cost'] = $branch_p_data['unit_cost'];
                $data['inventory_cost'] = $branch_p_data['unit_cost']*$v['stock_num'];
                $data['is_change'] = '1';
            }else{
                $avg_unitcost = '';
                $data['unit_cost'] = $branch_p_data['unit_cost'];
                $data['inventory_cost'] = $v['stock_num']*$branch_p_data['unit_cost'];
                $data['is_change'] = $this->is_change($v['branch_id'],$v['product_id'],$data['unit_cost'],$database);
            }
            
            $sql = 'update sdb_ome_dailystock set unit_cost='.$data['unit_cost'].',inventory_cost='.$data['inventory_cost'].',is_change='.$data['is_change'].' where id = '.$v['id'];

            $database->db->exec($sql);
        }

        return true;        
    }
    
    /*与上一条记录的单位成本是否有变化*/
    function is_change($branch_id,$product_id,$unit_cost,$database)
    {
        $dailystock_last_row = $database->db->select("select unit_cost from sdb_ome_dailystock where product_id=".intval($product_id)." and branch_id=".intval($branch_id)." order by id DESC limit 0,1");

        if($unit_cost!=$dailystock_last_row[0]['unit_cost']) return 1;
        else return 0;
    }

}