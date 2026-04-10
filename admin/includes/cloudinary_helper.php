<?php

/**
 * Cloudinary Helper Class
 * 
 * File này cung cấp các hàm helper để upload ảnh lên Cloudinary
 * 
 * Yêu cầu:
 * 1. Cài đặt Cloudinary PHP SDK: composer require cloudinary/cloudinary_php
 * 2. Cấu hình Cloudinary credentials trong file config hoặc biến môi trường
 */




// Load .env file nếu có - PHẢI load TRƯỚC khi require Cloudinary SDK
// if (file_exists(__DIR__ . '/../../.env')) {
//     $envFile = __DIR__ . '/../../.env';
//     $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
//     foreach ($lines as $line) {
//         $line = trim($line);
//         // Skip empty lines and comments
//         if (empty($line) || strpos($line, '#') === 0) {
//             continue;
//         }
        
//         // Parse key=value
//         if (strpos($line, '=') !== false) {
//             list($key, $value) = explode('=', $line, 2);
//             $key = trim($key);
//             $value = trim($value);
            
//             // Remove quotes if present
//             if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
//                 (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
//                 $value = substr($value, 1, -1);
//             }
            
//             if (!empty($key)) {
//                 $_ENV[$key] = $value;
//                 $_SERVER[$key] = $value; // Cloudinary SDK có thể đọc từ $_SERVER
//                 putenv("$key=$value");
//             }
//         }
//     }
// }

// Uncomment sau khi cài đặt Cloudinary SDK
require_once __DIR__ . '/../../vendor/autoload.php';

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;

class CloudinaryHelper
{
    private static $cloudinary = null;
    private static $envLoaded = false;

    /**
     * Đọc biến môi trường từ .env và cloudinary_config.php (nếu có)
     * Giúp tránh lỗi "Invalid configuration" khi chưa nạp ENV ở nơi khác.
     */
    private static function loadEnv()
    {
        if (self::$envLoaded) {
            return;
        }

        // 1) .env ở thư mục gốc
        $envPath = __DIR__ . '/../../.env';
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
                    continue;
                }
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                if ($key !== '') {
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }

