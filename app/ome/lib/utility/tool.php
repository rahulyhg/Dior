<?php
class ome_utility_tool{
    function get_temp_dir(){
        if ( !empty($_ENV['TMP']) )
       {
           return realpath( $_ENV['TMP'] );
       }
       else if ( !empty($_ENV['TMPDIR']) )
       {
           return realpath( $_ENV['TMPDIR'] );
       }
       else if ( !empty($_ENV['TEMP']) )
       {
           return realpath( $_ENV['TEMP'] );
       }

       // Detect by creating a temporary file
       else
       {
           // Try to use system's temporary directory
           // as random name shouldn't exist
           $temp_file = tempnam( md5(uniqid(rand(), TRUE)), '' );
           if ( $temp_file )
           {
               $temp_dir = realpath( dirname($temp_file) );
               unlink( $temp_file );
               return $temp_dir;
           }
           else
           {
               return FALSE;
           }
       }
        
    }
}


?>