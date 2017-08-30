<?php
/**
 * @author chenping<chenping@shopex.cn>
 */

class inventorydepth_finder_shop_batchframe {

    public $addon_cols = 'shop_bn_crc32,shop_id,store_info,statistical_time,frame_set,approve_status,iid,shop_bn,title';

    private $js_approve_status = false;

    function __construct($app)
    {
        $this->app = $app;
    }

    public $column_approve_status = '商品在架状态';
    public $column_approve_status_order = 3;
    public function column_approve_status($row) 
    {
        $finder_id = $_GET['_finder']['finder_id'];
        $shop_id = $row[$this->col_prefix.'shop_id'];
        $shop_bn = $row[$this->col_prefix.'shop_bn'];
        $iid  = $row[$this->col_prefix.'iid'];
        $shop_bn = addslashes(str_replace('+','%2B',$shop_bn));
        $approve_status = $row[$this->col_prefix.'approve_status'];
        $shop_title  = $row[$this->col_prefix.'title'];
            $id = md5($shop_id.$iid);
            if ($approve_status == 'onsale') {
                $color = 'green';
                $word = $this->app->_('在售');
                $href = "index.php?app=inventorydepth&ctl=shop_batchframe&act=approve_loading&p[0]={$id}&p[1]=instock";
                $title = "正在为【{$shop_title}】下架";
                $t = "点击下架【{$shop_title}】";
                $confirm_notice = "确定下架【{$shop_title}】";
            }else{
                $color = '#a7a7a7';
                $word = $this->app->_('下架');
                $href = "index.php?app=inventorydepth&ctl=shop_batchframe&act=approve_loading&p[0]={$id}&p[1]=onsale";
                $title = "正在为【{$shop_title}】上架";
                $t = "点击上架【{$shop_title}】";
                $confirm_notice = "确定上架【{$shop_title}】";
            }

            $html = <<<EOF
            <a style="background-color:{$color};float:left;text-decoration:none;" href="javascript:void(0);" title="{$t}" onclick="if(confirm('{$confirm_notice}')){new Event(event).stop();new Dialog('{$href}',{title:'{$title}',onClose:function(){
                finderGroup['{$finder_id}'].refresh();
            } });}"><span style="color:#eeeeee;padding:2px;">&nbsp;{$word}&nbsp;</span></a>
