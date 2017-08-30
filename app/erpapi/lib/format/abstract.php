<?php

abstract class erpapi_format_abstract{

    abstract public function data_encode($data);

    abstract public function data_decode($data);
}