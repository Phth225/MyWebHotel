<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- reset css -->
    <link rel="stylesheet" href="/My-Web-Hotel/client/assets/css/reset.css?v=<?php echo time(); ?>" />
    <!-- embed font -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto+Slab&family=Roboto:ital,wdth,wght@0,75..100,100..900;1,75..100,100..900&display=swap"
        rel="stylesheet" />
    <!--embed aos css -->
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <!-- embed icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css?" />
    <!-- embed css bootstrap -->
    <link href="/My-Web-Hotel/lib/Bootstrap/css/bootstrap.css" rel="stylesheet" />
    <!-- style -->
    <link rel="stylesheet" href="/My-Web-Hotel/client/assets/css/common.css?v=<?php echo time(); ?>">
    <!-- loading style -->
    <link rel="stylesheet" href="/My-Web-Hotel/client/assets/css/loading.css?v=<?php echo time(); ?>">
    <?php
  $current_page = isset($page) ? $page : (isset($_GET['page']) ? $_GET['page'] : 'home');
    if ($current_page == 'home') echo '
    <link rel="stylesheet" href="/My-Web-Hotel/client/assets/css/home.css?v=<?php echo time(); ?>">';
    if ($current_page == 'room') echo '
    <link rel="stylesheet" href="/My-Web-Hotel/client/assets/css/room.css?v=<?php echo time(); ?>">';
    if ($current_page == 'dichVu') echo '
    <link rel="stylesheet" href="/My-Web-Hotel/client/assets/css/dichVu.css?v=<?php echo time(); ?>">';
    if ($current_page == 'spa') echo '
    <link rel="stylesheet" href="/My-Web-Hotel/client/assets/css/spa.css?v=<?php echo time(); ?>">';
    if ($current_page == 'giaiTri') echo '
    <link rel="stylesheet" href="/My-Web-Hotel/client/assets/css/giaiTri.css?v=<?php echo time(); ?>">';
    if ($current_page == 'nhaHang') echo '
    <link rel="stylesheet" href="/My-Web-Hotel/client/assets/css/nhaHang.css?v=<?php echo time(); ?>">';
    if ($current_page == 'suKien') echo '
    <link rel="stylesheet" href="/My-Web-Hotel/client/assets/css/suKien.css?v=<?php echo time(); ?>">';
    if ($current_page == 'blog') echo '
    <link rel="stylesheet" href="/My-Web-Hotel/client/assets/css/blog.css?v=<?php echo time(); ?>">';
    if ($current_page == 'blog-detail') echo '
    <link rel="stylesheet" href="/My-Web-Hotel/client/assets/css/blog-detail.css?v=<?php echo time(); ?>">';
    if ($current_page == 'danhGia') echo '
    <link rel="stylesheet" href="/My-Web-Hotel/client/assets/css/danhGia.css?v=<?php echo time(); ?>">';
    if ($current_page == 'booking') echo '
    <link rel="stylesheet" href="/My-Web-Hotel/client/assets/css/booking.css?v=<?php echo time(); ?>">';
    if ($current_page == 'service-booking') echo '
    <link rel="stylesheet" href="/My-Web-Hotel/client/assets/css/service-booking.css?v=<?php echo time(); ?>">';
    if ($current_page == 'about') echo '
    <link rel="stylesheet" href="/My-Web-Hotel/client/assets/css/about.css?v=<?php echo time(); ?>">';
    if ($current_page == 'places') echo '
    <link rel="stylesheet" href="/My-Web-Hotel/client/assets/css/places.css?v=<?php echo time(); ?>">';
    if ($current_page == 'gallery') echo '
    <link rel="stylesheet" href="/My-Web-Hotel/client/assets/css/gallery.css?v=<?php echo time(); ?>">';
    if ($current_page == 'room-detail') echo '
    <link rel="stylesheet" href="/My-Web-Hotel/client/assets/css/room-detail.css?v=<?php echo time(); ?>">';
    if ($current_page == 'service-detail') echo '
    <link rel="stylesheet" href="/My-Web-Hotel/client/assets/css/service-detail.css?v=<?php echo time(); ?>">';
    ?>
    <link rel="stylesheet" href="/My-Web-Hotel/client/assets/css/header.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/My-Web-Hotel/client/assets/css/footer.css?v=<?php echo time(); ?>">
    <!-- favicon -->
    <link rel="icon" href="/My-Web-Hotel/client/assets/images/favicon.png" type="image/x-icon" />
    <title>OceanPearl Hotel</title>
