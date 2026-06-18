<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users_model extends CI_Model {
    
    public function getDatausers($id = null)
    {
        $this->datatables->select('users.id, username, first_name, last_name, email, FROM_UNIXTIME(created_on) as created_on, last_login, active, roles.name as level');
        $this->datatables->from('user_roles');
        $this->datatables->join('users', 'user_roles.user_id=users.id');
        $this->datatables->join('roles', 'user_roles.role_id=roles.id');
        if($id !== null){
            $this->datatables->where('users.id !=', $id);
        }
        return $this->datatables->generate();
    }
}