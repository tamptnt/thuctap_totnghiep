<?php
ob_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . 'dang-nhap');
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$preselect_order_id = (int)($_GET['order_id'] ?? 0);

require_once 'header.php';

$msg_type = '';
$msg_text = '';
if (isset($_SESSION['msg'])) {
    $msg_type = $_SESSION['msg']['type'];
    $msg_text = $_SESSION['msg']['text'];
    unset($_SESSION['msg']);
}

$returnable_orders = get_returnable_orders($conn, $user_id);
$my_returns = get_user_returns($conn, $user_id);

$reasons = ['Sản phẩm bị lỗi/hư hỏng', 'Giao sai sản phẩm, sai màu/phiên bản', 'Không đúng mô tả trên website', 'Không còn nhu cầu sử dụng', 'Lý do khác'];
?>
<style>
.returns-page{padding:15px 0 60px}
.returns-head{padding:17px 19px;border:1px solid var(--line);border-radius:14px;background:#fbfaf8;display:flex;align-items:center;justify-content:space-between;gap:12px}
.returns-head h1{color:var(--primary-dark);font-size:27px}
.returns-head p{margin-top:4px;color:var(--muted);font-size:14px}
.returns-head>i{font-size:30px;color:#c5aa91}
.returns-policy{margin:12px 0;padding:12px 14px;border:1px dashed var(--line);border-radius:11px;background:#fff;color:var(--muted);font-size:13px;display:flex;gap:10px;align-items:flex-start}
.returns-policy i{color:var(--primary);margin-top:2px}
.section-title{margin:22px 0 10px;color:var(--primary-dark);font-size:18px;display:flex;align-items:center;gap:8px}
.returnable-card{border:1px solid var(--line);border-radius:12px;background:#fff;margin-bottom:10px;overflow:hidden}
.returnable-card-head{padding:11px 14px;background:#fbfaf8;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:6px;font-size:13px;color:var(--muted)}
.returnable-card-head strong{color:var(--primary-dark)}
.deadline-pill{padding:4px 9px;border-radius:8px;background:#fff7e8;color:#987038;font-weight:bold;font-size:12px}
.ri-row{display:grid;grid-template-columns:52px 1fr auto;gap:10px;align-items:center;padding:11px 14px;border-top:1px solid var(--line)}
.ri-row:first-of-type{border-top:0}
.ri-row img{width:52px;height:46px;object-fit:contain;background:#fbfaf8;border-radius:8px}
.ri-name{color:var(--primary-dark);font-weight:bold;font-size:14px}
.ri-meta{color:var(--muted);font-size:12px;margin-top:2px}
.btn-request{height:36px;padding:0 13px;border:0;border-radius:8px;background:var(--primary);color:#fff;font-weight:bold;font-size:13px;cursor:pointer;white-space:nowrap}
.empty-box{padding:34px;text-align:center;border:1px solid var(--line);border-radius:12px;color:var(--muted)}
.empty-box i{font-size:30px;color:#c5aa91;margin-bottom:8px;display:block}
.rq-grid{display:grid;grid-template-columns:1fr;gap:9px}
.rq-card{border:1px solid var(--line);border-radius:12px;background:#fff;padding:13px;display:grid;grid-template-columns:56px 1fr auto;gap:12px;align-items:flex-start}
.rq-card img{width:56px;height:50px;object-fit:contain;background:#fbfaf8;border-radius:8px}
.rq-name{color:var(--primary-dark);font-weight:bold;font-size:14px}
.rq-meta{color:var(--muted);font-size:12px;margin-top:3px;line-height:1.6}
.rq-note{margin-top:6px;padding:8px 10px;border-radius:8px;background:#fbfaf8;font-size:12px;color:var(--text)}
.rq-side{display:flex;flex-direction:column;align-items:flex-end;gap:8px}
.status-badge{display:inline-flex;padding:5px 10px;border-radius:13px;font-size:12px;font-weight:bold;white-space:nowrap}
.status-pending{background:#fff7e8;color:#987038}
.status-approved{background:#eaf2fb;color:#2f6fad}
.status-completed{background:#edf6f0;color:#55755e}
.status-rejected{background:#fff0ef;color:#a75450}
.btn-cancel-rq{height:32px;padding:0 11px;border:0;border-radius:8px;background:#b45c57;color:#fff;font-weight:bold;font-size:12px;cursor:pointer}
.modal-box{display:none;position:fixed;inset:0;z-index:1600;padding:14px;background:rgba(67,54,45,.42);align-items:center;justify-content:center}
.modal-box.show{display:flex}
.modal-content{width:min(520px,100%);max-height:92vh;border-radius:12px;background:#fff;overflow:auto}
.modal-header,.modal-footer{padding:13px 15px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between}
.modal-footer{border-bottom:0;border-top:1px solid var(--line);justify-content:flex-end;gap:8px}
.modal-body{padding:15px;display:grid;gap:12px}
.form-group label{display:block;margin-bottom:6px;color:var(--primary-dark);font-weight:bold;font-size:13px}
.form-group select,.form-group textarea{width:100%;border:1px solid var(--line);border-radius:8px;padding:9px 10px;font-size:14px;font-family:inherit}
.type-choice{display:flex;gap:10px}
.type-choice label{flex:1;border:1px solid var(--line);border-radius:9px;padding:9px 10px;font-size:13px;display:flex;align-items:center;gap:7px;cursor:pointer}
.type-choice input{accent-color:var(--primary)}
.btn-submit-rq{height:38px;padding:0 16px;border:0;border-radius:8px;background:var(--primary);color:#fff;font-weight:bold;cursor:pointer}
.btn-close-modal{height:38px;padding:0 16px;border:1px solid var(--line);border-radius:8px;background:#fff;color:var(--primary-dark);cursor:pointer}
@media(max-width:700px){.rq-card{grid-template-columns:44px 1fr}.rq-side{grid-column:1/-1;flex-direction:row;justify-content:space-between;margin-top:6px}.ri-row{grid-template-columns:44px 1fr}.btn-request{grid-column:1/-1;margin-top:6px}}
</style>
<div class="container returns-page">
    <div class="breadcrumb"><a href="<?php echo $base_url; ?>"><i class="fas fa-home"></i> Trang chủ</a><i class="fas fa-chevron-right"></i><span>Đổi trả hàng</span></div>
    <section class="returns-head">
        <div>
            <h1>Đổi trả hàng</h1>
            <p>Gửi yêu cầu đổi hoặc trả hàng cho các sản phẩm thuộc đơn hàng đã hoàn thành.</p>
        </div>
        <i class="fas fa-rotate-left"></i>
    </section>
    <div class="returns-policy"><i class="fas fa-circle-info"></i><span>Áp dụng cho đơn hàng đã <strong>hoàn thành</strong>, trong vòng <strong><?php echo RETURN_WINDOW_DAYS; ?> ngày</strong> kể từ ngày hoàn thành. Sản phẩm cần còn nguyên vẹn, đầy đủ phụ kiện/hộp đi kèm.</div>

    <h2 class="section-title"><i class="fas fa-box-open"></i> Đơn hàng đủ điều kiện đổi trả</h2>
    <?php if (!$returnable_orders): ?>
        <div class="empty-box"><i class="fas fa-inbox"></i>Hiện không có đơn hàng nào đủ điều kiện gửi yêu cầu đổi trả.</div>
    <?php else: foreach ($returnable_orders as $order): ?>
        <div class="returnable-card">
            <div class="returnable-card-head">
                <span>Đơn hàng <strong>#<?php echo $order['id']; ?></strong> · hoàn thành <?php echo date('d/m/Y', strtotime(get_order_completed_at($conn, $order['id']))); ?></span>
                <span class="deadline-pill"><i class="far fa-clock"></i> Còn <?php echo (int)$order['days_left']; ?> ngày để gửi yêu cầu</span>
            </div>
            <?php foreach ($order['items'] as $item): ?>
            <div class="ri-row">
                <img src="<?php echo htmlspecialchars(media_url($item['product_image'] ?? '', $base_url)); ?>" alt="">
                <div>
                    <div class="ri-name"><?php echo htmlspecialchars($item['product_name']); ?><?php echo $item['variant_name'] ? ' (' . htmlspecialchars($item['variant_name']) . ')' : ''; ?></div>
                    <div class="ri-meta">Đã mua: <?php echo (int)$item['quantity']; ?> · Còn có thể yêu cầu: <?php echo (int)$item['remaining_quantity']; ?></div>
                </div>
                <button type="button" class="btn-request" onclick='openRequestModal(<?php echo json_encode([
                    "order_item_id" => (int)$item["id"],
                    "order_id" => (int)$order["id"],
                    "name" => $item["product_name"] . ($item["variant_name"] ? " (" . $item["variant_name"] . ")" : ""),
                    "max" => (int)$item["remaining_quantity"],
                ], JSON_UNESCAPED_UNICODE); ?>)'><i class="fas fa-rotate-left"></i> Gửi yêu cầu</button>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; endif; ?>

    <h2 class="section-title"><i class="fas fa-list-check"></i> Yêu cầu đổi trả của tôi</h2>
    <?php if (!$my_returns): ?>
        <div class="empty-box"><i class="fas fa-clipboard"></i>Bạn chưa gửi yêu cầu đổi trả nào.</div>
    <?php else: ?>
    <div class="rq-grid">
        <?php foreach ($my_returns as $rq): ?>
        <div class="rq-card">
            <img src="<?php echo htmlspecialchars(media_url($rq['product_image'] ?? '', $base_url)); ?>" alt="">
            <div>
                <div class="rq-name"><?php echo htmlspecialchars($rq['product_name']); ?><?php echo $rq['variant_name'] ? ' (' . htmlspecialchars($rq['variant_name']) . ')' : ''; ?></div>
                <div class="rq-meta">
                    Đơn hàng #<?php echo (int)$rq['order_id']; ?> · Số lượng: <?php echo (int)$rq['quantity']; ?> · <?php echo htmlspecialchars(return_type_label($rq['type'])); ?><br>
                    Lý do: <?php echo htmlspecialchars($rq['reason']); ?> · Gửi lúc <?php echo date('H:i d/m/Y', strtotime($rq['created_at'])); ?>
                </div>
                <?php if (!empty($rq['description'])): ?><div class="rq-note"><i class="fas fa-comment-dots"></i> <?php echo nl2br(htmlspecialchars($rq['description'])); ?></div><?php endif; ?>
                <?php if (!empty($rq['admin_note'])): ?><div class="rq-note"><i class="fas fa-user-shield"></i> Phản hồi cửa hàng: <?php echo nl2br(htmlspecialchars($rq['admin_note'])); ?></div><?php endif; ?>
            </div>
            <div class="rq-side">
                <span class="status-badge status-<?php echo $rq['status']; ?>"><?php echo return_status_label($rq['status']); ?></span>
                <?php if ($rq['status'] === 'pending'): ?>
                <form method="POST" action="<?php echo $base_url; ?>return_submit.php" onsubmit="return confirmCancelRequest(event,this)">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" name="return_id" value="<?php echo (int)$rq['id']; ?>">
                    <button type="submit" class="btn-cancel-rq"><i class="fas fa-xmark"></i> Huỷ yêu cầu</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<div class="modal-box js-request-modal">
    <div class="modal-content">
        <div class="modal-header"><h3 class="modal-title">Gửi yêu cầu đổi trả</h3><i class="fas fa-xmark" onclick="closeModal('js-request-modal')"></i></div>
        <form method="POST" action="<?php echo $base_url; ?>return_submit.php">
            <div class="modal-body">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="order_item_id" id="rm_order_item_id">
                <div class="form-group"><label id="rm_product_name" style="font-weight:bold;color:var(--primary-dark)"></label></div>
                <div class="form-group">
                    <label>Loại yêu cầu</label>
                    <div class="type-choice">
                        <label><input type="radio" name="type" value="return" checked> Trả hàng - hoàn tiền</label>
                        <label><input type="radio" name="type" value="exchange"> Đổi hàng khác</label>
                    </div>
                </div>
                <div class="form-group">
                    <label>Số lượng</label>
                    <select name="quantity" id="rm_quantity"></select>
                </div>
                <div class="form-group">
                    <label>Lý do</label>
                    <select name="reason" required>
                        <?php foreach ($reasons as $r): ?><option value="<?php echo htmlspecialchars($r); ?>"><?php echo htmlspecialchars($r); ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Mô tả thêm (không bắt buộc)</label>
                    <textarea name="description" rows="3" maxlength="1000" placeholder="Mô tả chi tiết tình trạng sản phẩm..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-close-modal" onclick="closeModal('js-request-modal')">Huỷ</button>
                <button type="submit" class="btn-submit-rq"><i class="fas fa-paper-plane"></i> Gửi yêu cầu</button>
            </div>
        </form>
    </div>
</div>
<script>
function openModal(className){document.querySelector('.'+className).classList.add('show')}
function closeModal(className){document.querySelector('.'+className).classList.remove('show')}
function openRequestModal(data){
    document.getElementById('rm_order_item_id').value=data.order_item_id;
    document.getElementById('rm_product_name').textContent=data.name;
    const qty=document.getElementById('rm_quantity');
    qty.innerHTML='';
    for(let i=1;i<=data.max;i++){const opt=document.createElement('option');opt.value=i;opt.textContent=i;qty.appendChild(opt)}
    openModal('js-request-modal');
}
function confirmCancelRequest(event,form){
    event.preventDefault();
    Swal.fire({title:'Huỷ yêu cầu đổi trả?',text:'Bạn có chắc chắn muốn huỷ yêu cầu này không?',icon:'warning',showCancelButton:true,confirmButtonColor:'#b65757',cancelButtonColor:'#667681',confirmButtonText:'Đồng ý huỷ',cancelButtonText:'Không'}).then(function(result){if(result.isConfirmed)form.submit()});
    return false;
}
<?php if ($preselect_order_id > 0): ?>
document.addEventListener('DOMContentLoaded', function(){
    document.querySelector('.returnable-card-head strong')?.scrollIntoView({behavior:'smooth',block:'center'});
});
<?php endif; ?>
<?php if ($msg_type !== ''): ?>
document.addEventListener('DOMContentLoaded', function(){
    Swal.fire({icon:<?php echo json_encode($msg_type); ?>,title:<?php echo json_encode($msg_type === 'success' ? 'Thành công' : 'Lỗi', JSON_UNESCAPED_UNICODE); ?>,text:<?php echo json_encode($msg_text, JSON_UNESCAPED_UNICODE); ?>,confirmButtonColor:'#27699a',timer:2800});
});
<?php endif; ?>
</script>
<?php require_once 'footer.php'; ?>
