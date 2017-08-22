# SqlElasticSearchQueryConverter
Converts AND OR SQL like query to Elastic Search query



`
((("query1 query 2" OR query3) OR query4) AND (query5 OR query6)) AND query7
`

TO

`
{"bool":{"must":[{"bool":{"must":[{"bool":{"should":[{"bool":{"should":[{"match_phrase":{"field":"query1 query 2"}},{"match":{"field":"query3"}}]}},{"match":{"field":"query4"}}]}},{"bool":{"should":[{"match":{"field":"query5"}},{"match":{"field":"query6"}}]}}]}},{"match":{"field":"query7"}}]}}
`


# Installation

```console
$ composer require santik/sql-elasticsearch-query-converter
```
# Usage

Look at examples/example1.php