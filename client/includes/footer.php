 <footer class="footer">
     <div class="footer-top">
         <div class="footer-section">
             <h4>Ocean Pearl</h4>
             <ul>
                 <li>
                     <i class="fa-solid fa-location-dot" style="color: #3c3b3b"></i>
                     Phú Quốc
                 </li>
                 <li>
                     <i class="fa-solid fa-phone" style="color: #3c3b3b"></i>
                     +84.244.243.434
                 </li>
                 <li>
                     <i class="fa-solid fa-envelope" style="color: #3c3b3b"></i>
                     contact@hoteloceanpearl.com
                 </li>
                 <li class="payments">
                     <span>Thanh toán:</span>
                     <i class="fab fa-cc-visa" style="color: #3c3b3b"></i>
                     <i class="fab fa-cc-mastercard" style="color: #3c3b3b"></i>
                     <i class="fab fa-cc-paypal" style="color: #3c3b3b"></i>
                 </li>
                 <li class="payments">
                     <span>Theo dõi chúng tôi:</span>
                     <i class="fab fa-square-facebook" style="color: #3c3b3b"></i>
                     <i class="fab fa-square-threads" style="color: #3c3b3b"></i>
                     <i class="fab fa-instagram" style="color: #3c3b3b"></i>
                     <i class="fab fa-square-x-twitter" style="color: #3c3b3b"></i>
                 </li>
             </ul>
         </div>

         <div class="footer-section">
             <h4>Liên kết hữu ích</h4>
             <ul>
                 <li><a href="#">Vị trí</a></li>
                 <li><a href="/My-Web-Hotel/client/index.php?page=about">Về chúng tôi</a></li>
                 <li><a href="/My-Web-Hotel/client/index.php?page=giaiTri">Giải trí</a></li>
                 <li><a href="/My-Web-Hotel/client/index.php?page=spa">Spa</a></li>
                 <li><a href="/My-Web-Hotel/client/index.php?page=blog">Blog</a></li>
             </ul>
         </div>

         <div class="footer-section">
             <h4>Hỗ trợ</h4>
             <ul>
                 <li><a href="#">Trung tâm trợ giúp</a></li>
                 <li><a href="#">Liên hệ</a></li>
                 <li><a href="#">Điều khoản & Điều kiện</a></li>
                 <li><a href="#">Thông tin an toàn</a></li>
                 <li><a href="#">Biện pháp Covid-19</a></li>
             </ul>
         </div>

         <div class="footer-section">
             <h4>Tin tức mới nhất</h4>
             <ul>
                 <li><a href="#">10+1 Cocktail hấp dẫn</a></li>
                 <li><a href="#">Dạo chơi ban ngày</a></li>
                 <li><a href="#">Top 10 bãi biển tuyệt đẹp</a></li>
                 <li><a href="#">Lễ cưới tại OceanPearl</a></li>
                 <li><a href="#">Bữa sáng tại khách sạn</a></li>
             </ul>
         </div>
     </div>

     <div class="footer-bottom">
         <p>© 2025 OceanPearl Hotel. Crafted with ♥ by CuteTeam</p>
         <div class="footer-links">
             <a href="#">Về chúng tôi</a>
             <a href="#">Liên hệ</a>
             <a href="#">Điều khoản</a>
         </div>
     </div>
 </footer>
 <!-- embed js Bootstrap -->
 <script src="/My-Web-Hotel/lib/Bootstrap/js/bootstrap.bundle.min.js"></script>
 <!-- embed aos js -->
 <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
 <script>
AOS.init();
 </script>
 <!-- js -->
 <script src="/My-Web-Hotel/client/database/data.js?v=<?php echo time(); ?>"></script>
 <!-- js loading -->
 <script src="/My-Web-Hotel/client/assets/js/loading.js?v=<?php echo time(); ?>"></script>
 <?php
        if ($page == 'home') echo '<script src="/My-Web-Hotel/client/assets/js/home.js"></script>';
        if ($page == 'room') echo '<script src="/My-Web-Hotel/client/assets/js/room.js"></script>';
        if ($page == 'dichVu') echo '<script src="/My-Web-Hotel/client/assets/js/dichVu.js"></script>';
        if ($page == 'blog') echo '<script src="/My-Web-Hotel/client/assets/js/blog.js"></script>';
        if ($page == 'danhGia') echo '<script src="/My-Web-Hotel/client/assets/js/danhGia.js"></script>';
        if ($page == 'booking') echo '<script src="/My-Web-Hotel/client/assets/js/booking.js"></script>';
        if ($page == 'about') echo '<script src="/My-Web-Hotel/client/assets/js/about.js"></script>';
        if ($page == 'places') echo '<script src="/My-Web-Hotel/client/assets/js/places.js"></script>';
        if ($page == 'gallery') echo '<script src="/My-Web-Hotel/client/assets/js/gallery.js"></script>';
        if ($page == 'room-detail') echo '<script src="/My-Web-Hotel/client/assets/js/room-detail.js"></script>';
        if ($page == 'service-booking') echo '<script src="/My-Web-Hotel/client/assets/js/service-booking.js?v=' . time() . '"></script>';
    ?>
 </body>

 </html>