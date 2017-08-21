# SqlElasticSearchQueryConverter
Converts AND OR SQL like query to Elastic Search query



`
((("query1 query 2" OR query3) OR query4) AND (query5 OR query6)) AND query7
`

TO

`
{"bool":{"must":[{"bool":{"must":[{"bool":{"should":[{"bool":{"should":[{"match":"query1 query 2"},{"match":"query3"}]}},{"match":"query4"}]}},{"bool":{"should":[{"match":"query5"},{"match":"query6"}]}}]}},{"match":"query7"}]}}s
`


# Installation

```console
$ composer require santik/sql-elasticsearch-query-converter
```
# Usage

Look at examples/example1.php