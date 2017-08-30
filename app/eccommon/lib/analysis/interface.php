<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

interface eccommon_analysis_interface
{

    public function get_logs($time);

    public function set_params($params);

    public function set_extra_view($array);

    public function get_type();

    public function graph();

    public function rank();

    public function detail();

    public function finder();

    public function fetch();

    public function display($fetch=false);

}//End Class