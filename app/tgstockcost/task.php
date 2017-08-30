<?php
class tgstockcost_task{

    function post_install(){
       // app::get("ome")->setConf("tgstockcost_install_time",time());//app安装时间放在OME APP里面 //todo 
       app::get("ome")->setConf("tgstockcost.installed",1);
    }
	function post_uninstall()
	{
		$iostock = app::get("ome")->model("iostock");
		$iostock->db->exec("update sdb_ome_iostock set unit_cost=0,inventory_cost=0,now_num=0,now_unit_cost=0,now_inventory_cost=0");
		$iostock->db->exec("update sdb_ome_branch_product set unit_cost=0,inventory_cost=0");
		$iostock->db->exec("delete from sdb_ome_dailystock");
		//$iostock->db->exec("delete from sdb_stockcost_fifo");
		app::get("ome")->setConf("tgstockcost.cost",'');
		app::get("ome")->setConf("tgstockcost.get_value_type",'');
		app::get("ome")->setConf("tgstockcost_install_time",'');
		app::get("ome")->setConf("tgstockcost.installed",0);
	}
}
