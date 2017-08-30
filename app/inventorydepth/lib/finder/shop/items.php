<?php
/**
 * @author chenping<chenping@shopex.cn>
 */

class inventorydepth_finder_shop_items {

    public $addon_cols = 'shop_bn_crc32,shop_id,store_info,statistical_time,frame_set,approve_status,iid,shop_bn,shop_type';

    private $js_approve_status = false;

    function __construct($app)
    {
        $this->app = $app;

        $this->_render = app::get('inventorydepth')->render();
    }

    public $column_operation = '操作';
    public $column_operation_width = 100;
    public $column_operation_order = 1;
    public function column_operation($row)
    {
        $src = app::get('desktop')->res_full_url.'/bundle/download.gif';
        $finder_id = $_GET['_finder']['finder_id'];
        $operation = <<<EOF
        <a style="margin:5px;padding:5px;background:url('{$src}') no-repeat scroll center center;" href='index.php?app=inventorydepth&ctl=shop&act=download_page&id={$row["id"]}&downloadType=iid' target="dialog::{title:'同步商品【{$row["title"]}】',onClose:function(){window.finderGroup['{$finder_id}'].refresh();}}" title="同步商品【{$row['title']}】"></a>
EOF;

        $src = app::get('desktop')->res_full_url.'/bundle/upload.gif';
        $href = "index.php?app=inventorydepth&ctl=shop_adjustment&act=syncItemStock&p[0]={$row['iid']}&p[1]={$row['shop_id']}";
        $confirm_notice = "确定回写【{$row['bn']}】的库存？";
        $title = "正在回写【{$row['bn']}】的库存";
        $operation .= <<<EOF
        <a style="margin:5px;padding:5px;background:url('{$src}') no-repeat scroll center center;" title='回写库存' onclick="javascript:if(confirm('{$confirm_notice}')){new Event(event).stop(); W.page('{$href}');}"></a>
EOF;
        return $operation;
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
    
    /*
    public $detail_frame_log = '上下架日志';
    public function detail_frame_log($id){
    
    }*/

    public $column_approve_status = '商品在架状态';
    public $column_approve_status_order = 3;
    public function column_approve_status($row) 
    {
        $finder_id = $_GET['_finder']['finder_id'];
        $shop_id = $row[$this->col_prefix.'shop_id'];
        $shop_bn = $row[$this->col_prefix.'shop_bn'];
        $shop_bn = addslashes(str_replace('+','%2B',$shop_bn));
        $shop_type = $row[$this->col_prefix.'shop_type'];
        if ($this->js_approve_status === false) {
            $this->js_approve_status = true;
            $return = <<<EOF
            <script>
                void function(){
                    function approve_request(){
                        var data = Array.flatten(arguments);
                        new Request.JSON({
                            url:"index.php?app=inventorydepth&ctl=shop_frame&act=getApproveStatus",
                            method:"post",
                            data:{"iid":data,"shop_id":"{$shop_id}","shop_bn":"{$shop_bn}","finder_id":"{$finder_id}","shop_type":"{$shop_type}"},
                            onComplete:function(rsp){
                                if(rsp.status=='fail'){console.log(rsp.msg);return;}
                                if(rsp.status=='succ'){
                                    rsp.data.each(function(item,index){
                                        var id = 'item-approve-'+item.id;
                                        if (\$defined(\$(id))){
                                            \$(id).setHTML(item.html);
                                        }
                                        
                                        id = 'store-statistics-'+item.id;
                                        if(\$defined(\$(id))){
                                            var html = '<em style=\'color:#cc0000;\'>'+item.num+'</em>/<em style=\'color:#0033ff;\'>'+item.actual_stock+'</em>';
                                            \$(id).setHTML(html);
                                        }
                                    });
                                }
                            }
                        }).send();
                    }
                    \$('main').addEvent('domready',function(){
                        var data = []; var dtime=0;
                        \$ES('.item-approve-status').each(function(i){
                            if(data.length>=20){
                                dtime = \$random(0,1000);
                                approve_request.delay(dtime,this,data);
                                data = [];
                            }
                            data.push(i.get("iid"));
                        });
                        if (data.length>0) {
                                dtime = \$random(0,1000);
                                approve_request.delay(dtime,this,data);
                        }
                        
                    });
                }();
            </script>
EOF;
        }
        
        $return .= <<<EOF
        <div class='item-approve-status' iid="{$row['iid']}" id="item-approve-{$row['id']}"></div>
EOF;
        return $return;
    }

    public $column_store_statistics = '前端/总';
    public $column_store_statistics_width = 90;
    public function column_store_statistics($row)
    {
        return <<<EOF
        <div id='store-statistics-{$row['id']}'></div>
EOF;
    }

    public $column_sku_num = 'SKU数';
    public $column_sku_num_order = 61;
    public function column_sku_num($row)
    {
        $filter = array(
            'shop_id' => $row[$this->col_prefix.'shop_id'],
            'shop_iid' => $row['iid'],
        );
        
        $count = $this->app->model('shop_skus')->count($filter);
        return $count;
        /*
        return <<<EOF
        <a href="index.php?app=inventorydepth&ctl=shop_adjustment&act=index&filter[shop_iid]={$row['iid']}&filter[shop_id]={$row[$this->col_prefix.'shop_id']}">{$count}</a>
EOF;*/
    }

    public $column_regulation = '应用上下架规则';
    public $column_regulation_order = 62;
    private $js_regulation = false;
    public function column_regulation($row) 
    {
        $id = $row['id']; 
        $iid = $row['iid']; 
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
    }

    public $detail_operation_log = '操作日志';
    public function detail_operation_log($item_id)
    {
        $optLogModel = app::get('inventorydepth')->model('operation_log');
        $filter = array('obj_type' => 'item','obj_id' => $item_id);
        $optLogList = $optLogModel->getList('*',$filter,0,200);
        foreach ($optLogList as &$log) {
            $log['operation'] = $optLogModel->get_operation_name($log['operation']);
        }

        $this->_render->pagedata['optLogList'] = $optLogList;
        return $this->_render->fetch('finder/frame/operation_log.html');
    }

}
