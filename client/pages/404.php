    <style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #96732eff 0%, #5d4a23ff 100%);
    min-height: 100vh;

}

.container-404 {
    text-align: center;
    padding: 20px;
    position: relative;
    z-index: 10;
}

.error-number {
    font-size: 180px;
    font-weight: 900;
    color: #fff;
    text-shadow:
        0 0 20px rgba(255, 255, 255, 0.3),
        0 10px 30px rgba(0, 0, 0, 0.3);
    margin-bottom: 20px;
    animation: float 3s ease-in-out infinite;
    letter-spacing: 10px;
}

@keyframes float {

    0%,
    100% {
        transform: translateY(0px);
    }

    50% {
        transform: translateY(-20px);
    }
}

.error-icon {
    font-size: 120px;
    margin-bottom: 30px;
    animation: rotate 4s linear infinite;
}

@keyframes rotate {
    from {
        transform: rotate(0deg);
    }

    to {
        transform: rotate(360deg);
    }
}

h1 {
    font-size: 48px;
    color: #fff;
    margin-bottom: 20px;
    text-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    animation: fadeInUp 0.8s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

p {
    font-size: 20px;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 40px;
    line-height: 1.6;
    animation: fadeInUp 1s ease-out;
}

.button-group {
    display: flex;
    gap: 20px;
    justify-content: center;
    flex-wrap: wrap;
    animation: fadeInUp 1.2s ease-out;
}

.button-group .btn {
    padding: 15px 40px;
    font-size: 18px;
    font-weight: 600;
    text-decoration: none;
    border-radius: 50px;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
}

.btn-primary {
    border: 1px solid #deb666;
    background: #fff;
    color: #deb666;
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(255, 255, 255, 0.3);
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
    border: 2px solid rgba(255, 255, 255, 0.5);
    backdrop-filter: blur(10px);
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(255, 255, 255, 0.2);
}

.suggestion-box {
    margin-top: 50px;
    padding: 30px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    animation: fadeInUp 1.4s ease-out;
}

.suggestion-box h3 {
    color: #fff;
    font-size: 24px;
    margin-bottom: 20px;
}

.links {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.links a {
    color: #fff;
    text-decoration: none;
    padding: 10px 20px;
    border-radius: 25px;
    background: rgba(255, 255, 255, 0.15);
    transition: all 0.3s ease;
    font-size: 16px;
}

.links a:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: scale(1.05);
}

/* Hiệu ứng nền */
.bg-animation {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    z-index: 1;
}

.circle {
    position: absolute;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.1);
    animation: rise 15s infinite ease-in;
}

.circle:nth-child(1) {
    width: 80px;
    height: 80px;
    left: 10%;
    animation-delay: 0s;
}

.circle:nth-child(2) {
    width: 120px;
    height: 120px;
    left: 30%;
    animation-delay: 2s;
}

.circle:nth-child(3) {
    width: 60px;
    height: 60px;
    left: 50%;
    animation-delay: 4s;
}

.circle:nth-child(4) {
    width: 100px;
    height: 100px;
    left: 70%;
    animation-delay: 6s;
}

.circle:nth-child(5) {
    width: 90px;
    height: 90px;
    left: 85%;
    animation-delay: 8s;
}

@keyframes rise {
    0% {
        bottom: -150px;
        opacity: 0;
    }

    50% {
        opacity: 0.5;
    }

    100% {
        bottom: 110%;
        opacity: 0;
    }
}

@media (max-width: 768px) {
    .error-number {
        font-size: 120px;
    }

    h1 {
        font-size: 32px;
    }

    p {
        font-size: 16px;
    }

    .btn {
        padding: 12px 30px;
        font-size: 16px;
    }

    .suggestion-box {
        padding: 20px;
    }
}
    </style>


    <main>
        <!-- Hiệu ứng nền -->
        <div class="bg-animation">
            <div class="circle"></div>
            <div class="circle"></div>
            <div class="circle"></div>
            <div class="circle"></div>
            <div class="circle"></div>
        </div>

        <div class="container-404">
            <div class="error-number">404</div>
            <h1>Rất tiếc! Không tìm thấy trang</h1>
            <p>
                Trang bạn đang tìm kiếm có thể đã bị xóa, đổi tên<br>
                hoặc tạm thời không khả dụng.
            </p>

            <div class="button-group">
                <a href="/My-Web-Hotel/client/index.php" class="btn btn-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    Về Trang Chủ
                </a>
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                    Quay Lại
                </a>
            </div>

            <div class="suggestion-box">
                <h3>Có thể bạn đang tìm kiếm:</h3>
                <div class="links">
                    <a href="/My-Web-Hotel/client/index.php?page=rooms">Đặt Phòng</a>
                    <a href="/My-Web-Hotel/client/index.php?page=services">Dịch Vụ</a>
                    <a href="/My-Web-Hotel/client/index.php?page=about">Giới Thiệu</a>
                    <a href="/My-Web-Hotel/client/index.php?page=contact">Liên Hệ</a>
                </div>
            </div>
        </div>
    </main>
    <script>
// Thêm hiệu ứng hover cho số 404
const errorNumber = document.querySelector('.error-number');
errorNumber.addEventListener('mouseover', function() {
    this.style.transform = 'scale(1.1) rotate(5deg)';
});
errorNumber.addEventListener('mouseout', function() {
    this.style.transform = 'scale(1) rotate(0deg)';
});

// Tự động chuyển về trang chủ sau 30 giây (tùy chọn)
// setTimeout(function() {
//     window.location.href = '/My-Web-Hotel/client/index.php';
// }, 30000);
    </script>