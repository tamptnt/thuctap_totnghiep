<?php
require_once 'header.php';
function createSlug($str){$u=['a'=>'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ','d'=>'đ','e'=>'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ','i'=>'í|ì|ỉ|ĩ|ị','o'=>'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ','u'=>'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự','y'=>'ý|ỳ|ỷ|ỹ|ỵ'];foreach($u as $n=>$v)$str=preg_replace("/($v)/i",$n,$str);$str=strtolower($str);$str=preg_replace('/[^a-z0-9\-]/','-',$str);return trim(preg_replace('/-+/','-',$str),'-');}
$id=max(1,(int)($_GET['id']??0));$stmt=$conn->prepare("SELECT p.*,c.name cat_name,b.name brand_name FROM products p LEFT JOIN categories c ON p.category_id=c.id LEFT JOIN brands b ON p.brand_id=b.id WHERE p.id=?");$stmt->bind_param('i',$id);$stmt->execute();$p=$stmt->get_result()->fetch_assoc();$stmt->close();if(!$p){header('Location: '.$base_url.'san-pham');exit;}
$variants=get_active_variants($conn,$p['id']);
$selected_variant=null;
foreach($variants as $v){ if($v['is_default']){ $selected_variant=$v; break; } }
if(!$selected_variant && $variants) $selected_variant=$variants[0];

