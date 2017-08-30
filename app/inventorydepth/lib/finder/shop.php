<?php
/**
 * @author chenping<chenping@shopex.cn>
 */

class inventorydepth_finder_shop {
    public $addon_cols = 'config,shop_type,name,shop_bn,business_type';
    public static $shop_regu_apply;

    function __construct($app)
    {
        $this->app = $app;

        $this->_render = app::get('inventorydepth')->render();
    }

    public $column_operator = '操作';
    public $column_operator_order = 1;
    public $column_operator_width = 100;
    public function column_operator($row)
    {
        $finder_id = $_GET['_finder']['finder_id'];
        $shop_name = addslashes($row['name']);
        # 判断是否支持同步前端商品
        if (inventorydepth_shop_api_support::items_all_get_support($row['shop_type'],$row[$this->col_prefix . 'business_type'])) {
            $src = app::get('desktop')->res_full_url.'/bundle/download.gif';
            $return .= <<<EOF
            <a style="margin:5px;padding:5px;background:url('{$src}') no-repeat scroll 50% 50%;" href='index.php?app=inventorydepth&ctl=shop&act=download_page&shop_id={$row["shop_id"]}&downloadType=shop' target="dialog::{title:'同步{$shop_name}店铺商品',onClose:function(){window.finderGroup['{$finder_id}'].refresh();}}" title="同步{$shop_name}店铺商品"></a>
EOF;
        } else {
            $return .= <<<EOF
            <a style="margin:5px;padding:5px;"></a>
EOF;
        }

        $src = app::get('desktop')->res_full_url.'/bundle/lorry.gif';
        $return .= <<<EOF
        <a style="margin:5px;padding:10px;background:url('{$src}') no-repeat scroll 50% 50%;" title="更新{$shop_name}店铺所有货品库存" target="dialog::{title:'更新{$shop_name}店铺所有货品库存',onClose:function(){window.finderGroup['{$finder_id}'].refresh();}}" href='index.php?app=inventorydepth&ctl=shop_adjustment&act=uploadPage&p[0]={$row["shop_id"]}'></a>
EOF;

        $src = $this->app->res_full_url.'/signup_icon.gif';
        $return .= <<<EOF
            <a style="display:none;margin:5px;padding:10px;background:url('{$src}') no-repeat scroll 50% 20%;" title="给{$shop_name}店铺解锁！" onclick="var pwd=prompt('请输入密码');if(pwd=='@shopex'){new Request({
                url:'index.php?app=inventorydepth&ctl=shop&act=downloadfinish&p[0]={$row["shop_id"]}',
                onComplete:function(resp){
                    MessageBox.show(resp);
                }
            }).send();}else{alert('密码错误');}"></a>
