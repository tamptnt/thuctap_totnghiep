<?php
/**
 * Partial: khối "Đánh giá bài viết" hiển thị trong trang chi tiết tin tức (news_detail.php).
 *
 * Biến cần có sẵn trước khi include file này (được chuẩn bị ở news_detail.php):
 *   int    $id                    - id bài viết đang xem
 *   string $base_url              - base url của site
 *   array  $news_rating_summary   - ['avg' => float, 'count' => int]        (get_news_rating_summary)
 *   array  $news_reviews_list     - danh sách đánh giá                       (get_news_reviews)
 *   bool   $can_review_news       - user đã đăng nhập và chưa đánh giá bài này
 *   bool   $already_reviewed_news - user đã đăng nhập và đã đánh giá rồi
 */
?>
<section class="news-reviews-card" id="news-reviews">
    <h2 class="card-head"><i class="fas fa-star"></i> Đánh giá bài viết</h2>
    <div class="news-reviews-body">
        <div class="rating-summary">
            <div class="rating-big"><?php echo $news_rating_summary['count'] > 0 ? number_format($news_rating_summary['avg'], 1) : '—'; ?></div>
            <div class="stars"><?php for ($s = 1; $s <= 5; $s++): ?><i class="fa-star <?php echo $s <= round($news_rating_summary['avg']) ? 'fas' : 'far'; ?>"></i><?php endfor; ?></div>
            <div class="rating-count"><?php echo $news_rating_summary['count']; ?> đánh giá</div>
        </div>
        <div class="review-list">
            <?php if (empty($news_reviews_list)): ?>
                <p class="no-review">Bài viết này chưa có đánh giá nào. Hãy là người đầu tiên chia sẻ nhận xét!</p>
            <?php else: foreach ($news_reviews_list as $rv): ?>
                <div class="review-item">
                    <div class="review-head">
                        <strong><?php echo htmlspecialchars(mask_reviewer_name($rv['fullname'])); ?></strong>
                        <span class="stars small"><?php for ($s = 1; $s <= 5; $s++): ?><i class="fa-star <?php echo $s <= $rv['rating'] ? 'fas' : 'far'; ?>"></i><?php endfor; ?></span>
                        <span class="review-date"><?php echo date('d/m/Y', strtotime($rv['created_at'])); ?></span>
                    </div>
                    <?php if (!empty($rv['comment'])): ?><p class="review-comment"><?php echo nl2br(htmlspecialchars($rv['comment'])); ?></p><?php endif; ?>
                </div>
            <?php endforeach; endif; ?>
        </div>
        <?php if ($can_review_news): ?>
        <form class="review-form" method="POST" action="<?php echo $base_url; ?>news_review_submit.php">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="news_id" value="<?php echo $id; ?>">
            <h3>Viết đánh giá của bạn</h3>
            <div class="star-select" id="newsStarSelect">
                <?php for ($s = 1; $s <= 5; $s++): ?><i class="fas fa-star" data-value="<?php echo $s; ?>" onclick="selectNewsRating(<?php echo $s; ?>)"></i><?php endfor; ?>
            </div>
            <input type="hidden" name="rating" id="newsRatingInput" value="5">
            <textarea name="comment" rows="3" maxlength="1000" placeholder="Chia sẻ nhận xét của bạn về bài viết..."></textarea>
            <button type="submit" class="btn-submit-review"><i class="fas fa-paper-plane"></i> Gửi đánh giá</button>
        </form>
        <?php elseif ($already_reviewed_news): ?>
            <p class="review-note"><i class="fas fa-circle-check"></i> Bạn đã đánh giá bài viết này. Cảm ơn bạn!</p>
        <?php else: ?>
            <p class="review-note"><a href="<?php echo $base_url; ?>dang-nhap">Đăng nhập</a> để đánh giá bài viết này.</p>
        <?php endif; ?>
    </div>
</section>
