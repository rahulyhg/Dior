<?php
/**
 * 打印模板控制器
 *
 * @author chenping<chenping@shopex.cn>
 * @version 2012-4-17 14:33
 * @package print
 */

class ome_ctl_admin_print_otmpl extends desktop_controller
{

    function __construct(&$app)
    {
        parent::__construct($app);
        $this->_request = kernel::single('base_component_request');
    }


    public function _views(){
        $views =  array(
            0=>array('label'=>$this->app->_('全部'),'optional'=>false,'filter'=>'','addon'=>''),
            1=>array('label'=>$this->app->_('备货单模板'),'optional'=>false,'filter'=>array('type'=>'stock'),'addon'=>''),
            2=>array('label'=>$this->app->_('发货单模板'),'optional'=>false,'filter'=>array('type'=>'delivery'),'addon'=>''),
            3=>array('label'=>$this->app->_('采购单模板'),'optional'=>false,'filter'=>array('type'=>'purchase'),'addon'=>''),
            4=>array('label'=>$this->app->_('采购入库单模板'),'optional'=>false,'filter'=>array('type'=>'pureo'),'addon'=>''),
            5=>array('label'=>$this->app->_('退货单模板'),'optional'=>false,'filter'=>array('type'=>'purreturn'),'addon'=>''),
            6=>array('label'=>$this->app->_('联合打印模板'),'optional'=>false,'filter'=>array('type'=>'merge','addon'=>'')),
            7=>array('label'=>$this->app->_('调拔单打印模板'),'optional'=>false,'filter'=>array('type'=>'appropriation','addon'=>'')),
        );
        foreach ($views as $key=>&$value) {
           $value['href'] = 'index.php?app=ome&ctl=admin_print_otmpl&act=index&view='.$key;
           $value['addon'] = $this->app->model('print_otmpl')->count($value['filter']);
        }

        return $views;
    }
    /**
     * 入口
     *
     * @return void
     * @author
     **/
    public function index()
    {
        $this->finder('ome_mdl_print_otmpl',array(
            'title'=>'打印模板',
            'actions' => array(
                array('label'=>'添加备货单模板','href'=>'index.php?app=ome&ctl=admin_print_otmpl&act=show&p[0]=0&p[1]=stock','target'=>'_blank'),
                array('label'=>'添加发货单模板','href'=>'index.php?app=ome&ctl=admin_print_otmpl&act=show&p[0]=0&p[1]=delivery','target'=>'_blank'),
            ),
            'use_buildin_recycle'=>true,
            'use_buildin_filter'=>true,
        ));
    }

    /**
     * 显示添加/编辑页面
     *
     * @return void
     * @author
     **/
    public function show($id=NULL,$type='delivery')
    {
        $id = (int)$id;
        $oTmplModel = $this->app->model('print_otmpl');
        $this->title = empty($id) ? app::get('ome')->_('添加') : app::get('ome')->_('编辑');
        $this->title .= $oTmplModel->otmpl[$type]['name'];
        $this->pagedata['title'] = $this->title;
        $this->pagedata['memo_header'] = $oTmplModel->otmpl[$type]['memo_header'];
        #获取类型
        #获取打印版本
        $this->pagedata['type'] = $type;
        $deliCfgLib = kernel::single('wms_delivery_cfg');
        $print_version = $deliCfgLib->getprintversion();
        $this->pagedata['print_version'] = $print_version;
        unset( $print_version );
        $printTmpl = array('type'=>$type);
        if ($id) {
            $row = $oTmplModel->select()->columns()->where('id=?',$id)->instance()->fetch_row();
            $printTmpl = array_merge($printTmpl,$row);
            $printTmpl['content'] = $oTmplModel->bodyFilter($printTmpl['content']);
        }
        $this->pagedata['printParams'] = $this->getPrintParams($type);

        $this->pagedata['printTmpl'] = $printTmpl;
        $this->singlepage('admin/print/show.html');
    }

