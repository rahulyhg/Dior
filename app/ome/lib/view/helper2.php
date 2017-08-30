<?php
class ome_view_helper2{

    function __construct(&$app){
        $this->app = $app;
    }

    public function modifier_visibility($productName,$productId){
        if (!$productId) {
            return $productName;
        }
        $productModel = $this->app->model('products');
        $visibility = $productModel->select()->columns('visibility')->where('product_id=?',$productId)->instance()->fetch_one();
        $style = ($visibility=='false') ? 'color:#808080;width:100%;' : ''; 
        return '<span style='.$style.' class="product-name" visibility='.$visibility.' onmouseover=visibility(event);>'.$productName.'</span>';
    }

    /**
     * @description
     * @access public
     * @param void
     * @return void
     */
    public function function_button_permission($params, &$smarty) 
    {
        # 判断是否有权限 且不是超级管理员
        $userLib = kernel::single('desktop_user');
        if (!$userLib->is_super()) {
            $group = $userLib->group();
            if (isset($params['permission'])) {
                $permission_id = $params['permission'];
            }elseif(isset($params['url'])){
                $url = parse_url($params['url']);
                parse_str($url['query'],$url_params);

                $menus = app::get('desktop')->model('menus');
                $permission_id = $menus->permissionId($url_params);
            }

            if ($permission_id && !in_array($permission_id,$group)) {
                $params['style'] = 'display:none;';
            }
        }


        return kernel::single('base_render')->ui()->button($params);
    }
}
