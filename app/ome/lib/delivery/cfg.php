<?php
/**
 * 发货配置处理文件
 *
 * @author chenping<chenping@shopex.cn>
 * @version 2012-5-10 14:03
 * @package delivery
 *
 */
class ome_delivery_cfg {

    /**
     * undocumented function
     *
     * @return void
     * @author
     **/
    public function __construct(&$app)
    {
        $this->app = $app;
        $this->deliveryCfg = $this->app->getConf('ome.delivery.status.cfg');
    }

    /**
     * 分析打印按钮状态
     *
     * @return BOOL TRUE:开启 FALSE:关闭
     * @author
     * @param $type 打印按钮类型
     * @param $sku 单品、多品
     **/
    public function analyse_btn_status($type,$sku='')
    {
        if(empty($this->deliveryCfg)) return true;

        if ($sku=='single') {
            $btnCfg = $this->deliveryCfg['set']['single'];
        }elseif($sku=='multi'){
            $btnCfg = $this->deliveryCfg['set']['multi'];
        }else{
            $btnCfg = $this->deliveryCfg['set'];
        }
        
        if($type=='merge'){
            if(is_null($btnCfg['stock']) && is_null($btnCfg['delie'])){ 
                return false;
            
            }elseif(is_null($btnCfg['merge']) && ($btnCfg['stock']==1 || $btnCfg['delie'] == 1)){
                return false;
            }
        }

        if(is_null($btnCfg[$type])) return true;

        return ($btnCfg[$type] == 0) ? false : true;
    }

    /**
     * 获取值
     *
     * @return void
     * @author
     * @param $key  键
     * @param $sku 单品、多品
     **/
    public function getValue($key,$sku='')
    {

        if ($this->deliveryCfg) {
            $cfg = $this->deliveryCfg['set'];
        }else{   //默认值
            $cfg = array(
                    'ome_batch_print_nums' => 100,
                    'ome_eachgroup_print_count' => 20,
                    'single' => array(
                        'ome_batch_print_nums' => 100,
                        'ome_eachgroup_print_count' => 20,
                    ),
                    'multi' => array(
                        'ome_batch_print_nums' => 100,
                        'ome_eachgroup_print_count' => 20,
                    ),
            );
        }
        if ($sku=='single') {
            $cfg = array_merge($cfg,$cfg['single']);
        }elseif($sku=='multi'){
            $cfg = array_merge($cfg,$cfg['multi']);
        }

        $cfg['ome_batch_print_nums'] = $cfg['ome_batch_print_nums'] ? $cfg['ome_batch_print_nums'] : 100;
        $cfg['ome_eachgroup_print_count'] = $cfg['ome_eachgroup_print_count'] ? $cfg['ome_eachgroup_print_count'] : 20;

        return $cfg[$key] ? $cfg[$key] : '';
    }

    /**
     * 按钮组合
     *
     * @return void
     * @author
     **/
    public function btnCombi($sku='')
    {
        if(empty($this->deliveryCfg)) return '1_1';

        $cfg = $this->deliveryCfg['set'];
        if ($sku=='single') {
            $cfg = array_merge($cfg,$cfg['single']);
        }elseif($sku=='multi'){
            $cfg = array_merge($cfg,$cfg['multi']);
        }
        
        if(is_null($cfg['stock']) && is_null($cfg['delie']) && is_null($cfg['merge'])){
            return '1_1';
        }elseif ($cfg['stock'] == 1 && $cfg['delie'] == 1) {
            return '1_1';
        }elseif ($cfg['stock'] == 1 && $cfg['delie'] == 0) {
            return '1_0';
        }elseif ($cfg['stock'] == 0 && $cfg['delie'] == 1) {
            return '0_1';
        }elseif ($cfg['stock'] == 0 && $cfg['delie'] == 0 && $cfg['merge'] == 1){
            return '1_1';
        }elseif ($cfg['stock'] ==0 && $cfg['delie'] == 0 && $cfg['merge'] == 0) {
            return '0_0';
        }else{
            return '1_1';
        }
    }

    /**
     * @打印版本
     * @access public
     * @param void
     * @return void
     */
    public function getprintversion() 
    {
        $print_version = $this->deliveryCfg['set']['ome_delivery_print_devision'];
        if(!$print_version){
            $print_version=0;
        }
        return $print_version;
    }

    /**
     * @获取打印模式
     * @access public
     * @param void
     * @return void
     */
    public function getprintstyle() 
    {
        $print_style = $this->deliveryCfg['set']['ome_delivery_print_style'];
        
        if($print_style==''){
            $print_style='1';
        }
       
        return $print_style;
    }
}