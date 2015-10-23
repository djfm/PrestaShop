<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation;

class QueryAssembler
{
    public function assemble(array $queryParts)
    {
        $sql = "SELECT {$queryParts['select']} FROM {$queryParts['from']}";
        if (!empty($queryParts['where'])) {
            $sql .= " WHERE {$queryParts['where']}";
        }
        if (!empty($queryParts['groupBy'])) {
            $sql .= " GROUP BY {$queryParts['groupBy']}";
        }
        if (!empty($queryParts['orderBy'])) {
            $sql .= " ORDER BY {$queryParts['orderBy']}";
        }
        if (!empty($queryParts['limit'])) {
            $sql .= " LIMIT {$queryParts['limit']}";
            if (!empty($queryParts['offset'])) {
                $sql .= " OFFSET {$queryParts['offset']}";
            }
        }
        return $sql;
    }
}
