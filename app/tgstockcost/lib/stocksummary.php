<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 * @version osc---hanbingshu sanow@126.com
 * @date 2012-0-03
 */
class tgstockcost_stocksummary extends eccommon_analysis_abstract implements eccommon_analysis_interface{
    public $detail_options = array( 'hidden' => false,'force_ext' => true);
    public $graph_options = array('hidden' => true,);
    public $type_options = array('display' => 'true',);

    public $logs_options = array(
        array('name' => '期初数量','flag' => array(),'memo' => '期初数量',),
        array('name' => '期初平均成本','flag' => array(),'memo' => '期初平均成本',),
        array('name' => '期初商品成本','flag' => array(),'memo' => '期初商品成本',),
        array('name' => '入库数量','flag' => array(),'memo' => '入库数量',),     
        array('name' => '入库平均成本','flag' => array(),'memo' => '入库平均成本',), 
        array('name' => '入库商品成本','flag' => array(),'memo' => '入库商品成本','br' => 'true'),

        array('name' => '期末数量','flag' => array(),'memo' => '期末数量',),
        array('name' => '期末平均成本','flag' => array(),'memo' => '期末平均成本',), 
        array('name' => '期末商品成本','flag' => array(),'memo' => '期末商品成本',), 
        array('name' => '出库数量','flag' => array(),'memo' => '出库数量',),
        array('name' => '出库平均成本','flag' => array(),'memo' => '出库平均成本',),
        array('name' => '出库商品成本','flag' => array(),'memo' => '出库商品成本',),
    );

    function __construct(&$app)
    {
        parent::__construct($app);


        // if(empty($stockcost_install_time)) $stockcost_install_time = time(); 
        
        // if(!$_POST['time_from']) $this->_render->pagedata['time_from'] = (time()-(date('w')?date('w')-1:6)*86400);
        // if(!$_POST['time_to']) $this->_render->pagedata['time_to'] = time();

        $this->_extra_view = array('tgstockcost' => 'admin/stocksummary_header.html');
    }

    public function get_type(){

        #仓库
        $branch_mdl = app::get("ome")->model("branch");
        $branch_datas = $branch_mdl->getList("branch_id,name");

        #$branch_data[0] = '全部';
        foreach($branch_datas as $v){
           $branch_data[$v['branch_id']] = $v['name'];
        }

        #品牌
        $brand_mdl = app::get("ome")->model("brand");
        $brand_datas = $brand_mdl->getList("brand_id,brand_name");
        $brand_data[0] = '全部';
        foreach($brand_datas as $v){
           $brand_data[$v['brand_id']] = $v['brand_name'];
        }

        #商品类型
        $gtype_mdl = app::get("ome")->model("goods_type");
        $gtype_datas = $gtype_mdl->getList("type_id,name");
        $gtype_data[0] = '全部';
        foreach($gtype_datas as $v){
           $gtype_data[$v['type_id']] = $v['name'];
        }

        $return = array(
          'branch_id'=>array(
            'lab' => '仓库',
            'data' => $branch_data,
            'type' => 'select',
          ),
          'product_bn'=>array(
            'lab' => '货号',
            'type' => 'text',
          ),
          'goods_bn'=>array(
            'lab' => '商品编号',
            'type' => 'text',
          ),
          'product_name'=>array(
            'lab' => '货品名称',
            'type' => 'text',
          ),
          'brand'=>array(
            'lab' => '品牌',
            'data' => $brand_data,
            'type' => 'select',
          ),
          'type_id'=>array(
            'lab' => '商品类型',
            'data' => $gtype_data,
            'type' => 'select',
          ),
        );
        return $return;
    }

