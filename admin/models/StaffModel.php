<?php
require_once __DIR__ . '/../core/BaseModel.php';

/**
 * Staff Model
 */
class StaffModel extends BaseModel {
    protected $table = 'nhan_vien';
    protected $primaryKey = 'id_nhan_vien';
    
    /**
     * Get staff with role info
     */
    public function getStaffWithRole($where = '', $orderBy = 'id_nhan_vien DESC', $limit = '') {
        $query = "
            SELECT nv.*, cv.ten_chuc_vu
            FROM {$this->table} nv
            LEFT JOIN chuc_vu cv ON nv.chuc_vu = cv.ma_chuc_vu
            WHERE nv.deleted IS NULL
        ";
        
        if ($where) {
            $query .= " AND {$where}";
        }
        
        if ($orderBy) {
            $query .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $query .= " LIMIT {$limit}";
        }
        
        $result = $this->mysqli->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    /**
     * Get staff permissions (role + individual)
     */
    public function getStaffPermissions($staff_id) {
        // Get role-based permissions
        $role_stmt = $this->mysqli->prepare("
            SELECT qcv.ten_quyen
            FROM nhan_vien nv
            JOIN chuc_vu cv ON nv.chuc_vu = cv.ma_chuc_vu
            JOIN quyen_chuc_vu qcv ON cv.ma_chuc_vu = qcv.ma_chuc_vu
            WHERE nv.id_nhan_vien = ?
        ");
        $role_stmt->bind_param("i", $staff_id);
        $role_stmt->execute();
        $role_result = $role_stmt->get_result();
        $permissions = [];
        while ($row = $role_result->fetch_assoc()) {
            $permissions[] = $row['ten_quyen'];
        }
        $role_stmt->close();
        
        // Get individual permissions
        $ind_stmt = $this->mysqli->prepare("
            SELECT ten_quyen FROM quyen_nhan_vien WHERE id_nhan_vien = ?
        ");
        $ind_stmt->bind_param("i", $staff_id);
        $ind_stmt->execute();
        $ind_result = $ind_stmt->get_result();
        while ($row = $ind_result->fetch_assoc()) {
            $permissions[] = $row['ten_quyen'];
        }
        $ind_stmt->close();
        
        return array_unique($permissions);
    }
}









