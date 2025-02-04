<?php
class Api_model extends CI_Model{
    function create_account($userValue){
        return $this->db->insert('user_accounts', $userValue);
    }

}
?>