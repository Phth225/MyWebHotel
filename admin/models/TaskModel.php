<?php
require_once __DIR__ . '/../core/BaseModel.php';

/**
 * Task Model
 */
class TaskModel extends BaseModel {
    protected $table = 'task';
    protected $primaryKey = 'task_id';
    
    /**
     * Get tasks with staff info
     */
    public function getTasksWithStaff($where = '', $orderBy = 'task_id DESC', $limit = '') {
        $query = "
            SELECT t.*,
                   nv.ho_ten as assigned_to_name,
                   nv2.ho_ten as created_by_name
            FROM {$this->table} t
            LEFT JOIN nhan_vien nv ON t.assigned_to = nv.id_nhan_vien
            LEFT JOIN nhan_vien nv2 ON t.created_by = nv2.id_nhan_vien
            WHERE t.deleted IS NULL
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
     * Get tasks for a specific staff member
     */
    public function getTasksForStaff($staff_id, $where = '', $orderBy = 'task_id DESC', $limit = '') {
        $where_clause = "assigned_to = {$staff_id}";
        if ($where) {
            $where_clause .= " AND {$where}";
        }
        return $this->getTasksWithStaff($where_clause, $orderBy, $limit);
    }
}









