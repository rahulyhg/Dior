<?php
class logisticsmanager_ctl_admin_channel extends desktop_controller{
    //1.渠道能获取哪些快递单号
    //2.渠道获取到的快递单号哪些店铺能用。
    public function index(){
        $this->finder('logisticsmanager_mdl_channel', array(
            'actions'=>array(
                array('label' => '添加来源', 'href' => 'index.php?app=logisticsmanager&ctl=admin_channel&act=add','target' => 'dialog::{width:620,height:360,title:\'来源添加/编辑\'}'),
                array('label' => '启用', 'submit' => 'index.php?app=logisticsmanager&ctl=admin_channel&act=toStatus&status=true','target'=>'refresh'),
                array('label' => '关闭', 'submit' => 'index.php?app=logisticsmanager&ctl=admin_channel&act=toStatus&status=false','target'=>'refresh'),
            ),
            'title' => '电子面单来源',
            'use_buildin_recycle' => false,
            'use_buildin_setcol' => false,
        ));

        $html = <<<EOF
        <script>
              $$(".show_list").addEvent('click',function(e){
                  var billtype = this.get('billtype');
                  var channel_id = this.get('channel_id');
                  var t_url ='index.php?app=logisticsmanager&ctl=admin_waybill&act=findwaybill&channel_id='+channel_id+'&billtype='+billtype;
              var url='index.php?app=desktop&act=alertpages&goto='+encodeURIComponent(t_url);
    	Ex_Loader('modedialog',function() {
			new finderDialog(url,{width:1000,height:660,
				
			});
		});
              });

        </script>
EOF;
        echo $html;exit;
    }

    public function add(){
        $this->_edit();
    }

    public function edit($channel_id){
        $this->_edit($channel_id);
    }
    
    private function _edit($channel_id=NULL){
        if($channel_id){
            $channelObj = &$this->app->model("channel");
            $channel = $channelObj->dump($channel_id);
            if($channel['channel_type']=='ems') {
                $emsinfo = explode('|||',$channel['shop_id']);
                $channel['emsuname'] = $emsinfo[0];
                $channel['emspasswd'] = $emsinfo[1];
            }
            elseif($channel['channel_type']=='360buy') {
                $jdinfo = explode('|||',$channel['shop_id']);
                $channel['shop_id'] =$jdinfo[1]; 
                $channel['jdbusinesscode'] = $jdinfo[0];
            }
            elseif ($channel['channel_type'] == 'sf') {
                $sfinfo = explode('|||', $channel['shop_id']);
                $channel['sfbusinesscode'] = $sfinfo[0];
                $channel['sfpassword'] = $sfinfo[1];
                $channel['pay_method'] = $sfinfo[2];
                $channel['sfcustid'] = $sfinfo[3];
            }
            elseif ($channel['channel_type'] == 'yunda') {
                $yundainfo = explode('|||', $channel['shop_id']);
                $channel['yundauname'] = $yundainfo[0];
                $channel['yundapassword'] = $yundainfo[1];
            }
            elseif ($channel['channel_type'] == 'sto') {
                $stoinfo = explode('|||', $channel['shop_id']);
                $channel['sto_custname'] = $stoinfo[0];
                $channel['sto_cutsite'] = $stoinfo[1];
                $channel['sto_cutpwd'] = $stoinfo[2];
            }
            $shopSql = "SELECT shop_id,name FROM sdb_ome_shop";
            
        } else {
            $shopSql = "SELECT shop_id,name FROM sdb_ome_shop WHERE node_type='taobao' and node_id IS NOT NULL";
        }

        //来源类型信息
        $funcObj = kernel::single('logisticsmanager_waybill_func');
        $channels = $funcObj->channels();
        $this->pagedata['channels'] = $channels;
        $jdshopSql = "SELECT shop_id,name FROM sdb_ome_shop WHERE node_type='360buy' and node_id IS NOT NULL";
        $jdshopList = kernel::database()->select($jdshopSql);
        $this->pagedata['jdshopList'] = $jdshopList;
        //获取店铺列表
        $shopList = kernel::database()->select($shopSql);
        $this->pagedata['shopList'] = $shopList;

        //物流公司信息
        if($channel['channel_type']) {
            $wlbObj = kernel::single('logisticsmanager_waybill_'.$channel['channel_type']);
            $logistics = $wlbObj->logistics();
            $this->pagedata['logistics'] = $logistics;
        }
        if ($channel['pay_method']) {
            $wlbObj = kernel::single('logisticsmanager_waybill_'.$channel['channel_type']);
            $pay_method = $wlbObj->pay_method();
            $this->pagedata['pay_method'] = $pay_method;
        }
        $this->pagedata['channel'] = $channel;

        $this->display("admin/channel/channel.html");
    }