EOF;
        return $return;
    }

    /*
    public $column_shop_url = '店铺URL';
    public $column_in_list = false;
    public function column_shop_url($row)
    {
        $config = unserialize($row[$this->col_prefix.'config']);

        $url = ('http://' == substr($config['url'], 0,7)) ? $config['url'] : 'http://'.$config['url'];
        return <<<EOF
        <a target='_blank' href='{$url}'>{$url}</a>
EOF;
    }*/

    public $column_request = '自动回写库存';
    public $column_request_order = 2;
    public $column_request_width = 100;
    public function column_request($row)
    {
        $request = kernel::single('inventorydepth_shop')->getStockConf($row['shop_id']);

        if ($request == 'true') {
            $word = $this->app->_('开启');
            $color = 'green';
            $title = '点击关闭向该店铺自动回写库存功能';
            $href = 'index.php?app=inventorydepth&ctl=shop&act=set_request&p[0]=false&p[1]='.$row['shop_id'].'&finder_id='.$_GET['_finder']['finder_id'];
        }else{
            $word = $this->app->_('关闭');
            $color = '#a7a7a7';
            $title = '点击开启向该店铺自动回写库存功能';
            $href = 'index.php?app=inventorydepth&ctl=shop&act=set_request&p[0]=true&p[1]='.$row['shop_id'].'&finder_id='.$_GET['_finder']['finder_id'];
        }

        return <<<EOF
        <a style="background-color:{$color};float:left;text-decoration:none;" href="{$href}"><span title="{$title}" style="color:#eeeeee;padding:2px;">&nbsp;{$word}&nbsp;</span></a>
EOF;
    }
    
    /*
    public $column_frame = '自动上下架';
    public $column_frame_order = 3;
    public $column_frame_width = 100;
    public function column_frame($row)
    {
        $request = kernel::single('inventorydepth_shop')->getFrameConf($row['shop_id']);

        if ($request == 'true') {
            $word = $this->app->_('开启');
            $color = 'green';
            $title = '点击关闭向该店铺自动进行上下架管理功能';
            $href = 'index.php?app=inventorydepth&ctl=shop&act=set_frame&p[0]=false&p[1]='.$row['shop_id'].'&finder_id='.$_GET['_finder']['finder_id'];
        }else{
            $word = $this->app->_('关闭');
            $color = '#a7a7a7';
            $title = '点击开启向该店铺自动进行上下架管理功能';

            $href = 'index.php?app=inventorydepth&ctl=shop&act=set_frame&p[0]=true&p[1]='.$row['shop_id'].'&finder_id='.$_GET['_finder']['finder_id'];
        }

        return <<<EOF
        <a style="background-color:{$color};float:left;text-decoration:none;" href="{$href}"><span title="{$title}" style="color:#eeeeee;padding:2px;">&nbsp;{$word}&nbsp;</span></a>
EOF;
    }*/

    public $column_skus_count = '货品总数';
    public $column_skus_count_order = 40;
    public function column_skus_count($row)
    {
        if (!inventorydepth_shop_api_support::items_all_get_support($row['shop_type'],$row[$this->col_prefix . 'business_type'])) {
            return '-';
        }

        $count = $this->app->model('shop_skus')->count(array('shop_id'=>$row['shop_id']));

        return <<<EOF
        <a href='index.php?app=inventorydepth&ctl=shop_adjustment&act=index&filter[shop_id]={$row["shop_id"]}'>{$count}</a>
EOF;
    }

    public $column_items_count = '商品总数';
    public $column_items_count_order = 30;
    public function column_items_count($row)
    {
        if (!inventorydepth_shop_api_support::items_all_get_support($row['shop_type'],$row[$this->col_prefix . 'business_type'])) {
            return '-';
        }

        $count = $this->app->model('shop_items')->count(array('shop_id'=>$row['shop_id']));

        return <<<EOF
        <a href='index.php?app=inventorydepth&ctl=shop_frame&act=index&filter[shop_id]={$row["shop_id"]}'>{$count}</a>
EOF;
    }

    public $column_stock_regulation = '库存更新规则';
    public $column_stock_regulation_order = 29;
    public function column_stock_regulation($row)
    {
        $regulation_id = $this->app->model('regulation_apply')->select()->columns('regulation_id')
                                        ->where('shop_id=?',$row['shop_id'])
                                        ->where('type=?','1')
                                        ->where('`condition`=?','stock')
                                        ->where('`using`=?','true')
                                        ->instance()->fetch_one();

        $rr = $this->app->model('regulation')->select()->columns('regulation_id,heading')
                ->where('regulation_id=?',$regulation_id)
                ->where('`using`=?','true')
                ->instance()->fetch_row();
        if($rr){
        return <<<EOF
        <a href="index.php?app=inventorydepth&ctl=regulation&act=dialogEdit&p[0]={$rr['regulation_id']}&finder_id={$_GET['_finder']['finder_id']}" target="dialog::{title:'修改规则',width:800}">{$rr['heading']}</a>
EOF;
        }else{
            $src = app::get('desktop')->res_full_url.'/bundle/btn_add.gif';
            $shop_bn = $row[$this->col_prefix.'shop_bn'];
           return <<<EOF
            <div><a title="添加规则" target="dialog::{title:'添加规则'}" href="index.php?app=inventorydepth&ctl=regulation&act=dialogAdd&p[0]={$row['shop_id']}&p[1]={$shop_bn}&finder_id={$_GET['_finder']['finder_id']}"><img src={$src} ></a></div>
EOF;
        }
    }
    
    /*
    public $column_frame_regulation = '上下架规则';
    public $column_frame_regulation_order = 30;
    public function column_frame_regulation($row)
    {
        $regulation_id = $this->app->model('regulation_apply')->select()->columns('regulation_id')
                                        ->where('shop_id=?',$row['shop_id'])
                                        ->where('type=?','1')
                                        ->where('`condition`=?','frame')
                                        ->where('`using`=?','true')
                                        ->instance()->fetch_one();

        $rr = $this->app->model('regulation')->select()->columns('regulation_id,heading')
                ->where('regulation_id=?',$regulation_id)
                ->where('`using`=?','true')
                ->instance()->fetch_row();
        if($rr){
        return <<<EOF
        <a href="index.php?app=inventorydepth&ctl=regulation&act=dialogEdit&p[0]={$rr['regulation_id']}&finder_id={$_GET['_finder']['finder_id']}" target="dialog::{title:'修改规则',width:900}">{$rr['heading']}</a>
EOF;
        }else{
            $src = app::get('desktop')->res_full_url.'/bundle/btn_add.gif';
            $shop_bn = $row[$this->col_prefix.'shop_bn'];
           return <<<EOF
            <div><a title="添加规则" target="dialog::{title:'添加规则',width:900}" href="index.php?app=inventorydepth&ctl=regulation&act=dialogAdd&p[0]={$row['shop_id']}&p[1]={$shop_bn}&p[2]=frame&finder_id={$_GET['_finder']['finder_id']}"><img src={$src} ></a></div>
EOF;
        }
    }*/

    public $column_supply_branches = '供货仓';
    public $column_supply_branches_order = 90;
    public function column_supply_branches($row) 
    {
        $branches = kernel::single('inventorydepth_shop')->getBranchByshop($row[$this->col_prefix.'shop_bn']);
        $branchList = app::get('ome')->model('branch')->getList('name',array('branch_bn'=>$branches));
        if ($branchList) {
            $branches = array_map('current',$branchList);
        }
        if ($branches) {
            $html = '<span style="color:#0000ff">'.implode('</span>、<span style="color:#0000ff">',$branches).'</span>';
            return '<div class="desc-tip" onmouseover="bindFinderColTip(event);">'.$html.'<textarea style="display:none;"><h3>店铺【<span style="color:red;">'.$row[$this->col_prefix.'name'].'</span>】供货仓库</h3>'.$html.'</textarea></div>';
        } else {
            $html = '<div style="color:red;font-weight:bold;" onmouseover="bindFinderColTip(event);" rel="请先去仓库管理里绑定仓库与店铺关系，否则将影响库存回写！！！">无仓库供货</div>';
            return $html;
        }
    }

    public $detail_operation_log = '操作日志';
    public function detail_operation_log($shop_id)
    {
        $optLogModel = app::get('inventorydepth')->model('operation_log');
        $filter = array('obj_type' => 'shop','obj_id' => $shop_id);
        $optLogList = $optLogModel->getList('*',$filter,0,200);
        foreach ($optLogList as &$log) {
            $log['operation'] = $optLogModel->get_operation_name($log['operation']);
        }
        
        $this->_render->pagedata['optLogList'] = $optLogList;
        return $this->_render->fetch('finder/shop/operation_log.html');
    }

}
