<?php
/**
* 对外接口函数
* 
* @copyright shopex.cn 2013.4.10
* @author dongqiujin<123517746@qq.com>
*/
class channel_func{

    /**
    * 判断渠道是否已绑定
    *
    * @access public
    * @param String $channel_id 渠道ID
    * @return bool
    */
    public function isBind($channel_id=''){

        $channel_adapter = app::get('channel')->model('channel');
        $detail = $channel_adapter->getList('node_id',array('channel_id'=>$channel_id),0,1);
        return isset($detail[0]) && !empty($detail[0]['node_id']) ? true : false;
    }

    /**
    * 存储渠道与适配器的关系
    * @access public
    * @param String $channel_id 渠道ID
    * @param String $adapter 适配器
    * @return bool
    */
    public function saveChannelAdapter($channel_id,$adapter){
        $adapterMdl = app::get('channel')->model('adapter');
        $adapter_sdf = array(
            'channel_id' => $channel_id,
            'adapter' => $adapter
        );
        return $adapterMdl->save($adapter_sdf);
    }

    /**
    * 获取wms适配器列表
    * @access public
    * @return Array 适配器
    */
    public function getWmsAdapterList(){
        return middleware_adapter::getWmsList();
    }

    /**
    * 获取所有WMS类型的渠道
    * @access public
    * @return Array 适配器
    */
    public function getWmsChannelList(){
        $channelMdl = app::get('channel')->model('channel');
        $channel_list = $channelMdl->getList('channel_id AS wms_id,channel_bn AS wms_bn,channel_name AS wms_name,node_id',array('channel_type'=>'wms'),0,-1);
        
        if($channel_list){
            foreach ($channel_list as &$val){
                $val['adapter'] = $this->getAdapterByChannelId($val['wms_id']);
            }
        }
        
        return $channel_list;
    }

    /**
    * 根据节点获取适配器
    *
    * @access public
    * @param String $node_id 节点号
    * @return Array 适配器
    */
    public function getAdapterByNodeId($node_id=''){

        $channelMdl = app::get('channel')->model('channel');
        $channel = $channelMdl->getList('channel_id',array('node_id'=>$node_id),0,1);

        $channel_adapter = app::get('channel')->model('adapter');
        $detail = $channel_adapter->getList('adapter',array('channel_id'=>$channel[0]['channel_id']),0,1);
        return isset($detail[0]) ? $detail[0]['adapter'] : '';
    }

    /**
    * 根据channel_id获取适配器
    *
    * @access public
    * @param String $channel_id 渠道ID
    * @return Array 适配器
    */
    public function getAdapterByChannelId($channel_id=''){

        $channel_adapter = app::get('channel')->model('adapter');
        $detail = $channel_adapter->getList('adapter',array('channel_id'=>$channel_id),0,1);
        return isset($detail[0]) ? $detail[0]['adapter'] : '';
    }

    /**
    * 根据wms_id获取wms_bn
    *
    * @access public
    * @param String $wms_id 渠道ID
    * @return String wms_bn
    */
    public function getWmsBnByWmsId($wms_id=''){

        $channel_adapter = app::get('channel')->model('channel');
        $detail = $channel_adapter->getList('channel_bn',array('channel_id'=>$wms_id),0,1);
        return isset($detail[0]) ? $detail[0]['channel_bn'] : '';
    }
    
    /**
    * 通过node_id获取渠道名称
    * @param String $node_id 节点号
    * @return String 
    */
    public function getChannelNameByNodeId($node_id=''){

        $channel_adapter = app::get('channel')->model('channel');
        $detail = $channel_adapter->getList('channel_name',array('node_id'=>$node_id),0,1);
        return isset($detail[0]) ? $detail[0]['channel_name'] : '';
    }

    
    /**
    * 通过wms_id获取渠道名称
    * @param String $wms_id wmsID
    * @return String 
    */
    public function getChannelNameById($wms_id=''){

        $channel_adapter = app::get('channel')->model('channel');
        $detail = $channel_adapter->getList('channel_name',array('channel_id'=>$wms_id),0,1);
        return isset($detail[0]) ? $detail[0]['channel_name'] : '';
    }

    /**
    * 根据channel_id获取node_id节点号
    * @param String $channel_id 渠道ID
    * @return String 
    */
    public function getNodeIdByChannelId($channel_id=''){

        $channel_adapter = app::get('channel')->model('channel');
        $detail = $channel_adapter->getList('node_id',array('channel_id'=>$channel_id),0,1);
        return isset($detail[0]) ? $detail[0]['node_id'] : '';
    }

    /**
    * 根据node_id获取wms_id
    * @param String $node_id 节点号
    * @return String 
    */
    public function getWmsIdByNodeId($node_id=''){

        $channel_adapter = app::get('channel')->model('channel');
        $detail = $channel_adapter->getList('channel_id',array('node_id'=>$node_id),0,1);
        return isset($detail[0]) ? $detail[0]['channel_id'] : '';
    }

    /**
     * 获取渠道类型
     *
     * @param Int $channel_id
     * @return void
     * @author 
     **/
    public function getWmsNodeTypeById($channel_id)
    {
        $channel_adapter = app::get('channel')->model('channel');

        $channel = $channel_adapter->dump($channel_id,'node_type');

        return $channel['node_type'];
    }

    /**
    * 根据node_id获取adapter_type
    * @param String $node_id
    * @return String 
    */
    public function getAdapterTypeByNodeId($node_id=''){
        $channel_adapter = app::get('channel')->model('channel');
        $detail = $channel_adapter->getList('channel_type',array('node_id'=>$node_id),0,1);
        return isset($detail[0]) ? $detail[0]['channel_type'] : '';
    }

    /**
    * 是否自有仓储
    * @param String $wms_id
    * @return bool 
    */
    public function isSelfWms($wms_id=''){

        $channel_adapter = app::get('channel')->model('adapter');
        $detail = $channel_adapter->getList('adapter',array('channel_id'=>$wms_id),0,1);
        return isset($detail[0]) && $detail[0]['adapter']=='selfwms' ? true : false;
    }

    /**
    * 获取适配器sign密钥
    * @param String $node_id
    * @return String 
    */
    public function getSignKey($node_id=''){
        $channel_adapter = app::get('channel')->model('channel');
        $detail = $channel_adapter->getList('secret_key',array('node_id'=>$node_id),0,1);
        return isset($detail[0]) ? $detail[0]['secret_key'] : '';
    }

}