<?php
class ome_mdl_shop extends dbeav_model{

    static $restore = false;

    /**
    * 快速查询店铺信息
    * @access public
    * @param mixed $shop_id 店铺ID
    * @param String $cols 字段名
    * @return Array 店铺信息
    */
    function getRow($filter,$cols='*'){
        if (empty($filter)) return array();
        
        $shop = $this->dump($filter,$cols);
        if($shop){
           return $shop;
        }else{
           return false;
        }
    }
    
    function gen_id($shop_bn){
        if(empty($shop_bn)){
            return false;
        }else{
            $shop_id = md5($shop_bn);
            if($this->db->selectrow("SELECT shop_id FROM sdb_ome_shop WHERE shop_id='".$shop_id."'")){
                return false;
            }else{
                return $shop_id;
            }
        }
    }
    
    function save(&$data){
 
        if(isset($data['config']) && is_array($data['config'])){
            $config = $data['config'];
            if($config['password']) $config['password'] = $this->aes_encode($config['password']);
            unset($data['config']);
            $data['config'] = serialize($config);
        }
        $data['active'] = 'true';

        if(self::$restore){
            return parent::save($data);
        }else{
            if(!$data['shop_id']){
                $shop_id = $this->gen_id($data['shop_bn']);
                if($shop_id){
                    $data['shop_id'] = $shop_id;
                }else{
                    return false;
                }
                parent::save($data); 
                return true;
            }else{
                return parent::save($data);    
            }
        }
    }
    
    public function insert(&$data){
        if(parent::insert($data)){
            foreach(kernel::servicelist('ome_shop_ex') as $name=>$object){
                if(method_exists($object,'insert')){
                    $object->insert($data);
                }
            }
            return true;
        }else{
            return false;
        }
    }
    
    public function update($data,$filter,$mustUpdate = null){
        if(parent::update($data,$filter,$mustUpdate)){
            foreach(kernel::servicelist('ome_shop_ex') as $name=>$object){
                if(method_exists($object,'update')){
                    $object->update($data);
                }
            }
            return true;
        }else{
            return false;
        }
    }
    
    public function delete($filter,$subSdf = 'delete'){
        if(parent::delete($filter)){
            foreach(kernel::servicelist('ome_shop_ex') as $name=>$object){
                if(method_exists($object,'delete')){
                    $object->delete($filter);
                }
            }
            return true;
        }else{
            return false;
        }
    }
    
    /*需要删除的代码
    function shop_callback($log_id,$msg){
        $decode_msg = json_decode($msg,true);
        
        if($decode_msg && $decode_msg['res'] == 'succ'){
            $status = 'success';
        }else{
            $status = 'fail';
        }
        
        app::get('ome')->model('api_log')->update_log($log_id,$msg,$status);
    }*/
    
    //店铺类型
   function modifier_shop_type($row){
       $tmp = ome_shop_type::get_shop_type();
       return $tmp[$row];
    }
    
    
    function pre_recycle($data){
        $filter = $data;
        unset($filter['_finder']);
        if($data['isSelectedAll'] == '_ALL_'){
            $shop = $this->getList('shop_id',$filter);
            foreach($shop as $v){
                $shop_id[] = $v['shop_id'];
            }
        }else{
            $shop_id = $data['shop_id'];
        }
        if ($data){
            $orderObj = app::get('ome')->model('orders');
            $relation = app::get('ome')->getConf('shop.branch.relationship');
            foreach ($data as $key=>$val){
                //判断是否已绑定，否则无法删除
                if ($val['node_id']){
                    $this->recycle_msg = '店铺:'.$val['name'].'已绑定，无法删除!';
                    return false;
                }
                //查看是否有订单
                $order_count = $orderObj->count(array('shop_id'=>$val['shop_id']));
                if ($order_count>0) {
                    $this->recycle_msg = '店铺:'.$val['name'].'已有相关订单,不可以删除!';
                    return false;
                }
                unset($relation[$val['shop_bn']]);
            }

            app::get('ome')->setConf('shop.branch.relationship',$relation);
        }
        //$syndata['shop_id'] = implode(",",$shopid);
        return true;
    }
    
    function pre_delete($shop_id){
        return true;
    }
    
    function pre_restore($shop_id){
        self::$restore = true;
        return true;
    }

    /*需要删除的代码
    function get_format_post($certi_app,$post=array(),$format='json'){
        $post_basic = array(
            'certi_app' => $certi_app,
            'certificate_id' => kernel::single("base_certificate")->get('certificate_id'),
            'app_id' => 'ecos.ome', //默认就用ecos，以后有新app再和申请到的license进行绑定
            'app_instance_id' => '',
            'version' => '1.0',
            'certi_url' => kernel::base_url(1),
            'certi_session' => kernel::single('base_session')->sess_id(),
            //'certi_validate_url' => kernel::api_url('api.shop_callback','certi_validate'),
            'format' => $format,
            'shop_version'=>''
        );
        $post = array_merge($post,$post_basic);
        $post['certi_ac'] = $this->make_shopex_ac($post,kernel::single("base_certificate")->get('token'));
        return $post;
    }

    function make_shopex_ac($temp_arr,$token){
        ksort($temp_arr);
        $str = '';
        foreach($temp_arr as $key=>$value){
            if($key!='certi_ac') {
                $str.=$value;
            }
        }
        return md5($str.$token);
    }
    
    function to_shopex_certificate($format_post){
        $url = LICENSE_CENTER;
        $res = kernel::single('base_httpclient')->post($url,$format_post);
        return $res;
    }*/

    function aes_encode($str){
        $aes = kernel::single('ome_aes',true);// 把加密后的字符串按十六进制进行存储
        $key = kernel::single("base_certificate")->get('token');// 密钥
        $keys = $aes->makeKey($key);

        $ct = $aes->encryptString($str, $keys);
        return $ct;
    }
    
    function aes_decode($str){
        $aes = kernel::single('ome_aes',true);// 把加密后的字符串按十六进制进行存储
        $key = kernel::single("base_certificate")->get('token');// 密钥
        $keys = $aes->makeKey($key);
        
        $dt = $aes->decryptString($str, $keys);
        
        return $dt;
    }
    
    function searchOptions(){
        return array(
                
            );
    }

    
    /**
     * 返回店铺类型
     * @param   type    $varname    description
     * @return  type    description
     * @access  public
     * @author cyyr24@sina.cn
     */
    function getShoptype($shop_id){
        $shop = $this->dump($shop_id);
        $shop_type = $shop['shop_type'];
        if ($shop_type == 'taobao') {
            if (strtoupper($shop['tbbusiness_type']) == 'B') {
                $shop_type = 'tmall';
            }
        }
        return $shop_type;
        
    }

    function get_taobao_name(){
        $shop = $this->getList('name', array('node_type' => 'taobao'));
        if($shop) {
            foreach($shop as $key => $val) {
                $arrName[] = $val['name'];
            }
            return implode(',', $arrName);
        } else {
            return false;
        }
    }
}
?>