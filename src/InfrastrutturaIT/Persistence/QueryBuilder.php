<?php
declare(strict_types=1);

namespace FratellanzaMilitare\InfrastrutturaIT\Persistence;

class QueryBuilder
{
    private array $select = ['*'];
    private ?string $table = null;
    private array $joins = [];
    private array $where = [];
    private array $bindings = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $orderBy = [];
    private array $groupBy = [];

    public function select(array|string $columns): self
    {
        $this->select = is_array($columns) ? $columns : [$columns];
        return $this;
    }

    public function from(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    public function join(string $table, string $condition, string $type = 'INNER'): self
    {
        $this->joins[] = "$type JOIN $table ON $condition";
        return $this;
    }

    public function leftJoin(string $table, string $condition): self
    {
        return $this->join($table, $condition, 'LEFT');
    }

    public function where(string $column, string $operator, mixed $value): self
    {
        $this->where[] = "$column $operator ?";
        $this->bindings[] = $value;
        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        $this->where[] = "$column IN ($placeholders)";
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->where[] = "$column IS NULL";
        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->where[] = "$column IS NOT NULL";
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = "$column $direction";
        return $this;
    }

    public function groupBy(string|array $columns): self
    {
        $this->groupBy = is_array($columns) ? $columns : [$columns];
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function pagination(int $page, int $perPage = 50): self
    {
        $this->limit = $perPage;
        $this->offset = ($page - 1) * $perPage;
        return $this;
    }

    public function toSql(): string
    {
        if (!$this->table) {
            throw new \RuntimeException("Table not specified");
        }

        $sql = "SELECT " . implode(', ', $this->select);
        $sql .= " FROM {$this->table}";

        if (!empty($this->joins)) {
            $sql .= " " . implode(' ', $this->joins);
        }

        if (!empty($this->where)) {
            $sql .= " WHERE " . implode(' AND ', $this->where);
        }

        if (!empty($this->groupBy)) {
            $sql .= " GROUP BY " . implode(', ', $this->groupBy);
        }

        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . implode(', ', $this->orderBy);
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    public function execute(\PDO $pdo): array
    {
        $stmt = $pdo->prepare($this->toSql());
        $stmt->execute($this->getBindings());
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
