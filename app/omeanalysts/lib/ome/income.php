<?php
class omeanalysts_ome_income extends eccommon_analysis_abstract implements eccommon_analysis_interface{
    public $type_options = array(
        'display' => 'true',
    );
    public $logs_options = array(
        '1' => array(
            'name' => '收款额',
            'flag' => array(),
            'memo' => '支付单支付金额总计',
            'icon' => 'money.gif',
        ),
        '2' => array(
            'name' => '退款额',
            'flag' => array(),
            'memo' => '退款单退款金额总计',
            'icon' => 'money_delete.gif',
        ),
        '3' => array(
            'name' => '差额',
            'flag' => array(),
            'memo' => '“收款额”减去“退款额”',
            'icon' => 'coins.gif',
        ),
    );

    public $graph_options = array(
        'hidden' => true,
    );


    public function get_type(){

        #店铺
        $shop_mdl = app::get("ome")->model("shop");
        $shop_datas = $shop_mdl->getList("shop_id,name");

        $shop_data[0] = '全部';
        foreach($shop_datas as $v){
           $shop_data[$v['shop_id']] = $v['name'];
        }

        #单据类型
        $billtype_mdl = $this->app->model("ome_income");
        $billtype_data = $billtype_mdl->get_billtype();

        #支付方式
        $payment_mdl = app::get("ome")->model("payment_cfg");
        $payment_datas = $payment_mdl->getList("custom_name");

        $payment_data[0] = '全部';
        foreach($payment_datas as $v){
           $payment_data[$v['custom_name']] = $v['custom_name'];
        }

        $return = array(
          'type_id'=>array(
            'lab' => '店铺',
            'data' => $shop_data,
            'type' => 'select',
          ),
          'bill_type'=>array(
            'lab' => '单据类型',
            'data' => $billtype_data,
            'type' => 'select',
          ),
          'paymethod'=>array(
            'lab' => '支付方式',
            'data' => $payment_data,
            'type' => 'select',
          ),
        );
        return $return;
    }

    public function ext_detail(&$detail){
        $filter = $this->_params;

        $incomeObj = $this->app->model('ome_income');
        $payMoney = $incomeObj->get_payMoney($filter); //收款额
        $refundMoney = $incomeObj->get_refundMoney($filter); //退款额
        $earn = $payMoney-$refundMoney; //差额

        $detail['收款额']['value'] = number_format($payMoney,2,"."," ");
        $detail['退款额']['value'] = number_format($refundMoney,2,"."," ");
        $detail['差额']['value'] = number_format($earn,2,"."," ");
    }

    public function headers(){
        parent::headers();

        if($this->type_options['display'] == 'true'){
            $this->_render->pagedata['type_display'] = 'true';
            $this->_render->pagedata['typeData'] = $this->get_type();
            $type_selected = array(
                                'type_id'=>$this->_params['type_id'],
                                'bill_type'=>$this->_params['bill_type'],
                                'paymethod'=>$this->_params['paymethod'],
                            );
            $this->_render->pagedata['type_selected'] = $type_selected;
        }

    }

    public function finder(){

        $_extra_view = array(
            'omeanalysts' => 'ome/income/extra_view.html',
        );

        $this->set_extra_view($_extra_view);

        $params =  array(
            'model' => 'omeanalysts_mdl_ome_income',
            'params' => array(
                'actions'=>array(
                    array(
                        'label'=>app::get('omeanalysts')->_('生成报表'),
                        'class'=>'export',
                        'icon'=>'add.gif',
                        'href'=>'index.php?app=omeanalysts&ctl=ome_analysis&act=income&action=export',
                        'target'=>'{width:600,height:300,title:\'生成报表\'}'),
                ),
                'title'=>app::get('omeanalysts')->_('订单收入统计<script>if($$(".finder-list").getElement("tbody").get("html") == "\n" || $$(".finder-list").getElement("tbody").get("html") == "" ){$$(".export").set("href","javascript:;").set("onclick", "alert(\"没有可以生成的数据\")");}else{$$(".export").set("href",\'index.php?app=omeanalysts&ctl=ome_analysis&act=income&action=export\');}</script>'),
                'use_buildin_recycle'=>false,
                'use_buildin_selectrow'=>false,
                'use_view_tab'=>true,
            ),
        );
        #增加报表导出权限
        $is_export = kernel::single('desktop_user')->has_permission('analysis_export');
        if(!$is_export){
            unset($params['params']['actions']);
        }
        return $params;
    }
}