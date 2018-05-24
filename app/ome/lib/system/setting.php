<?php
class ome_system_setting{
    /**
     * 配置项
     *
     * @var string
     **/
    private $_setting_tab = array(
        array('name' => '订单配置', 'file_name' => 'admin/system/setting/tab_order.html', 'app' => 'ome', 'order' => 1),
        array('name' => '仓储采购', 'file_name' => 'admin/system/setting/tab_storage.html', 'app' => 'ome', 'order' => 10),
        array('name' => '预处理配置', 'file_name' => 'admin/system/setting/tab_preprocess.html', 'app' => 'ome', 'order' => 20),
        array('name' => '订单复审设置', 'file_name' => 'admin/system/setting/tab_retrial.html', 'app'=>'ome', 'order' => 90),
        array('name' => '自动审单配置', 'file_name' => 'admin/system/setting/tab_consign.html', 'app'=>'ome', 'order' => 95),
        array('name' => '其他配置', 'file_name' => 'admin/system/setting/tab_other.html', 'app'=>'ome', 'order' => 40),
    );

    public function get_setting_tab()
    {
        return $this->_setting_tab;
    }

    public function view(){
        $settings = $this->all_settings();
        foreach($settings as $set){
            $key = str_replace('.','_',$set);
            $setData[$key] = &app::get('ome')->getConf($set);
        }

        $render = kernel::single('base_render');

        $render->pagedata['setData'] = $setData;
        $render->pagedata['branchCount'] = $this->getBranchMode();

        $html = $render->fetch('admin/system/setting.html','ome');
        return $html;
    }

    public function all_settings(){
        $all_settings =array(
            'ome.branch.mode',
            'ome.order.failtime',
            'ome.api_log.clean_time',
            'ome.order.unconfirmtime',
        	'ome.product.serial.merge',
            //'ome.delivery.consign',
            //'ome.delivery.check_type',
            'ome.delivery.check_show_type',
            'ome.batch_print_nums',
            'ome.delivery.check_ident',
            'ome.delivery.weight',
            'ome.delivery.logi',//设置快递单与称重的顺序
            'ome.delivery.check_delivery',//校验后，直接发货
            'ome.delivery.minWeight',
            'ome.delivery.maxWeight',
            'ome.delivery.sellagent',#分销王订单是否打印代销人
            'ome.product.serial.merge',
            'ome.product.serial.separate',
            'ome.checkems',
            'ome.getOrder.intervalTime',
            'ome.payment.confirm',
            'ome.delivery.method',
            'ome.delivery.wuliubao',
            'ome.delivery.hqepay',
            'ome.order.mark',
            'ome.combine.member_id',            // 新增合并购买人
            'ome.combine.shop_id',              // 合并店铺
            'ome.preprocess.tbgift',
            'ome.combine.memberidconf',
            'ome.combine.addressconf',
            'desktop.finder.tab',
            'desktop.finder.tab.count.expire',
            'ome.delivery.checknum.show',
            'ome.delivery.consignnum.show',
            'auto.setting',
            'purchase.stock_confirm',
            'purchase.stock_cancel',
            'taoguanallocate.appropriation_type',
            'purchase.po_type',
            'purchase.stock.stockset',
            'ome.orderpause.to.syncmarktext',   // 同步订单备注暂停操作配置
            'ome.product.serial.delivery',
            'ome.combine.select',
            'ome.order.is_retrial',//是否对修改订单进行复审
            'ome.order.retrial',//复审规则
            'ome.order.clean_day',//复审日志保留天数
            'ome.order.is_monitor',//是否开启价格监控
            'ome.order.cost_multiple',//成本价倍数
            'ome.order.sales_multiple',//销售价倍数
            'ome.order.is_auto_combine',//是否开启系统自动审核
            'ome.order.is_merge_order',//是否忽略可合并的订单
            'ome.apifail.retry',
			'ome.alipay.url',
        );
        return $all_settings;
    }

    public function getBranchMode(){
        $oBranch = app::get('ome')->model('branch');
        $con = count( $oBranch->Get_branchlist());
        return $con;
    }

    public function saveConf($settings)
    {
        $all_settings = $this->all_settings();

        foreach ($settings as $set => $value) {
            $old_setting = app::get('ome')->getConf($set);

            if ($old_setting != $value && in_array($set, $all_settings)) {
                app::get('ome')->setConf($set,$value);
            }
        }
    }

    public function get_setting_data()
    {
        $setData = array();

        $all_settings = $this->all_settings();

        foreach($all_settings as $set){
            $key = str_replace('.','_',$set);
            $setData[$key] = app::get('ome')->getConf($set);
        }

        return $setData;
    }
}