    /**
     * 保存打印模板
     *
     * @return void
     * @author
     **/
    public function save()
    {
        $params = $this->_request->get_post();

        $params['id'] = (int)$params['id'];
        $update = $params['id'] ? true : false;
        $this->begin("index.php?app=ome&ctl=admin_print_otmpl&act=show&p[0]={$params['id']}&p[1]={$params['type']}");
        if (!$params['title']) {
            $this->end(false,app::get('ome')->_('请完善标题!'));
        }
        if (!$params['content']) {
            $this->end(false,app::get('ome')->_('请完善模板样式!'));
        }
        if ($params['aloneBtn']=='true' && !$params['btnName']) {
            $this->end(false,app::get('ome')->_('请完善独立按钮名称'));
        }

        $oTmplModel = $this->app->model('print_otmpl');
        $types = array_keys($oTmplModel->otmpl);
        if (!in_array($params['type'], $types)) {
            $this->end(false,app::get('ome')->_('请选择打印模板类型'));
        }


        if ($params['is_default']=='true') {
            $filter = array('is_default' => 'true','type' => $params['type'] );
            $data = array('is_default' => 'false');
            $oTmplModel->update($data,$filter);
            $params['open'] = 'true';
        }

        if ($params['id'] && ($params['is_default']=='false' || $params['open']=='false') ) {
            $row = $oTmplModel->select()->columns('id')
            ->where('id!=?',$params['id'])
            ->where('is_default=?','true')
            ->where('type=?',$params['type'])
            ->where('open=?','true')
            ->instance()->fetch_one();
            if (!$row) {
                $this->end(false,app::get('ome')->_('先开启其他同类项后，再取消默认!'));
            }
        }

        if ($params['aloneBtn']=='false') {
            $params['btnName'] = '';
        }

        $params['last_modified'] = time();
        //$params['content'] = htmlspecialchars_decode($params['content']);
        $params['content'] = $oTmplModel->bodyFilter($params['content'],true,$params['type']);

        $result = $oTmplModel->save($params);
        if ($update==false) {
            $path = 'admin/print/otmpl/'.$params['id'];
            $oTmplModel->update(array('path'=>$path),array('id'=>$params['id']));
        }
        $this->end($result);
    }

    /**
     * 获取默认打印模样(页面文件)
     *
     * @return json
     * @param String $type 打印类型
     * @author
     **/
    public function getDefaultTmpl($type)
    {
        $oTmplModel = $this->app->model('print_otmpl');

        if (!in_array($type, array_keys($oTmplModel->otmpl))) {
            $this->splash('error',NULL,$this->app->_('不存在该类型!'));
        }

        $content  = $oTmplModel->getDefaultTmplByHtml($type);
        $defaultTmpl = array('title'=>$oTmplModel->otmpl[$type]['name'],'content'=>$content);
        $this->splash('success',NULL,$this->app->_('获取成功'),'redirect',$defaultTmpl);
    }

