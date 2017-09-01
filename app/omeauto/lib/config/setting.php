<?php
/**
 * 自动确认设置
 *
 * @author hzjsq@msn.com
 * @version 0.1b
 */

class omeauto_config_setting {

    /**
     *
     */
    public function saveAutoCnf($cnf) {

        $cnfString = serialize($cnf);
        app::get('omeauto')->setConf('auto.setting', $cnfString);
    }

    /**
     * 获得当前设置内容
     *
     * @param void
     * @return Array
     */
    function getAutoCnf() {
        $autoCnf = app::get('ome')->getConf('auto.setting');

        if (empty($autoCnf)) {
            return $this->_defaultAutoCnf();
        } else {
            if (!is_array($autoCnf) || empty($autoCnf)) {
                return $this->_defaultAutoCnf();
            } else {
                return $autoCnf;
            }
        }
    }

    /**
     * 获取缺省设置
     *
     * @param void
     * @return Array
     */
    private function _defaultAutoCnf() {

        return array('bufferTime' => '30', 'autoCod' => 'N', 'chkNoPayOrder' => 'Y', 'chkMemo' => 'Y', 'chkCustom' => 'Y', 'chkProduct' => 'N', 'autoDelivery' => 'Y', 'combineMember' => 'N', 'chkShipAddress' => 'N');
    }
}