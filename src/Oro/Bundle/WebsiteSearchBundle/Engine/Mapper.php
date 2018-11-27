<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;

/**
 * It returns mappings for selected fields which
 * are used to create result item objects
 */
class Mapper
{
    /**
     * @param Query $query
     * @param array $item
     * @param array $serviceFields
     * @return array
     */
    public function mapSelectedData(Query $query, array $item, $serviceFields = [])
    {
        $selects = $query->getSelect();
        $selectAliases = $query->getSelectAliases();

        if (empty($selects)) {
            return [];
        }

        $result = [];

        foreach ($selects as $select) {
            list($type, $name) = Criteria::explodeFieldTypeName($select);

            if (isset($selectAliases[$name])) {
                $resultName = $selectAliases[$name];
            } elseif (isset($selectAliases[$select])) {
                $resultName = $selectAliases[$select];
            } else {
                $resultName = $name;
            }

            // Skip service fields
            if (in_array($resultName, $serviceFields, true)) {
                continue;
            }

            $result[$resultName] = '';

            if (isset($item[$name])) {
                $result[$resultName] = $this->parseValue($item[$name], $type);
            }
        }

        return $result;
    }

    /**
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    protected function parseValue($value, $type)
    {
        if (is_array($value)) {
            $value = array_shift($value);
        }

        if (is_numeric($value)) {
            if ($type === Query::TYPE_INTEGER) {
                $value = (int)$value;
            } elseif ($type === Query::TYPE_DECIMAL) {
                $value = (float)$value;
            }
        }

        return $value;
    }
}
