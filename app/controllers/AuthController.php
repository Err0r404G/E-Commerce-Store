<?php
// controllers/AuthController.php

class AuthController {
    
    public function showLogin() {
        // Path to your login view
        require_once 'views/auth/login.php';
    }

    public function showSignup() {
        // Path to your signup view
        require_once 'views/auth/signup.php';
    }
}