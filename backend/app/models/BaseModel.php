<?php
/**
 * BaseModel — thin PDO wrapper shared by all models.
 */

class BaseModel
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = DB::get();
    }

    /**
     * Execute a prepared statement and return all rows.
     */
    protected function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Execute a prepared statement and return only the first row (or null).
     */
    protected function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Execute an INSERT / UPDATE / DELETE and return affected row count.
     */
    protected function execute(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Execute an INSERT and return the last inserted ID.
     */
    protected function insert(string $sql, array $params = []): string
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $this->db->lastInsertId();
    }
}
