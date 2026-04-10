<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trải Nghiệm Tại Khách Sạn</title>
    <style>
    .gallery-container {
        max-width: 1400px;
        margin: 0 auto 50px;
    }

    .filter-tabs {
        display: flex;
        gap: 30px;
        margin-bottom: 40px;
        flex-wrap: wrap;
    }

    .filter-tab {
        background: none;
        border: none;
        color: #999;
        font-size: 2rem;
        cursor: pointer;
        padding: 10px 0;
        transition: all 0.3s ease;
        position: relative;
        font-weight: 500;
    }

    .filter-tab.active {
        color: #333;
        font-weight: 600;
    }

    .filter-tab.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: #deb666;
    }

    .filter-tab:hover {
        color: #333;
    }

    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 25px;
        transition: all 0.5s ease;
    }

    .gallery-item {
        position: relative;
        height: 350px;
        border-radius: 12px;
        overflow: hidden;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        opacity: 1;
        transform: scale(1);
    }

    .gallery-item.hidden {
        opacity: 0;
        transform: scale(0.8);
        width: 0;
        height: 0;
        margin: 0;
        padding: 0;
        overflow: hidden;
        position: absolute;
        pointer-events: none;
    }

    .gallery-item:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
    }

    .gallery-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .gallery-item:hover img {
        transform: scale(1.1);
    }

    .gallery-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.7) 0%, transparent 100%);
        padding: 30px 20px 20px;
        color: white;
    }

    .gallery-label {
        font-size: 1.3rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .gallery-label::before {
        content: '—';
        font-weight: 300;
    }

    @media (max-width: 768px) {
        .gallery-grid {
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .gallery-item {
            height: 300px;
        }

        .filter-tabs {
            gap: 20px;
        }

        .filter-tab {
            font-size: 1rem;
        }
    }

    @media (max-width: 480px) {
        body {
            padding: 20px 15px;
        }

        .gallery-grid {
            grid-template-columns: 1fr;
        }

        .filter-tabs {
            overflow-x: auto;
            flex-wrap: nowrap;
            padding-bottom: 10px;
        }

        .filter-tab {
            white-space: nowrap;
        }
    }

    /* Lightbox styles */
    .lightbox {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.95);
        z-index: 1000;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .lightbox.active {
        display: flex;
    }

    .lightbox-content {
        max-width: 90%;
        max-height: 90vh;
        position: relative;
    }

    .lightbox-content img {
        width: 100%;
        height: auto;
        max-height: 90vh;
        object-fit: contain;
    }

    .lightbox-close {
        position: absolute;
        top: -40px;
        right: 0;
        background: none;
        border: none;
        color: white;
        font-size: 2rem;
        cursor: pointer;
        padding: 10px;
    }

    .lightbox-title {
        color: white;
        font-size: 1.5rem;
        margin-top: 20px;
        text-align: center;
    }
    </style>
</head>

<body>
    <div class="gallery-container">
        <div class="filter-tabs">
            <button class="filter-tab active" data-filter="all">Tất cả</button>
            <button class="filter-tab" data-filter="bar">Quán bar</button>
            <button class="filter-tab" data-filter="restaurant">Nhà hàng</button>
            <button class="filter-tab" data-filter="room">Phòng</button>
            <button class="filter-tab" data-filter="spa">Spa</button>
            <button class="filter-tab" data-filter="pool">Hồ bơi</button>
        </div>

        <div class="gallery-grid">
            <!-- Swimming Pool -->
            <div class="gallery-item" data-category="pool">
                <img src="https://images.unsplash.com/photo-1575429198097-0414ec08e8cd?w=800" alt="Swimming Pool">
                <div class="gallery-overlay">
                    <div class="gallery-label">Swimming Pool</div>
                </div>
            </div>

            <!-- Room View -->
            <div class="gallery-item" data-category="room">
                <img src="https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800" alt="Room View">
                <div class="gallery-overlay">
                    <div class="gallery-label">Room View</div>
                </div>
            </div>

            <!-- Cocktail/Bar -->
            <div class="gallery-item" data-category="bar">
                <img src="https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?w=800" alt="Cocktail">
                <div class="gallery-overlay">
                    <div class="gallery-label">Cocktail</div>
                </div>
            </div>

            <!-- Breakfast/Restaurant -->
            <div class="gallery-item" data-category="restaurant">
                <img src="https://images.unsplash.com/photo-1533089860892-a7c6f0a88666?w=800" alt="Breakfast">
                <div class="gallery-overlay">
                    <div class="gallery-label">Breakfast</div>
                </div>
            </div>

            <!-- Restaurant -->
            <div class="gallery-item" data-category="restaurant">
                <img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=800" alt="Restaurant">
                <div class="gallery-overlay">
                    <div class="gallery-label">Restaurant</div>
                </div>
            </div>

            <!-- Spa -->
            <div class="gallery-item" data-category="spa">
                <img src="https://images.unsplash.com/photo-1540555700478-4be289fbecef?w=800" alt="Spa">
                <div class="gallery-overlay">
                    <div class="gallery-label">Spa</div>
                </div>
            </div>

            <!-- Pool Bar -->
            <div class="gallery-item" data-category="bar pool">
                <img src="https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=800" alt="Pool Bar">
                <div class="gallery-overlay">
                    <div class="gallery-label">Pool Bar</div>
                </div>
            </div>

            <!-- Fine Dining -->
            <div class="gallery-item" data-category="restaurant">
                <img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800" alt="Fine Dining">
                <div class="gallery-overlay">
                    <div class="gallery-label">Fine Dining</div>
                </div>
            </div>

            <!-- Luxury Room -->
            <div class="gallery-item" data-category="room">
                <img src="https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=800" alt="Luxury Room">
                <div class="gallery-overlay">
                    <div class="gallery-label">Luxury Suite</div>
                </div>
            </div>

            <!-- Rooftop Bar -->
            <div class="gallery-item" data-category="bar">
                <img src="https://images.unsplash.com/photo-1559339352-11d035aa65de?w=800" alt="Rooftop Bar">
                <div class="gallery-overlay">
                    <div class="gallery-label">Rooftop Bar</div>
                </div>
            </div>

            <!-- Spa Treatment -->
            <div class="gallery-item" data-category="spa">
                <img src="https://images.unsplash.com/photo-1544161515-4ab6ce6db874?w=800" alt="Spa Treatment">
                <div class="gallery-overlay">
                    <div class="gallery-label">Spa Treatment</div>
                </div>
            </div>

            <!-- Beach Pool -->
            <div class="gallery-item" data-category="pool">
                <img src="https://images.unsplash.com/photo-1540541338287-41700207dee6?w=800" alt="Beach Pool">
                <div class="gallery-overlay">
                    <div class="gallery-label">Beach Pool</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lightbox -->
    <div class="lightbox" id="lightbox">
        <div class="lightbox-content">
            <button class="lightbox-close" onclick="closeLightbox()">&times;</button>
            <img src="" alt="" id="lightbox-img">
            <div class="lightbox-title" id="lightbox-title"></div>
        </div>
    </div>

    <script>
    // Filter functionality
    const filterTabs = document.querySelectorAll('.filter-tab');
    const galleryItems = document.querySelectorAll('.gallery-item');

    filterTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            filterTabs.forEach(t => t.classList.remove('active'));
            // Add active class to clicked tab
            this.classList.add('active');

            const filter = this.getAttribute('data-filter');

            // Filter gallery items with smooth transition
            galleryItems.forEach((item, index) => {
                const categories = item.getAttribute('data-category');

                if (filter === 'all' || categories.includes(filter)) {
                    // Show item
                    item.classList.remove('hidden');
                    setTimeout(() => {
                        item.style.opacity = '1';
                        item.style.transform = 'scale(1)';
                    }, index * 30);
                } else {
                    // Hide item
                    item.style.opacity = '0';
                    item.style.transform = 'scale(0.8)';
                    setTimeout(() => {
                        item.classList.add('hidden');
                    }, 300);
                }
            });
        });
    });

    // Lightbox functionality
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    const lightboxTitle = document.getElementById('lightbox-title');

    galleryItems.forEach(item => {
        item.addEventListener('click', function() {
            const img = this.querySelector('img');
            const label = this.querySelector('.gallery-label').textContent.trim();

            lightboxImg.src = img.src;
            lightboxTitle.textContent = label;
            lightbox.classList.add('active');
        });
    });

    function closeLightbox() {
        lightbox.classList.remove('active');
    }

    // Close lightbox on background click
    lightbox.addEventListener('click', function(e) {
        if (e.target === lightbox) {
            closeLightbox();
        }
    });

    // Close lightbox on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeLightbox();
        }
    });
    </script>
</body>

</html>