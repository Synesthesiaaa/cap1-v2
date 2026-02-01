<?php

namespace Repositories;

use Database\Connection;
use Database\QueryBuilder;

/**
 * Ticket Repository
 * 
 * Handles all database operations for tickets
 */
class TicketRepository
{
    private $conn;
    private $table = 'tbl_ticket';

    public function __construct()
    {
        $this->conn = Connection::getInstance()->getConnection();
    }

    /**
     * Create a new ticket
     */
    public function create(array $data): int
    {
        $builder = new QueryBuilder($this->conn, $this->table);
        return $builder->insert($data);
    }

    /**
     * Find ticket by ID
     */
    public function findById(int $id): ?array
    {
        $builder = new QueryBuilder($this->conn, $this->table);
        return $builder->where('ticket_id', $id)->first();
    }

    /**
     * Find ticket by reference ID
     */
    public function findByReference(string $referenceId): ?array
    {
        $builder = new QueryBuilder($this->conn, $this->table);
        return $builder->where('reference_id', $referenceId)->first();
    }

    /**
     * Update ticket
     */
    public function update(int $id, array $data): bool
    {
        $builder = new QueryBuilder($this->conn, $this->table);
        return $builder->where('ticket_id', $id)->update($data);
    }

    /**
     * Get tickets with filters
     */
    public function getTickets(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $builder = new QueryBuilder($this->conn, $this->table);

        // Apply filters
        if (isset($filters['user_id'])) {
            $builder->where('user_id', $filters['user_id']);
        }

        if (isset($filters['status'])) {
            $builder->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $builder->where('priority', $filters['priority']);
        }

        if (isset($filters['assigned_technician_id'])) {
            $builder->where('assigned_technician_id', $filters['assigned_technician_id']);
        }

        if (isset($filters['type'])) {
            $builder->where('type', $filters['type']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $builder->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('reference_id', 'LIKE', "%{$search}%");
        }

        // Get total count
        $total = $builder->count();

        // Apply pagination
        $offset = ($page - 1) * $perPage;
        $builder->limit($perPage)->offset($offset);

        // Apply ordering
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'DESC';
        $builder->orderBy($orderBy, $orderDir);

        $tickets = $builder->get();

        return [
            'data' => $tickets,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Delete ticket
     */
    public function delete(int $id): bool
    {
        $builder = new QueryBuilder($this->conn, $this->table);
        return $builder->where('ticket_id', $id)->delete();
    }
}
