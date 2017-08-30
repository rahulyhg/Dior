<?php
class omeanalysts_ome_orderSteamItems extends eccommon_analysis_abstract implements eccommon_analysis_interface{

    public $graph_options = array(
        'hidden' => true,
    );
    public $type_options = array(
        'display' => 'false',
    );

    public $detail_options = array(
        'hidden' => true,
        'force_ext' => true,
    );

    function __construct(&$app)
    {
        parent::__construct($app);
    }

    public function headers(){

        parent::headers();

        if($this->type_options['display'] == 'true'){
            $this->_render->pagedata['type_display'] = 'true';
            $this->_render->pagedata['typeData'] = $this->get_type();
            $type_selected = array(
                                'shop_id'=>$this->_params['shop_id'],
                                'branch_id'=>$this->_params['branch_id'],
                                'brand_id'=>$this->_params['brand_id'],
                                'goods_type_id'=>$this->_params['goods_type_id'],
                            );
            $this->_render->pagedata['type_selected'] = $type_selected;
        }

    }


    public function finder(){

        $_extra_view = array(
            'omeanalysts' => 'ome/extra_view.html',
        );

        $this->set_extra_view($_extra_view);

        $params =  array(
            'model' => 'omeanalysts_mdl_ome_orderSteamItems',
            'params' => array(
                'actions'=>array(
                     array(
                          'class' => 'export',
                         'label' => '生成报表',
                         'href'=>'index.php?app=omeanalysts&ctl=ome_analysis&act=order_stream_item&action=export',
                         'target'=>'{width:600,height:300,title:\'生成报表\'}'
                     ),
                ),
                'title'=>app::get('omeanalysts')->_('订单明细<script>if($$(".finder-list").getElement("tbody").get("html") == "\n" || $$(".finder-list").getElement("tbody").get("html") == "" ){$$(".export").set("href","javascript:;").set("onclick", "alert(\"没有可以生成的数据\")");}else{$$(".export").set("href",\'index.php?app=omeanalysts&ctl=ome_analysis&act=order_stream_item&action=export\');}</script>'),
                'use_buildin_recycle'=>false,
                'use_buildin_selectrow'=>false,
                'use_buildin_filter'=>false,
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