#!/usr/bin/env php
<?php
$dir = realpath(dirname(__FILE__).'/../');
$path = array_shift($_SERVER['argv']);
$server_name = array_shift($_SERVER['argv']);
array_unshift($_SERVER['argv'],$path);
$_SERVER['SERVER_NAME'] = $server_name;
include $dir."/app/base/cmd";
