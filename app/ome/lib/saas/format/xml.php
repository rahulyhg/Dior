<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class ome_saas_format_xml extends ome_saas_format_data{
    
    public function __construct($info) {
        
        if(isset($info->success) && $info->success == 'true') {
            $data = get_object_vars($info->data);
            
            foreach ($data as $name => $value) {
                $v = explode('_', $name);
                $pn = '';
                foreach ($v as $un) {
                    $pn.= ucfirst($un);
                }
                
                $pn = 'set'. $pn;
                if(method_exists($this, $pn)) {
                    $this->$pn(trim($value));
                }
            }
        }
    }
    
}