<?php
/**
* 订单量数据
* @author chenjun 2013.04.15
* @copyright shopex.cn
*/
class ome_collection_func_order{
    public $beginTime;
    public $endTime;
    public $shop_id;

    public $field = array(
        'enumproductid',#来源标识
        'shopexid',#SHOPEXID
        'email',#EMAIL
        'bingingshopnumber',#绑定店铺数
        'accountmember',#系统用户数
        'accountroleamount',#系统角色数
        'skunumber',#当天售卖SKU数
        'inventorynumber',#当天库存数
        'storageamount',#仓库数量
        'orderamount',#已付款订单量(根据支付时间范围)
        'shippedorder',#已发货订单量(根据发货时间范围)
        'customerserviceorder',#退换货订单量
        'customerunit',#已支付订单总金额/已支付订单数(客单价)
        'achievement',#店铺销售额(已支付订单总金额)
        'matrixid',#矩阵nodeid
        'matrixtype',#矩阵类型
        'shipmentsnumber',#店铺发货量
        'locallicenseid',#本地licenseid
        'localmatrixid',#本地matrixid
        'localmatrixtype',#本地matrixtype
        'createtime',#数据创建时间
    );

    /**
    * 根据所有店铺获取订单量数据
    * @access public
    * @return array
    */
    public function getData(){
        $shop_list = app::get('ome')->model('shop')->getList('shop_id,node_type,name,node_id', array('active'=>'true'), 0, -1);
        $data = array();
        foreach($shop_list as $shop){
            $sdata = array();
            $this->shop_id = $shop['shop_id'];
            foreach($this->field as $field){
                $method = 'get_'.$field;
                if(method_exists($this,$method)){
                    $value = $this->$method();
                    $sdata[$field] = $value == '' ? 0 : $value;
                }
            }
            $data[] = $sdata;
        }
        return $data;
    }

    /**
    * 来源标识
    * 
    * @return number
    */
    public function get_enumproductid(){
        return 33;
    }

    /**
    * SHOPEXID
    * 
    * @return number
    */
    public function get_shopexid(){
        return base_enterprise::ent_id();
    }

    /**
    * EMAIL
    * 
    * @return string
    */
    public function get_email(){
        return kernel::single('ome_collection_request')->get_manage_email();
    }

    /**
    * 绑定店铺数
    * 
    * @return number
    */
    public function get_bingingshopnumber(){
        $sql = 'SELECT COUNT(*) AS _count FROM sdb_ome_shop WHERE node_id != \'\'';
        $result = kernel::database()->selectrow($sql);
        return $result['_count'];
    }

    /**
    * 系统用户数
    * 
    * @return number
    */
    public function get_accountmember(){
        $sql = 'SELECT COUNT(*) AS _count FROM sdb_pam_account';
        $result = kernel::database()->selectrow($sql);
        return $result['_count'];
    }

    /**
    * 系统角色数
    * 
    * @return number
    */
    public function get_accountroleamount(){
        $sql = 'SELECT COUNT(*) AS _count FROM sdb_desktop_roles';
        $result = kernel::database()->selectrow($sql);
        return $result['_count'];
    }

