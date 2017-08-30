<?php
/**
 * 供应商推送
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_wms_request_supplier extends erpapi_wms_request_abstract
{
    /**
     * 供应商创建
     *
     * @return void
     * @author 
     **/
    public function supplier_create($sdf)
    {
        $title = $this->__channelObj->wms['channel_name'] . '供应商添加';

        $params = $this->_format_supplier_create_params($sdf);
        
        return $this->__caller->call(WMS_VENDORS_GET, $params, null, $title, 10);
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    protected function _format_supplier_create_params($sdf)
    {
        $area = $sdf['area'];

        if ($area) {
            $area        = explode(':',$area);
            $area_detail = explode('/',$area[1]);
            $state       = $area_detail[1];
            $city        = $area_detail[0];
        }

        $params = array(
            'CustomerID'   => $sdf['bn'],//
            'vendor_ename' => $sdf['name'],//
            'vendor_name'  => $sdf['name'],
            'address'      => $sdf['addr'],//
            'state'        => $state,//
            'city'         => $city,//
            'country'      => '中国',//
        );

        return $params;
    }
}