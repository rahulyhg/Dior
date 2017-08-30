<?php
class omeanalysts_ome_shop extends eccommon_analysis_abstract implements eccommon_analysis_interface{
    public $report_type = 'true';
    public $type_options = array(
        'display' => 'true',
    );
    public $logs_options = array(
        '1' => array(
            'name' => '销售单数',
            'flag' => array(),
            'memo' => '销售单数',
            'icon' => 'money.gif',
            'col'  => '1',
        ),
        '2' => array(
            'name' => '销售货品数',
            'flag' => array(),
            'memo' => '销售货品数',
            'icon' => 'money.gif',
            'col'  => '2',
        ),
        '3' => array(
            'name' => '销售金额',
            'flag' => array(),
            'memo' => '销售金额',
            'icon' => 'coins.gif',
            'col'  => '3',
        ),
        '4' => array(
            'name' => '售后单数',
            'flag' => array(),
            'memo' => '售后单数',
            'icon' => 'money.gif',
            'col'  => '4',
        ),
        '5' => array(
            'name' => '售后货品数',
            'flag' => array(),
            'memo' => '售后货品数',
            'icon' => 'money.gif',
            'col'  => '5',
        ),
        '6' => array(
            'name' => '退款金额',
            'flag' => array(),
            'memo' => '退款金额',
            'icon' => 'coins.gif',
            'col'  => '6',
        ),
        '7' => array(
            'name' => '合计金额',
            'flag' => array(),
            'memo' => '合计金额',
            'icon' => 'coins.gif',
            'col'  => '7',
        ),
    );
    public $graph_options = array(
        'hidden' => true,
        'iframe_height' => 180,
        'callback' => 'omeanalysts_ome_graph_month',
    );

    public function __construct(&$app){
        parent::__construct($app);
        $typeObj = $this->app->model('ome_type');
        $flagList = $typeObj->get_shop();
        foreach($this->logs_options as $key=>$val){
            $this->logs_options[$key]['flag'][0] = '全部';
            foreach($flagList as $shop){
                $this->logs_options[$key]['flag'][$shop['relate_id']] = $shop['name'];
            }
        }
    }

    public function get_type(){
        $typeObj = $this->app->model('ome_type');
        $shop_data = $typeObj->get_shop();

        $return = array(
          'type_id'=>array(
            'lab' => '店铺',
            'data' => $shop_data,
          ),
        );

        return $return;
    }


    public function get_logs($time){
        $filter = $this->_params;

/** 依据 所有店铺 来统计数据 **/
        $filter = array(
            'time_from' => date('Y-m-d',$time),
            'time_to' => date('Y-m-d',$time),
        );

        $shopObj = $this->app->model('ome_shop');

        $all = $shopObj->add_shoplog($filter);

        $result[] = array('type'=>0, 'target'=>1, 'flag'=>0, 'value'=>$all['sale_order']);
        $result[] = array('type'=>0, 'target'=>2, 'flag'=>0, 'value'=>$all['sale_num']);
        $result[] = array('type'=>0, 'target'=>3, 'flag'=>0, 'value'=>$all['sale_amount']);
        $result[] = array('type'=>0, 'target'=>4, 'flag'=>0, 'value'=>$all['aftersale_order']);
        $result[] = array('type'=>0, 'target'=>5, 'flag'=>0, 'value'=>$all['aftersale_num']);
        $result[] = array('type'=>0, 'target'=>6, 'flag'=>0, 'value'=>$all['aftersale_amount']);
        $result[] = array('type'=>0, 'target'=>7, 'flag'=>0, 'value'=>$all['total_amount']);

/** 依据 各个店铺 来统计数据 **/

        $typeObj = $this->app->model('ome_type');
        $flagList = $typeObj->get_shop();
        foreach($flagList as $key=>$val){

            $filter['type_id'] = $val['type_id'];
            $shop = $shopObj->add_shoplog($filter);

            $result[] = array('type'=>0, 'target'=>1, 'flag'=>$val['relate_id'], 'value'=>$shop['sale_order']);
            $result[] = array('type'=>0, 'target'=>2, 'flag'=>$val['relate_id'], 'value'=>$shop['sale_num']);
            $result[] = array('type'=>0, 'target'=>3, 'flag'=>$val['relate_id'], 'value'=>$shop['sale_amount']);
            $result[] = array('type'=>0, 'target'=>4, 'flag'=>$val['relate_id'], 'value'=>$shop['aftersale_order']);
            $result[] = array('type'=>0, 'target'=>5, 'flag'=>$val['relate_id'], 'value'=>$shop['aftersale_num']);
            $result[] = array('type'=>0, 'target'=>6, 'flag'=>$val['relate_id'], 'value'=>$shop['aftersale_amount']);
            $result[] = array('type'=>0, 'target'=>7, 'flag'=>$val['relate_id'], 'value'=>$shop['total_amount']);

        }

        return $result;
    }


