<?php
require_once 'config.php';require_once 'includes/auth_services.php';
if(isset($_SESSION['user_id'])){header('Location: '.$base_url);exit;}
$url=auth_google_start_url($conn);if($url===''){$_SESSION['auth_error']='Đăng nhập Google chưa được cấu hình trong quản trị.';header('Location: '.$base_url.'dang-nhap');exit;}header('Location: '.$url);exit;
?>
