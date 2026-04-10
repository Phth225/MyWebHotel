<?php
require_once __DIR__ . '/../core/BaseController.php';
require_once __DIR__ . '/../models/TaskModel.php';
require_once __DIR__ . '/../models/StaffModel.php';

/**
 * Task Controller
 */
class TaskController extends BaseController {
    
    public function index() {
        if (!$this->checkPermission('task.view')) {
            $this->redirect('index.php?page=403');
            return;
        }
        
        $taskModel = new TaskModel($this->mysqli);
        $staffModel = new StaffModel($this->mysqli);
        
        // Handle POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['add_task'])) {
                $this->handleAdd($taskModel);
            } elseif (isset($_POST['update_task'])) {
                $this->handleUpdate($taskModel);
            } elseif (isset($_POST['delete_task'])) {
                $this->handleDelete($taskModel);
            }
        }
        
        // Get data
        $tasks = $taskModel->getTasksWithStaff('', 'task_id DESC');
        $staffs = $staffModel->getStaffWithRole('trang_thai = "Active"', 'ho_ten ASC');
        
        $editTask = null;
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
            $editTask = $taskModel->getById($_GET['id']);
        }
        
        $data = [
            'tasks' => $tasks,
            'staffs' => $staffs,
            'editTask' => $editTask,
            'currentStaffId' => $this->getCurrentStaffId(),
            'canCreate' => $this->checkPermission('task.create'),
            'canEdit' => $this->checkPermission('task.edit'),
            'canDelete' => $this->checkPermission('task.delete'),
            'canViewDetail' => $this->checkPermission('task.view_detail')
        ];
        
        // For now, use old page structure until views are fully migrated
        // Make $mysqli available in included file scope
        $mysqli = $this->mysqli;
        $panel = 'task-manager';
        include __DIR__ . '/../pages/staff-manager.php';
    }
    
    public function myTasks() {
        if (!$this->checkPermission('task.view')) {
            $this->redirect('index.php?page=403');
            return;
        }
        
        $taskModel = new TaskModel($this->mysqli);
        $staff_id = $this->getCurrentStaffId();
        
        // Handle POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_task_progress'])) {
            $this->handleUpdateProgress($taskModel);
        }
        
        // Filter
        $status = $_GET['status'] ?? '';
        $where = "assigned_to = {$staff_id}";
        if ($status) {
            $where .= " AND status = '{$status}'";
        }
        
        // Pagination
        $page = isset($_GET['page_num']) ? intval($_GET['page_num']) : 1;
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        
        $tasks = $taskModel->getTasksForStaff($staff_id, $status ? "status = '{$status}'" : '', 'task_id DESC', "{$offset}, {$perPage}");
        $total = $taskModel->count("assigned_to = {$staff_id}" . ($status ? " AND status = '{$status}'" : ''));
        $totalPages = ceil($total / $perPage);
        
        $data = [
            'tasks' => $tasks,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'status' => $status
        ];
        
        // For now, use old page structure until views are fully migrated
        // Make $mysqli available in included file scope
        $mysqli = $this->mysqli;
        include __DIR__ . '/../pages/my-tasks.php';
    }
    
    private function handleAdd($model) {
        if (!$this->checkPermission('task.create')) {
            $_SESSION['message'] = 'Bạn không có quyền tạo nhiệm vụ';
            $_SESSION['messageType'] = 'danger';
            $this->redirect('index.php?page=task-manager');
            return;
        }
        
        $data = [
            'title' => $_POST['title'],
            'description' => $_POST['description'] ?? '',
            'assigned_to' => intval($_POST['assigned_to']),
            'priority' => $_POST['priority'] ?? 'Medium',
            'due_date' => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
            'status' => $_POST['status'] ?? 'Pending',
            'created_by' => $this->getCurrentStaffId(),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        if ($model->create($data)) {
            $_SESSION['message'] = 'Thêm nhiệm vụ thành công';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Lỗi khi thêm nhiệm vụ';
            $_SESSION['messageType'] = 'danger';
        }
        
        $this->redirect('index.php?page=task-manager');
    }
    
    private function handleUpdate($model) {
        if (!$this->checkPermission('task.edit')) {
            $_SESSION['message'] = 'Bạn không có quyền sửa nhiệm vụ';
            $_SESSION['messageType'] = 'danger';
            $this->redirect('index.php?page=task-manager');
            return;
        }
        
        $id = intval($_POST['task_id']);
        $data = [
            'title' => $_POST['title'],
            'description' => $_POST['description'] ?? '',
            'assigned_to' => intval($_POST['assigned_to']),
            'priority' => $_POST['priority'],
            'due_date' => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
            'status' => $_POST['status']
        ];
        
        if ($model->update($id, $data)) {
            $_SESSION['message'] = 'Cập nhật nhiệm vụ thành công';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Lỗi khi cập nhật nhiệm vụ';
            $_SESSION['messageType'] = 'danger';
        }
        
        $this->redirect('index.php?page=task-manager');
    }
    
    private function handleDelete($model) {
        if (!$this->checkPermission('task.delete')) {
            $_SESSION['message'] = 'Bạn không có quyền xóa nhiệm vụ';
            $_SESSION['messageType'] = 'danger';
            $this->redirect('index.php?page=task-manager');
            return;
        }
        
        $id = intval($_POST['task_id']);
        if ($model->delete($id)) {
            $_SESSION['message'] = 'Xóa nhiệm vụ thành công';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Lỗi khi xóa nhiệm vụ';
            $_SESSION['messageType'] = 'danger';
        }
        
        $this->redirect('index.php?page=task-manager');
    }
    
    private function handleUpdateProgress($model) {
        $id = intval($_POST['task_id']);
        $data = [
            'progress' => intval($_POST['progress']),
            'status' => $_POST['status'] ?? 'In Progress',
            'notes' => $_POST['notes'] ?? ''
        ];
        
        if ($model->update($id, $data)) {
            $_SESSION['message'] = 'Cập nhật tiến độ thành công';
            $_SESSION['messageType'] = 'success';
        } else {
            $_SESSION['message'] = 'Lỗi khi cập nhật tiến độ';
            $_SESSION['messageType'] = 'danger';
        }
        
        $this->redirect('index.php?page=my-tasks');
    }
}

