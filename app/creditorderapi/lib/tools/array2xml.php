<?php
/**
 * Created by PhpStorm.
 * User: D1M_zzh
 * Date: 2018/03/13
 * Time: 11:45
 */
class creditorderapi_tools_array2xml{
    public function array2xml($data, $root='root')
    {
        $xml = '<' . $root . '>';
        _array2xml($data, $xml);
        $xml .= '</' . $root . '>';
        return $xml;
    }

    public function _array2xml(&$data, &$xml, $key = '')
    {
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                if (is_numeric($k)) {
                    $xml .= '<' . $key . '>';
                    $xml .= _array2xml($v, $xml);
                    $xml .= '</' . $key . '>';
                } else {
                    if(!isnumericArray($v))
                    {
                        $xml .= '<' . $k . '>';
                    }
                    $xml .= _array2xml($v, $xml, $k);
                    if(!isnumericArray($v))
                    {
                        $xml .= '</' . $k . '>';
                    }
                }
            }
        } elseif (is_numeric($data)) {
            $xml .= $data;
        } elseif (is_string($data)) {
            $xml .= '<![CDATA[' . $data . ']]>';
        }
    }

    public function isnumericArray($array)
    {
        if(is_array($array)) {
            $keys = array_keys($array);
            return $keys != array_keys($keys);
        }
        return false;
    }
}