<?php
/**
* 渠道对外接口
* @author Jack.dong
* @copyright shopex.cn
*/
class channel_channel{

    function __construct(){
        $this->channel_mdl = app::get('channel')->model('channel');
    }

    /**
    * 判断渠道是否存在
    * @param $channel_type 渠道类型
    * @return bool
    */
    public function exists($channel_type=''){
        if(empty($channel_type)) return false;

        $channel = $this->channel_mdl->getList('channel_id',array('channel_type'=>$channel_type),0,1);
        return isset($channel[0]['channel_id']) && $channel[0]['channel_id'] ? true : false;
    }

    /**
    * 获取渠道信息
    * @param $filter 条件
    * @return mixd
    */
    public function dump($filter='',$cols='*'){
        if(empty($filter)) return NULL;
        
        $cols = $cols ? $cols : 'channel_id,channel_name,node_id,node_type';
        $channel = $this->channel_mdl->getList($cols,$filter,0,1);
        return isset($channel[0]) ? $channel[0] : NULL;
    }

    /**
    * 获取渠道信息列表
    * @param $filter 条件
    * @return mixd
    */
    public function getList($filter='',$cols='*'){
        if(empty($filter)) return NULL;
        
        $cols = $cols ? $cols : 'channel_id,channel_name,node_id,node_type';
        $filter = $filter ? $filter : array();
        $channel = $this->channel_mdl->getList($cols,$filter,0,-1);
        return isset($channel[0]) ? $channel : NULL;
    }

    /**
    * 添加渠道记录
    * @param $sdf 渠道数据
    * @return bool
    */
    public function insert(&$sdf){
        if(empty($sdf)) return false;

        return $this->channel_mdl->insert($sdf);
    }

    /**
    * 更新渠道记录
    * @param $sdf 更新数据
    * @param $filter 更新条件
    * @return bool
    */
    public function update($sdf,$filter=''){
        if(empty($sdf) || empty($filter)) return false;

        return $this->channel_mdl->update($sdf,$filter);
    }

    /**
    * 删除渠道记录
    * @param $filter 删除条件
    * @return bool
    */
    public function delete($filter=''){
        if(empty($filter)) return false;

        return $this->channel_mdl->delete($filter);
    }

    /**
    * 绑定
    * @param $node_id 节点号
    * @param $node_type 节点类型
    * @param $filter 更新绑定条件
    * @return bool
    */
    public function bind($node_id,$node_type,$filter=''){
        if(empty($node_id) || empty($filter)) return false;

        return $this->channel_mdl->update(array('node_id'=>$node_id,'node_type'=>$node_type),$filter);
    }

    /**
    * 解除绑定
    * @param $filter 更新绑定条件
    * @return bool
    */
    public function unbind($filter=''){
        if(empty($filter)) return false;

        return $this->channel_mdl->update(array('node_id'=>''),$filter);
    }

}