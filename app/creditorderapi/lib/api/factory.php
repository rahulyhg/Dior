<?php
/**
 * 工厂方法模式
 *
 * 定义一个用于创建对象的类
 */
class creditorderapi_api_factory{

    /**
     * 根据店铺类型实列化对象
     * @param $shop_bn
     * @return mixed
     */
    public static function create_factory($shop_bn){

        switch ($shop_bn) {
            case 'lvhv':
                return kernel::single('creditorderapi_api_lvhvsite');
                break;

            case 'fresh':
                return kernel::single('creditorderapi_api_freshsite');
                break;

            case 'givenchy':
                return kernel::single('creditorderapi_api_givsite');
                break;

            case 'guerlain':
                return kernel::single('creditorderapi_api_guerlainsite');
                break;

            case 'dior':
                return kernel::single('creditorderapi_api_diorsite');
                break;

            default :
                return kernel::single('creditorderapi_api_site');
                break;
        }
    }

    /**
     * 根据店铺类型实列化对象-sitesftp
     * @param $shop_bn
     * @return mixed
     */
    public static function create_factory_sitesftp($shop_bn){

        switch ($shop_bn) {

            case 'givenchy':
                return kernel::single('creditorderapi_api_givsitesftp');
                break;

            default :
                return kernel::single('creditorderapi_api_sitesftp');
                break;
        }
    }
}