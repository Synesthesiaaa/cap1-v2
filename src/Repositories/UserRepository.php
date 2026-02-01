<?php

namespace Repositories;

use Database\Connection;
use Database\QueryBuilder;

/**
 * User Repository
 * 
 * Handles all database operations for users
 */
class UserRepository
{
    private $conn;
    private $table = 'tbl_user';

    public function __construct()
    {
        $this->conn = Connection::getInstance()->getConnection();
    }

    /**
     * Find user by ID
     */
    public function findById(int $id): ?array
    {
        $builder = new QueryBuilder($this->conn, $this->table);
        return $builder->where('user_id', $id)->first();
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array
    {
        $builder = new QueryBuilder($this->conn, $this->table);
        return $builder->where('email', $email)->first();
    }

    /**
     * Create new user
     */
    public function create(array $data): int
    {
        $builder = new QueryBuilder($this->conn, $this->table);
        return $builder->insert($data);
    }

    /**
     * Update user
     */
    public function update(int $id, array $data): bool
    {
        $builder = new QueryBuilder($this->conn, $this->table);
        return $builder->where('user_id', $id)->update($data);
    }

    /**
     * Get users with filters
     */
    public function getUsers(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $builder = new QueryBuilder($this->conn, $this->table);

        // Join with department table
        $builder->leftJoin('tbl_department', 'tbl_user.department_id', '=', 'tbl_department.department_id');

        // Apply filters
        if (isset($filters['user_type'])) {
            $builder->where('tbl_user.user_type', $filters['user_type']);
        }

        if (isset($filters['status'])) {
            $builder->where('tbl_user.status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $builder->where('tbl_user.name', 'LIKE', "%{$search}%")
                    ->orWhere('tbl_user.email', 'LIKE', "%{$search}%")
                    ->orWhere('tbl_user.company', 'LIKE', "%{$search}%");
        }

        // Get total count
        $total = $builder->count();

        // Apply pagination
        $offset = ($page - 1) * $perPage;
        $builder->limit($perPage)->offset($offset);

        // Apply ordering
        $orderBy = $filters['order_by'] ?? 'user_id';
        $orderDir = $filters['order_dir'] ?? 'DESC';
        $builder->orderBy($orderBy, $orderDir);

        $users = $builder->get();

        return [
            'data' => $users,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }
}
