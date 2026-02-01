<?php

namespace Tests\Unit\Database;

use PHPUnit\Framework\TestCase;
use Database\QueryBuilder;

/**
 * QueryBuilder Unit Tests
 */
class QueryBuilderTest extends TestCase
{
    /**
     * Test WHERE clause building
     */
    public function testWhereClause(): void
    {
        // Mock connection
        $conn = $this->createMock(\mysqli::class);
        
        $builder = new QueryBuilder($conn, 'tbl_ticket');
        $builder->where('status', 'pending');
        
        // Use reflection to access private method
        $reflection = new \ReflectionClass($builder);
        $method = $reflection->getMethod('buildSelectQuery');
        $method->setAccessible(true);
        
        $sql = $method->invoke($builder);
        
        $this->assertStringContainsString('WHERE', $sql);
        $this->assertStringContainsString('status', $sql);
    }

    /**
     * Test WHERE IN clause
     */
    public function testWhereInClause(): void
    {
        $conn = $this->createMock(\mysqli::class);
        
        $builder = new QueryBuilder($conn, 'tbl_ticket');
        $builder->whereIn('status', ['pending', 'assigned']);
        
        $reflection = new \ReflectionClass($builder);
        $method = $reflection->getMethod('buildSelectQuery');
        $method->setAccessible(true);
        
        $sql = $method->invoke($builder);
        
        $this->assertStringContainsString('WHERE', $sql);
        $this->assertStringContainsString('IN', $sql);
    }

    /**
     * Test ORDER BY clause
     */
    public function testOrderByClause(): void
    {
        $conn = $this->createMock(\mysqli::class);
        
        $builder = new QueryBuilder($conn, 'tbl_ticket');
        $builder->orderBy('created_at', 'DESC');
        
        $reflection = new \ReflectionClass($builder);
        $method = $reflection->getMethod('buildSelectQuery');
        $method->setAccessible(true);
        
        $sql = $method->invoke($builder);
        
        $this->assertStringContainsString('ORDER BY', $sql);
        $this->assertStringContainsString('created_at DESC', $sql);
    }

    /**
     * Test LIMIT clause
     */
    public function testLimitClause(): void
    {
        $conn = $this->createMock(\mysqli::class);
        
        $builder = new QueryBuilder($conn, 'tbl_ticket');
        $builder->limit(10);
        
        $reflection = new \ReflectionClass($builder);
        $method = $reflection->getMethod('buildSelectQuery');
        $method->setAccessible(true);
        
        $sql = $method->invoke($builder);
        
        $this->assertStringContainsString('LIMIT 10', $sql);
    }
}
