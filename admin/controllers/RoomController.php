<?php
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/RoomModel.php';
require_once __DIR__ . '/../models/RoomTypeModel.php';

/**
 * Room Controller
 */
class RoomController extends BaseController
{

    public function index()
    {
        if (!$this->checkAccessSection('room-manager')) {
            $this->redirect('index.php?page=403');
            return;
        }

        $panel = $_GET['panel'] ?? 'room-panel';

        if ($panel === 'room-panel') {
            $this->roomPanel();
        } elseif ($panel === 'roomType-panel') {
            $this->roomTypePanel();
        } else {
            $this->redirect('index.php?page=room-manager&panel=room-panel');
        }
    }

    private function roomPanel()
    {
        $roomModel = new RoomModel($this->mysqli);
        $roomTypeModel = new RoomTypeModel($this->mysqli);


        // Handle POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['add_room'])) {
                $this->handleAddRoom($roomModel);
            } elseif (isset($_POST['update_room'])) {
                $this->handleUpdateRoom($roomModel);
            } elseif (isset($_POST['delete_room'])) {
                $this->handleDeleteRoom($roomModel);
            }
        }

        // Get data
        $rooms = $roomModel->getRoomsWithType('', 'room_id ASC');
        $roomTypes = $roomTypeModel->getAll('deleted IS NULL', 'room_type_name ASC');

        $editRoom = null;
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
            $editRoom = $roomModel->getById($_GET['id']);
        }

        $data = [
            'rooms' => $rooms,
            'roomTypes' => $roomTypes,
            'editRoom' => $editRoom,
            'canCreate' => $this->checkPermission('room.create'),
            'canEdit' => $this->checkPermission('room.edit'),
            'canDelete' => $this->checkPermission('room.delete')
        ];

        // For now, use old page structure until views are fully migrated
        // Make $mysqli available in included file scope
        $mysqli = $this->mysqli;
        $panel = 'room-panel';
        include __DIR__ . '/../pages/room-manager.php';
    }

    private function roomTypePanel()
    {
        $roomTypeModel = new RoomTypeModel($this->mysqli);

        // Handle POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['add_room_type'])) {
                $this->handleAddRoomType($roomTypeModel);
            } elseif (isset($_POST['update_room_type'])) {
                $this->handleUpdateRoomType($roomTypeModel);
            } elseif (isset($_POST['delete_room_type'])) {
                $this->handleDeleteRoomType($roomTypeModel);
            }
        }

        // Get data
        $roomTypes = $roomTypeModel->getRoomTypesWithCount('', 'room_type_id ASC');

        $editRoomType = null;
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
            $editRoomType = $roomTypeModel->getById($_GET['id']);
        }

        $data = [
            'roomTypes' => $roomTypes,
            'editRoomType' => $editRoomType,
            'canCreate' => $this->checkPermission('roomType.create'),
            'canEdit' => $this->checkPermission('roomType.edit'),
            'canDelete' => $this->checkPermission('roomType.delete')
        ];

        // For now, use old page structure until views are fully migrated
        // Make $mysqli available in included file scope
        $mysqli = $this->mysqli;
        $panel = 'roomType-panel';
        include __DIR__ . '/../pages/room-manager.php';
    }

    private function handleAddRoom($model)
    {
        if (!$this->checkPermission('room.create')) {
            $_SESSION['message'] = 'Bạn không có quyền tạo phòng';
            $_SESSION['messageType'] = 'danger';
            $this->redirect('index.php?page=room-manager&panel=room-panel');
            return;
        }

        $data = [
            'room_number' => $_POST['room_number'],
            'room_type_id' => intval($_POST['room_type_id']),
            'floor' => intval($_POST['floor'] ?? 1),
            'status' => $_POST['status'] ?? 'Available',
            'description' => $_POST['description'] ?? ''
        ];

        if ($model->create($data)) {
            $_SESSION['message'] = 'Thêm phòng thành công';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Lỗi khi thêm phòng';
            $_SESSION['messageType'] = 'danger';
        }

        $this->redirect('index.php?page=room-manager&panel=room-panel');
    }

    private function handleUpdateRoom($model)
    {
        if (!$this->checkPermission('room.edit')) {
            $_SESSION['message'] = 'Bạn không có quyền sửa phòng';
            $_SESSION['messageType'] = 'danger';
            $this->redirect('index.php?page=room-manager&panel=room-panel');
            return;
        }

        $id = intval($_POST['room_id']);
        $data = [
            'room_number' => $_POST['room_number'],
            'room_type_id' => intval($_POST['room_type_id']),
            'floor' => intval($_POST['floor'] ?? 1),
            'status' => $_POST['status'],
            'description' => $_POST['description'] ?? ''
        ];

        if ($model->update($id, $data)) {
            $_SESSION['message'] = 'Cập nhật phòng thành công';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Lỗi khi cập nhật phòng';
            $_SESSION['messageType'] = 'danger';
        }

        $this->redirect('index.php?page=room-manager&panel=room-panel');
    }

    private function handleDeleteRoom($model)
    {
        if (!$this->checkPermission('room.delete')) {
            $_SESSION['message'] = 'Bạn không có quyền xóa phòng';
            $_SESSION['messageType'] = 'danger';
            $this->redirect('index.php?page=room-manager&panel=room-panel');
            return;
        }

        $id = intval($_POST['room_id']);
        if ($model->delete($id)) {
            $_SESSION['message'] = 'Xóa phòng thành công';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Lỗi khi xóa phòng';
            $_SESSION['messageType'] = 'danger';
        }

        $this->redirect('index.php?page=room-manager&panel=room-panel');
    }

    private function handleAddRoomType($model)
    {
        if (!$this->checkPermission('roomType.create')) {
            $_SESSION['message'] = 'Bạn không có quyền tạo loại phòng';
            $_SESSION['messageType'] = 'danger';
            $this->redirect('index.php?page=room-manager&panel=roomType-panel');
            return;
        }

        $data = [
            'room_type_name' => $_POST['room_type_name'],
            'base_price' => floatval($_POST['base_price']),
            'capacity' => intval($_POST['capacity'] ?? 2),
            'description' => $_POST['description'] ?? '',
            'amenities' => $_POST['amenities'] ?? '',
            'status' => $_POST['status'] ?? 'active',
            'area' => floatval($_POST['area'] ?? 0)
        ];

        $roomTypeId = $model->create($data);
        if ($roomTypeId) {

            if (isset($_FILES['room_type_images']) && $this->hasRoomTypeImages($_FILES['room_type_images'])) {
                $this->uploadRoomTypeImages($roomTypeId, $_FILES['room_type_images']);
            }

            $_SESSION['message'] = 'Thêm loại phòng thành công';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Lỗi khi thêm loại phòng';
            $_SESSION['messageType'] = 'danger';
        }

        $this->redirect('index.php?page=room-manager&panel=roomType-panel');
    }

    private function handleUpdateRoomType($model)
    {
        if (!$this->checkPermission('roomType.edit')) {
            $_SESSION['message'] = 'Bạn không có quyền sửa loại phòng';
            $_SESSION['messageType'] = 'danger';
            $this->redirect('index.php?page=room-manager&panel=roomType-panel');
            return;
        }

        $id = intval($_POST['room_type_id']);
        $data = [
            'room_type_name' => $_POST['room_type_name'],
            'base_price' => floatval($_POST['base_price']),
            'capacity' => intval($_POST['capacity'] ?? 2),
            'description' => $_POST['description'] ?? '',
            'amenities' => $_POST['amenities'] ?? '',
            'status' => $_POST['status'] ?? 'active',
            'area' => floatval($_POST['area'] ?? 0)
        ];

        if ($model->update($id, $data)) {
            if (isset($_FILES['room_type_images']) && $this->hasRoomTypeImages($_FILES['room_type_images'])) {
                $this->uploadRoomTypeImages($id, $_FILES['room_type_images']);
            }

            $_SESSION['message'] = 'Cập nhật loại phòng thành công';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Lỗi khi cập nhật loại phòng';
            $_SESSION['messageType'] = 'danger';
        }

        $this->redirect('index.php?page=room-manager&panel=roomType-panel');
    }

    private function handleDeleteRoomType($model)
    {
        if (!$this->checkPermission('roomType.delete')) {
            $_SESSION['message'] = 'Bạn không có quyền xóa loại phòng';
            $_SESSION['messageType'] = 'danger';
            $this->redirect('index.php?page=room-manager&panel=roomType-panel');
            return;
        }

        $id = intval($_POST['room_type_id']);
        if ($model->delete($id)) {
            $_SESSION['message'] = 'Xóa loại phòng thành công';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Lỗi khi xóa loại phòng';
            $_SESSION['messageType'] = 'danger';
        }

        $this->redirect('index.php?page=room-manager&panel=roomType-panel');
    }
    private function hasRoomTypeImages($files)
    {
        if (!isset($files['name'])) {
            return false;
        }

        if (is_array($files['name'])) {
            foreach ($files['name'] as $name) {
                if (!empty($name)) {
                    return true;
                }
            }
            return false;
        }

        return !empty($files['name']);
    }

    private function uploadRoomTypeImages($roomTypeId, $files, $maxImages = 6)
    {
        if (!$roomTypeId || !$this->hasRoomTypeImages($files)) {
            return;
        }

        require_once __DIR__ . '/../includes/cloudinary_helper.php';

        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        $statsStmt = $this->mysqli->prepare("SELECT COUNT(*) AS total, COALESCE(MAX(display_order), -1) AS max_order FROM roomtype_images WHERE room_type_id = ?");
        $statsStmt->bind_param("i", $roomTypeId);
        $statsStmt->execute();
        $stats = $statsStmt->get_result()->fetch_assoc();
        $statsStmt->close();

        $existingCount = (int)($stats['total'] ?? 0);
        $nextOrder = ((int)($stats['max_order'] ?? -1)) + 1;
        $remainingSlots = max(0, $maxImages - $existingCount);

        if ($remainingSlots <= 0) {
            return;
        }

        $isMultiple = is_array($files['name']);
        $fileCount = $isMultiple ? count($files['name']) : 1;
        $uploaded = 0;

        for ($i = 0; $i < $fileCount && $uploaded < $remainingSlots; $i++) {
            $name = $isMultiple ? $files['name'][$i] : $files['name'];
            $type = $isMultiple ? $files['type'][$i] : $files['type'];
            $tmp  = $isMultiple ? $files['tmp_name'][$i] : $files['tmp_name'];
            $error = $isMultiple ? $files['error'][$i] : $files['error'];
            $size  = $isMultiple ? $files['size'][$i] : $files['size'];

            if ($error !== UPLOAD_ERR_OK || empty($name)) {
                continue;
            }

            if (!in_array($type, $allowedTypes) || $size > $maxSize) {
                continue;
            }

            $url = CloudinaryHelper::upload($tmp, 'room-type');
            if ($url === false) {
                error_log("Cloudinary upload failed for room type {$roomTypeId}, file {$name}");
                continue;
            }

            $displayOrder = $nextOrder++;
            $isPrimary = ($existingCount === 0 && $uploaded === 0) ? 1 : 0;

            $insertStmt = $this->mysqli->prepare("INSERT INTO roomtype_images (room_type_id, image_url, display_order, is_primary) VALUES (?, ?, ?, ?)");
            $insertStmt->bind_param("isii", $roomTypeId, $url, $displayOrder, $isPrimary);
            $insertStmt->execute();
            $insertStmt->close();

            $uploaded++;
        }
    }
}
