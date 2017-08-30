<?php
class omeanalysts_ome_goods extends eccommon_analysis_abstract implements eccommon_analysis_interface{
    public $report_type = 'true';
    public $type_options = array(
        'display' => 'true',
    );
    public $logs_options = array(
        '1' => array(
            'name' => '销售量',
            'flag' => array(),
            'memo' => '商品销售数量',
            'icon' => 'money.gif',
        ),
        '2' => array(
            'name' => '销售额',
            'flag' => array(),
            'memo' => '商品销售金额',
            'icon' => 'money_delete.gif',
        ),
        '3' => array(
            'name' => '退换货量',
            'flag' => array(),
            'memo' => '商品退换货数量',
            'icon' => 'coins.gif',
        ),
        '4' => array(
            'name' => '退换货率',
            'flag' => array(),
            'memo' => '退换量占销售量的比率',
            'icon' => 'coins.gif',
        ),
    );
    public $graph_options = array(
        'hidden' => false,
        'iframe_height' => 180,
        'callback' => 'omeanalysts_ome_graph_month',
    );

    public function get_type(){
        $lab = '店铺';
        $typeObj = $this->app->model('ome_type');
        $data = $typeObj->get_shop();
        $return = array(
            'lab' => $lab,
            'data' => $data,
        );
        return $return;
    }

    public function get_logs($time){
        $filter = $this->_params;
        $filter = array(
            'time_from' => date('Y-m-d',$time),
            'time_to' => date('Y-m-d',$time),
            'order_status' => $filter['order_status'],
        );

        $goodsObj = $this->app->model('ome_goods');
        $sale = $goodsObj->get_sale($filter);
        $reship_num = $goodsObj->get_reship($filter);
        $reship_ratio = $sale['sale_num']?number_format($reship_num/$sale['sale_num'],2):0;

        $result[] = array('type'=>0, 'target'=>1, 'flag'=>0, 'value'=>intval($sale['sale_num']));
        $result[] = array('type'=>0, 'target'=>2, 'flag'=>0, 'value'=>number_format($sale['sale_amount'],2,".",""));
        $result[] = array('type'=>0, 'target'=>3, 'flag'=>0, 'value'=>$reship_num);
        $result[] = array('type'=>0, 'target'=>4, 'flag'=>0, 'value'=>$reship_ratio);

        return $result;
    }

    public function ext_detail(&$detail){
        $filter = $this->_params;
        if(isset($filter['report']) && $filter['report']=='month'){
            $time_from = strtotime($filter['time_from']);
            $time_to = explode('-',$filter['time_to']);
            $filter['time_from'] = date("Y-m",$time_from).'-01';
            $filter['time_to'] = date("Y-m-d",mktime(0, 0, 0, $time_to[1]+1, 0, $time_to[0]));
        }
        $filter = array(
            'time_from' => $filter['time_from'],
            'time_to' => $filter['time_to'],
            'order_status' => $filter['order_status'],
            'type_id' => $filter['type_id'],
        );

        $goodsObj = $this->app->model('ome_goods');
        $sale = $goodsObj->get_sale($filter);
        $reship_num = $goodsObj->get_reship($filter);
        $reship_ratio = $sale['sale_num']?number_format($reship_num/$sale['sale_num'],2):0;

        $detail['销售量']['value'] = intval($sale['sale_num']);
        $detail['销售额']['value'] = number_format($sale['sale_amount'],2,"."," ");
        $detail['退换货量']['value'] = $reship_num;
        $detail['退换货率']['value'] = $reship_ratio;
    }

    public function finder(){
        $filter = $this->_params;
        if(isset($filter['report']) && $filter['report']=='month'){
            $time_from = strtotime($filter['time_from']);
            $time_to = explode('-',$filter['time_to']);
            $filter['time_from'] = date("Y-m",$time_from).'-01';
            $filter['time_to'] = date("Y-m-d",mktime(0, 0, 0, $time_to[1]+1, 0, $time_to[0]));
        }
        $base_query_string = 'time_from='.$filter['time_from'].'&time_to='.$filter['time_to'];
        return array(
            'model' => 'omeanalysts_mdl_ome_goods',
            'params' => array(
                'actions'=>array(
                    array(
                        'label'=>app::get('omeanalysts')->_('生成报表'),
                        'class'=>'export',
                        'icon'=>'add.gif',
                        'href'=>'index.php?app=omeanalysts&ctl=ome_analysis&act=goods&action=export',
                        'target'=>'{width:400,height:170,title:\'生成报表\'}'),
                ),
                'title'=>app::get('omeanalysts')->_('商品销售情况'),
                'use_buildin_recycle'=>false,
                'use_buildin_selectrow'=>false,
                'use_buildin_filter'=>true,
                'base_query_string'=>$base_query_string,
            ),
        );
    }
}