<?php
/**
* 安装任务
*
* @author chenping<chenping@shopex.cn>
* @version 2012-5-30 17:36
*/
class inventorydepth_task{
    private $allowSystem = array('taog'=>'taog','ECC-K'=>'ecck');

    const syskey = 'inventorydepth.system.identity';

    public function __construct($app)
    {
        $this->app = $app;
    }


    /**
     * @description 获取商品ID
     * @access public
     * @param void
     * @return void
     */
    public function getIdentity() 
    {
        $deploy = base_setup_config::deploy_info();
        $identity = $this->allowSystem[$deploy['product_id']];
        return $identity ? $identity : 'taog';
    }

    /**
     * 安装前验证
     *
     * @return void
     * @author
     **/
    public function pre_install($options)
    {
        $deploy = base_setup_config::deploy_info();
        if (!in_array($deploy['product_id'], array_keys($this->allowSystem))) {
            kernel::log('your system has no privileges to install this app!'); exit;
            return false;
        }
        return true;
    }

    /**
     * 安装中执行
     *
     * @return void
     * @author
     **/
    public function post_install($options)
    {
        $deploy = base_setup_config::deploy_info();
        $this->app->setConf(self::syskey,$deploy['product_id']);

        $this->init();
    }

    /**
     * undocumented function
     *
     * @return void
     * @author
     **/
    public function post_update($options)
    {
        $deploy = base_setup_config::deploy_info();
        $identity = $this->app->getConf(self::syskey);
        if ($deploy['product_id'] != $identity) {
            $this->app->setConf(self::syskey,$deploy['product_id']);
        }
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function init()
    {
        $reguModel = $this->app->model('regulation');
        $applyModel = $this->app->model('regulation_apply');
        $remote_ip = kernel::single('base_component_request')->get_remote_ip();
        # 店铺
        $storeConfig = ome_shop_type::get_store_config();
        $shops = $this->app->model('shop')->getList('shop_type,shop_id,name,node_id,shop_bn');
        foreach ($shops as $key=>$shop) {
            if ($shop['node_id'] && $shop['shop_type']) {
                $regulation = array(
                    'condition' => 'stock',
                    'bn' => $shop['shop_bn'],
                    'heading' => $shop['name'].'规则',
                    'using' => 'true',
                    'content' =>  array (
                        'filters' =>array (
                          0 =>array (
                            'object' => 'actual_stock',
                            'comparison' => 'bthan',
                            'compare_increment' => '0',
                          ),
                        ),
                        'stockupdate' => '1',
                        'result' => '{可售库存}',
                    ),
                    'operator' => 16777215,
                    'update_time' => time(),
                    'operator_ip' => $remote_ip,
                    'type' => '1',
                );
                if ($storeConfig[$shop['shop_type']] && $storeConfig[$shop['shop_type']] == 'on') {
                    $regulation['content']['result'] = '{可售库存}+{店铺预占}';
                }

                $reguModel->save($regulation);

                $apply = array(
                    'bn' => $shop['shop_bn'],
                    'heading' => $shop['name'].'应用',
                    'condition' => 'stock',
                    'style' => 'stock_change',
                    'start_time' => time(),
                    'end_time' => strtotime('2030-12-12'),
                    'shop_id' => $shop['shop_id'],
                    'using' => 'true',
                    'al_exec' => 'false',
                    'operator' => 16777215,
                    'update_time' => time(),
                    'operator_ip' => $remote_ip,
                    'regulation_id' => $regulation['regulation_id'],
                    'apply_goods' => '_ALL_',
                    'apply_pkg' => '_ALL_',
                    'priority' => 10,
                    'type' => '1',
                );
                $applyModel->save($apply);


                kernel::single('inventorydepth_shop')->setStockConf($shop['shop_id'],'true');
                kernel::single('inventorydepth_shop')->setFrameConf($shop['shop_id'],'false');
            }
        }

        # 全局变量
        $regulation = array(
            'condition' => 'stock',
            'bn' => 'stock_global',
            'heading' => '全局规则',
            'using' => 'true',
            'content' =>  array (
                'filters' =>array (
                  0 =>array (
                    'object' => 'actual_stock',
                    'comparison' => 'bthan',
                    'compare_increment' => '0',
                  ),
                ),
                'stockupdate' => '1',
                'result' => '{可售库存}',
            ),
            'operator' => 16777215,
            'update_time' => time(),
            'operator_ip' => $remote_ip,
            'type' => '0',
        );
        $reguModel->save($regulation);

        $apply = array(
            'bn' => 'stock_global',
            'heading' => '全局规则应用',
            'condition' => 'stock',
            'style' => 'stock_change',
            'start_time' => time(),
            'end_time' => strtotime('2030-12-12'),
            'shop_id' => '_ALL_',
            'using' => 'true',
            'al_exec' => 'false',
            'operator' => 16777215,
            'update_time' => time(),
            'operator_ip' => $remote_ip,
            'regulation_id' => $regulation['regulation_id'],
            'apply_goods' => '_ALL_',
            'apply_pkg' => '_ALL_',
            'priority' => 10,
            'type' => '0',
        );
        $applyModel->save($apply);

        $shopModel = $this->app->model('shop');
        $shop = $shopModel->getList('shop_bn',array('filter_sql'=>'node_id is not null'));
        if ($shop) {
            $shop = array_map('current',$shop);

            app::get('ome')->setConf('shop.branch.relationship',array());
            # 获取所以线上仓
            $branches = app::get('ome')->model('branch')->getList('branch_id,branch_bn',array('attr'=>'true'));
            foreach ($branches as $key=>$branche) {
                ome_shop_branch::update_relation($branche['branch_bn'],$shop,$branche['branch_id']);
            }
        }

    }
}
