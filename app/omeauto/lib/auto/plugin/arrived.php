<?php

/**
 * 是否能到判断
 *
 * 
 * 
 */
class omeauto_auto_plugin_arrived extends omeauto_auto_plugin_abstract implements omeauto_auto_plugin_interface {


    public $address = '';
    public $area = '';
    public static $corpList ='';
    public $corp = '';
    public $apiurl = '';
    const SEARCH_APP_SECRET = '4b491ea061808883c8d5c84305fc37fd2eb108e25edde5145c58501f92dbc245';
    /**
     * 状态码
     * 
     * @var Integer
     */
    protected $__STATE_CODE = omeauto_auto_const::_LOGIST_ARRIVED;

   

    /**
     * 开始处理
     *
     * @param omeauto_auto_group_item $group 要处理的订单组
     * @return Array
     */
    public function process(& $group, &$confirmRoles) {
        $arrived_conf = app::get('ome')->getConf('ome.logi.arrived');
        $arrived_auto_conf = app::get('ome')->getConf('ome.logi.arrived.auto');
        $allow = true;
        $checkCorp = $this->getCheckCorp();
        if ($arrived_conf=='1' && $arrived_auto_conf=='1') {
            $orders = $group->getOrders();
            foreach ($orders as $order ) {
                
                $area = $order['ship_area'];
                $addr = $order['ship_addr'];
                $orderId = $order['order_id'];
                $corps = $group->getDlyCorp();
                $corp_id = $corps['corp_id'];
              
                $this->setAddress($area,$addr);
                $this->corp=$corps['type'];
                
                if (!in_array($this->corp,$checkCorp)) {
                    return true;
                }
                $result = $this->request();

                if(empty($result)) {
                    return true;
                }
                if (in_array($result,array('0','2','3'))) {
                    $allow = false;
                    $group->setOrderStatus($orderId, $this->getMsgFlag());
                }

            }
        }
        if ( !$allow ) {
            $group->setStatus(omeauto_auto_group_item::__OPT_HOLD, $this->_getPlugName());
        }
    }

    public function setAddress($area, $addr) {
        $areas =explode(':', $area);
        $area = $areas[1];
        $this->address = $area.$addr;
        
        $area = preg_replace("/省|市|县|区/","",$area);
        $this->area = $area;
    }

    
    public function getCorp($corp_id)
    {
        $corpObj = app::get('ome')->model('dly_corp');
        if (self::$corpList[$corp_id] === null) {
            $corp_detail = $corpObj->dump($corp_id,'type');
            self::$corpList['corp_id'] = $corp_detail['type'];
        }

        $this->corp = self::$corpList[$corp_id];

    }
   /**
    *请求接口获取结果
    * @param  
    * @return 
    * @access  public
    * @author sunjing@shopex.cn
    */
   public function request()
   {
       $shop = $this->get_shop();
       $params = array(
            	'area'=>$this->area,
                'addr'=>$this->address,
            	'exp'=>$this->corp,
                'method'=>'logistics/query',
                'from_node_id'=>base_shopnode::node_id('ome'),
                'token'=>base_certificate::token(),
                'certi_id'=>base_certificate::certi_id(),
                'to_node_id'=>$shop['node_id'],
                'node_type'=>'taobao',
                
        );
       $params['sign'] = self::gen_sign($params);
       $status = 0;
       $core_http = kernel::single('base_httpclient');
       $api_url = 'http://api.log.taoex.com/logistics/api.php';
       if ($params['to_node_id']) {
           $result = $core_http->set_timeout(10)->post($api_url, $params, $headers);
       
            $result = json_decode($result,true);
        
            $result = $result['data'];
       }
       
        if ($result) {
           $status = $result;
       }

       return $status;
       
   }
    
    /**
     * 获取该插件名称
     *
     * @param Void
     * @return String
     */
    public function getTitle() {

        return '物流到不到';
    }

    /**
     * 获取提示信息
     *
     * @param Array $order 订单内容
     * @return Array
     */
    public function getAlertMsg(& $order) {

        return array('color' => 'orange', 'flag' => '到', 'msg' => '物流公司不到');
    }

   
      
     /**
      *目前支持到不到物流查询列表
      * @param 
      * @return  
      * @access  public
      * @author sunjing@shopex.cn
      */
     public function getCheckCorp()
     {
         $corp = array('POST','EMS','EYB','GTO','HTKY','FAST','QFKD','UC','YTO','ZJS','ZTO','SF','TTKDEX','YUNDA');
         return $corp;
     }

     static function gen_sign($params){
         
        return strtoupper(md5(strtoupper(md5(self::assemble($params)))));
    }

    static function assemble($params)
    {
        if(!is_array($params))  return null;
        ksort($params, SORT_STRING);
        $sign = '';
        foreach($params AS $key=>$val){
            if(is_null($val))   continue;
            if(is_bool($val))   $val = ($val) ? 1 : 0;
            $sign .= $key . (is_array($val) ? self::assemble($val) : $val);
        }
        return $sign;
    }

    
    /**
     * 获取绑定淘宝店铺
     * @param 
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function get_shop()
    {
        $shopObj = app::get('ome')->model('shop');
        $shop_detail = $shopObj->db->selectrow("SELECT node_id FROM sdb_ome_shop WHERE shop_type='taobao' AND node_id!=''");
        return $shop_detail;
    }
}