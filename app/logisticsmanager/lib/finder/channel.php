<?php
class logisticsmanager_finder_channel{
    var $addon_cols = "channel_id,status,channel_type,logistics_code,shop_id,bind_status";
    var $column_control = '操作';
    var $column_control_width = '60';
    var $column_control_order = COLUMN_IN_HEAD;
     var $detail_shop_address='发货地址';
    var $detail_log = '导入面单号记录';
    function column_control($row){
        $channel_id = $row[$this->col_prefix.'channel_id'];
        $bind_status = $row[$this->col_prefix.'bind_status'];
        $channel_type = $row[$this->col_prefix.'channel_type'];
        $button = "<a href='javascript:void(0);' onclick=\"new Dialog('index.php?app=logisticsmanager&ctl=admin_channel&act=edit&p[0]={$channel_id}&finder_id={$_GET[_finder][finder_id]}',{width:620,height:260,title:'来源添加/编辑'}); \">编辑</a>";
        if (in_array($channel_type,array('sto')) && $bind_status=='true') {
            $button.= "<a href='javascript:void(0);' onclick=\"new Dialog('index.php?app=logisticsmanager&ctl=admin_channel&act=index&action=import&p[0]={$channel_id}&finder_id={$_GET[_finder][finder_id]}&channel_id={$channel_id}',{width:620,height:260,title:'导入面单号'}); \">|导入</a>";
        }
        
        return $button;
    }

    var $column_channel_type = '来源类型';
    var $column_channel_type_width = '80';
    var $column_channel_type_order = COLUMN_IN_TAIL;
    function column_channel_type($row){
        $funcObj = kernel::single('logisticsmanager_waybill_func');
        $channel_type = $row[$this->col_prefix.'channel_type'];
        $channels = $funcObj->channels($channel_type);
        if($channels) {
            return $channels['name'];
        } else {
            return '未知';
        }
    }

    var $column_logistics = '物流公司';
    var $column_logistics_width = '80';
    var $column_logistics_order = COLUMN_IN_TAIL;
    function column_logistics($row){
        $channel_type = $row[$this->col_prefix.'channel_type'];
        if ($channel_type && class_exists('logisticsmanager_waybill_'.$channel_type)) {
            $wlbObj = kernel::single('logisticsmanager_waybill_'.$channel_type);
            $logistics_code = $row[$this->col_prefix.'logistics_code'];
            $logistics = $wlbObj->logistics($logistics_code);
        }
        
        
        if($logistics) {
            return $logistics['name'];
        } else {
            return '未知';
        }
    }

    var $column_waybillnum = '本地可用';
    var $column_waybillnum_width = '80';
    var $column_waybillnum_order = COLUMN_IN_TAIL;
    function column_waybillnum($row){
        $waybillObj = app::get('logisticsmanager')->model('waybill');
        $filter = array('status'=>0);
        $filter['channel_id'] = $row[$this->col_prefix.'channel_id'];
        $filter['logistics_code'] = $row[$this->col_prefix.'logistics_code'];

        $count = $waybillObj->count($filter);

        return "<span class=show_list channel_id=".$filter['channel_id']." billtype='active'><a >".$count."</a></span>";
    }

    var $column_shop = '适用店铺';
    var $column_shop_width = '150';
    var $column_shop_order = COLUMN_IN_TAIL;
    function column_shop($row){
        if($row[$this->col_prefix.'channel_type'] == 'wlb' || $row[$this->col_prefix.'channel_type'] == 'taobao') {
            $shopObj = app::get('ome')->model('shop');
            $shop = $shopObj->dump($row[$this->col_prefix.'shop_id'],'name');
            return $shop['name'];
        } elseif($row[$this->col_prefix.'channel_type'] == 'ems') {
            if($row[$this->col_prefix.'bind_status'] == 'true') {
                return '全部';
            } else {
                return '未绑定';
            }
        } elseif ($row[$this->col_prefix.'channel_type'] == '360buy') {
            $logistics_code = $row[$this->col_prefix.'logistics_code'];
            if (strtoupper($logistics_code) == 'SOP') {
                return '京东' . $logistics_code;
            }
            else {
                return '京东';
            }
        } else {
            return '全部';
        }

    }
    function detail_log($channel_id){
        
        $render = app::get('logisticsmanager')->render();
        $oOperation_log = &app::get('ome')->model('operation_log');
        $log_list = $oOperation_log->read_log(array('obj_id'=>$channel_id,'obj_type'=>'channel@logisticsmanager'),0,-1);
        
        foreach ($log_list as $k=>$v ) {
            $log_list[$k]['operate_time'] = date('Y-m-d H:i:s',$v['operate_time']);
        }

        $render->pagedata['log_list'] = $log_list;
        return $render->fetch('admin/channel/detail_log.html');
    }
    
     /**
     * 作废物流单号.
     * @param  
     * @return  
     * @access  
     * @author sunjing@shopex.cn
     */
    var $column_recycle_waybill='本地作废';
    var $column_recycle_waybill_width = '80';
    function column_recycle_waybill($row)
    {
        $waybillObj = app::get('logisticsmanager')->model('waybill');
        $filter = array('status'=>2);
        $filter['channel_id'] = $row[$this->col_prefix.'channel_id'];
        $filter['logistics_code'] = $row[$this->col_prefix.'logistics_code'];

        $count = $waybillObj->count($filter);
        
        
        return "<span class='show_list' channel_id=".$filter['channel_id']." billtype='recycle' ><a >".$count."</a></span>";
    }

    /**
     * 作废物流单号.
     * @param  
     * @return  
     * @access  
     * @author sunjing@shopex.cn
     */
    var $column_use_waybill='本地已用';
    var $column_use_waybill_width = '80';
    function column_use_waybill($row)
    {
        $waybillObj = app::get('logisticsmanager')->model('waybill');
        $filter = array('status'=>1);
        $filter['channel_id'] = $row[$this->col_prefix.'channel_id'];
        $filter['logistics_code'] = $row[$this->col_prefix.'logistics_code'];

        $count = $waybillObj->count($filter);
        
        
        return "<span class='show_list' channel_id=".$filter['channel_id']." billtype='used' ><a >".$count."</a></span>";
    }
    /**
     * 店铺地址
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function detail_shop_address($channel_id)
    {
        $render = app::get('logisticsmanager')->render();
        $channelObj =  app::get('logisticsmanager')->model('channel');
        $channel_detail = $channelObj->dump($channel_id,'channel_type,bind_status');
        $render->pagedata['channel_id'] = $channel_id;
        $render->pagedata['channel_detail'] = $channel_detail;
        
        $extendObj = app::get('logisticsmanager')->model('channel_extend');
        $extend = $extendObj->dump(array('channel_id'=>$channel_id),'*');
        $render->pagedata['show_shop_address'] = $show_shop_address;
        $render->pagedata['extend_detail'] = $extend;
        $render->pagedata['channel_id'] = $channel_id;
        unset($extend);
        return $render->fetch('admin/channel/detail_address.html');
    }









}
