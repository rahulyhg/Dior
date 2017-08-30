<?php

class pam_encrypt{
    public static function get_encrypted_password($password,$account_type){

        $encrypt = kernel::service('encrypt_'.$account_type);
        if(is_object($encrypt)){
            if(method_exists($encrypt,'get_encrypted')){

            }
        }else{
            $encrypt = kernel::single('pam_encrypt_default');
        }
        return $encrypt->get_encrypted($password);
    }
}