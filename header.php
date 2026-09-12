<?php
require_once 'config.php';
$site_settings=[];
$set_query=$conn->query("SELECT setting_key,setting_value,icon FROM settings");
while($r=$set_query->fetch_assoc())$site_settings[$r['setting_key']]=$r;
if(!isset($_SESSION['cart']))$_SESSION['cart']=[];
sync_cart_product_media($conn);
$cart_count=array_sum(array_map(function($item){return (int)($item['quantity']??1);},$_SESSION['cart']));
$siteName=$site_settings['site_name']['setting_value']??'NovaTech';
$hotline=$site_settings['hotline']['setting_value']??'0908 246 810';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?php echo htmlspecialchars($siteName); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<link href="<?php echo $base_url; ?>assets/tech-theme.css" rel="stylesheet">
</head>
<body>
<header class="site-header">
<div class="utility-bar"><div class="container utility-inner"><div><span><i class="fas fa-shield-halved"></i> Hàng chính hãng</span><span><i class="fas fa-rotate"></i> Đổi trả theo chính sách</span></div><div><span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($hotline); ?></span><span><i class="fas fa-truck-fast"></i> Giao hàng toàn quốc</span></div></div></div>
<div class="header-main"><div class="container header-main-inner">
<button class="mobile-trigger js-toggle-sidebar" type="button"><i class="fas fa-bars"></i></button>
<a class="brand" href="<?php echo $base_url; ?>"><span class="brand-mark"><i class="fas fa-microchip"></i></span><span><strong><?php echo htmlspecialchars($siteName); ?></strong><small>Công nghệ gọn cho mọi nhu cầu</small></span></a>
<form class="search-box" action="<?php echo $base_url; ?>san-pham" method="GET"><input type="text" name="search" placeholder="Tìm laptop, điện thoại, màn hình, phụ kiện..."><button type="submit"><i class="fas fa-search"></i></button></form>
<div class="header-actions">
<a class="build-shortcut" href="<?php echo $base_url; ?>san-pham?categories[]=1"><i class="fas fa-laptop"></i><span><small>Gợi ý nhanh</small><strong>Laptop</strong></span></a>
<div class="action-item"><a class="circle-action" href="<?php echo $base_url; ?>gio-hang"><i class="fas fa-cart-shopping"></i><b class="badge"><?php echo $cart_count; ?></b></a><div class="dropdown-menu cart-dropdown">
<?php if($cart_count>0):$count=0;foreach($_SESSION['cart'] as $id=>$item):if($count>=3)break;?><div class="mini-cart-item"><img src="<?php echo htmlspecialchars(media_url($item['image']??'', $base_url)); ?>" alt="Sản phẩm"><div><strong><?php echo htmlspecialchars($item['name']); ?></strong><span><?php echo number_format($item['price'],0,'.','.'); ?> đ × <?php echo $item['quantity']; ?></span></div></div><?php $count++;endforeach;?><a class="mini-cart-button" href="<?php echo $base_url; ?>gio-hang">Xem giỏ hàng</a><?php else:?><div class="empty-mini-cart"><i class="fas fa-cart-shopping"></i><span>Giỏ hàng đang trống</span></div><?php endif;?></div></div>
<div class="action-item user-dropdown">
<?php if(isset($_SESSION['user_id'])):?><button class="user-button js-dropdown-toggle" type="button" aria-expanded="false"><i class="fas fa-circle-user"></i><span><?php echo htmlspecialchars($_SESSION['fullname']); ?></span><i class="fas fa-chevron-down"></i></button><div class="dropdown-menu user-menu"><?php if($_SESSION['role']==='admin'):?><a href="<?php echo $base_url; ?>admin/"><i class="fas fa-gauge-high"></i> Quản trị</a><?php endif;?><a href="<?php echo $base_url; ?>tai-khoan"><i class="fas fa-user"></i> Tài khoản</a><a href="<?php echo $base_url; ?>don-hang"><i class="fas fa-box"></i> Đơn hàng</a><a href="<?php echo $base_url; ?>dang-xuat"><i class="fas fa-right-from-bracket"></i> Đăng xuất</a></div><?php else:?><a class="user-button" href="<?php echo $base_url; ?>dang-nhap"><i class="fas fa-circle-user"></i><span>Đăng nhập</span></a><?php endif;?>
</div></div></div></div>
<nav class="main-nav"><div class="container nav-inner"><a class="nav-all" href="<?php echo $base_url; ?>san-pham"><i class="fas fa-border-all"></i> Sản phẩm</a><a href="<?php echo $base_url; ?>">Trang chủ</a><a href="<?php echo $base_url; ?>san-pham?categories[]=1">Laptop</a><a href="<?php echo $base_url; ?>san-pham?categories[]=2">Điện thoại</a><a href="<?php echo $base_url; ?>san-pham?categories[]=4">Màn hình</a><a href="<?php echo $base_url; ?>san-pham?categories[]=5">Phụ kiện</a><a href="<?php echo $base_url; ?>tin-tuc">Tin công nghệ</a><a href="<?php echo $base_url; ?>lien-he">Liên hệ</a><span class="nav-spacer"></span><a class="nav-support js-open-ai" href="#"><i class="fas fa-robot"></i> Tư vấn AI</a></div></nav>
</header>
<aside class="mobile-sidebar js-mobile-sidebar"><div class="mobile-sidebar-head"><a class="brand" href="<?php echo $base_url; ?>"><span class="brand-mark"><i class="fas fa-microchip"></i></span><span><strong><?php echo htmlspecialchars($siteName); ?></strong><small>Thiết bị công nghệ</small></span></a><button class="js-close-sidebar"><i class="fas fa-xmark"></i></button></div><form class="mobile-search" action="<?php echo $base_url; ?>san-pham" method="GET"><input type="text" name="search" placeholder="Tìm sản phẩm"><button><i class="fas fa-search"></i></button></form><nav class="mobile-nav"><a href="<?php echo $base_url; ?>"><i class="fas fa-house"></i> Trang chủ</a><a href="<?php echo $base_url; ?>san-pham"><i class="fas fa-laptop"></i> Sản phẩm</a><a href="<?php echo $base_url; ?>tin-tuc"><i class="fas fa-newspaper"></i> Tin công nghệ</a><a href="<?php echo $base_url; ?>lien-he"><i class="fas fa-envelope"></i> Liên hệ</a><a href="<?php echo $base_url; ?>gio-hang"><i class="fas fa-cart-shopping"></i> Giỏ hàng</a></nav></aside>
<div class="mobile-overlay js-overlay"></div><main>
