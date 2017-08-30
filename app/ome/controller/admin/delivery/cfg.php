<?php
class ome_ctl_admin_delivery_cfg extends desktop_controller{
	function index(){
        # 发货组配置
        $typeobj = kernel::single('omeauto_auto_type');
        $orderTypes = $typeobj->getDeliveryGroupTypes();
        $this->pagedata['orderTypes'] = $orderTypes;

		
        $data = $this->app->getConf('ome.delivery.status.cfg');

        $this->pagedata['setting'] = $this->options();
        $this->pagedata['data'] = $data['set'] ? $data['set'] : array();

        $cfgr = $this->app->getConf('ome.delivery.cfg.radio');
        
        $this->pagedata['basic_cfg'] = ($cfgr!=2) ? true : false;

        $this->page('admin/delivery/delivery_cfg.html');
	}

    /**
     * 保存发货配置
     *
     * @return void
     * @author 
     **/
    public function save()
    {
        $post = kernel::single('base_component_request')->get_post();
        $this->begin();
        if ($post) {
            $post['set']['expre'] = '1';
            # 验证数据
            if ($post['set']['merge'] == 1 && $post['set']['ome_batch_print_nums'] < $post['set']['ome_eachgroup_print_count']) {
                $this->end(false,'联合分组数不能大于批量打印数量！');
            }
            if ($post['set']['single']['merge'] == 1 && $post['set']['single']['ome_batch_print_nums'] < $post['set']['single']['ome_eachgroup_print_count']) {
                $this->end(false,'联合分组数不能大于批量打印数量！');
            }
            if ($post['set']['multi']['merge'] == 1 && $post['set']['multi']['ome_batch_print_nums'] < $post['set']['multi']['ome_eachgroup_print_count']) {
                $this->end(false,'联合分组数不能大于批量打印数量！');
            }
            $this->app->setConf('ome.delivery.status.cfg',$post);
        }
        $this->end(true,'保存成功');
    }


    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    private function options()
    {
        $set = array(
            'stock' => array(
                'options' => array(
                    '0' => $this->app->_('关闭'),
                    '1' => $this->app->_('开启'),
                ),
            ),
            'ome_delivery_is_printstock' => array(
                'options' => array(
                    '1' => $this->app->_('前台'),
                    '2' => $this->app->_('后台'),
                ),
            ),
            'delie' => array(
                'options' => array(
                    '0' => $this->app->_('关闭'),
                    '1' => $this->app->_('开启'),
                ),
            ),
            'ome_delivery_is_printdelivery' => array(
                'options' => array(
                    '1' => $this->app->_('前台'),
                    '2' => $this->app->_('后台'), 
                ),
            ),
            'merge' => array(
                'options' => array(
                    '0' => $this->app->_('关闭'),
                    '1' => $this->app->_('开启'),
                ),   
            ),
            'ome_delivery_merge_print' => array(
                'options' => array(
                    '1' => $this->app->_('前台'),
                    '2' => $this->app->_('后台'), 
                ),                  
            ),
            'expre' => array(
                'options' => array(
                    '0' => $this->app->_('关闭'),
                    '1' => $this->app->_('开启'),
                ),   
            ),
            'ome_delivery_is_printship' => array(
                'options' => array(
                    '1' => $this->app->_('前台'),
                    '2' => $this->app->_('后台'), 
                ),                  
            ),   
            'verify' => array(
                'options' => array(
                    '0' => $this->app->_('关闭'),
                    '1' => $this->app->_('开启'),
                ),   
            ),  
            'consign' => array(
                'options' => array(
                    '0' => $this->app->_('关闭'),
                    '1' => $this->app->_('开启'),
                ),   
            ),
            'print_merge' => array(
                'options' => array(
                    '0' => $this->app->_('关闭'),
                    '1' => $this->app->_('开启'),
                ),
            ), 
            'print_style'=>array(
                'options'=>array(

                    '0'=>$this->app->_('按销售清单'),
                    '1'=>$this->app->_('按拣货格式'),
                    )

                ),
            'print_devision'=>array(
                 'options'=>array(

                    '0'=>$this->app->_('老版本'),
                    '1'=>$this->app->_('新版本'),
                    )   
                ),
           'print_order'=>array(
                   'options'=>array(
                        0=>$this->app->_('货号排序'),
                        1=>$this->app->_('货位排序'),       
                    )
                   ),
         'print_pkg_goods'=>array(
                'options'=>array(
                        0=>$this->app->_('不打印捆绑商品货号'),
                        1=>$this->app->_('打印捆绑商品货号'),
                )
         ),
         'sellagent' => array(
                    'options' => array(
                            '0' => $this->app->_('使用ERP店铺发货信息'),
                            '1' => $this->app->_('启用分销王代销人发货信息'),
                    )
            ), 
         'print_mode'=>array(
                'options'=>array(
                        0=>$this->app->_('html风格'),
                        1=>$this->app->_('控件风格'),
                )
         ),
         'stock_print_mode'=>array(
                'options'=>array(
                        0=>$this->app->_('html风格'),
                        1=>$this->app->_('控件风格'),
                )
         ),        );
        return $set;
    }


}