    /**
    * 当天售卖SKU数
    * 
    * @return number
    */
    public function get_skunumber(){
        $sql = 'SELECT COUNT(*) AS _count FROM sdb_ome_order_items WHERE order_id in(
            SELECT order_id FROM sdb_ome_orders WHERE createtime >= '.$this->beginTime.' AND createtime <= '.$this->endTime.'
        )';
        $result = kernel::database()->selectrow($sql);
        return $result['_count'];
    }

    /**
    * 当天库存数
    * 
    * @return number
    */
    public function get_inventorynumber(){
        $sql = 'SELECT (SUM(store)-SUM(store_freeze)) AS _count FROM sdb_ome_branch_product';
        $result = kernel::database()->selectrow($sql);
        return $result['_count'];
    }

    /**
    * 仓库数量
    * 
    * @return number
    */
    public function get_storageamount(){
        $sql = 'SELECT COUNT(*) AS _count FROM sdb_ome_branch';
        $result = kernel::database()->selectrow($sql);
        return $result['_count'];
    }

    /**
    * 已付款订单量(根据支付时间范围)
    * 
    * @return number
    */
    public function get_orderamount(){
        $sql = 'SELECT COUNT(*) AS _count FROM sdb_ome_orders WHERE shop_id = \''.$this->shop_id.'\' AND  paytime >= '.$this->beginTime.' AND paytime <='.$this->endTime;
        $result = kernel::database()->selectrow($sql);
        return $result['_count'] ? $result['_count'] : 0;
    }

    /**
    * 已发货订单量(根据发货时间范围)
    * 
    * @return number
    */
    public function get_shippedorder(){
        $sql = 'SELECT COUNT(*) AS _count FROM sdb_ome_orders WHERE ship_status = \'1\' AND order_id in(
            SELECT distinct order_id FROM sdb_ome_delivery_order WHERE delivery_id in(
                SELECT delivery_id FROM sdb_ome_delivery WHERE shop_id = \''.$this->shop_id.'\' AND  delivery_time >= '.$this->beginTime.' AND delivery_time <='.$this->endTime.'
            )
        )';
        $result = kernel::database()->selectrow($sql);
        return $result['_count'];
    }

    /**
    * 退换货订单量
    * 
    * @return number
    */
    public function get_customerserviceorder(){
        $sql = 'SELECT COUNT(*) AS _count FROM sdb_ome_orders WHERE ship_status in(3,4) AND order_id in(
            SELECT distinct order_id FROM sdb_ome_reship WHERE t_end >= '.$this->beginTime.' AND t_end <= '.$this->endTime.'
        )';
        $result = kernel::database()->selectrow($sql);
        return $result['_count'];
    }

    /**
    * 已支付订单总金额/已支付订单数(客单价)
    * 
    * @return number
    */
    public function get_customerunit(){
        $orderamount = $this->get_orderamount();
        $achievement = $this->get_achievement();
        if($orderamount == 0 || $achievement == 0) $customerunit = 0;
        else $customerunit = $achievement/$orderamount;
        return $customerunit;
    }

    /**
    * 店铺销售额(已支付订单总金额)
    * 
    * @return number
    */
    public function get_achievement(){
        $sql = 'SELECT SUM(total_amount) AS _count FROM sdb_ome_orders WHERE shop_id = \''.$this->shop_id.'\' AND paytime >= '.$this->beginTime.' AND paytime <= '.$this->endTime;
        $result = kernel::database()->selectrow($sql);
        return $result['_count'] ? $result['_count'] : 0;
    }


    /**
    * 店铺nodeid
    * 
    * @return number
    */
    public function get_matrixid(){
        $shop = app::get('ome')->model('shop')->getlist('*',array('shop_id'=>$this->shop_id));
        return $shop[0]['node_id'];
    }

    /**
    * 店铺nodetype
    * 
    * @return number
    */
    public function get_matrixtype(){
        $shop = app::get('ome')->model('shop')->getlist('*',array('shop_id'=>$this->shop_id));
        return $shop[0]['node_type'];
    }

    /**
     * 店铺发货量
     * 
     * @return number
     */
    public function get_shipmentsnumber(){
        $sql = 'SELECT COUNT(*) AS _count FROM sdb_ome_delivery WHERE shop_id = \''.$this->shop_id.'\' AND status = \'succ\' AND delivery_time >= '.$this->beginTime.' AND delivery_time <='.$this->endTime;
        $result = kernel::database()->selectrow($sql);
        return $result['_count'] ? $result['_count'] : 0;
    }

    /**
     * 当前LicenseId
     * 
     * @return number
     */
    public function get_locallicenseid(){
        return base_certificate::get('certificate_id');
    }

    /**
    * 当前nodeid
    * 
    * @return number
    */
    public function get_localmatrixid(){
        return base_shopnode::node_id('ome');
    }

    /**
    * 当前nodetype
    * 
    * @return number
    */
    public function get_localmatrixtype(){
        return 'ecos.tg';
    }

    /**
    * 数据创建时间
    * 
    * @return number
    */
    public function get_createtime(){
        return time();
    }



}