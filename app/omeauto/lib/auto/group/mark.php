<?php

/**
 * 标记语言处理
 *
 *
 */
class omeauto_auto_group_mark {

    //系统设置
    private $_cnf = null;
    //要检查的订单
    private $_orders = null;
    //提醒代码
    private $_alertCode = array('w' => 0x00100000);

    /**
     * 析构
     */
    function __construct() {

        $this->_cnf = app::get('ome')->getConf('mark.config');
    }

    /**
     * 设置所有要处理的订单
     *
     * @param array $orders 订单
     * @return void
     */
    function setOrders(& $orders) {

        $this->_orders = & $orders;
    }

    /**
     * 获取指定配置的代码
     *
     * @param String $fix 标志
     * @return String
     */
    function getCodeByFix($fix) {

        return $this->_cnf[$fix];
    }

    /**
     * 获取发货用的物流公司
     *
     * @return mixed
     */
    function getDeliveryCorps() {

        if (empty($this->_orders) || !is_array($this->_orders)) {

            return;
        }

        if (!$this->useMark()) {

            return;
        }

        $ret = array();
        foreach ($this->_orders as $order) {

            $codeList = $this->getMark($this->_cnf['markDelivery'], $body);
            if (is_array($codeList)) {
                foreach ($codeList as $code) {
                    if (!is_array($code, $ret)) {
                        $ret[] = $code;
                    }
                }
            }
        }

        return $ret;
    }

    /**
     * 获取前台审单OK标志
     *
     * @return mixed
     */
    function isConfirm($content,$memo) {

        if (!$this->useMark()) {

            return false;
        }

        $od = $this->getOd();
        $cd = $this->getCd();
        $pregOCode = preg_quote($od) . preg_quote($this->_cnf['markOK']) . preg_quote($cd);
        $pregWCode = preg_quote($od) . '[^' . preg_quote($cd) . ']{1,}:' . '[^' . preg_quote($cd) . ']{1,}' . preg_quote($cd);

        if (preg_match('/' . $pregOCode . '/is', $content)) {

            return true;
        } elseif(trim($memo) == '') {

            //检查除CODE外有没有内容
            $rContent = preg_replace('/(' . $pregCode . ')|(' . $pregWCode . ')/is', '', $content);
            $rContent = preg_replace('/([' . preg_quote(',.?\'";:-_=+]}[{|\~!@#$%^&*()"') . ']*)|(\s*)/is', '', $rContent);
            if (trim($rContent) == '') {
                return true;
            } else {
                return false;
            }
        } else {

            return false;
        }
    }

    function useMark() {

        if ($this->_cnf && trim($this->_cnf['markFix']) <> '') {

            return true;
        } else {

            return false;
        }
    }

    /**
     * 获取指定代码的内容
     *
     * return mixed
     */
    function getMark($code, $body) {

        $od = $this->getOd();
        $cd = $this->getCd();
        $pregCode = preg_quote($od) . preg_quote($code) . ':(' . '[^' . preg_quote($cd) . ']*)' . preg_quote($cd);

        preg_match_all('/' . $pregCode . '/is', $body, $match);

        if (!empty($match[1])) {

            return $match[1];
        } else {

            return array();
        }
    }

    /**
     * 获取备注错误码
     *
     * @param string $flag
     * @return integer
     */
    function getMsgFlag($flag) {

        $flag = strtolower($flag);
        return $this->_alertCode[$flag];
    }

    /**
     * 获取提示信息
     *
     * @param Integer $staus
     * @param Array $order
     * @return mixed
     */
    function fetchAlertMsg($staus, $order) {

        $result = array();
        foreach ($this->_alertCode as $key => $code) {

            if (($staus & $code)> 0) {

                $result[] = $this->getAlertMsg($key, $order);
            }
        }

        return $result;
    }

    /**
     * 获取指定代码的提示信息
     *
     * @param Integer $code
     * @param Array $order
     * @return Mixed
     */
    function getAlertMsg($code, $order) {

        switch($code) {

            case 'w':
                return array('color' => '#F38D23', 'flag' => '标', 'msg' => '标记内容冲突或存在其它问题');
                break;
        }
    }

    /**
     *
     */
    function fetchCorpId($flag) {

        if (!$this->useMark()) {

            return null;
        } else {

            $flag = trim(strtoupper($flag));
            foreach ($this->_cnf['wd'] as $cid => $code) {

                if (trim(strtoupper($code)) == $flag) {

                    return $cid;
                }
            }

            return null;
        }
    }

    /**
     * 获取MARK标记前缀
     */
    function getOd() {

        switch ($this->_cnf['markFix']) {
            case '{}':
                return '{';
                break;
            case '<>':
                return '<';
                break;
            case '[]':
                return '[';
                break;
            case '()':
                return '(';
                break;
        }
        return '';
    }

    /**
     * 获取MARK标记后缀
     */
    function getCd() {

        switch ($this->_cnf['markFix']) {
            case '{}':
                return '}';
                break;
            case '<>':
                return '>';
                break;
            case '[]':
                return ']';
                break;
            case '()':
                return ')';
                break;
        }

        return '';
    }

}