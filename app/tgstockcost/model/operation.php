<?php
class tgstockcost_mdl_operation extends dbeav_model
{
    function history_data(){
        $all_history_data = $this->getList('tgstockcost_cost,tgstockcost_get_value_type,install_time,status',array(),0,-1,'operation_id  desc');
        if(!empty($all_history_data)){
            foreach($all_history_data as $key=>$v){
                $all_history_data[$key]['tgstockcost_cost'] = $this->getTgCostInfo('tgstockcost_cost',$v['tgstockcost_cost']);
                $all_history_data[$key]['tgstockcost_get_value_type'] = $this->getTgCostInfo('tgstockcost_get_value_type',$v['tgstockcost_get_value_type']);
            }       
        }
        return $all_history_data;
        
    }
    #成本计价法的配置
    function getTgCostInfo($type=null,$key=null){
        if($type == 'tgstockcost_cost'){
            $name[1] = "不计成本法";
            $name[2] = "固定成本法";
            $name[3] = "平均成本法";
            $name[4] = "先进先出法";
        }else if($type == 'tgstockcost_get_value_type'){
            $name[1] = "取货品的固定成本";
            $name[2] = "取货品的单位平均成本";
            $name[3] = "取货品的最近一次出入库成本";
            $name[4] = "取0";
        }
        return $name[$key];
    }
    #检测查询日期是否合法
    function checkedDate($from_time,$to_time){
       $from_time = strtotime($from_time);
       $to_time = strtotime($to_time);
        #检查起始成本
        $install_time = &app::get("ome")->getConf("tgstockcost_install_time");
        if (!$install_time) {
            return 'succ';
        }

        if($from_time <  $install_time){
            #提示：查询时间不能小于安装时间
            return 'time_less';
        }
        #检查历史成本设置
        $sql = 'select  operation_id  from sdb_tgstockcost_operation where install_time<='.$from_time.'   and end_time>='.$to_time.' and status=\'0\' AND `type`="1"';
        $rs  = $this->db->select($sql);
        #检查当前成本设置
        if(empty($rs)){
            $sql = 'select  operation_id  from sdb_tgstockcost_operation where install_time<='.$from_time.' and status=\'1\' AND `type`="1"';
            $rs  = $this->db->selectRow($sql);
            if(empty($rs)){
                 #提示：查询时间不能跨成本法
                 return 'time_cross';
            }
        }
        return 'succ';
    }
}