<?php
require_once __DIR__ . '/../config/bootstrap.php';
session_destroy();
header('Location: /auth/login.php');
