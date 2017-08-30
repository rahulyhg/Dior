<?php
class console_finder_stockaccount extends eccommon_analysis_abstract implements eccommon_analysis_interface{
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
        $res = '';

        foreach((array)$_POST as $k=>$v){

            if($k!='_DTYPE_DATE'){
               $res .='&'.$k.'='.$v;
            }
        }
        $rtrn =  array(
            'model' => 'console_mdl_stockaccount',
            'params' => array(
                'title'=>app::get('console')->_('库存对账查询'),
                'use_buildin_recycle'=>false,
                'use_buildin_filter' => true,
                'allow_detail_popup' => false,
                'use_buildin_selectrow' => false,
                'base_query_string'=>$base_query_string,
                'actions'=>array(
                    array(
                        'label'=>app::get('tgstockcost')->_('导出'),
                        'class'=>'export',
                        'icon'=>'add.gif',
                        'href'=>'index.php?app=console&ctl=admin_stockaccount&action=export'.$res,
                        'target'=>'{width:400,height:170,title:\'导出\'}'),
                ),
            ),
        );
        return $rtrn;
    }

}