<?php

namespace Database;

/**
 * Query Builder
 * 
 * Provides a fluent interface for building SQL queries
 */
class QueryBuilder
{
    private $conn;
    private $table;
    private $select = '*';
    private $where = [];
    private $whereParams = [];
    private $whereTypes = '';
    private $join = [];
    private $orderBy = [];
    private $groupBy = [];
    private $having = [];
    private $limit = null;
    private $offset = null;

    public function __construct($connection, string $table)
    {
        $this->conn = $connection;
        $this->table = $table;
    }

    /**
     * Set columns to select
     */
    public function select(string $columns = '*'): self
    {
        $this->select = $columns;
        return $this;
    }

    /**
     * Add WHERE condition
     */
    public function where(string $column, $operator, $value = null, string $type = 'AND'): self
    {
        // Support where('column', 'value') syntax
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $placeholder = '?';
        $this->where[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'type' => count($this->where) === 0 ? '' : $type
        ];

        // Determine parameter type
        if (is_int($value)) {
            $this->whereTypes .= 'i';
        } elseif (is_float($value)) {
            $this->whereTypes .= 'd';
        } else {
            $this->whereTypes .= 's';
        }

        $this->whereParams[] = $value;

        return $this;
    }

    /**
     * Add OR WHERE condition
     */
    public function orWhere(string $column, $operator, $value = null): self
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * Add WHERE IN condition
     */
    public function whereIn(string $column, array $values, string $type = 'AND'): self
    {
        $placeholders = str_repeat('?,', count($values) - 1) . '?';
        $this->where[] = [
            'column' => $column,
            'operator' => 'IN',
            'value' => $values,
            'type' => count($this->where) === 0 ? '' : $type,
            'in' => true
        ];

        foreach ($values as $value) {
            if (is_int($value)) {
                $this->whereTypes .= 'i';
            } elseif (is_float($value)) {
                $this->whereTypes .= 'd';
            } else {
                $this->whereTypes .= 's';
            }
            $this->whereParams[] = $value;
        }

        return $this;
    }

    /**
     * Add JOIN clause
     */
    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): self
    {
        $this->join[] = [
            'type' => $type,
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];
        return $this;
    }

    /**
     * Add LEFT JOIN
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * Add ORDER BY clause
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = "{$column} {$direction}";
        return $this;
    }

    /**
     * Add GROUP BY clause
     */
    public function groupBy(string $column): self
    {
        $this->groupBy[] = $column;
        return $this;
    }

    /**
     * Add HAVING clause
     */
    public function having(string $condition): self
    {
        $this->having[] = $condition;
        return $this;
    }

    /**
     * Set LIMIT
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set OFFSET
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Build and execute SELECT query
     */
    public function get(): array
    {
        $sql = $this->buildSelectQuery();
        return $this->execute($sql);
    }

    /**
     * Get first result
     */
    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * Get count
     */
    public function count(): int
    {
        $originalSelect = $this->select;
        $this->select = 'COUNT(*) as count';
        $result = $this->first();
        $this->select = $originalSelect;
        return (int)($result['count'] ?? 0);
    }

    /**
     * Build SELECT query string
     */
    private function buildSelectQuery(): string
    {
        $sql = "SELECT {$this->select} FROM {$this->table}";

        // Add JOINs
        foreach ($this->join as $join) {
            $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
        }

        // Add WHERE clauses
        if (!empty($this->where)) {
            $sql .= " WHERE ";
            $whereParts = [];
            foreach ($this->where as $where) {
                $part = $where['type'] ? " {$where['type']} " : '';
                
                if (isset($where['in']) && $where['in']) {
                    $placeholders = str_repeat('?,', count($where['value']) - 1) . '?';
                    $part .= "{$where['column']} IN ({$placeholders})";
                } else {
                    $part .= "{$where['column']} {$where['operator']} ?";
                }
                
                $whereParts[] = $part;
            }
            $sql .= implode('', $whereParts);
        }

        // Add GROUP BY
        if (!empty($this->groupBy)) {
            $sql .= " GROUP BY " . implode(', ', $this->groupBy);
        }

        // Add HAVING
        if (!empty($this->having)) {
            $sql .= " HAVING " . implode(' AND ', $this->having);
        }

        // Add ORDER BY
        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . implode(', ', $this->orderBy);
        }

        // Add LIMIT
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        // Add OFFSET
        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    /**
     * Execute query and return results
     */
    private function execute(string $sql): array
    {
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            throw new \Exception("Query preparation failed: " . $this->conn->error);
        }

        if (!empty($this->whereParams)) {
            $stmt->bind_param($this->whereTypes, ...$this->whereParams);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        
        $stmt->close();
        return $rows;
    }

    /**
     * Insert data
     */
    public function insert(array $data): int
    {
        $columns = array_keys($data);
        $values = array_values($data);
        $placeholders = str_repeat('?,', count($values) - 1) . '?';
        
        $types = '';
        foreach ($values as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES ({$placeholders})";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            throw new \Exception("Insert preparation failed: " . $this->conn->error);
        }

        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $insertId = $this->conn->insert_id;
        $stmt->close();

        return $insertId;
    }

    /**
     * Update data
     */
    public function update(array $data): bool
    {
        $columns = array_keys($data);
        $values = array_values($data);
        $setClause = [];
        
        $types = '';
        foreach ($columns as $index => $column) {
            $value = $values[$index];
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $setClause[] = $column . " = ?";
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause);

        // Add WHERE clauses
        if (!empty($this->where)) {
            $sql .= " WHERE ";
            $whereParts = [];
            foreach ($this->where as $where) {
                $part = $where['type'] ? " {$where['type']} " : '';
                $part .= "{$where['column']} {$where['operator']} ?";
                $whereParts[] = $part;
            }
            $sql .= implode('', $whereParts);
            $types .= $this->whereTypes;
            $values = array_merge($values, $this->whereParams);
        }

        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            throw new \Exception("Update preparation failed: " . $this->conn->error);
        }

        $stmt->bind_param($types, ...$values);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    /**
     * Delete records
     */
    public function delete(): bool
    {
        $sql = "DELETE FROM {$this->table}";

        // Add WHERE clauses
        if (!empty($this->where)) {
            $sql .= " WHERE ";
            $whereParts = [];
            foreach ($this->where as $where) {
                $part = $where['type'] ? " {$where['type']} " : '';
                $part .= "{$where['column']} {$where['operator']} ?";
                $whereParts[] = $part;
            }
            $sql .= implode('', $whereParts);
        } else {
            throw new \Exception("Delete query requires WHERE clause for safety");
        }

        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            throw new \Exception("Delete preparation failed: " . $this->conn->error);
        }

        if (!empty($this->whereParams)) {
            $stmt->bind_param($this->whereTypes, ...$this->whereParams);
        }

        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }
}
