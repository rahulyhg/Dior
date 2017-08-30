<?php
class omeanalysts_ctl_ome_productSaleRank extends desktop_controller{

    public function __construct($app){
        parent::__construct($app);
        $timeBtn = omeanalysts_func::timeBtn();
        $this->pagedata['timeBtn'] = $timeBtn;

    }

    function index(){
    	//产品销售排行榜crontab的手动
    	//kernel::single('omeanalysts_crontab_script_productSaleRank')->statistics();
        if(empty($_POST)){
            $time_from = strtotime(date("Y-m-1"));
            $time_to = strtotime(date("Y-m-d 23:59:59",time()-24*60*60));
            $this->pagedata['time_from'] = $time_from;
            $this->pagedata['time_to'] = $time_to;
        }else{
            $time_from = strtotime($_POST['time_from']);
            $time_to = strtotime($_POST['time_to'].' 23:59:59');
            $this->pagedata['time_from'] = $time_from;
            $this->pagedata['time_to'] = $time_to;
        }
        // 店铺列表
        $shopModel = &app::get('ome')->model('shop');
        $shop_list = $shopModel->getList('name,shop_id');
        $this->pagedata['shop_list'] = $shop_list;

        // 放大镜数据
        if ($shop_list) {
            $rank_data = array();
            foreach ( $shop_list as $shop ){
                $result = $this->_get_sale_rank($shop['shop_id'],$time_from,$time_to);
                $rank_data[$shop['shop_id']] = array(
                    'title' => $shop['name'],
                    'categories' => '['.implode(',',$result['categories']).']',
                    'data' => '[{name: \'数量\',data: ['.implode(',', $result['data']).']}]'
                );
            }
        }
        $this->pagedata['rank_data']= $rank_data;

        $this->pagedata['form_action'] = 'index.php?app=omeanalysts&ctl=ome_productSaleRank&act=index';
        $this->pagedata['path']= '产品销售排行榜';
        $this->page('ome/product_sales.html');
    }

    /*
    *获取各店铺货号销售排行数据
    * @param $shop_id 店铺ID
    */
    function sale_rank(){
        $title = $_GET['title'];
        $categories = $_GET['categories'];
        $data = $_GET['data'];

        $this->pagedata['title'] = '\''.$title.'\'';
     	$this->pagedata['categories'] = $categories;
     	$this->pagedata['data'] = $data;

        $this->display('ome/map.html');
    }

    private function _get_sale_rank($shop_id,$time_from,$time_to){
        $shopModel = &app::get('ome')->model('shop');
        $shop_detail = $shopModel->dump(array('shop_id'=>$shop_id),'name');
        $shop_name = $shop_detail['name'];
        $sql = sprintf('SELECT bn,sum(sales_num) AS sales_num FROM `sdb_omeanalysts_products_sale` WHERE sales_time>=\'%s\' AND sales_time<=\'%s\' AND shop_id=\'%s\' GROUP BY bn ORDER BY sales_num desc,sales_amount desc LIMIT 0,10 ',$time_from,$time_to,$shop_id);
        $tmp = kernel::database()->select($sql);
        if ($tmp){
            $categories = array();
            $data = array();
            foreach ( $tmp as $val ){
                $categories[] = '\''.$val['bn'].'\'';
                $data[] = $val['sales_num'];
            }
        }else{
            $categories = $data = array('0','0','0','0','0','0','0','0','0','0');
        }
        $result = array(
            'title' => $shop_name,
            'categories' => $categories,
            'data' => $data,
        );
        return $result;
    }
    
    public function index2(){
    	$_POST['_params'] = $_GET['_params'];
        $params = array(
            'params' => array(
                'actions'=>array( 
                    array(
                    	 'class' => 'export',
                         'label' => '导出',
                         //'href' => 'index.php?app=omeio&ctl=admin_task&act=create_export&_params[app]=omeanalysts&_params[mdl]=analysis_catSaleStatis&_params[time_from]='.$filter['time_from'].'&_params[time_to]='.$filter['time_to'],
                         'href'=>'index.php?app=omeanalysts&ctl=ome_productsSale&act=index&action=export',
                         //'target' => "dialog::{width:400,height:170,title:'生成报表'}",
                         'target'=>'{width:400,height:170,title:\'生成报表\'}'
                     ),
                ),
                'title'=>app::get('omeanalysts')->_('商品类目销售对比统计<script>if($$(".finder-list").getElement("tbody").get("html") == "\n" || $$(".finder-list").getElement("tbody").get("html") == "" ){$$(".export").set("href","javascript:;").set("onclick", "alert(\"没有可以生成的数据\")");}else{$$(".export").set("href",\'index.php?app=omeanalysts&ctl=ome_catSaleStatis&act=index&action=export\');}</script>'),
                'use_buildin_recycle'=>false,
                'use_buildin_filter'=>true,
                'use_buildin_selectrow'=>false,
                'base_query_string'=>$base_query_string,
            ),
       );
       $this->finder('omeanalysts_mdl_ome_productsSale',$params);
       
    }

}
?>