EOF;
        return $html;
    }

    public $detail_skus = 'SKU列表';
    public function detail_skus($id) 
    {
        $render = app::get('inventorydepth')->render();
        
        $items = $this->app->model('shop_items')->select()->columns('*')->where('id=?',$id)->instance()->fetch_row();

        # 实时读ITEM
        $result = kernel::single('inventorydepth_service_shop_items')->item_get($items['iid'],$items['shop_id'],$errormsg);
        $request = false;
        if ($result !== false) {
            $request = true;
            if ($result['item']['skus']['sku']) {
                foreach ($result['item']['skus']['sku'] as $key=>$value) {
                    $taobaoItem[strval($result['item']['iid'])][strval($value['sku_id'])] = $value['quantity'];
                }
            } else {
                $taobaoItem[strval($result['item']['iid'])] = $result['item']['num'];
            }
            unset($result);
        }

        $skus = $this->app->model('shop_adjustment')->getList('*',array('shop_id'=>$items['shop_id'],'shop_iid'=>$items['iid'],'sync_shop_stock'=>true));
        # 写内存
        if ($skus) {
            $spbn = array();
            foreach ($skus as $key=>$value) {
                $spbn[] = $value['shop_product_bn'];
            }
            $spbn = array_filter($spbn);
            $products = $this->app->model('products')->getList('product_id,goods_id,bn,store,store_freeze,max_store_lastmodify,last_modified',array('bn'=>$spbn));
            $products = $products ? $products : array();
            kernel::single('inventorydepth_stock_pkg')->writeMemory($products);
            kernel::single('inventorydepth_stock_products')->writeMemory($products);
            unset($spbn,$products);
        }
        # END
        if ($skus[0]['bind'] == '1') {
            $skus[0]['actual_stock'] = kernel::single('inventorydepth_logic_pkgstock')->dealWithRegu($skus[0]['shop_product_bn'],$skus[0]['shop_id'],$skus[0]['shop_bn']);
            $skus[0]['astock'] = kernel::single('inventorydepth_stock_calculation')->get_pkg_actual_stock($skus[0]['shop_product_bn'],$skus[0]['shop_bn'],$skus[0]['shop_id']);
            if(!is_numeric($skus[0]['astock'])) $skus[0]['astock'] = '-';
        } else {
            foreach ($skus as &$sku) {
                $sku['actual_stock'] = kernel::single('inventorydepth_logic_stock')->dealWithRegu($sku['shop_product_bn'],$sku['shop_id'],$sku['shop_bn']);
                $sku['astock'] = kernel::single('inventorydepth_stock_calculation')->get_actual_stock($sku['shop_product_bn'],$sku['shop_bn'],$sku['shop_id']);
                if(!is_numeric($sku['astock'])) $sku['astock'] = '-';
                
                if ($request) {
                    $sku['shop_stock'] = isset($taobaoItem[$sku['shop_iid']][$sku['shop_sku_id']]) ? $taobaoItem[$sku['shop_iid']][$sku['shop_sku_id']] : (is_numeric($taobaoItem[$sku['shop_iid']]) ? $taobaoItem[$sku['shop_iid']] : $sku['shop_stock']);
                }
            }
        }
        $render->pagedata['skus'] = $skus;

        return $render->fetch('shop/items/detail/skus.html');
    }

    public $column_sku_num = 'SKU数';
    public $column_sku_num_order = 61;
    public function column_sku_num($row)
    {
        $filter = array(
            'shop_id' => $row[$this->col_prefix.'shop_id'],
            'shop_iid' => $row[$this->col_prefix.'iid'],
        );
        
        $count = $this->app->model('shop_skus')->count($filter);
        return $count;
    }
    
    /*
    public $column_regulation = '应用上下架规则';
    public $column_regulation_order = 62;
    private $js_regulation = false;
    public function column_regulation($row) 
    {
        $id = $row['id']; 
        $iid = $row[$this->col_prefix.'iid']; 
        $shop_id = $row[$this->col_prefix.'shop_id']; 
        $shop_bn = $row[$this->col_prefix.'shop_bn'];
        $shop_bn = addslashes(str_replace('+','%2B',$shop_bn));
        if ($this->js_regulation === false) {
            $this->js_regulation = true;
            $return = <<<EOF
            <script>
                void function(){
                    function regulation_request(data){
                        new Request.JSON({
                            url:"index.php?app=inventorydepth&ctl=shop_frame&act=getApplyRegu",
                            method:"post",
                            data:{"iid":data,"shop_id":"{$shop_id}","shop_bn":"{$shop_bn}"},
                            onComplete:function(rsp){
                                if(rsp.status=='fail'){console.log(rsp.msg);return;}
                                if(rsp.status=='succ'){
                                    rsp.data.each(function(item,index){
                                        var id = 'regulation-'+item.id;
                                        if (\$defined(\$(id))){
                                            \$(id).setHTML(item.html);
                                        }
                                        
                                    });
                                }
                            }
                        }).send();
                    }
                    \$('main').addEvent('domready',function(){
                        var data = [];
                        \$ES('.apply-regulation').each(function(i){
                            data.push(i.get("iid"));
                        });
                        if (data.length>0) {
                            regulation_request(data);
                        }
                        
                    });
                }();
            </script>
EOF;
        }
        $return .= <<<EOF
        <div id="regulation-{$id}" class="apply-regulation" iid="{$iid}"></div>
EOF;
        
        return $return;
    }*/

}
