<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class ome_saas_service {
    
    private $site;
    
    public function __construct(ome_saas_site &$site) {
        $this->site = $site;
    }
    
    /**
     * @获取ome_saas_site实例化的对象
     * @access public
     * @param void
     * @return object
     */
    public function getSite(){
        return $this->site;
    }
    
    /**
     * @获取服务到期的剩余天数
     * @access public
     * @param void
     * @return int
     */
    public function getValidityDate() {
        return $this->site->getManager ()->getValidityDate ();
    }

    /**
     * @获取服务基本信息
     * @access public
     * @param void
     * @return int
     */
    public function getInfo() {
        return $this->site->getManager ()->getInfo ();
    }
    
}