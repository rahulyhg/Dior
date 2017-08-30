<?php
/**
* 发货备货单打印模型类
*
* @author chenping<chenping@shopex.cn>
* @version 2012-4-17 17:56
* @package print
*/
class ome_mdl_print_otmpl extends dbeav_model
{

    function __construct(&$app)
    {
        parent::__construct($app);
        $this->otmpl = array(
            'delivery' =>array('name' => $this->app->_('打印发货模板'),'defaultPath' => '/admin/delivery/delivery_print','app'=>'wms','printpage'=>'admin/delivery/print.html','memo_header'=>'(支持代销模式)'),
            'stock' =>array('name' => $this->app->_('打印备货模板'),'defaultPath' => '/admin/delivery/stock_print','app'=>'wms','printpage'=>'admin/delivery/print.html'),
            'purchase' =>array('name' => $this->app->_('打印采购模板'),'defaultPath' => '/admin/purchase/purchase_print','app'=>'purchase','printpage'=>'admin/prints.html'),
            'pureo' =>array('name' => $this->app->_('打印采购入库模板'),'defaultPath' => '/admin/eo/eo_print','app'=>'purchase','printpage'=>'admin/prints.html'),
            'purreturn' =>array('name' => $this->app->_('打印采购退货模板'),'defaultPath' => '/admin/returned/return_print','app'=>'purchase','printpage'=>'admin/prints.html'),
            'merge' => array('name'=> $this->app->_('打印联合模板'),'defaultPath' => '/admin/delivery/merge_print','app' => 'wms','printpage' => 'admin/delivery/print.html'),
            'delivery_pmt_old'=>array('name' => $this->app->_('打印优惠发货模板'),'defaultPath' => '/admin/delivery/delivery_print_pmt_price','app'=>'wms','printpage'=>'admin/delivery/print.html','memo_header'=>'(支持代销模式)'),
            'delivery_pmt_new'=>array('name' => $this->app->_('打印优惠发货模板'),'defaultPath' => '/admin/delivery/new_delivery_print_pmt_price','app'=>'wms','printpage'=>'admin/delivery/print.html','memo_header'=>'(支持代销模式)'),
            'delivery_pickmode'=>array('name' => $this->app->_('打印拣货发货模板'),'defaultPath' => '/admin/delivery/new_delivery_print_pickmode','app'=>'ome','printpage'=>'admin/delivery/print.html','memo_header'=>'(支持代销模式)'),
            'delivery_pickmode'=>array('name' => $this->app->_('打印拣货发货模板'),'defaultPath' => '/admin/delivery/new_delivery_print_pickmode','app'=>'ome','printpage'=>'admin/delivery/print.html','memo_header'=>'(支持代销模式)'),
            'appropriation'=>array('name' => $this->app->_('调拔单打印模板'),'defaultPath' => '/admin/appropriation/printtemp','app'=>'taoguanallocate','printpage'=>'admin/print.html'),
        );
    }

    /**
     * 模样过滤
     *
     * @return String $content
     * @param String $content
     **/
    public function bodyFilter($body,$js=false,$type='delivery')
    {

        $body = htmlspecialchars_decode($body);
        //过滤js
        $body = preg_replace('/<script[^>]*>([\s\S]*?)<\/script>/i',' ',$body);
        $defaultPath = $this->otmpl[$type]['defaultPath'];
        $deliveryCfgLib = kernel::single('ome_delivery_cfg');
        $print_version = $deliveryCfgLib->getprintversion();

        if($print_version=='1'){
            if($type=='delivery'){
               $defaultPath = '/admin/delivery/new_delivery_print';
            }
            if($type=='merge'){
                $defaultPath = '/admin/delivery/new_merge_print';
            }
        }

        if ($js==true) {
            $contents =  file_get_contents($this->_file($this->otmpl[$type]['app'],$defaultPath));
            $re = preg_match_all('/<script[^>]*>([\s\S]*?)<\/script>/i',$contents,$matches);
            if ($re) {
                foreach ($matches[0] as $value) {
                    $body .= $value;
                }
            }

            $body = htmlspecialchars($body);
        }

       return $body;
    }

    /**
     * 获取默认打印模样(页面文件)
     *
     * @return String $content 页面内容
     * @param String $type 打印属性
     * @author
     **/
    public function getDefaultTmplByHtml($type)
    {
        $defaultPath = $this->otmpl[$type]['defaultPath'];
        $deliveryCfgLib = kernel::single('ome_delivery_cfg');
        $print_version = $deliveryCfgLib->getprintversion();
        if($print_version=='1'){
            if($type=='delivery'){
               $defaultPath = '/admin/delivery/new_delivery_print';
            }
            if($type=='merge'){
                $defaultPath = '/admin/delivery/new_merge_print';
            }
        }
        $content = file_get_contents($this->_file($this->otmpl[$type]['app'],$defaultPath));
        $content = $this->bodyFilter($content,false,$type);
        return $content;
    }

    private function _file($app,$name){
        return ROOT_DIR.'/app/'.$app.'/view/'.$name.'.html';
    }

    private function tpl_src($matches){
        return '<{'.html_entity_decode($matches[1]).'}>';
    }

    /**
     * 删除前的操作
     *
     * @return bool
     * @author
     **/
    public function pre_recycle($rows)
    {
        $allow = true;
        foreach ($rows as $value) {
            if ($value['is_default']=='true') {
                $this->recycle_msg = '默认打印模板不能删除!';
                $allow = false;
                break;
            }

        }
        return $allow;
    }

}