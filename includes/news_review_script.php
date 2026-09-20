<?php
/**
 * Script riêng cho khối "Đánh giá bài viết".
 * Include file này bên trong thẻ <script> của trang chi tiết tin tức.
 *
 * Biến cần có sẵn trước khi include:
 *   array|null $news_review_flash - ['status' => 'success'|'error', 'message' => string] hoặc null
 */
?>
function selectNewsRating(value){
    document.getElementById('newsRatingInput').value=value;
    document.querySelectorAll('#newsStarSelect i').forEach(function(star){
        const starValue=parseInt(star.dataset.value,10);
        star.classList.toggle('fas',starValue<=value);
        star.classList.toggle('far',starValue>value);
    });
}
<?php if ($news_review_flash): ?>
document.addEventListener('DOMContentLoaded',function(){
    if(window.Swal){
        Swal.fire({
            icon:<?php echo json_encode($news_review_flash['status'] === 'success' ? 'success' : 'error', JSON_UNESCAPED_UNICODE); ?>,
            title:<?php echo json_encode($news_review_flash['status'] === 'success' ? 'Thành công!' : 'Không thể gửi đánh giá', JSON_UNESCAPED_UNICODE); ?>,
            text:<?php echo json_encode($news_review_flash['message'], JSON_UNESCAPED_UNICODE); ?>,
            confirmButtonColor:'#173f67'
        });
    }
    const target=document.getElementById('news-reviews');
    if(target) target.scrollIntoView({behavior:'smooth',block:'start'});
});
<?php endif; ?>
