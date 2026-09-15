      	      	
    </div> <!-- /container -->

    <?php
      $footerCurrentPage = basename($_SERVER['PHP_SELF']);
      $showSiteFeatures = ($footerCurrentPage === 'index.php') ||
        ($footerCurrentPage === 'orders.php' && !empty($_SESSION['user']) && empty($_SESSION['admin']));
    ?>
    <?php if ($showSiteFeatures): ?>
    <section class="site-features" aria-label="Thông tin dịch vụ">
      <article class="site-feature-card"><div class="site-feature-icon"><i class="fas fa-shipping-fast"></i></div><h3>Miễn Phí Vận Chuyển</h3><p>Vận chuyển miễn phí và hoàn trả miễn phí cho tất cả đơn hàng</p></article>
      <article class="site-feature-card"><div class="site-feature-icon"><i class="fas fa-undo"></i></div><h3>Hoàn Trả Trong 30 Ngày</h3><p>Trả lại sản phẩm trong vòng 30 ngày để nhận hoàn tiền đầy đủ</p></article>
      <article class="site-feature-card"><div class="site-feature-icon"><i class="fas fa-lock"></i></div><h3>Thanh Toán An Toàn</h3><p>Trải nghiệm sự yên tâm với hệ thống thanh toán an toàn của chúng tôi</p></article>
      <article class="site-feature-card"><div class="site-feature-icon"><i class="fas fa-headset"></i></div><h3>Hỗ Trợ 24/7</h3><p>Đội hỗ trợ chuyên dụng của chúng tôi luôn sẵn sàng giúp đỡ</p></article>
    </section>
    <style>
      .site-features{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:30px;margin:0px auto 0;padding:30px 40px;background:#f8f9fa}
      .site-feature-card{text-align:center;padding:30px;background:#fff;border:2px solid #c5cfdd;border-radius:12px;box-shadow:0 4px 12px rgba(23,32,51,.06)}
      .site-feature-icon{margin-bottom:15px;color:#ffc107;font-size:2.5rem}
      .site-feature-card h3{margin:0 0 10px;color:#222;font-size:1.2rem;font-weight:600}
      .site-feature-card p{margin:0;color:#666;font-size:.95rem;line-height:1.5}
      @media(max-width:991.98px){.site-features{grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;padding:40px 20px}}
      @media(max-width:575.98px){.site-features{grid-template-columns:1fr;margin-top:40px;padding:30px 15px}.site-feature-card{padding:20px}}
    </style>
    <?php endif; ?>

    <?php if (empty($_SESSION['admin'])): ?>
      <?php require_once __DIR__ . '/../chatbot_widget.php'; ?>
    <?php endif; ?>
  </body>
</html>