    public function headers(){
        $this->_render->pagedata['title'] = $this->_title;

        if($this->type_options['display'] == 'true'){

            $this->_render->pagedata['type_display'] = 'true';
            $this->_render->pagedata['typeData'] = $this->get_type();
            $this->_render->pagedata['type_selected'] = array(
                                                'branch_id'    => $this->_params['branch_id'],
                                                'product_bn'   => $this->_params['product_bn'],
                                                'goods_bn'     => $this->_params['goods_bn'],
                                                'product_name' => $this->_params['product_name'],
                                                'brand'        => $this->_params['brand'],
                                                'type_id'      => $this->_params['type_id'],
                                            );


            $this->_render->pagedata['time_from'] = $this->_params['time_from'] ? $this->_params['time_from'] : (time()-(date('w')?date('w')-1:6)*86400);
            $this->_render->pagedata['time_to']   = $this->_params['time_to'] ? $this->_params['time_to'] : time();
        }

        $stockcost_install_time = app::get("ome")->getConf("tgstockcost_install_time");
        $this->_render->pagedata['install_time'] = strtotime(date('Y-m-d',$stockcost_install_time)).'000';

        $this->_render->pagedata['date_check'] = $this->_params['date_check'] == true ? true : false;
    }


    /**
     * 去首尾空格
     *
     * @param Array
     * @return Array
     * @author 
     **/
    static function trim(&$arr)
    {        
        foreach ($arr as $key => &$value) {
            if (is_array($value)) {
                self::trim($value);
            } elseif (is_string($value)) {
                $value = trim($value);
            }
        }
    }

    /*设置参数*/
    public function set_params($params)
    {
        self::trim($params);
        
        $this->_params = $params;

        // $time_from = date("Y-m-d", time()-(date('w')?date('w')-1:6)*86400);
        $time_from = date('Y-m-d',time());
        $time_to = date('Y-m-d',time());

        $branch_mdl = app::get("ome")->model("branch");
        $branch_data = $branch_mdl->getList("branch_id");
        $this->_params['branch_id'] = ($this->_params['branch_id']) ? $this->_params['branch_id'] : $branch_data[0]['branch_id'];

        $this->_params['time_from'] = ($this->_params['time_from']) ? $this->_params['time_from'] : $time_from;
        $this->_params['time_to'] = ($this->_params['time_to']) ? $this->_params['time_to'] : $time_to;
        return $this;
    }//End F


    public function finder(){
        // $_instance = kernel::single("tgstockcost_instance_branchproduct");
        // $href_params = $this->_params;
        // $href_params['app'] = 'tgstockcost';
        // $href_params['mdl'] = 'branch_product';
        // $href = $_instance->get_export_href($href_params);
        // $_GET['_finder']['finder_id'] = $finder_id = substr(md5($_SERVER['QUERY_STRING']),5,6);

        $url = base_request::get_request_uri().'&action=export';

        if($_GET['act'] == 'index'){
            $params = array(
                'actions'=>array(
                    array(
                        'label'   => '导出',
                        'href' => $url,
                        'target'  => "{width:600,height:300,title:'导出'}",
                        'class' => 'export',
                    ),
                ),
                'title'                 => app::get('tgstockcost')->_('进销存<script>if($$(".finder-list").getElement("tbody").get("html") == "\n" || $$(".finder-list").getElement("tbody").get("html") == "" ){$$(".export").set("href","javascript:;").set("onclick", "alert(\"没有可以导出的数据\")");}else{$$(".export").set("href",\'index.php?app=tgstockcost&ctl=stocksummary&act=index&action=export\');}</script>'),
                'use_buildin_recycle'   => false,
                'use_buildin_selectrow' => false,  
                'use_buildin_filter'    => false,
            );
        }elseif($_GET['act'] == 'sellstorage'){
            $params = array(
                'actions'=>array(
                    array(
                        'label'   => '导出',
                        'href' => $url,
                        'target'  => "{width:600,height:300,title:'导出'}",
                        'class' => 'export',
                    ),
                ),
                'title'                 => app::get('tgstockcost')->_('进销存<script>if($$(".finder-list").getElement("tbody").get("html") == "\n" || $$(".finder-list").getElement("tbody").get("html") == "" ){$$(".export").set("href","javascript:;").set("onclick", "alert(\"没有可以导出的数据\")");}else{$$(".export").set("href",\'index.php?app=tgstockcost&ctl=stocksummary&act=sellstorage&action=export\');}</script>'),
                'use_buildin_recycle'   => false,
                'use_buildin_selectrow' => false,  
                'use_buildin_filter'    => false,
            );
        }

        #增加报表导出权限
        $is_export = kernel::single('desktop_user')->has_permission('analysis_export');
        if(!$is_export){
            unset($params['actions']);
        }

        return array(
            'model' => 'tgstockcost_mdl_branch_product',
            'params' => $params,
        );
    }