if($selected_variant){
    $eff=variant_effective_price($p['price'],$p['sale_price'],$selected_variant);
    $current_price=$eff['sale_price']!==null && $eff['sale_price']<$eff['price'] ? $eff['sale_price'] : $eff['price'];
    $display_original_price=$eff['price'];
    $discount=($eff['sale_price']!==null && $eff['sale_price']<$eff['price']) ? round(($eff['price']-$eff['sale_price'])/$eff['price']*100) : 0;
    $stock_for_display=(int)$selected_variant['stock_quantity'];
}else{
    $current_price=$p['sale_price']>0?$p['sale_price']:$p['price'];
    $display_original_price=$p['price'];
    $discount=$p['sale_price']>0?round(($p['price']-$p['sale_price'])/$p['price']*100):0;
    $stock_for_display=(int)$p['stock_quantity'];
}
$short_text=trim(mb_substr(strip_tags($p['description']),0,220));$related_stmt=$conn->prepare("SELECT p.*,b.name brand_name FROM products p LEFT JOIN brands b ON p.brand_id=b.id WHERE p.category_id=? AND p.id<>? ORDER BY p.sales_count DESC LIMIT 4");$related_stmt->bind_param('ii',$p['category_id'],$p['id']);$related_stmt->execute();$related_result=$related_stmt->get_result();
$specs=array_values(array_filter(array_map('trim',preg_split('/[·|]/u',(string)$p['warranty_text']))));
$gallery_images=get_product_images($conn,$p['id']);
$rating_summary=get_product_rating_summary($conn,$p['id']);
$product_reviews=get_product_reviews($conn,$p['id']);
$can_review=false;$already_reviewed=false;
if(isset($_SESSION['user_id'])){
    $already_reviewed=has_reviewed($conn,(int)$_SESSION['user_id'],$p['id']);
    if(!$already_reviewed) $can_review=(bool)find_completed_order_for_review($conn,(int)$_SESSION['user_id'],$p['id']);
}
$review_flash=$_SESSION['review_flash']??null;unset($_SESSION['review_flash']);
?>
<style>
.product-page{padding:9px 0 60px}.detail-shell{display:grid;grid-template-columns:minmax(0,1.08fr) minmax(330px,.92fr) 245px;gap:12px;align-items:start}.gallery-card,.info-card,.service-card,.description-card,.spec-card{border:1px solid var(--line);border-radius:15px;background:#fff}.gallery-card{padding:14px}.main-photo{height:455px;border-radius:12px;background:#f7faff;position:relative;display:flex;align-items:center;justify-content:center;overflow:hidden}.main-photo img{width:100%;height:100%;object-fit:contain;padding:20px}.hot-mark{position:absolute;left:10px;top:10px;padding:5px 8px;border-radius:7px;background:#ef4444;color:#fff;font-size:12px}.gallery-points{margin-top:8px;display:grid;grid-template-columns:repeat(3,1fr);gap:6px}.thumb-row{margin-top:8px;display:flex;gap:8px;flex-wrap:wrap}.thumb{width:64px;height:64px;object-fit:contain;background:#f7faff;border:2px solid var(--line);border-radius:9px;cursor:pointer;padding:4px}.thumb.active{border-color:var(--primary)}.gallery-points span{min-height:44px;padding:6px;border:1px solid var(--line);border-radius:8px;color:var(--muted);font-size:12px;text-align:center;display:flex;flex-direction:column;align-items:center;justify-content:center}.gallery-points i{color:var(--primary);margin-bottom:3px}.info-card{padding:18px}.product-topline{display:flex;align-items:center;justify-content:space-between;gap:10px;color:var(--muted);font-size:13px}.brand-label{height:29px;padding:0 9px;border-radius:7px;background:var(--soft);color:var(--primary);display:inline-flex;align-items:center;font-weight:bold}.product-title{margin:10px 0 7px;color:var(--navy);font-size:28px;line-height:1.22}.product-facts{display:flex;gap:11px;flex-wrap:wrap;color:var(--muted);font-size:13px}.stars{color:#f59e0b}.stars-link{display:inline-flex;align-items:center;gap:6px;color:var(--muted);text-decoration:none}.stars-link strong{color:var(--navy)}.spec-chips{margin:13px 0;display:flex;flex-wrap:wrap;gap:6px}.spec-chips span{min-height:30px;padding:0 8px;border-radius:7px;background:#f2f6fc;color:#435773;font-size:12px;display:flex;align-items:center}.variant-picker{margin:6px 0 4px}.variant-picker-label{display:block;margin-bottom:7px;color:var(--navy);font-size:13px;font-weight:bold}.variant-options{display:flex;flex-wrap:wrap;gap:8px}.variant-chip{min-height:38px;padding:0 12px;border:1.5px solid var(--line);border-radius:9px;background:#fff;color:var(--text);font-size:13px;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;line-height:1.25}.variant-chip small{color:#be123c;font-size:10px}.variant-chip.active{border-color:var(--primary);background:var(--soft);color:var(--primary-dark);font-weight:bold}.variant-chip:disabled{opacity:.45;cursor:not-allowed;text-decoration:line-through}.price-line{display:flex;align-items:flex-end;gap:9px;flex-wrap:wrap;margin-top:4px}.price-line strong{color:#e11d48;font-size:30px}.price-line del{color:#98a3b3;font-size:14px}.discount{padding:4px 7px;border-radius:6px;background:#fff0f3;color:#be123c;font-size:12px}.stock{margin:8px 0;color:var(--success);font-size:14px;font-weight:bold}.stock.out{color:var(--danger)}.short-text{color:var(--muted);font-size:14px;line-height:1.65}.buy-panel{margin-top:14px;padding:12px;border:1px solid #dbe6f5;border-radius:11px;background:#f8fbff}.buy-panel h2{color:var(--navy);font-size:15px}.buy-panel small{color:var(--muted);font-size:12px}.buy-row{margin-top:9px;display:grid;grid-template-columns:112px 1fr;gap:7px}.qty-box{height:40px;border:1px solid var(--line);border-radius:8px;background:#fff;display:grid;grid-template-columns:34px 1fr 34px;overflow:hidden}.qty-btn{border:0;background:#fff;color:var(--navy);cursor:pointer}.qty-input{width:100%;border:0;text-align:center;outline:0}.add-cart-btn,.ai-buy{height:40px;border:0;border-radius:8px;font-weight:bold;cursor:pointer}.add-cart-btn{background:var(--primary);color:#fff}.ai-buy{grid-column:1/-1;background:#fff;border:1px solid var(--line);color:var(--primary)}.service-card{padding:13px}.service-card h3{color:var(--navy);font-size:16px;margin-bottom:8px}.service-list{display:grid;gap:7px}.service-item{padding:9px;border-radius:9px;background:#f7faff;display:grid;grid-template-columns:31px 1fr;gap:8px;align-items:center}.service-item i{width:31px;height:31px;border-radius:8px;background:#eaf2ff;color:var(--primary);display:flex;align-items:center;justify-content:center}.service-item strong{display:block;color:var(--navy);font-size:13px}.service-item span{display:block;margin-top:2px;color:var(--muted);font-size:11px;line-height:1.35}.service-card .contact-link{margin-top:9px;height:37px;border:1px solid var(--line);border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--primary);font-weight:bold;font-size:13px}.detail-lower{margin-top:12px;display:grid;grid-template-columns:minmax(0,1.5fr) minmax(270px,.5fr);gap:12px}.card-head{min-height:49px;padding:0 14px;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:8px;color:var(--navy);font-size:16px}.description-body{padding:17px;color:var(--text);line-height:1.75}.spec-list{padding:12px}.spec-row{padding:9px 0;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;gap:10px;font-size:13px}.spec-row span{color:var(--muted)}.spec-row strong{color:var(--navy);text-align:right}.related-title{margin:25px 0 10px;display:flex;align-items:center;justify-content:space-between}.related-title h2{color:var(--navy);font-size:22px}.related-title a{color:var(--primary);font-weight:bold;font-size:13px}.related-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}.related-card{border:1px solid var(--line);border-radius:12px;background:#fff;overflow:hidden}.related-image{height:170px;padding:8px;background:#f8fbff}.related-image img{width:100%;height:100%;object-fit:contain}.related-info{padding:10px}.related-info small{color:var(--primary)}.related-info h3{height:38px;margin:4px 0;color:var(--navy);font-size:14px;line-height:1.3;overflow:hidden}.related-info strong{color:#e11d48}
.reviews-card{margin-top:12px;border:1px solid var(--line);border-radius:15px;background:#fff}
.reviews-body{padding:17px}
.rating-summary{display:flex;align-items:center;gap:12px;padding-bottom:14px;border-bottom:1px solid var(--line);margin-bottom:14px}
.rating-big{font-size:36px;font-weight:900;color:var(--navy)}
.rating-summary .stars{font-size:18px}
.rating-count{color:var(--muted);font-size:13px}
.review-list{display:flex;flex-direction:column;gap:14px;margin-bottom:16px}
.no-review{color:var(--muted);font-size:14px}
.review-item{padding-bottom:14px;border-bottom:1px solid var(--line)}
.review-item:last-child{border-bottom:0;padding-bottom:0}
.review-head{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.review-head strong{color:var(--navy);font-size:14px}
.stars.small{font-size:12px}
.review-date{color:var(--muted);font-size:12px}
.review-comment{margin-top:6px;color:var(--text);font-size:14px;line-height:1.6}
.verified-tag{display:inline-flex;align-items:center;gap:5px;margin-top:6px;color:var(--success);font-size:12px}
.review-form{padding-top:14px;border-top:1px solid var(--line)}
.review-form h3{color:var(--navy);font-size:15px;margin-bottom:10px}
.star-select{font-size:22px;color:#d1d5db;margin-bottom:10px;cursor:pointer}
.star-select i{margin-right:4px}
.star-select i.fas{color:#f59e0b}
.review-form textarea{width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:8px;font-family:inherit;font-size:14px;resize:vertical}
.btn-submit-review{margin-top:10px;height:40px;padding:0 18px;border:0;border-radius:8px;background:var(--primary);color:#fff;font-weight:bold;cursor:pointer}
.review-note{color:var(--muted);font-size:13px;padding-top:14px;border-top:1px solid var(--line)}
.review-note a{color:var(--primary);font-weight:bold}@media(max-width:1050px){.detail-shell{grid-template-columns:1fr 1fr}.service-card{grid-column:1/-1}.service-list{grid-template-columns:repeat(3,1fr)}}@media(max-width:780px){.detail-shell{grid-template-columns:1fr}.main-photo{height:370px}.service-card{grid-column:auto}.service-list{grid-template-columns:1fr}.detail-lower{grid-template-columns:1fr}.related-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:500px){.main-photo{height:300px}.product-title{font-size:24px}.buy-row{grid-template-columns:100px 1fr}.related-image{height:145px}}
</style>
<div class="container product-page"><div class="breadcrumb"><a href="<?php echo $base_url; ?>"><i class="fas fa-home"></i> Trang chủ</a><i class="fas fa-chevron-right"></i><a href="<?php echo $base_url; ?>san-pham">Sản phẩm</a><i class="fas fa-chevron-right"></i><span><?php echo htmlspecialchars($p['name']); ?></span></div>
<section class="detail-shell"><div class="gallery-card"><div class="main-photo"><?php if($p['is_hot']):?><span class="hot-mark">Bán chạy</span><?php endif;?><img id="mainProductPhoto" src="<?php echo htmlspecialchars(media_url($p['image'],$base_url)); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>"></div><?php if($gallery_images):?><div class="thumb-row"><img class="thumb active" src="<?php echo htmlspecialchars(media_url($p['image'],$base_url)); ?>" onclick="switchMainPhoto(this)" alt=""><?php foreach($gallery_images as $gimg):?><img class="thumb" src="<?php echo htmlspecialchars(media_url($gimg['image'],$base_url)); ?>" onclick="switchMainPhoto(this)" alt=""><?php endforeach;?></div><?php endif;?><div class="gallery-points"><span><i class="fas fa-box-open"></i> Kiểm tra ngoại quan</span><span><i class="fas fa-shield-halved"></i> Bảo hành rõ ràng</span><span><i class="fas fa-truck-fast"></i> Đóng gói an toàn</span></div></div>
<div class="info-card"><div class="product-topline"><span class="brand-label"><?php echo htmlspecialchars($p['brand_name']); ?></span><span>Mã SP: #<?php echo $p['id']; ?></span></div><h1 class="product-title"><?php echo htmlspecialchars($p['name']); ?></h1><div class="product-facts"><a href="#reviews" class="stars-link"><span class="stars"><?php for($s=1;$s<=5;$s++):?><i class="fa-star <?php echo $s<=round($rating_summary['avg'])?'fas':'far'; ?>"></i><?php endfor;?></span><?php if($rating_summary['count']>0):?><strong><?php echo number_format($rating_summary['avg'],1); ?></strong><span>(<?php echo $rating_summary['count']; ?> đánh giá)</span><?php else:?><span>Chưa có đánh giá</span><?php endif;?></a><span>Đã bán <?php echo number_format($p['sales_count']); ?></span><span><?php echo htmlspecialchars($p['cat_name']); ?></span></div><div class="spec-chips"><?php foreach($specs as $spec):?><span><?php echo htmlspecialchars($spec); ?></span><?php endforeach;?></div>
<?php if($variants):?>
<div class="variant-picker">
    <span class="variant-picker-label">Chọn phiên bản</span>
    <div class="variant-options">
        <?php foreach($variants as $v):$veff=variant_effective_price($p['price'],$p['sale_price'],$v);$vPrice=$veff['sale_price']!==null && $veff['sale_price']<$veff['price']?$veff['sale_price']:$veff['price'];?>
        <button type="button" class="variant-chip <?php echo $selected_variant && (int)$selected_variant['id']===(int)$v['id']?'active':''; ?>"
            data-id="<?php echo $v['id']; ?>"
            data-name="<?php echo htmlspecialchars($v['variant_name']); ?>"
            data-price="<?php echo $vPrice; ?>"
            data-original="<?php echo $veff['price']; ?>"
            data-stock="<?php echo (int)$v['stock_quantity']; ?>"
            <?php echo $v['stock_quantity']<=0?'disabled':''; ?>>
            <?php echo htmlspecialchars($v['variant_name']); ?>
            <?php if($v['stock_quantity']<=0):?><small>(Hết hàng)</small><?php endif;?>
        </button>
        <?php endforeach;?>
    </div>
</div>
<?php endif;?>
<div class="price-line" id="priceLine"><strong id="priceMain"><?php echo number_format($current_price,0,',','.'); ?> đ</strong><?php if($discount>0):?><del id="priceOriginal"><?php echo number_format($display_original_price,0,',','.'); ?> đ</del><span class="discount" id="priceDiscount">-<?php echo $discount; ?>%</span><?php endif;?></div><div class="stock <?php echo $stock_for_display<=0?'out':''; ?>" id="stockLine"><i class="fas <?php echo $stock_for_display>0?'fa-circle-check':'fa-circle-xmark'; ?>"></i> <span id="stockText"><?php echo $stock_for_display>0?'Còn '.$stock_for_display.' sản phẩm':'Tạm hết hàng'; ?></span></div><p class="short-text"><?php echo htmlspecialchars($short_text); ?>...</p>
<div class="buy-panel"><div><h2>Thêm vào giỏ hàng</h2><small>Chọn số lượng theo tồn kho hiện tại.</small></div><form action="<?php echo $base_url; ?>add_to_cart.php" method="POST"><?php echo csrf_field(); ?><input type="hidden" name="product_id" value="<?php echo $p['id']; ?>"><input type="hidden" name="variant_id" id="variantIdInput" value="<?php echo $selected_variant ? $selected_variant['id'] : ''; ?>"><div class="buy-row"><div class="qty-box"><button class="qty-btn" type="button" onclick="changeQty(-1)"><i class="fas fa-minus"></i></button><input class="qty-input" id="quantity" name="quantity" type="number" value="1" min="1" max="<?php echo max(1,$stock_for_display); ?>" readonly><button class="qty-btn" type="button" onclick="changeQty(1)"><i class="fas fa-plus"></i></button></div><button class="add-cart-btn" id="addCartBtn" type="submit" <?php echo $stock_for_display<=0?'disabled':''; ?>><i class="fas fa-cart-shopping"></i> Thêm vào giỏ</button><button type="button" class="ai-buy js-open-ai"><i class="fas fa-robot"></i> Hỏi Gemini về sản phẩm này</button></div></form></div></div>
<aside class="service-card"><h3>Yên tâm khi mua</h3><div class="service-list"><div class="service-item"><i class="fas fa-certificate"></i><div><strong>Thông tin rõ ràng</strong><span>Model, cấu hình và bảo hành hiển thị trực tiếp.</span></div></div><div class="service-item"><i class="fas fa-credit-card"></i><div><strong>Thanh toán linh hoạt</strong><span>Hỗ trợ COD và VNPay theo cấu hình cửa hàng.</span></div></div><div class="service-item"><i class="fas fa-robot"></i><div><strong>Tư vấn Gemini AI</strong><span>Gợi ý dựa trên giá, tồn kho và dữ liệu sản phẩm hiện có.</span></div></div></div><a class="contact-link" href="<?php echo $base_url; ?>lien-he"><i class="fas fa-headset"></i>&nbsp; Liên hệ tư vấn</a></aside></section>
<section class="detail-lower"><div class="description-card"><h2 class="card-head"><i class="fas fa-align-left"></i> Mô tả sản phẩm</h2><div class="description-body"><?php echo $p['description']; ?></div></div><aside class="spec-card"><h2 class="card-head"><i class="fas fa-list-check"></i> Thông tin nhanh</h2><div class="spec-list"><div class="spec-row"><span>Thương hiệu</span><strong><?php echo htmlspecialchars($p['brand_name']); ?></strong></div><div class="spec-row"><span>Danh mục</span><strong><?php echo htmlspecialchars($p['cat_name']); ?></strong></div><div class="spec-row"><span>Tồn kho</span><strong><?php echo (int)$p['stock_quantity']; ?> sản phẩm</strong></div><div class="spec-row"><span>Đã bán</span><strong><?php echo number_format($p['sales_count']); ?></strong></div><div class="spec-row"><span>Thông số / bảo hành</span><strong><?php echo htmlspecialchars($p['warranty_text']); ?></strong></div></div></aside></section>
<section class="reviews-card" id="reviews">
    <h2 class="card-head"><i class="fas fa-star"></i> Đánh giá sản phẩm</h2>
    <div class="reviews-body">
        <div class="rating-summary">
            <div class="rating-big"><?php echo $rating_summary['count']>0?number_format($rating_summary['avg'],1):'—'; ?></div>
            <div class="stars"><?php for($s=1;$s<=5;$s++):?><i class="fa-star <?php echo $s<=round($rating_summary['avg'])?'fas':'far'; ?>"></i><?php endfor;?></div>
            <div class="rating-count"><?php echo $rating_summary['count']; ?> đánh giá</div>
        </div>
        <div class="review-list">
            <?php if(empty($product_reviews)):?>
                <p class="no-review">Sản phẩm này chưa có đánh giá nào. Hãy là người đầu tiên chia sẻ trải nghiệm!</p>
            <?php else: foreach($product_reviews as $rv):?>
                <div class="review-item">
                    <div class="review-head">
                        <strong><?php echo htmlspecialchars(mask_reviewer_name($rv['fullname'])); ?></strong>
                        <span class="stars small"><?php for($s=1;$s<=5;$s++):?><i class="fa-star <?php echo $s<=$rv['rating']?'fas':'far'; ?>"></i><?php endfor;?></span>
                        <span class="review-date"><?php echo date('d/m/Y',strtotime($rv['created_at'])); ?></span>
                    </div>
                    <?php if(!empty($rv['comment'])):?><p class="review-comment"><?php echo nl2br(htmlspecialchars($rv['comment'])); ?></p><?php endif;?>
                    <span class="verified-tag"><i class="fas fa-circle-check"></i> Đã mua hàng</span>
                </div>
            <?php endforeach; endif;?>
        </div>

        <?php if($can_review):?>
        <form class="review-form" method="POST" action="<?php echo $base_url; ?>review_submit.php">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
            <h3>Viết đánh giá của bạn</h3>
            <div class="star-select" id="starSelect">
                <?php for($s=1;$s<=5;$s++):?><i class="fas fa-star" data-value="<?php echo $s; ?>" onclick="selectRating(<?php echo $s; ?>)"></i><?php endfor;?>
            </div>
            <input type="hidden" name="rating" id="ratingInput" value="5">
            <textarea name="comment" rows="3" maxlength="1000" placeholder="Chia sẻ cảm nhận của bạn về sản phẩm..."></textarea>
            <button type="submit" class="btn-submit-review"><i class="fas fa-paper-plane"></i> Gửi đánh giá</button>
        </form>
        <?php elseif($already_reviewed):?>
            <p class="review-note"><i class="fas fa-circle-check"></i> Bạn đã đánh giá sản phẩm này. Cảm ơn bạn!</p>
        <?php elseif(isset($_SESSION['user_id'])):?>
            <p class="review-note">Bạn cần mua và nhận sản phẩm này (đơn hàng đã hoàn thành) mới có thể đánh giá.</p>
        <?php else:?>
            <p class="review-note"><a href="<?php echo $base_url; ?>dang-nhap">Đăng nhập</a> để đánh giá nếu bạn đã mua sản phẩm này.</p>
        <?php endif;?>
    </div>
</section>

<div class="related-title"><h2>Sản phẩm cùng danh mục</h2><a href="<?php echo $base_url; ?>san-pham?categories[]=<?php echo $p['category_id']; ?>">Xem tất cả</a></div><section class="related-grid"><?php while($r=$related_result->fetch_assoc()):$rp=$r['sale_price']>0?$r['sale_price']:$r['price'];?><article class="related-card"><a class="related-image" href="<?php echo $base_url.'san-pham/'.createSlug($r['name']).'-'.$r['id']; ?>"><img src="<?php echo htmlspecialchars(media_url($r['image'],$base_url)); ?>" alt="<?php echo htmlspecialchars($r['name']); ?>"></a><div class="related-info"><small><?php echo htmlspecialchars($r['brand_name']); ?></small><a href="<?php echo $base_url.'san-pham/'.createSlug($r['name']).'-'.$r['id']; ?>"><h3><?php echo htmlspecialchars($r['name']); ?></h3></a><strong><?php echo number_format($rp,0,',','.'); ?> đ</strong></div></article><?php endwhile;?></section></div>
<script>
function changeQty(delta){const input=document.getElementById('quantity');if(!input)return;const min=parseInt(input.min||'1',10),max=parseInt(input.max||'999',10),value=parseInt(input.value||'1',10);input.value=Math.max(min,Math.min(max,value+delta))}
function switchMainPhoto(thumbEl){
    document.getElementById('mainProductPhoto').src=thumbEl.src;
    document.querySelectorAll('.thumb').forEach(function(t){t.classList.remove('active')});
    thumbEl.classList.add('active');
}
function selectRating(value){
    document.getElementById('ratingInput').value=value;
    document.querySelectorAll('#starSelect i').forEach(function(star){
        const starValue=parseInt(star.dataset.value,10);
        star.classList.toggle('fas',starValue<=value);
        star.classList.toggle('far',starValue>value);
    });
}
<?php if($review_flash):?>
document.addEventListener('DOMContentLoaded',function(){
    if(window.Swal){
        Swal.fire({
            icon:<?php echo json_encode($review_flash['status']==='success'?'success':'error',JSON_UNESCAPED_UNICODE); ?>,
            title:<?php echo json_encode($review_flash['status']==='success'?'Thành công!':'Không thể gửi đánh giá',JSON_UNESCAPED_UNICODE); ?>,
            text:<?php echo json_encode($review_flash['message'],JSON_UNESCAPED_UNICODE); ?>,
            confirmButtonColor:'#173f67'
        });
    }
    const target=document.getElementById('reviews');
    if(target) target.scrollIntoView({behavior:'smooth',block:'start'});
});
<?php endif;?>
document.querySelectorAll('.variant-chip').forEach(function(chip){
    chip.addEventListener('click',function(){
        if(chip.disabled) return;
        document.querySelectorAll('.variant-chip').forEach(function(c){c.classList.remove('active')});
        chip.classList.add('active');

        const price=parseFloat(chip.dataset.price||'0');
        const original=parseFloat(chip.dataset.original||'0');
        const stock=parseInt(chip.dataset.stock||'0',10);
        const fmt=function(n){return Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.')};

        document.getElementById('variantIdInput').value=chip.dataset.id;
        document.getElementById('priceMain').textContent=fmt(price)+' đ';

        const delEl=document.getElementById('priceOriginal'),discEl=document.getElementById('priceDiscount');
        if(price<original){
            if(delEl){delEl.textContent=fmt(original)+' đ';delEl.style.display='';}
            if(discEl){discEl.textContent='-'+Math.round((original-price)/original*100)+'%';discEl.style.display='';}
        }else{
            if(delEl) delEl.style.display='none';
            if(discEl) discEl.style.display='none';
        }

        const stockLine=document.getElementById('stockLine'),stockText=document.getElementById('stockText'),icon=stockLine?stockLine.querySelector('i'):null;
        if(stock>0){
            stockLine.classList.remove('out');
            if(icon){icon.classList.remove('fa-circle-xmark');icon.classList.add('fa-circle-check');}
            stockText.textContent='Còn '+stock+' sản phẩm';
        }else{
            stockLine.classList.add('out');
            if(icon){icon.classList.remove('fa-circle-check');icon.classList.add('fa-circle-xmark');}
            stockText.textContent='Tạm hết hàng';
        }

        const qtyInput=document.getElementById('quantity'),addBtn=document.getElementById('addCartBtn');
        qtyInput.max=Math.max(1,stock);
        qtyInput.value=stock>0?1:0;
        addBtn.disabled=stock<=0;
    });
});
</script>
<?php require_once 'footer.php'; ?>
