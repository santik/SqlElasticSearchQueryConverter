<?php

namespace Santik\SqlElasticSearchQueryConverter;

use Ramsey\Uuid\Uuid;

class SqlElasticSearchQueryConverter
{
    private $operators = ['AND', 'OR'];
    private $parts = [];

    private function __construct(){}


    public static function convert(string $query, string $field):string
    {
        $convert = new SqlElasticSearchQueryConverter();
        return $convert->parse($query, $field);
    }


    public function parse(string $query, string $field): string
    {
        $this->checkQueryNumberOfParentheses($query);

        $parsedQuery = $this->proccessOperators(
            $this->parseParentheses($query)
        );

        //todo make single query less ugly
        if (!is_array($parsedQuery)) {
            $parsedQuery = ['OR' => [$parsedQuery, $parsedQuery]];
        }

        return json_encode($this->makeEsQuery($parsedQuery, $field));
    }


    private function makeEsQuery(array $parsedSQLQuery, string $field): array
    {
        $parts = [];
        foreach ($parsedSQLQuery as $operator => $subqueries) {
            foreach ($subqueries as $subquery) {
                if (is_array($subquery)) {
                    $parts[] = $this->makeEsQuery($subquery, $field);
                } else {
                    $parts[] = [$this->getMatchType($subquery) => [$field => $this->cleanKeyword($subquery)]];
                }
            }

            if ($operator == 'AND') {
                $esOperator = 'must';
            } else {
                $esOperator = 'should';
            }

            return ['bool'=> [$esOperator => $parts]];

        }
    }


    private function parseParentheses(string $query):string
    {
        $pattern = '/\((?:[^()]+|\(?R\))*\)/';

        preg_match_all($pattern, $query, $groups);
        if (!count($groups) || (count($groups) == 1 && empty($groups[0]))) {
            return $query;
        }
        foreach ($groups[0] as $group) {
            $id = Uuid::uuid4()->toString();
            $this->parts[$id] = $group;
            $query = str_replace($group, $id, $query);
        }

        return $this->parseParentheses($query);
    }


    private function checkQueryNumberOfParentheses(string $query)
    {
        $openParenthesesNumber = substr_count($query, '(');
        $closeParenthesesNumber = substr_count($query, ')');

        if ($openParenthesesNumber != $closeParenthesesNumber) {
            throw new \Exception('Parentheses are incorrect');
        }
    }


    private function proccessOperators(string $parsedQuery)
    {
        $operatedString = $this->extractOperatedString($parsedQuery);
        $hasOperator = false;
        $queryStructured = [];

        // supports only 1 operator in single parenthesis
        // like "option1 OR option2"
        // and not "option1 OR option2 AND option3"
        foreach ($this->operators as $operator) {
            $stringParts = explode($operator, $operatedString);
            if (count($stringParts) > 1) {
                $queryStructured[$operator] = $stringParts;
                $hasOperator = true;
                break;
            }
        }

        if (!$hasOperator) {
            return $operatedString;
        }

        foreach ($queryStructured[$operator] as $i => $part) {
            $processed = $this->proccessOperators(trim($part));
            $queryStructured[$operator][$i] = $processed;
        }

        return $queryStructured;
    }


    private function extractOperatedString(string $parsedQuery): string
    {
        $parsedQuery = trim($parsedQuery);

        if (isset($this->parts[$parsedQuery])) {
            $parsedQuery = trim($this->parts[$parsedQuery], '()');
            return $this->extractOperatedString($parsedQuery);
        }

        return $parsedQuery;
    }

    private function isExactMatchSearch($string)
    {
        return substr($string, 0, 1) == '"' && substr($string, -1) == '"';
    }

    private function getMatchType($keyword)
    {
        if ($this->isExactMatchSearch($keyword)) {
            return "match_phrase";
        }
        return "match";
    }

    private function cleanKeyword($keyword)
    {
        if ($this->isExactMatchSearch($keyword)) {
            return trim(trim($keyword, '"'));
        }
        return trim($keyword);
    }
}
