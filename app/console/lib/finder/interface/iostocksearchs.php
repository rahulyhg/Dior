<?php
class console_finder_interface_iostocksearchs extends eccommon_analysis_abstract implements eccommon_analysis_interface{
    public $detail_options = array(
        'hidden' => true,
    );

    public $graph_options = array(
        'hidden' => true,
    );

    public function finder(){
        $filter = $this->_params;
        if(isset($filter['report']) && $filter['report']=='month'){
            $time_from = strtotime($filter['time_from']);
            $time_to = explode('-',$filter['time_to']);
            $filter['time_from'] = date("Y-m",$time_from).'-01';
            $filter['time_to'] = date("Y-m-d",mktime(0, 0, 0, $time_to[1]+1, 0, $time_to[0]));
        }
        $base_query_string = 'time_from='.$filter['time_from'].'&time_to='.$filter['time_to'];
        $rtrn =  array(
            'model' => 'console_mdl_interface_iostocksearchs',
            'params' => array(
                /*'actions'=>array(
                    array(
                        'label'=>app::get('ordersource')->_('生成报表'),
                        'class'=>'export',
                        'icon'=>'add.gif',
                        'href'=>'index.php?app=ome&ctl=admin_interface_iostocksearchs&act=index&action=export',
                        'target'=>'{width:400,height:170,title:\'生成报表\'}'),
               ),*/
                'title'=>app::get('ome')->_('库存异动查询'),
                'use_buildin_recycle'=>false,
                'use_buildin_filter' => true,
                'allow_detail_popup' => false,
                'base_query_string'=>$base_query_string,
            ),
        );
        return $rtrn;
    }
}