// Initialize CKEditor for Blog
let blogEditorInstance;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Blog Editor
    const blogEditorElement = document.querySelector("#blogEditor");
    if (blogEditorElement) {
        // Tránh HTML5 validate trên textarea ẩn (CKEditor sẽ quản lý nội dung)
        blogEditorElement.removeAttribute('required');

        ClassicEditor.create(blogEditorElement, {
            toolbar: {
                items: [
                    "heading", "|",
                    "bold", "italic", "link",
                    "bulletedList", "numberedList", "|",
                    "outdent", "indent", "|",
                    "imageUpload", "blockQuote", "insertTable", "mediaEmbed", "|",
                    "undo", "redo"
                ],
            },
            language: "vi",
            image: {
                toolbar: ["imageTextAlternative", "imageStyle:inline", "imageStyle:block", "imageStyle:side", "linkImage"],
            },
            table: {
                contentToolbar: ["tableColumn", "tableRow", "mergeTableCells"],
            },
            mediaEmbed: {
                previewsInData: true,
            },
        })
            .then((editor) => {
                blogEditorInstance = editor;
            })
            .catch((error) => {
            });
    }

    // Setup drag and drop
    setupDragAndDrop();
});

// Preview image
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview) return;
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.style.display = "block";

            // Reset trạng thái xóa và hiện lại nút X (blog thumbnail)
            const removeThumbInput = document.getElementById('removeBlogThumbnail');
            if (removeThumbInput) {
                removeThumbInput.value = '0'; // Reset về 0 vì đã chọn ảnh mới
            }
            const removeThumbBtn = document.getElementById('blogImageRemoveBtn');
            if (removeThumbBtn) {
                removeThumbBtn.style.display = 'inline-block'; // Hiện lại nút X
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Save blog - submit form thông thường
function saveBlog() {
    try {
        const form = document.querySelector('#addBlogModal form');
        if (!form) {
            alert('Không tìm thấy form');
            return;
        }

        const title = form.querySelector('input[name="title"]').value;
        const content = blogEditorInstance ? blogEditorInstance.getData() : form.querySelector('textarea[name="content"]').value;

        if (!title || !content) {
            alert('Vui lòng nhập tiêu đề và nội dung');
            return;
        }

        // Set content từ CKEditor vào textarea trước khi submit
        if (blogEditorInstance) {
            const contentTextarea = form.querySelector('textarea[name="content"]');
            contentTextarea.value = content;
        }

        // Submit form thông thường
        form.submit();
    } catch (error) {
        alert('Lỗi khi lưu bài viết: ' + error.message);
    }
}

// Edit blog - redirect để load dữ liệu
function editBlog(id) {
    try {
        window.location.href = 'index.php?page=blogs-manager&action=edit&id=' + id;
    } catch (error) {
    }
}

// Delete blog - submit form thông thường
function deleteBlog(id) {
    if (confirm('Bạn có chắc chắn muốn xóa bài viết này?')) {
        try {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'index.php?page=blogs-manager';
            form.innerHTML = `
                <input type="hidden" name="blog_id" value="${id}">
                <input type="hidden" name="delete_blog" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        } catch (error) {
            alert('Lỗi khi xóa bài viết');
        }
    }
}

// Load blog preview - hiển thị dữ liệu từ PHP
function loadBlogPreview(blogId) {
    // Dữ liệu đã được render sẵn trong modal từ PHP
    // Không cần AJAX
    try {
        const modal = new bootstrap.Modal(document.getElementById('viewBlogModal' + blogId));
        modal.show();
    } catch (error) {
    }
}

// Helper functions
function formatDateDisplay(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return day + '/' + month + '/' + year;
}

function escapeHtml(text) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Setup drag and drop
function setupDragAndDrop() {
    const uploadArea = document.querySelector(".image-upload-area");
    if (!uploadArea) return;

    ["dragenter", "dragover", "dragleave", "drop"].forEach((eventName) => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ["dragenter", "dragover"].forEach((eventName) => {
        uploadArea.addEventListener(eventName, () => {
            uploadArea.style.borderColor = "#d4b896";
            uploadArea.style.background = "#f8f9fa";
        }, false);
    });

    ["dragleave", "drop"].forEach((eventName) => {
        uploadArea.addEventListener(eventName, () => {
            uploadArea.style.borderColor = "#ddd";
            uploadArea.style.background = "";
        }, false);
    });

    uploadArea.addEventListener("drop", handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;

        if (files.length > 0) {
            const fileInput = document.getElementById("blogImage");
            if (fileInput) {
                fileInput.files = files;
                previewImage(fileInput, "blogPreview");
            }
        }
    }
}

// Xóa ảnh đại diện blog (thumbnail)
function clearBlogImage(button) {
    const preview = document.getElementById('blogPreview');
    const removeInput = document.getElementById('removeBlogThumbnail');
    const fileInput = document.getElementById('blogImage');
    const removeBtn = document.getElementById('blogImageRemoveBtn');
    
    // Ẩn ảnh preview
    if (preview) {
        preview.src = '';
        preview.style.display = 'none';
    }
    
    // Ẩn nút X
    if (removeBtn) {
        removeBtn.style.display = 'none';
    }
    
    // Đánh dấu là đã xóa để backend xử lý
    if (removeInput) {
        removeInput.value = '1';
    }
    
    // Reset input file để có thể chọn lại
    if (fileInput) {
        fileInput.value = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const addBlogBtn = document.querySelector('[data-bs-target="#addBlogModal"]');
    if (addBlogBtn) {
        addBlogBtn.addEventListener('click', function() {
            const form = document.querySelector('#addBlogModal form');
            if (form) {
                form.reset();
                if (blogEditorInstance) {
                    blogEditorInstance.setData('');
                }
            }
        });
    }

    // Xử lý khi đóng modal - quay lại URL cũ
    const addBlogModal = document.getElementById('addBlogModal');
    if (addBlogModal) {
        addBlogModal.addEventListener('hide.bs.modal', function() {
            // Nếu đang edit, quay lại URL danh sách (xóa action=edit)
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('action') === 'edit') {
                // Xóa action=edit khỏi URL
                urlParams.delete('action');
                urlParams.delete('id');
                
                // Tạo URL mới
                let newUrl = 'index.php?page=blogs-manager';
                const search = urlParams.get('search');
                const status = urlParams.get('status');
                const category = urlParams.get('category');
                const pageNum = urlParams.get('pageNum');
                
                if (search) newUrl += '&search=' + encodeURIComponent(search);
                if (status) newUrl += '&status=' + encodeURIComponent(status);
                if (category) newUrl += '&category=' + encodeURIComponent(category);
                if (pageNum) newUrl += '&pageNum=' + pageNum;
                
                // Redirect (không dùng history.back vì có thể user từ trang khác)
                window.location.href = newUrl;
            }
        });
    }
});