    public function ext_detail(&$detail){
        foreach($this->logs_options AS $target=>$option){
            $detail[$option['name']]['value'] = 0;
            $detail[$option['name']]['memo']  = $this->logs_options[$target]['memo'];
            $detail[$option['name']]['icon']  = $this->logs_options[$target]['icon'];
        }

        $filter = $this->_params;

        $total_start_nums = $total_start_unit_cost = $total_start_inventory_cost = 0;
        $total_in_nums    = $total_in_unit_cost = $total_in_inventory_cost = 0;
        $total_out_nums   = $total_out_unit_cost = $total_out_inventory_cost = 0;
        $total_store      = $total_unit_cost = $total_inventory_cost = 0;

        $re = kernel::single("tgstockcost_taog_branchproduct")->getTotalCostInfo($filter);
        $total_start_nums           = $re['total_start_nums'];
        $total_start_inventory_cost = $re['total_start_inventory_cost'];
        $total_in_nums              = $re['total_in_nums'];
        $total_in_inventory_cost    = $re['total_in_inventory_cost'];
        $total_out_nums             = $re['total_out_nums'];
        $total_out_inventory_cost   = $re['total_out_inventory_cost'];
        $total_store                = $re['total_store'];
        $total_inventory_cost       = $re['total_inventory_cost'];

        $total_start_unit_cost = ($total_start_nums!=0) ? bcdiv($total_start_inventory_cost,$total_start_nums,2) : 0;
        $total_in_unit_cost    = ($total_in_nums!=0) ? bcdiv($total_in_inventory_cost,$total_in_nums,2) : 0;
        $total_out_unit_cost   = ($total_out_nums!=0) ? bcdiv($total_out_inventory_cost,$total_out_nums,2) : 0;
        $total_unit_cost       = ($total_store!=0) ? bcdiv($total_inventory_cost,$total_store,2) : 0;

        if (isset($detail['期初数量']))     $detail['期初数量']['value']   = $total_start_nums;
        if (isset($detail['期初平均成本'])) $detail['期初平均成本']['value'] = '￥'.$total_start_unit_cost;
        if (isset($detail['期初商品成本'])) $detail['期初商品成本']['value'] = '￥'.$total_start_inventory_cost;
        if (isset($detail['入库数量']))     $detail['入库数量']['value']   = $total_in_nums;
        if (isset($detail['入库平均成本'])) $detail['入库平均成本']['value'] = '￥'.$total_in_unit_cost;
        if (isset($detail['入库商品成本'])) $detail['入库商品成本']['value'] = '￥'.$total_in_inventory_cost;
        if (isset($detail['出库数量']))     $detail['出库数量']['value']   = $total_out_nums;
        if (isset($detail['出库平均成本'])) $detail['出库平均成本']['value'] = '￥'.$total_out_unit_cost;
        if (isset($detail['出库商品成本'])) $detail['出库商品成本']['value'] = '￥'.$total_out_inventory_cost;
        if (isset($detail['期末数量']))     $detail['期末数量']['value']   = $total_store;
        if (isset($detail['期末平均成本'])) $detail['期末平均成本']['value'] = '￥'.$total_unit_cost;
        if (isset($detail['期末商品成本'])) $detail['期末商品成本']['value'] = '￥'.$total_inventory_cost;
    }

    public function detail()
    {
        // 验证是否具有读库存成本权限
        // $permission = kernel::single('desktop_user')->has_permission('tgstockcost_stocksummary_cost');
        if ($_GET['act'] == 'sellstorage' && $_GET['ctl'] == 'stocksummary' && $_GET['app'] == 'tgstockcost') {
            $detail_cost = array('期初平均成本','期初商品成本','入库平均成本','入库商品成本','出库平均成本','出库商品成本','期末平均成本','期末商品成本');
            foreach($this->logs_options AS $target=>$option){
                if (in_array($option['name'], $detail_cost)) {
                    unset($this->logs_options[$target]);
                }
            }
        }

        parent::detail();

        foreach($this->logs_options AS $target=>$option){
            if (isset($option['br'])) {
                $this->_render->pagedata['detail'][$option['name']]['br'] = $option['br'];
            }
        }
    }

}