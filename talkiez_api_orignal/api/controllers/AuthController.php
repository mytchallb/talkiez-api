<?php
require_once 'controllers/DatabaseController.php';

class AuthController {
  private $db;

    public function __construct() {
        $this->db = new DatabaseController();
    }

  public function getUsers() {
    return $this->db->getUsers();
  }

  public function register($username, $password) {
    
    return $this->db->register($username, $password);
  }

  public function login() {
    return $this->db->login();
  }
}