    public function do_save(){
        $data = array();
        $data['name'] = $_POST['name'];
        $data['channel_type'] = $_POST['channel_type'];
        $channelObj = &$this->app->model('channel');
        
        if($data['channel_type']=='ems') {
            $_POST['shop_id'] = $_POST['emsuname'].'|||'.$_POST['emspasswd'];
            $data['shop_id'] = $_POST['shop_id'];

            //绑定EMS
            if(!$_POST['channel_id'] || $_POST['bind_status']=='false') {
                $emsRpcObj = kernel::single('logisticsmanager_rpc_request_ems');
                $bind_status = $emsRpcObj->bind();
                if($bind_status) {
                    $data['bind_status'] = 'true';
                }
            }
        }
        elseif ($data['channel_type'] == '360buy') {
            $_POST['shop_id'] = $_POST['jdbusinesscode'] . '|||'.$_POST['jd_shop_id'];
            $data['shop_id'] = $_POST['shop_id'];
        }
        elseif ($data['channel_type'] == 'taobao') {
            if ($_POST['taobao_shop_id']) {
                $_POST['shop_id'] = $_POST['taobao_shop_id'];
                $data['shop_id'] = $_POST['shop_id'];
            }
        }
        elseif ($data['channel_type'] == 'sf') {
            if ($_POST['channel_id']) {
                $channel = $channelObj->dump(array('channel_id' => $_POST['channel_id']));
                $sfinfo = explode('|||', $channel['shop_id']);
                $_POST['pay_method'] = $sfinfo[2];
            }
            $_POST['shop_id'] = $_POST['sfbusinesscode'] . '|||' . $_POST['sfpassword'] . '|||' . $_POST['pay_method'] . '|||' . $_POST['sfcustid'];
            $data['shop_id'] = $_POST['shop_id'];
            //绑定顺丰
            if (!$_POST['channel_id'] || $_POST['bind_status']=='false') {
                $sfRpcObj = kernel::single('logisticsmanager_rpc_request_sf');
                $bind_status = $sfRpcObj->bind();
                if ($bind_status) {
                    $data['bind_status'] = 'true';
                }
            }                           
        }
        elseif ($data['channel_type'] == 'yunda') {
            $_POST['shop_id'] = $_POST['yundauname'].'|||'. $_POST['yundapassword'];
            $data['shop_id'] = $_POST['shop_id'];
            if (!$_POST['channel_id'] || $_POST['bind_status']=='false') {
                $yundaRpcObj = kernel::single('logisticsmanager_rpc_request_yunda');
                $bind_status = $yundaRpcObj->bind();
                if ($bind_status) {
                    $data['bind_status'] = 'true';
                }
            }
        }
        elseif ( $data['channel_type'] == 'sto') {
            $_POST['shop_id'] = $_POST['sto_custname'].'|||'. $_POST['sto_cutsite'].'|||'.$_POST['sto_cutpwd'];
            $data['shop_id'] = $_POST['shop_id'];
            if (!$_POST['channel_id'] || $_POST['bind_status']=='false') {
                $stoRpcObj = kernel::single('logisticsmanager_rpc_request_sto');
                $bind_status = $stoRpcObj->bind();
                
                if ($bind_status) {
                    $data['bind_status'] = 'true';
                    
                }
            }
        }
        if($_POST['channel_id']){
            //更新渠道
            $channelObj->update($data,array('channel_id'=>$_POST['channel_id']));
            $data['channel_id'] = $_POST['channel_id'];
        }else{
            if(!$_POST['shop_id']) {
                echo '请选择主店铺!';
                exit;
            }
            if(!$_POST['logistics_code']) {
                echo '请选择物流公司!';
                exit;
            }
            $filter = array(
                'shop_id' => $_POST['shop_id'],
                'logistics_code' => $_POST['logistics_code'],
            );
            if($data['channel_type']=='ems') {
              
                $filter['channel_type'] = 'ems';
                unset($filter['shop_id']);
                $filter['shop_id|head'] = $_POST['emsuname'];
            }
            elseif ($data['channel_type']=='360buy') {
                $filter['channel_type'] = '360buy';
                $filter['shop_id'] = $_POST['jdbusinesscode'] . '|||';;
                //unset($filter['shop_id']);
            }
            elseif ($data['channel_type'] == 'taobao') {
                $filter['channel_type'] = 'taobao';
            }
            elseif ($data['channel_type'] == 'sf') {
                $filter['channel_type'] = 'sf';
            } 
            elseif ($data['channel_type'] == 'yunda') {
                $filter['channel_type'] = 'yunda';
                unset($filter['shop_id']);
            }

            $channel = $channelObj->dump($filter,'channel_id');
            if($channel) {
                echo '已经添加过相同来源，无需重复添加!';
                exit;
            }
            //添加渠道
            $data['shop_id'] = $_POST['shop_id']; //不允许更新
            $data['logistics_code'] = $_POST['logistics_code']; //不允许更新
            $data['create_time'] = time();
            $channelObj->insert($data);
            
            if ($data['bind_status']=='true' && $data['channel_type'] == 'sto') {
                $this->get_waybill_sto($data['channel_id']);
            }
            if ($data['channel_type'] == 'taobao') {//默认获取发货地址
                $extendObj = app::get('logisticsmanager')->model('channel_extend');
                $extend = $extendObj->dump(array('channel_id'=>$data['channel_id']),'id');
                if (!$extend) {
                    $waybillObj = kernel::single('logisticsmanager_service_waybill');
                    $waybillObj->get_ship_address($data['channel_id']);
                }
            }

        }

        echo "SUCC";
    }

