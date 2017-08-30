<?php
/**
 * CONFIG
 *
 * @category 
 * @package 
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
class wmsmgr_auth_config 
{
    private $__config = array(
            'selfwms' => array(
                'label' => '系统自有仓储',
                'desc' => '使用系统自带自有仓储发货',
            ),
            'matrixwms' => array(
                'label' => '商派云对接',
                'desc' => '通过WEBSERVICE和第三方仓储进行对接,如(科捷,酷武,伊藤忠)',
            ),
            'openapiwms' => array(
                'label' => '本地API直联',
                'platform' => array(),
                'desc' => '可通过API接口直接对接第三方仓储',
            ),
            'ilcwms' => array(
                'label' => '通过FTP直联',
                'desc' => '通过配置,使用FTP方式和第三方仓储进行系统对接(如伊藤忠FTP)'
            ),


        );
    
    public function __construct()
    {
        if (class_exists('erpapi_wms_openapi_config')) {
            $openapi_platform = kernel::single('erpapi_wms_openapi_config')->get_openapi_list();

            $this->__config['openapiwms']['platform'] = $openapi_platform;
        }
    }

    public function getConfig()
    {
        return $this->__config;
    }

    public function getAdapterList()
    {
        $adapter = array();

        foreach ($this->__config as $key => $value) {
            $adapter[] = array('value'=>$key, 'label'=>$value['label'], 'desc'=>$value['desc']);
        }

        return $adapter;
    }

    public function getPlatformList($adapter)
    {
        $platform = array();

        foreach ($this->__config[$adapter]['platform'] as $key => $value) {
            $platform[] = array('value'=>$key,  'label'=>$value['label']);
        }

        return $platform;
    }

    public function getPlatformParam($platform)
    {
        return $this->__config['openapiwms']['platform'][$platform]['params'];
    }
}