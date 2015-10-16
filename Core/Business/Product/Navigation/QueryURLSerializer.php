<?php

namespace PrestaShop\PrestaShop\Core\Business\Product\Navigation;

use PrestaShop\PrestaShop\Core\Foundation\Database\AutoPrefixingDatabase;

class QueryURLSerializer
{
    private $db;

    public function __construct(AutoPrefixingDatabase $db)
    {
        $this->db = $db;
    }

    public function queryToURLFragment(Query $query)
    {

    }

    public function URLFragmentToQuery($fragment)
    {

    }
}