    /**
     * undocumented function
     *
     * @return void
     * @author
     **/
    private function getPrintParams($type='delivery')
    {
        $params['delivery'][] = array(
            'name' => '发货明细',
            'param' => '
            <{foreach from=$items item=item}><br/>
            <{foreach from=$item.delivery_items item=i}>
            <ul>
                <li>商品名称：<{$i.name}></li>
                <li>商品规格：<{$i.addon|default:"--"}></li>
                <li>货号：<{$i.bn}></li>
                <li>货位：<{$i.store_position}></li>
                <li>数量：<{$i.number}></li>
                <li>单价：<{$i.price}></li>
            </ul>
            <{/foreach}><br/>
            <{/foreach}>
            ',
        );
        $params['delivery'][] = array(
            'name' => '订单附言',
            'param' => '
            <{foreach from=$items item=item}><br/>
              <{foreach name="m2" from=$item._mark key=key item=item2}>
                  <br><{$key}>:
                  <{foreach from=$item2 item=it}>
                      <br>&nbsp;&nbsp;&nbsp;&nbsp;<b><{$it.op_content}></b> <{$it.op_time}> by <{$it.op_name}><br/>
                  <{/foreach}><br/>
              <{/foreach}><br/>
            <{/foreach}>
            ',
        );
        $params['delivery'][] = array(
            'name' => '头部内容',
            'param' => '
            <{capture name="header"}><br/>
                # your code <br/>
            <{/capture}>
            ',
        );
        $params['delivery'][] = array(
            'name' => '错误提示',
            'param' => '
                <{ if $errIds }><br>
                    <{ foreach from=$errIds item=id }><br/>
                        <{$errBns[$id]}>：<{$errInfo[$id]}><br>
                    <{ /foreach }><br/>
                <{ /if }>
            ',
        );
        $params['delivery'][] = array(
            'name' => '发货单信息',
            'param' => '
                <{foreach from=$items item=item}>
                <ul>
                <li>货到付款：<{if $item.is_code=="true"}># your code<{/if}></li>
                <li>来源店铺：<{$item.shop_name}></li>
                <li>店铺LOGO：<{$item.shop_logo_url}></li>
                <li>发货单号：<{$item.delivery_bn}></li>
                <li>条形码：<{$item.delivery_bn|barcode}></li>
                <li>打印批次号：<{$idents[$item["delivery_id"]]}></li>
                <li>订单号：<{$item.order_bn}></li>
                <li>会员名：<{$item.member_name}></li>
                <li>打印日期：<{$time}></li>
                <li>操作员：<{$item.op_name}></li>
                <li>发货单数量总计：<{$item.delivery_total_nums}></li>
                <li>订单总金额：<{$item.order_total_amount}></li>
                <li>应收金额：<{$item.total_receivable}></li>
                <li>商品总金额：<{$item.order_cost_item}></li>
                <li>收货人：<{$item.consignee.name}></li>
                <li>电话：<{$item.consignee.telephone}></li>
                <li>手机：<{$item.consignee.mobile}></li>
                <li>邮编：<{$item.consignee.zip}></li>
                <li>地址：<{$item.consignee.area|region}> <{$item.consignee.addr}></li>
                </ul>
                <{/foreach}>
            ',
        );
        $params['delivery'][] = array(
            'name' => '订单备注',
            'param' => '
            <{foreach from=$items item=item}><br/>
              <{foreach name="m1" from=$item._mark_text key=key item=item1}><br>
                  <{$key}>:<br/>
                  <{foreach from=$item1 item=it}>
                      <br>&nbsp;&nbsp;&nbsp;&nbsp;<b><{$it.op_content}></b> <{$it.op_time}> by <{$it.op_name}><br/>
                  <{/foreach}><br/>
              <{/foreach}><br/>
            <{/foreach}>
            ',
        );
        $params['delivery'][] = array(
            'name' => '发票信息',
            'param' => '
            <{foreach from=$item._tax_info item=item3}>
                <br/><{$item3.order_bn}>:
                    <br/>&nbsp;&nbsp;&nbsp;&nbsp;发票抬头：<{$item3.tax_title}>&nbsp;&nbsp;发票号：<{$item3.tax_no}>
            <{/foreach}>
            ',
        );
        $params['delivery'][] = array(
                'name' => '商品重量',
                'param' => '<{$i.product_weight}>',
        );
        $params['delivery'][] = array(
                'name' => '商品单位',
                'param' => '<{$i.unit}>',
        );
        ##发货单销售价老模板

        ##发货单销售价新模板
        $params['stock'][0] = array(
            'name' => '头部内容',
            'param' => '
            <{capture name="header"}>
                # your code <br/>
            <{/capture}>
            ',
        );
        $params['stock'][1] = array(
            'name' => '错误提示',
            'param' => '
            <{if $errIds }>
                <{ foreach from=$errIds item=id }>
                <{$errBns[$id]}>：<{$errInfo[$id]}>
                <{ /foreach }>
            <{/if}>
            ',
        );
        $params['stock'][2] = array(
            'name' => '备货明细',
            'param' => '
                <{foreach from=$rows item=item name=ff}>
                <ul>
                <li>货号：<{$item.bn}></li>
                <li>货位：<{$item.store_position}></li>
                <li>条形码：<{$item.barcode}></li>
                <li>商品名称：<{$item.name}></li>
                <li>商品规格：<{$item.spec_info|default:"--"}></li>
                <li>商品数量：<{$item.num}></li>
                <li>合计金额：<{$item.box_price}></li>
                <li>盒子号：<{$item.box}></li>
                <li>订单附言：</li>
                </ul>
                <{/foreach}>
            ',
        );
        $params['stock'][3] = array(
            'name' => '订单附言',
            'param' => '
                <{foreach from=$memo[0] item=items}><br/>
                <{$items.op_content|escape:"HTML"}><br/>
                <{/foreach}>
            ',
        );
        $params['stock'][4] = array(
            'name' => '数量总计',
            'param' => '<{$delivery_total_nums}> ',
        );
        $params['stock'][5] = array(
            'name' => '出库金额总计',
            'param' => '<{$delivery_total_price}>',
        );
        $params['stock'][6] = array(
            'name' => '备货单金额总计',
            'param' => '<{$picking_list_price}>',
        );
        $params['stock'][7] = array(
            'name' => '优惠金额总计',
            'param' => '<{$delivery_discount_price}>',
        );
        $params['stock'][8] = array(
            'name' => '批次号',
            'param' => '<{$ident}>',
        );
        $params['stock'][9] = array(
                'name' => '商品重量',
                'param' => '<{$item.product_weight}>',
        );
        $params['stock'][10] = array(
                'name' => '商品单位',
                'param' => '<{$item.unit}>',
        );
        $params['purchase'][] = array(
            'name' => '打印日期',
            'param' => ' <{$time|date_format:"%Y-%m-%d %H:%I:%S"}>',
        );
        $params['purchase'][] = array(
            'name' => '采购单号',
            'param' => '<{$po.po_bn}>',
        );
        $params['purchase'][] = array(
            'name' => '采购方式',
            'param' => '<{if $po.po_type=="cash"}>现购<{else}>赊购<{/if}>',
        );
        $params['purchase'][] = array(
            'name' => '供应商',
            'param' => '<{$po.supplier}>',
        );
        $params['purchase'][] = array(
            'name' => '采购员',
            'param' => '<{$po.operator}>',
        );
        $params['purchase'][] = array(
                'name' => '采购单创建人',
                'param' => '<{$po.op_name}>',
        );
        $params['purchase'][] = array(
                'name' => '审核人',
                'param' => '<{$po.check_operator}>',
        );
        $params['purchase'][] = array(
                'name' => '规格',
                'param' => '<{$i.spec_info}>',
        );        
        $params['purchase'][] = array(
            'name' => '指定仓库',
            'param' => '<{$po.branch}>',
        );
        $params['purchase'][] = array(
            'name' => '采购日期',
            'param' => '<{$po.purchase_time|date_format:"%Y-%m-%d %H:%I:%S"}>',
        );
        $params['purchase'][] = array(
            'name' => '到货日期',
            'param' => '<{$po.arrive_time|date_format:"%Y-%m-%d"}>',
        );
        $params['purchase'][] = array(
            'name' => '金额总计',
            'param' => '<{$po.amount}>',
        );
        $params['purchase'][] = array(
            'name' => '预付款',
            'param' => '<{$po.deposit}>',
        );
        $params['purchase'][] = array(
            'name' => '备注',
            'param' => '
                <{foreach from=$po.memo item=items}><br/>
                <b><{$items.op_content|escape:"HTML"}></b> <{$items.op_time}> by <{$items.op_name}><br/>
                <{/foreach}>
            ',
        );
        $params['purchase'][] = array(
            'name' => '商品信息',
            'param' => '
            <{foreach from=$po.po_items item=i}>
            <ul>
                <li>商品名称：<{$i.name}></li>
                <li>商品编号：<{$i.goods_bn}></li>
                <li>计量单位：<{$i.unit}></li>
                <li>货号：<{$i.bn}></li>
                <li>采购数量：<{$i.num}></li>
                <li>待入库数量：<{$i.num-$i.in_num-$i.out_num}></li>
                <li>采购单价：<{$i.price}></li>
            </ul>
            <{/foreach}>
            ',
        );
        $params['pureo'][] = array(
            'name' => '金额总计',
            'param' => '<{$eo.detail.product_cost}>',
        );
        $params['pureo'][] = array(
            'name' => '经办人',
            'param' => '<{$eo.detail.oper}>',
        );
        $params['pureo'][] = array(
            'name' => '到货仓库',
            'param' => '<{$eo.branch_name}>',
        );
        $params['pureo'][] = array(
            'name' => '供应商',
            'param' => '<{$eo.supplier_name}>',
        );
        $params['pureo'][] = array(
            'name' => '入库日期',
            'param' => '<{$eo.detail.create_time|date_format:"%Y-%m-%d"}>',
        );
        $params['pureo'][] = array(
            'name' => '入库单编号',
            'param' => '<{$eo.detail.iso_bn}>',
        );
        $params['pureo'][] = array(
            'name' => '入库单备注',
            'param' => '<{$eo.detail.memo}>',
        );
        $params['pureo'][] = array(
                'name' => '商品规格',
                'param' => '<{$items.spec_info}>',
        );
        $params['pureo'][] = array(
            'name' => '商品明细',
            'param' => '
                <{foreach from=$eo.items item=items}>
                <ul>
                <li>商品名称：<{$items.product_name}></li>
                <li>货号：<{$items.bn}></li>
                <li>货位：<{$items.store_position}></li>
                <li>单位：<{$items.unit}></li>
                <li>数量：<{$items.nums}></li>
                <li>价格：<{$items.price}></li>
                </ul>
                <{/foreach}>
            ',
        );
        $params['pureo'][] = array(
            'name' => '单据类型',
            'param' => '<{if(!isset($process_name))}><{assign var=process_name value="入库"}><{/if}>',
        );
        $params['purreturn'][] = array(
            'name' => '退货底单',
            'param' => '<{$po.logi_no}>',
        );
        $params['purreturn'][] = array(
            'name' => '打印日期',
            'param' => ' <{$time|date_format:"%Y-%m-%d %H:%I:%S"}>',
        );
        $params['purreturn'][] = array(
            'name' => '退货单号',
            'param' => '<{$po.rp_bn}>',
        );
        $params['purreturn'][] = array(
            'name' => '是否特别退货',
            'param' => '<{if $po.emergency=="false"}>否<{else}>是<{/if}>',
        );
        $params['purreturn'][] = array(
            'name' => '供应商',
            'param' => '<{$po.supplier}>',
        );
        $params['purreturn'][] = array(
            'name' => '经办人',
            'param' => '<{$po.operator}>',
        );
        $params['purreturn'][] = array(
            'name' => '退货仓库',
            'param' => '<{$po.branch}>',
        );
        $params['purreturn'][] = array(
            'name' => '退货日期',
            'param' => '<{$po.returned_time|date_format:"%Y-%m-%d %H:%I:%S"}>',
        );
        $params['purreturn'][] = array(
            'name' => '物流费用',
            'param' => '<{$po.delivery_cost}>',
        );
        $params['purreturn'][] = array(
            'name' => '金额总计',
            'param' => '<{$po.amount}>',
        );
        $params['purreturn'][] = array(
            'name' => '备注',
            'param' => '
            <{foreach from=$po.memo item=items}><br/>
            <b><{$items.op_content|escape:"HTML"}></b> <{$items.op_time}> by <{$items.op_name}><br/>
            <{/foreach}>
            ',
        );
        $params['purreturn'][] = array(
            'name' => '商品明细',
            'param' => '
            <{foreach from=$po.po_items item=i}>
                <ul>
                    <li>商品名称：<{$i.name}></li>
                    <li>货号：<{$i.bn}></li>
                    <li>规格：<{$i.spec_info}></li>
                    <li>条形码：<{$i.barcode}></li>
                    <li>退货数量：<{$i.num}></li>
                    <li>退货单价：<{$i.price}></li>
                </ul>
            <{/foreach}>
            ',
        );
        $params['merge'][] = array(
            'name' => '头部内容',
            'param' => '
            <{capture name="header"}>
                # your code <br/>
            <{/capture}>
            ',
        );
        $params['merge'][] = array(
            'name' => '错误提示',
            'param' => '
            <{ if $errIds }><br/>
                <{ foreach from=$errIds item=id }><br/>
                    <{$allItems[$id]["delivery_bn"]}> &nbsp; : &nbsp; <{$errInfo[$id]}><br><br/>
                <{ /foreach }><br/>
            <{ /if }>
            ',
        );
        $params['merge'][] = array(
            'name' => '备货单明细',
            'param' => '
            <{foreach from=$items item=group}><br/>
                <{foreach from=$group.stock item=item name=ff}><br/>
                    <ul>
                        <li>货号：<{$item.bn}></li>
                        <li>货位：<{$item.store_position}></li>
                        <li>商品名称：<{if $is_front_pname}><{$item.product_name}><{else}><{$item.name}><{/if}></li>
                        <li>商品规格：<{$item.spec_info|default:\'--\'}></li>
                        <li>商品数量：<{$item.num}></li>
                        <li>盒子号<盒号(件数)-序号>：<{$item.box}></li>
                    </ul>
                <{/foreach}><br/>
            <{/foreach}><br/>
            ',
        );
        $params['merge'][] = array(
                'name' => '商品重量',
                'param' => '<{product_weight}>',
        );
        $params['merge'][] = array(
                'name' => '商品单位',
                'param' => '<{unit}>',
        );
        $params['merge'][] = array(
            'name' => '备货单信息',
            'param' => '
            <{foreach from=$items item=group}><br/>
                <{foreach from=$group.stock item=item name=ff}><br/>
                    <ul>
                        <li>备货单数量总计：<{$group.delivery_total_nums}></li>
                        <li>出库金额总计：<{assign var="delivery_total_price" value=$group.delivery_total_price+$group.delivery_discount_price}>
                        <{$delivery_total_price}></li>
                        <li>备货单金额总计：<{$group.delivery_total_price|default:0}></li>
                        <li>优惠金额总计：<{$group.delivery_discount_price|default:0}></li>
                    </ul>
                <{/foreach}><br/>
            <{/foreach}><br/>

            ',
        );
        $params['merge'][] = array(
            'name' => '发货明细',
            'param' => '
                <{foreach from=$items item=group}>
                <{foreach from=$group.delivery item=item}><br/>
                    <{foreach from=$item.delivery_items item=i}><br/>
                        <ul>
                           <li>商品名称：<{if $is_front_pname}><{$i.product_name}><{else}><{$i.name}><{/if}></li>
                           <li>商品规格：<{$i.addon|default:\'--\'}></li>
                           <li>货号：<{$i.bn}></li>
                           <li>货位：<{$i.store_position}></li>
                           <li>数量：<{$i.number}></li>
                           <li>单价：<{$i.price}></li>
                        </ul>
                    <{/foreach}><br/>
                <{/foreach}><br/>
                <{/foreach}><br/>
            ',
        );
        $params['merge'][] = array(
            'name' => '发货单信息',
            'param' => '
             <{foreach from=$items item=group}>
            <{foreach from=$group.delivery item=item}>
            <ul>
            <li>条形码：<{$item.delivery_bn|barcode}></li>
            <li>发货单号：<{$item.delivery_bn}></li>
            <li>制单人：<{$item.op_name}></li>
            <li>电话号码：<{$item.consignee.telephone}>/<{$item.consignee.mobile}></li>
            <li>会员名：<{$item.member_name}></li>
            <li>打印日期： <{$time}></li>
            <li>订单号：<{$item.order_bn}></li>
            <li>地址：<{$item.consignee.area|region}></li>
            <li>销售发货单数量总计：<{$item.delivery_total_nums}></li>
            <li>订单总金额：<{$item.order_total_amount}></li>
            <li>收货人：<{$item.consignee.name}></li>
            <li>手机：<{$item.consignee.mobile}></li>
            <li>邮编：<{$item.consignee.zip}></li>
            <li>地址：<{$item.consignee.area|region}></li>
            <li>批次号：<{$idents[$item[\'delivery_id\']]}></li>
            </ul>
            <{/foreach}>
            <{/foreach}>
            ',
        );
        $params['merge'][] = array(
            'name' => '订单备注',
            'param' => '
            <{foreach from=$items item=group}><br/>
            <{foreach from=$group.delivery item=item}><br/>
              <{foreach name="m1" from=$item._mark_text key=key item=item1}><br/>
                  <{$key}>:
                  <{foreach from=$item1 item=it}>
                      <br>&nbsp;&nbsp;&nbsp;&nbsp;<b><{$it.op_content}></b> <{$it.op_time}> by <{$it.op_name}>
                  <{/foreach}><br/>
              <{/foreach}><br/>
            <{/foreach}><br/>
            <{/foreach}><br/>
            ',
        );
        $params['merge'][] = array(
            'name' => '订单附言',
            'param' => '
            <{foreach from=$items item=group}><br/>
            <{foreach from=$group.delivery item=item}><br/>
              <{foreach name="m1" from=$item._mark key=key item=item1}>
                  <br><{$key}>:
                  <{foreach from=$item1 item=it}>
                      <br>&nbsp;&nbsp;&nbsp;&nbsp;<b><{$it.op_content}></b> <{$it.op_time}> by <{$it.op_name}>
                  <{/foreach}><br/>
              <{/foreach}><br/>
            <{/foreach}><br/>
            <{/foreach}><br/>
            ',
        );
        $params['merge'][] = array(
            'name' => '发票信息',
            'param' => '
            <{foreach from=$item._tax_info item=item3}>
                <br/><{$item3.order_bn}>:
                    <br/>&nbsp;&nbsp;&nbsp;&nbsp;发票抬头：<{$item3.tax_title}>&nbsp;&nbsp;发票号：<{$item3.tax_no}>
            <{/foreach}>
            ',
        );
        $params['appropriation'][] = array(
                'name' => '货位',
                'param' => '<{$item.store_position}>',
        );
        return $type=='all' ? $params : $params[$type];
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function getdefault_sale_print($type)
    {
        $oTmplModel = $this->app->model('print_otmpl');



        $content  = $oTmplModel->getDefaultTmplByHtml($type);
        $defaultTmpl = array('title'=>$oTmplModel->otmpl[$type]['name'],'content'=>$content);
        $this->splash('success',NULL,$this->app->_('获取成功'),'redirect',$defaultTmpl);
    }
}
