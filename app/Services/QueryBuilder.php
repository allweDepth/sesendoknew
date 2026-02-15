<?php

class QueryBuilder
{
    private string $where = '';
    private array $params = [];
    private string $order = '';
    private string $limit = '';

    public function where(string $condition, array $params = []): self
    {
        $this->where = "WHERE $condition";
        $this->params = $params;
        return $this;
    }

    public function order(string $order): self
    {
        $this->order = "ORDER BY $order";
        return $this;
    }

    public function limit(int $offset, int $limit): self
    {
        $this->limit = "LIMIT $offset, $limit";
        return $this;
    }

    public function build(): array
    {
        return [
            'clause' => trim("{$this->where} {$this->order} {$this->limit}"),
            'params' => $this->params
        ];
    }
}