    public function toStatus(){
        $this->begin('index.php?app=logisticsmanager&ctl=admin_channel&act=index');
        if($_GET['status'] && $_GET['status']=='true'){
            $data['status'] = 'true';
        }else{
            $data['status'] = 'false';
        }

        if($_POST['channel_id'] && is_array($_POST['channel_id'])){
            $filter = array('channel_id'=>$_POST['channel_id']);
        }elseif($_POST['isSelectedAll'] && $_POST['isSelectedAll'] == '_ALL_'){
            $filter = array();
        }else{
            $this->end(false, '操作失败。');
        }

        $channelObj = app::get('logisticsmanager')->model('channel');
        $channelObj->update($data,$filter);
        $this->end(true, '操作成功。');
    }

    public function getLogistics() {
        $type = $_POST['type'];
        $wlbObj = kernel::single('logisticsmanager_waybill_'.$type);
        $logistics = $wlbObj->logistics();
        $result = $logistics ? json_encode($logistics) : '';

        echo $result;
    }

    public function getPayMethod() {
        $type = $_POST['type'];
        $wlbObj = kernel::single('logisticsmanager_waybill_'.$type);
        $payMethondList = $wlbObj->pay_method();
        $result = $payMethondList ? json_encode($payMethondList) : '';

        echo $result;
    }

    
    /**
     * 获取申通电子面单绑定成功后.
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    public function get_waybill_sto($channel_id)
    {
        $db = kernel::database();
        $waybillObj = kernel::single('logisticsmanager_service_waybill');
        $sql = "SELECT * FROM sdb_logisticsmanager_channel WHERE channel_type='sto' AND bind_status='true' AND channel_id=$channel_id";
        $channel = $db->select($sql);
        
        if ($channel) {
            foreach ($channel as $info ) {
                if ( $info && in_array($info['channel_type'],array('sto'))) {
                    $limit = 500;
                    $page = ceil($limit/100);
                    for($i=0;$i<$page;$i++){
                        //获取电子面单后去更新
                        $wbParams = array(
                            'channel_id' => $info['channel_id'],
                        );
                        $waybillObj->request_waybill($wbParams);
                        unset($wbParams);
                        
                    }
                }
                
            }
            
        }
    }

    
    /**
     * 获取发货地址.
     * @param  $channel_id   
     * @return  address
     * @access  public
     * @author sunjing@shopex.cn
     */
    function get_ship_address($channel_id)
    {
        $this->pagedata['finder_id'] = $_GET['finder_id'];
        $this->pagedata['channel_id'] = $channel_id;
        $this->display('admin/channel/download_address.html');
    }

    
    /**
     * 下载发货地址
     * @param  
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function download_address($channel_id)
    {
        $rsp = array('rsp'=>'succ','msg'=>'获取成功');
        $waybillObj = kernel::single('logisticsmanager_service_waybill');
        
        $rsp = $waybillObj->get_ship_address($channel_id);
        echo json_encode($rsp);
    }

    
    /**
     * 保存地址
     * @param 
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function save_address()
    {
        
        $rsp = array('rsp'=>'succ','msg'=>'获取成功');
        $extendObj = app::get('logisticsmanager')->model('channel_extend');
        $ext_data = array(
            'province'=>$_POST['province'],
            'city'=>$_POST['city'],
            'area'=>$_POST['area'],
            'address_detail'=>$_POST['address_detail'],
            'default_sender'=>$_POST['default_sender'],
            'mobile'=>$_POST['mobile'],
            'tel'=>$_POST['tel'],
            'shop_name'=>$_POST['shop_name'],
            'zip'=>$_POST['zip'],
        );
        if ($_POST['id']) {
            $ext_data['id']=$_POST['id'];  
        }

        if ($_POST['channel_id']) {
            $ext_data['channel_id']=$_POST['channel_id'];  
        }
        $extendObj->save($ext_data);
        echo json_encode($rsp);
    }

    
    /**
     * 选择店铺.
     * @param   
     * @return  
     * @access  public
     * @author sunjing@shopex.cn
     */
    function select_shop()
    {
        $shopObj = app::get('ome')->model('shop');
        $shop = $shopObj->getlist('area,zip,addr,default_sender,mobile');
        
    }

    function findShop(){

        $params = array(
            'title'=>'店铺列表',
            'use_buildin_new_dialog' => false,
            'use_buildin_set_tag'=>false,
            'use_buildin_recycle'=>false,
            'use_buildin_export'=>false,
            'use_buildin_import'=>false,
            'use_buildin_filter'=>true,
            
        );
        $this->finder('ome_mdl_shop', $params);

    } 

    /*
     * 通过id获取地址
     */
    function getShopById(){
        
        $shop_id = $_POST['id'];
        if ($shop_id){
            $shopObj = app::get('ome')->model('shop');
            $shop = $shopObj->dump(array('shop_id'=>$shop_id));
            $area = explode(':',$shop['area']);
            $area = explode('/',$area[1]);
            $tmp = array(
                'province'    =>$area[0],
                'city'       =>$area[1],
                'area'      =>$area[2],
                'address_detail'  =>$shop['addr'],
                'default_sender'         =>$shop['default_sender'],
                'tel'=>$shop['tel'],
                'mobile'=>$shop['mobile'],
                'shop_name'=>$shop['name'],
                'zip'=>$shop['zip'],
            );
            echo json_encode($tmp);
            
        }
    }

       
}