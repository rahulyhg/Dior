<?php
/**
 * RPC请求基类
 * 各个同步点先组织应用级参数，然后统一调用本类的公共方法向框架发起RPC
 * @author shopex.cn
 * @access public
 * @copyright www.shopex.cn 2010
 */

kernel::require_ego();
