<?php
class pam_encrypt_default{
    public static function get_encrypted($source_str){
        return md5($source_str);
    }
}