</head>

<body>
    <?php
        // Kiểm tra trạng thái đăng nhập
        $isLoggedIn = isset($_SESSION['user_id']);
        $username = isset($_SESSION['username']) ? $_SESSION['username'] : null;
    ?>

    <script>
    // Gán biến toàn cục từ PHP sang JavaScript
    window.IS_LOGGED_IN = <?= $isLoggedIn ? 'true' : 'false' ?>;
    window.CURRENT_USER_NAME = <?= $username ? json_encode($username) : 'null' ?>;
    </script>

    <header>
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <nav class="navbar">
                    <div class="container">
                        <a class="navbar-brand" href="/My-Web-Hotel/client/index.php?page=about">
                            <img src="/My-Web-Hotel/client/assets/images/logo.png" alt="Bootstrap" />
                        </a>
                    </div>
                </nav>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false"
                    aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="<?php echo ($current_page=='home') ? 'nav-link active' : 'nav-link'; ?>"
                                href="/My-Web-Hotel/client/index.php?page=home">
                                <span>Trang chủ</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <?php $roomsActive = in_array($current_page, ['room','room-detail', 'booking']) ? 'nav-link active' : 'nav-link'; ?>
                            <a class="<?php echo $roomsActive; ?>" href="/My-Web-Hotel/client/index.php?page=room">
                                <span>Đặt Phòng</span>
                            </a>
                        </li>
                        <li class="nav-item drop">
                            <?php $servicesActive = in_array($current_page, ['dichVu','spa','giaiTri','nhaHang','suKien','service-detail', 'service-booking']) ? 'nav-link active' : 'nav-link'; ?>
                            <a class="<?php echo $servicesActive; ?>" href="/My-Web-Hotel/client/index.php?page=dichVu">
                                <span>Đặt Dịch vụ</span>
                            </a>
                            <div class="services-dropdown">
                                <a href="/My-Web-Hotel/client/index.php?page=spa">Sức khoẻ & Spa</a>
                                <a href="/My-Web-Hotel/client/index.php?page=giaiTri">Giải trí</a>
                                <a href="/My-Web-Hotel/client/index.php?page=nhaHang">Ăn uống</a>
                                <a href="/My-Web-Hotel/client/index.php?page=suKien">Hội nghị & Sự kiện</a>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="<?php echo ($current_page=='blog') ? 'nav-link active' : 'nav-link'; ?>"
                                href="/My-Web-Hotel/client/index.php?page=blog"><span>Blog</span></a>
                        </li>
                        <li class="nav-item">
                            <a class="<?php echo ($current_page=='danhGia') ? 'nav-link active' : 'nav-link'; ?>"
                                href="/My-Web-Hotel/client/index.php?page=danhGia"><span>Đánh giá</span></a>
                        </li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="action">
                            <a href="/My-Web-Hotel/client/pages/profile.php"
                                class="btn btn-info header-btn <?php echo ($current_page=='profile') ? 'active' : ''; ?>"
                                aria-label="Tài khoản">
                                <div class="icon">
                                    <i class="fa-solid fa-user fa-sm"></i>
                                </div>
                                <span><?php echo htmlspecialchars($_SESSION['username'] ?? 'Tài khoản'); ?></span>
                            </a>
                        </li>
                        <?php else: ?>
                        <li class="action">
                            <a href="/My-Web-Hotel/client/pages/logIn.php" class="btn btn-info header-btn"
                                aria-label="Đăng nhập">
                                <div class="icon">
                                    <i class="fa-solid fa-right-to-bracket fa-sm"></i>
                                </div>
                                <span>Đăng nhập</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>