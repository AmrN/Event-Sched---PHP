<?php
 
class PassHash {
 
    public static function hash($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
 
    public static function check_password($hash, $password) {
        return password_verify($password, $hash);
    }

}
 
?>