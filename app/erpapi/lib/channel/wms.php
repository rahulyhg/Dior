<?php
/**
 * WMS
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class erpapi_channel_wms extends erpapi_channel_abstract
{
    public $wms;

    private static $wms_mapping = array(
        'jd_wms'   => '360buy',
        'sf_wms'   => 'sf',
        'flux_wms' => 'flux',
    );

    public function init($node_id,$channel_id)
    {
        $wmsModel = app::get('channel')->model('channel');

        $filter = $channel_id ? array('channel_id'=>$channel_id) : array('node_id'=>$node_id);

        $wms = $wmsModel->dump($filter);

        if (!$wms) return false;

        $adapterModel = app::get('channel')->model('adapter');
        $adapter = $adapterModel->dump(array('channel_id'=>$wms['channel_id']));

        $adapter['config'] = @unserialize($adapter['config']);

        $wms['adapter'] = $adapter;

        $this->wms = $wms;

        if ($adapter['adapter'] == 'selfwms') {
            $this->__adapter = '';
            $this->__platform = 'selfwms';
            
        } elseif ($adapter['adapter'] == 'ilcwms') {

            $this->__adapter = '';
            $this->__platform = 'ftp';
        }  elseif ('wms' == substr($adapter['adapter'], -3) ) {

            $this->__adapter = substr($adapter['adapter'],0,-3);

            $this->__platform = isset(self::$wms_mapping[$wms['node_type']]) ? self::$wms_mapping[$wms['node_type']] : $wms['node_type'];
        }

        return true;
    }
}