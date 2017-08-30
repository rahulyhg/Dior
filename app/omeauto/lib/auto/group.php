<?php

/**
 * 订单按分组类型的组合
 */
class omeauto_auto_group {

    /**
     * 该分类下的所有订单组
     *
     * @var array
     */
    private $items = array();

    /**
     * 对应的订单分组规则对像
     *
     * @var Object
     */
    private $filter = array();

    /**
     * 配置信息
     *
     * @var array
     */
    private $config = null;

    /**
     * 审单规则配置
     *
     * @var Array
     */
    private $confirmRoles = null;

    /**
     * 审单插件对像
     *
     * @var Array
     */
    static $_plugObjects = array();

    /**
     * 是否缺省订单组
     *
     * @var boolean
     */
    private $isDefault = false;

    /**
     * 设置过滤规则
     *
     * @param array $config
     * @return void
     */
    function setConfig($config) {

        $this->isDefault = false;
        //想办法加上键值判断
        $this->config = $config;
        //删除过渡器
        $this->clearFilters();
        $roles = unserialize($config['config']);
        //创建Filters对像
        foreach ($roles as $role) {
            $role = json_decode($role, true);
            if (is_array($role)) {
                $className = sprintf('omeauto_auto_type_%s', $role['role']);
                $filter = new $className();
                $filter->setRole($role['content']);
                $this->filter[] = $filter;
            }
        }
    }

    function getConfig() {
        if($this->config){
            return $this->config;
        }
        return array();
    }

    /**
     * 设置缺省规则
     *
     * @param void
     * @return void
     */
    function setDefault() {

        $this->isDefault = true;
        $this->config = $this->getDefaultRoles();
        $this->clearFilters();
    }

    /**
     * 增加订单组
     *
     * @param omeauto_auto_group_item $item
     * @return void
     */
    function addItem($item) {

        //想办法加上键值判断
        $this->items[] = $item;
    }

    /**
     * 检查是否是该类型的订单
     *
     * @param Object $item
     * @return boolean
     */
    function vaild($item) {
        if (!empty($this->filter)) {
            foreach ($this->filter as $filter) {
                if (!$filter->vaild($item)) {
                    return false;
                }
            }
            return true;
        } else {

            if ($this->isDefault) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * 执行该规则对应的审单规则
     *
     * @param void
     * @return mixed
     */
    public function process() {

        $confirmRoles = $this->getRoles();
        $plugins = $this->getPluginNames($this->confirmRoles);
        
        foreach ($plugins as $plugName) {

            $plugObj = $this->initPlugin($plugName);
            if (is_object($plugObj)) {
                foreach ((array) $this->items as $key => $item) {

                    $plugObj->process($this->items[$key], $confirmRoles);
                }
            }
        }

        $result = array('total' => 0, 'succ' => 0, 'fail' => 0);
        foreach ((array) $this->items as $key => $group) {
            $result['total'] += $group->orderNums;
            if ($group->process($confirmRoles)) {
                $result['succ'] += $group->orderNums;
            } else {
                $result['fail'] += $group->orderNums;
            }
        }

        return $result;
    }

    /**
     * 通过插件名获取插件类并返回
     *
     * @param String $plugName 插件名
     * @return Object
     */
    private function & initPlugin($plugName) {

        $fullPluginName = sprintf('omeauto_auto_plugin_%s', $plugName);
        $fix = md5(strtolower($fullPluginName));

        if (!isset(self::$_plugObjects[$fix])) {

            $obj = new $fullPluginName();
            if ($obj instanceof omeauto_auto_plugin_interface) {

                self::$_plugObjects[$fix] = $obj;
            }
        }
        return self::$_plugObjects[$fix];
    }

    /**
     * 根据审单配置信息获取使用到的审单插件名列表
     *
     * @param Array $cfg 寓单配置信息
     * @return Array
     */
    private function getPluginNames($cfg) {
        $combine_select = app::get('ome')->getConf('ome.combine.select');
        if ($combine_select=='1') {
                 $plugins = array(
                'branch', //先选择仓库
                'store', //在判断库存
                'flag', //备注和留言
                'pay', //有未付订单
                'logi', //判定物流
                //'member', //用户多地址
                //'ordermulti', // 是否多单合
                'abnormal', //数据字段异常订单
                'oversold',//超卖订单
                'tbgift',//淘宝订单有赠品
    //            'ordersingle', //单订单
    //            'examine', //单订单且有备注
                //'shopcombine',
                 'crm',//crm赠品
                 'tax',//开发票
                 'arrived',//物流到不到
            );
        } else{
            $plugins = array(
            'branch', //先选择仓库
            'store', //在判断库存
            'flag', //备注和留言
            'pay', //有未付订单
            'logi', //判定物流
            'member', //用户多地址
            'ordermulti', // 是否多单合
            'abnormal', //数据字段异常订单
            'oversold',//超卖订单
            'tbgift',//淘宝订单有赠品
//            'ordersingle', //单订单
//            'examine', //单订单且有备注
            'shopcombine',
             'crm',//crm赠品
             'tax',//开发票
             'arrived',//物流到不到
        );
        }
        
    
        return $plugins;
    }

    /**
     * 获取当前组的审单配置信息
     *
     * @param void
     * @return Array
     */
    private function getRoles() {

        //检查定单组配置信息
        if (!empty($this->config) && $this->config['oid'] > 0) {
            //有特定审单规则
            $confirmRoles = app::get('omeauto')->model('autoconfirm')->dump(array('oid' => intval($this->config['oid'])));
            if ($confirmRoles && $confirmRoles['config']) {
                //特定审单规则
                return $confirmRoles['config'];
            } else {
                //缺省规则
                return $this->getDefaultRoles();
            }
        } else {
            //缺省规则
            return $this->getDefaultRoles();
        }
    }

    /**
     * 获取缺省的审单规则
     *
     * @param void
     * @return Array
     */
    static function getDefaultRoles() {

        $config = self::fetchDefaultRoles();

        if (empty($config)) {

            return array(
                "autoOrders" => "-1",
                "morder" => "1",
                "payStatus" => "1",
                "memo" => "1",
                "mark" => "1",
                "autoCod" => "0",
                "allDlyCrop" => "1",
                "autoConfirm" => "1"
            );
        } else {

            return $config;
        }
    }

    /**
     * 从数据库获取默认审单规则
     *
     * @param void
     * @return Array
     */
    static function fetchDefaultRoles() {

        $configRow = app::get('omeauto')->model('autoconfirm')->dump(array('defaulted' => 'true', 'disabled' => 'false'));
        return $configRow['config'];
    }

    /**
     * 清除对像
     *
     * @param void
     * @return void
     */
    private function clearFilters() {

        foreach ($this->filter as $key => $value) {

            unset($this->filter[$key]);
        }

        $this->filter = array();
    }

}