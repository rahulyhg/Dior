<?php
class omeanalysts_mdl_ome_rmatype extends dbeav_model{
	
    public function get_rmatype($filter=null){
        //销售额
        //$sql = 'SELECT sum(r.num) as num , p.name as name FROM sdb_omeanalysts_ome_rmatype as r , sdb_ome_return_product_problem as p WHERE r.problem_id = p.problem_id and '.$this->_filter($filter) .' GROUP BY r.problem_id,p.name';

		$sql = 'SELECT sum(r.num) as num , p.problem_name as name FROM sdb_omeanalysts_ome_rmatype as r right join sdb_ome_return_product_problem as p on r.problem_id = p.problem_id and '.$this->_filter($filter) .' GROUP BY r.problem_id,p.problem_name';

        $row = $this->db->select($sql);
        return $row;
    }

	
	
	public function _filter($filter,$tableAlias=null,$baseWhere=null){
        $where = array(1);
        if(isset($filter['time_from']) && $filter['time_from']){
            $where[] = ' r.createtime >='.strtotime($filter['time_from']);
        }
        if(isset($filter['time_to']) && $filter['time_to']){
            $where[] = ' r.createtime <'.(strtotime($filter['time_to'])+86400);
        }
        if(isset($filter['problem_id']) && $filter['problem_id']){
            $where[] = ' r.bn LIKE \''.addslashes($filter['problem_id']).'%\'';
        }
		if(isset($filter['type_id']) && $filter['type_id'] && $filter['type_id']!=0){
            $where[] = ' r.shop_id LIKE \''.addslashes($filter['type_id']).'%\'';
        }

        return parent::_filter($filter,'r',$baseWhere)." AND ".implode($where,' AND ');
    }

}