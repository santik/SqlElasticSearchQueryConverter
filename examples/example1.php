<?php

use Santik\SqlElasticSearchQueryConverter\SqlElasticSearchQueryConverter;

include_once dirname(__FILE__) . "/../vendor/autoload.php";

$sqlQuery = '((("query1 query 2" OR query3) OR query4) AND (query5 OR query6)) AND query7';

$esQuery = SqlElasticSearchQueryConverter::convert($sqlQuery, 'field');

print_r($esQuery);