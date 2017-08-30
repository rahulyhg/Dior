<?php
/**
 * api版本映射表
 * @author dqiujing@gmail.com
 * @access public
 * @copyright www.shopex.cn 2012
 *
 */
class ome_rpc_mapper{

    /**
    * ecstore 版本映射
    */
    private $ecosb2c = array(
        '1' => '1.0',
        '2' => '2.2'
    );

    /**
    * 店掌柜 版本映射
    */
    private $ecosdzg = array(
        '1' => '1.0',
        '2' => '2.2'
    );

    /**
    * b2b 版本映射
    */
    private $shopexb2b = array(
        '1' => '1.0',
        '3.2' => '2.2'
    );

    /**
    * 485 版本映射
    */
    private $shopexb2c = array(
        '1' => '1.0',
        '2' => '2.2',
    );

    /**
    * 淘管版本与淘管 API文件映射
    */
    private $tg = array(
        'base' => 'base',
        '1.0' => '1',
        '2.2' => '2',
    );
    
    
    function __construct(){
        $this->version_list = array(
            'shopex_b2c' => $this->shopexb2c,
            'shopex_b2b' => $this->shopexb2b,
            'ecos.b2c' => $this->ecosb2c,
            'ecos.dzg' => $this->ecosdzg,
        );
    }
    
    /**
     * 接收API版本文件路由
     * @access public
     * @param String $node_id 前端店铺节点标识
     * @param String $api_class 接口类名
     * @param String $api_method 接口方法
     * @param Array $params 接口参数
     * @return array('rsp'=>'保存状态success/fail','msg'=>'错误消息','data'=>'数据')
     */
    public function response_router($node_id,$api_class='',$api_method='',$params=array()){
        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (empty($node_id) || empty($api_class) || empty($api_method)){
            $rs['msg'] = '参数传递错误';
            return $rs;
        }else{
            $oResponse = kernel::single('ome_rpc_response');
            $shop_info = $oResponse->filter(array('node_id'=>$node_id));
            if ($shop_info['rsp'] == 'fail'){
                $rs['msg'] = $shop_info['msg'];
                return $rs;
            }else{
                #存储前端店铺API版本号
                $shop_api_v = isset($params['node_version']) ? $params['node_version'] : '';
                if (!empty($shop_api_v)){
                    $oFunc = kernel::single('ome_rpc_func');
                    $oFunc->store_shop_api_v($shop_api_v,$node_id);
                    $cur_ver = $shop_api_v;
                }else{
                    $cur_ver = $shop_info['api_version'];
                }

                $node_type = $shop_info['node_type'];
                if($shop_ver_list = $this->version_list[$node_type]){
                    $tg_ver = $this->ver_compre($node_type,$cur_ver,$shop_ver_list,'shop');
                }else{
                    $tg_ver = '1.0';
                }
                $tg_ver_list = $this->tg;
                $params['shop_id'] = $shop_info['shop_id'];
                $params['shop_name'] = $shop_info['name'];
                $params['shop_type'] = $shop_info['node_type'];
                $params['node_id'] = $shop_info['node_id'];
                $action_rs = $this->action($node_type,'tg',$tg_ver,$tg_ver_list,$api_class,$api_method,$params);
                
                if ($action_rs['rsp'] == 'success'){
                    $rs['rsp'] = 'success';
                }else{
                    $rs['msg'] = $action_rs['msg'];
                }
                $rs['data'] = $action_rs['data'];
                $rs['logTitle'] = $action_rs['logTitle'];
                $rs['logInfo'] = $action_rs['logInfo'];
                return $rs;
            }
        }
    }


    /**
     * 发起API版本文件路由
     * @access public
     * @param String $node_id 前端店铺节点号
     * @param String $api_class 接口类名
     * @param String $api_method 接口方法
     * @param Array $params 接口参数
     * @return array('rsp'=>'保存状态success/fail','msg'=>'错误消息','data'=>'数据')
     */
    public function request_router($node_id,$api_class='',$api_method='',$params=array()){

        $rs = array('rsp'=>'fail','msg'=>'','data'=>'');
        if (empty($node_id) || empty($api_class) || empty($api_method)){
            $rs['msg'] = '参数传递错误';
            return $rs;
        }else{
            #获取前端店铺API版本号
            $oFunc = kernel::single('ome_rpc_func');
            $shopObj = &app::get('ome')->model('shop');
            $shop_detail = $shopObj->getRow(array('node_id'=>$node_id),'shop_id,name,node_type,node_id');
        
            $node_type = $shop_detail['node_type'];
            $cur_ver = $oFunc->fetch_shop_api_v($node_id);

            if($shop_ver_list = $this->version_list[$node_type]){
                $tg_ver = $this->ver_compre($node_type,$cur_ver,$shop_ver_list,'shop');
            }else{
                $tg_ver = '1.0';
            }
            $tg_ver_list = $this->tg;
            $params['shop_id'] = $shop_detail['shop_id'];
            $params['shop_name'] = $shop_detail['name'];
            $params['shop_type'] = $shop_detail['node_type'];
            $params['node_type'] = $node_type;
            $params['node_id'] = $node_id;
            
            $action_rs = $this->action($node_type,'tg',$tg_ver,$tg_ver_list,$api_class,$api_method,$params,'request');
            if ($action_rs['rsp'] == 'success'){
                $rs['rsp'] = 'success';
            }else{
                $rs['msg'] = $action_rs['msg'];
            }
            $rs['data'] = $action_rs['data'];
            $rs['logTitle'] = $action_rs['logTitle'];
            $rs['logInfo'] = $action_rs['logInfo'];
            return $rs;
        }
    }


    private function action($node_type,$ver_type,$cur_ver,$his_ver,$class='',$method='',$params='',$api_type='response',$max=1){

        if ($cur_ver != 'base'){
            $api_ver = $this->ver_compre($node_type,$cur_ver,$his_ver,$ver_type);
        }else{
            $api_ver = 'base';
        }
        $api_class = 'ome_rpc_'.$api_type.'_version_'.$api_ver.'_'.$class;

        if (ome_func::class_exists($api_class) && $instance = kernel::single($api_class)){
            if (method_exists($instance,$method)){
                return $instance->$method($params);
            }
        }
        array_pop($his_ver);
        if ($max >= 30) return;#防止死循环
        static $max;
        $max += 1;
        return $this->action($node_type,$ver_type,$cur_ver,$his_ver,$class,$method,$params,$api_type,$max);
    }

    private function ver_compre($node_type,$cur_ver,$his_ver,$ver_type,$max=1){
        $tmp_ver = array_pop($his_ver);
        if ($ver_type == 'shop'){
            $ver_list = $this->version_list[$node_type];
        }else{
            $ver_list = $this->tg;
        }
        $tmp_ver_list = array_flip($ver_list);
        $compre_ver = $tmp_ver_list[$tmp_ver];
        if ($cur_ver == '' || $compre_ver == 'base'){
            return $ver_list['base'] ? $ver_list['base'] : $ver_list['1'];
        }else{
            if(version_compare($cur_ver,$compre_ver,'>=')){
                return $ver_list[$compre_ver];
            }else{
                if ($max >= 30) return;#防止死循环
                static $max;
                $max += 1;
                return $this->ver_compre($node_type,$cur_ver,$his_ver,$ver_type,$max);
            }
        }
    }

}