    public function ext_detail(&$detail){

        $filter = $this->_params;

        $shops = $this->app->model('ome_shop')->getlist('*',$filter);

        $sale_order = $sale_num = $sale_amount = $aftersale_order = 0;
        $aftersale_num = $aftersale_amount = $total_amount = 0;

        foreach ($shops as $shop) {
            $sale_order        += $shop['sale_order'];
            $sale_num          += $shop['sale_num'];
            $sale_amount       += $shop['sale_amount'];
            $aftersale_order   += $shop['aftersale_order'];
            $aftersale_num     += $shop['aftersale_num'];
            $aftersale_amount  += $shop['aftersale_amount'];
            $total_amount      += $shop['total_amount'];
        }

        $detail['销售单数']['value'] = $sale_order;
        $detail['销售货品数']['value'] = $sale_num;
        $detail['销售金额']['value'] = "￥".$sale_amount;
        $detail['售后单数']['value'] = $aftersale_order;
        $detail['售后货品数']['value'] = $aftersale_num;
        $detail['退款金额']['value'] = "￥".$aftersale_amount;
        $detail['合计金额']['value'] = "￥".$total_amount;

    }

    public function finder(){

        $_extra_view = array(
            'omeanalysts' => 'ome/extra_view.html',
        );

        $this->set_extra_view($_extra_view);

        $params =  array(
            'model' => 'omeanalysts_mdl_ome_shop',
            'params' => array(
                'actions'=>array(
                    'export'=>array(
                        'label'=>app::get('omeanalysts')->_('导出报表'),
                        'class'=>'export',
                        'icon'=>'add.gif',
                        'href'=>'index.php?app=omeanalysts&ctl=ome_analysis&act=shop&action=export',
                        'target'=>'{width:600,height:300,title:\'导出报表\'}'
                    ),
                    array(
                        'label'=>app::get('omeanalysts')->_('重新生成报表'),
                        'href'=>'index.php?app=omeanalysts&ctl=ome_analysis&act=regenerate_report&p[0]=shop&p[1]=regenerate',
                        'target'=>'dialog::{width:400,height:170,title:\'重新生成报表\'}',
                    ),                    
                ),
                'title'=>app::get('omeanalysts')->_('店铺每日汇总<script>if($$(".finder-list").getElement("tbody").get("html") == "\n" || $$(".finder-list").getElement("tbody").get("html") == "" ){$$(".export").set("href","javascript:;").set("onclick", "alert(\"没有可以生成的数据\")");}else{$$(".export").set("href",\'index.php?app=omeanalysts&ctl=ome_analysis&act=shop&action=export\');}</script>'),
                'use_buildin_recycle'=>false,
                'use_buildin_selectrow'=>false,
            ),
        );
        #增加报表导出权限
        $is_export = kernel::single('desktop_user')->has_permission('analysis_export');
        if(!$is_export){
            unset($params['params']['actions']['export']);
        }
        return $params;
    }

   public function detail() 
    {
        if($this->detail_options['hidden'] == true){
            $this->_render->pagedata['detail_hidden'] = 1;
            return false;
        }
        $detail = array();

        foreach($this->logs_options AS $target=>$option){
            $detail[$option['name']]['value'] = ($tmp[$target]) ? $tmp[$target] : 0;
            $detail[$option['name']]['memo'] = $this->logs_options[$target]['memo'];
            $detail[$option['name']]['icon'] = $this->logs_options[$target]['icon'];
            $detail[$option['name']]['col'] = $target;
        }

        if(method_exists($this, 'ext_detail')){
            $this->ext_detail($detail);
        }
        foreach($detail AS $key=>$val){
            $name = $this->app->_($key);
            $data[$name]['value'] = $val['value'];
            $data[$name]['memo'] = $this->app->_($val['memo']);
            $data[$name]['icon'] = $val['icon'];
            $data[$name]['col'] = $val['col'];
        }
        $this->_render->pagedata['detail'] = $data;
        return true;
    }//End Function

    public function headers() 
    {
        parent::headers();
        if($this->type_options['display'] == 'true'){
            $this->_render->pagedata['type_display'] = 'true';
            $this->_render->pagedata['typeData'] = $this->get_type();
            $type_selected = array(
                                'type_id'=>$this->_params['type_id']
                            );
            $this->_render->pagedata['type_selected'] = $type_selected;
        }
    }//End Function

}