        // 2) cloudinary_config.php nếu tồn tại
        $configFile = __DIR__ . '/cloudinary_config.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
            if (!empty($config['cloud_name'])) {
                $_ENV['CLOUDINARY_CLOUD_NAME'] = $config['cloud_name'];
                $_SERVER['CLOUDINARY_CLOUD_NAME'] = $config['cloud_name'];
                putenv('CLOUDINARY_CLOUD_NAME=' . $config['cloud_name']);
            }
            if (!empty($config['api_key'])) {
                $_ENV['CLOUDINARY_API_KEY'] = $config['api_key'];
                $_SERVER['CLOUDINARY_API_KEY'] = $config['api_key'];
                putenv('CLOUDINARY_API_KEY=' . $config['api_key']);
            }
            if (!empty($config['api_secret'])) {
                $_ENV['CLOUDINARY_API_SECRET'] = $config['api_secret'];
                $_SERVER['CLOUDINARY_API_SECRET'] = $config['api_secret'];
                putenv('CLOUDINARY_API_SECRET=' . $config['api_secret']);
            }
        }

        // Tự build CLOUDINARY_URL nếu chưa có
        if (empty(getenv('CLOUDINARY_URL'))) {
            $cloudName = getenv('CLOUDINARY_CLOUD_NAME');
            $apiKey = getenv('CLOUDINARY_API_KEY');
            $apiSecret = getenv('CLOUDINARY_API_SECRET');
            if ($cloudName && $apiKey && $apiSecret) {
                $url = "cloudinary://{$apiKey}:{$apiSecret}@{$cloudName}";
                putenv('CLOUDINARY_URL=' . $url);
                $_ENV['CLOUDINARY_URL'] = $url;
                $_SERVER['CLOUDINARY_URL'] = $url;
            }
        }

        self::$envLoaded = true;
    }

    /**
     * Khởi tạo Cloudinary client
     * Cần cấu hình credentials trước khi sử dụng
     */
    private static function init()
    {
        self::loadEnv(); // bảo đảm ENV đã sẵn sàng

        if (self::$cloudinary === null) {
            // Thử lấy từ CLOUDINARY_URL trước (format: cloudinary://api_key:api_secret@cloud_name)
            $cloudinaryUrl = self::getConfig('CLOUDINARY_URL');
            
            if (!empty($cloudinaryUrl)) {
                // Sử dụng CLOUDINARY_URL nếu có
                try {
                    // Set biến môi trường cho Cloudinary SDK
                    putenv('CLOUDINARY_URL=' . $cloudinaryUrl);
                    $_ENV['CLOUDINARY_URL'] = $cloudinaryUrl;
                    $_SERVER['CLOUDINARY_URL'] = $cloudinaryUrl;
                    
                    // Cloudinary SDK sẽ tự động đọc từ biến môi trường
                    self::$cloudinary = new Cloudinary();
                    return self::$cloudinary;
                } catch (Exception $e) {
                    error_log("Cloudinary URL config error: " . $e->getMessage());
                }
            }
            
            // Nếu không có CLOUDINARY_URL, thử lấy từng biến riêng
            $cloudName = self::getConfig('CLOUDINARY_CLOUD_NAME');
            $apiKey = self::getConfig('CLOUDINARY_API_KEY');
            $apiSecret = self::getConfig('CLOUDINARY_API_SECRET');
            
            // Debug: Log để kiểm tra
            error_log("Cloudinary Config - Cloud Name: " . ($cloudName ? 'SET (' . strlen($cloudName) . ' chars)' : 'NOT SET'));
            error_log("Cloudinary Config - API Key: " . ($apiKey ? 'SET (' . strlen($apiKey) . ' chars)' : 'NOT SET'));
            error_log("Cloudinary Config - API Secret: " . ($apiSecret ? 'SET (' . strlen($apiSecret) . ' chars)' : 'NOT SET'));
            
            if (empty($cloudName) || empty($apiKey) || empty($apiSecret)) {
                $errorMsg = 'Cloudinary credentials chưa được cấu hình. ';
                $errorMsg .= 'Vui lòng kiểm tra file .env hoặc cloudinary_config.php. ';
                $errorMsg .= 'Cloud Name: ' . ($cloudName ? 'OK' : 'MISSING') . ', ';
                $errorMsg .= 'API Key: ' . ($apiKey ? 'OK' : 'MISSING') . ', ';
                $errorMsg .= 'API Secret: ' . ($apiSecret ? 'OK' : 'MISSING');
                $errorMsg .= '<br>Hoặc sử dụng CLOUDINARY_URL trong file .env';
                error_log($errorMsg);
                throw new Exception($errorMsg);
            }

            // Cấu hình Cloudinary - thử nhiều cách
            try {
                // Cách 1: Tạo CLOUDINARY_URL và set vào biến môi trường (Cloudinary SDK tự động đọc)
                $cloudinaryUrl = "cloudinary://{$apiKey}:{$apiSecret}@{$cloudName}";
                putenv('CLOUDINARY_URL=' . $cloudinaryUrl);
                $_ENV['CLOUDINARY_URL'] = $cloudinaryUrl;
                $_SERVER['CLOUDINARY_URL'] = $cloudinaryUrl;
                
                // Cloudinary SDK sẽ tự động đọc từ biến môi trường CLOUDINARY_URL
                self::$cloudinary = new Cloudinary();
                
            } catch (Exception $e) {
                // Thử cách 2: Cấu hình trực tiếp bằng Configuration::instance()
                try {
                    Configuration::instance([
                        'cloud' => [
                            'cloud_name' => $cloudName,
                            'api_key' => $apiKey,
                            'api_secret' => $apiSecret
                        ],
                        'url' => [
                            'secure' => true
                        ]
                    ]);

                    self::$cloudinary = new Cloudinary();
                    
                } catch (Exception $e2) {
                    error_log("Cloudinary Configuration Error (Method 1 - URL): " . $e->getMessage());
                    error_log("Cloudinary Configuration Error (Method 2 - Direct): " . $e2->getMessage());
                    error_log("Stack trace: " . $e2->getTraceAsString());
                    throw new Exception("Lỗi cấu hình Cloudinary. Method 1: " . $e->getMessage() . " | Method 2: " . $e2->getMessage());
                }
            }
        }
        return self::$cloudinary;
    }
    
    /**
     * Lấy config từ biến môi trường hoặc file config
     * 
     * @param string $key Tên key cần lấy
     * @return string|null Giá trị config hoặc null nếu không tìm thấy
     */
    private static function getConfig($key)
    {
        // Ưu tiên lấy từ biến môi trường
        if (isset($_ENV[$key]) && !empty($_ENV[$key])) {
            return $_ENV[$key];
        }
        
        // Thử lấy từ getenv()
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }
        
        // Thử lấy từ file config (nếu có)
        $configFile = __DIR__ . '/cloudinary_config.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
            $configKey = strtolower(str_replace('CLOUDINARY_', '', $key));
            if (isset($config[$configKey])) {
                return $config[$configKey];
            }
        }
        
        return null;
    }

    /**
     * Upload ảnh lên Cloudinary
     * 
     * @param string $filePath Đường dẫn file tạm thời (tmp_name)
     * @param string $folder Folder trên Cloudinary (staff, room, blog, voucher, service, room-type)
     * @param array $options Các tùy chọn bổ sung (transformation, tags, etc.)
     * @return string|false URL của ảnh trên Cloudinary hoặc false nếu lỗi
     */
    public static function upload($filePath, $folder = 'general', $options = [])
    {
        try {
            // Kiểm tra file có tồn tại không
            if (!file_exists($filePath)) {
                error_log("Cloudinary upload error: File not found - $filePath");
                return false;
            }
            
            $cloudinary = self::init();
            
            $defaultOptions = [
                'folder' => $folder,
                'resource_type' => 'image',
                'use_filename' => true,
                'unique_filename' => true,
                'overwrite' => false
            ];
            
            $uploadOptions = array_merge($defaultOptions, $options);
            
            $result = $cloudinary->uploadApi()->upload($filePath, $uploadOptions);
            
            if (isset($result['secure_url'])) {
                return $result['secure_url'];
            } elseif (isset($result['url'])) {
                return $result['url'];
            } else {
                error_log("Cloudinary upload error: No URL in response - " . json_encode($result));
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Cloudinary upload error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Upload nhiều ảnh cùng lúc
     * 
     * @param array $files Mảng các file paths
     * @param string $folder Folder trên Cloudinary
     * @param array $options Các tùy chọn bổ sung
     * @return array Mảng các URLs đã upload thành công
     */
    public static function uploadMultiple($files, $folder = 'general', $options = [])
    {
        $uploadedUrls = [];

        foreach ($files as $filePath) {
            $url = self::upload($filePath, $folder, $options);
            if ($url !== false) {
                $uploadedUrls[] = $url;
            }
        }

        return $uploadedUrls;
    }

    /**
     * Xóa ảnh trên Cloudinary
     * 
     * @param string $publicId Public ID của ảnh trên Cloudinary
     * @return bool True nếu xóa thành công
     */
    public static function delete($publicId)
    {
        try {
            $cloudinary = self::init();
            $result = $cloudinary->uploadApi()->destroy($publicId);
            return isset($result['result']) && $result['result'] === 'ok';
        } catch (Exception $e) {
            error_log("Cloudinary delete error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lấy Public ID từ URL Cloudinary
     * 
     * @param string $url URL của ảnh trên Cloudinary
     * @return string|false Public ID hoặc false nếu không phải URL Cloudinary
     */
    public static function getPublicIdFromUrl($url)
    {
        // Parse URL để lấy public_id
        // Format: https://res.cloudinary.com/{cloud_name}/image/upload/{folder}/{public_id}.{ext}
        if (preg_match('/\/upload\/(?:v\d+\/)?(.+?)(?:\.[^.]+)?$/', $url, $matches)) {
            return $matches[1];
        }
        return false;
    }

    /**
     * Xóa ảnh từ URL Cloudinary
     * 
     * @param string $url URL của ảnh trên Cloudinary
     * @return bool True nếu xóa thành công
     */
    public static function deleteByUrl($url)
    {
        $publicId = self::getPublicIdFromUrl($url);
        if ($publicId) {
            return self::delete($publicId);
        }
        return false;
    }

    /**
     * Transform ảnh (resize, crop, etc.)
     * 
     * @param string $publicId Public ID của ảnh
     * @param array $transformations Các transformation options
     * @return string|false URL của ảnh đã transform
     */
    public static function transform($publicId, $transformations = [])
    {
        try {
            $cloudinary = self::init();
            $url = $cloudinary->image($publicId)->toUrl($transformations);
            return $url;
        } catch (Exception $e) {
            error_log("Cloudinary transform error: " . $e->getMessage());
            return false;
        }
    }
}
