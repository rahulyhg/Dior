<?php
class omeftp_mdl_ftplog extends dbeav_model{

    function searchOptions(){
        return array(
                'order_bn'=>app::get('base')->_('订单号'),
            );
    }

	function _filter($filter,$tableAlias=null,$baseWhere=null){
		if(isset($filter['order_bn'])){
			$obj = app::get('omeftp')->model('filelog');
			$info = $obj->db->select("select * from sdb_omeftp_filelog where content like '%".$filter['order_bn']."%'");
			if(empty($info)){
				$filter['file_local_route'] = '-1';
			}else{
				$file_routes = array();
				foreach($info as $row){
					$file_routes[] = $row['file_route'];
				}
				$filter['file_local_route'] = $file_routes;
			}
			unset($filter['order_bn']);
		}
		return parent::_filter($filter,$tableAlias=null,$baseWhere=null);
	}
}
