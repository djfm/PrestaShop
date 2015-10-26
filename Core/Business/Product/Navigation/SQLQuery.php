<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation;

class SQLQuery
{
    private $queryParts;

    public function __construct(array $queryParts = [])
    {
        $this->queryParts = $queryParts;
    }

    public function getSQLString()
    {
        $sql = "SELECT {$this->queryParts['select']} FROM {$this->queryParts['from']}";
        if (!empty($this->queryParts['where'])) {
            $sql .= " WHERE {$this->queryParts['where']}";
        }
        if (!empty($this->queryParts['groupBy'])) {
            $sql .= " GROUP BY {$this->queryParts['groupBy']}";
        }
        if (!empty($this->queryParts['orderBy'])) {
            $sql .= " ORDER BY {$this->queryParts['orderBy']}";
        }
        if (!empty($this->queryParts['limit'])) {
            $sql .= " LIMIT {$this->queryParts['limit']}";
            if (!empty($this->queryParts['offset'])) {
                $sql .= " OFFSET {$this->queryParts['offset']}";
            }
        }

        return $sql;
    }
}
