<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */
namespace humhub\modules\massuserimport\components;

use humhub\modules\massuserimport\models\ExtendedUserInvite;
use Yii;

/**
 * CsvParser parses a csv file to an array of models.
 *
 * @package humhub.modules.massuserimport.components
 * @since 1.0
 * @author Sebastian Stumpf
 */
class CsvParser
{

    /**
     * Parse a csv file an array of model instances.
     *
     * @param string $csvDelimiter
     *            csv delimiter.
     * @param string $propertyDelimiter
     *            property delimiter. example: user.name will refer to user->name if '.' is property delimiter.
     * @param string $modelClass
     *            the model class will be used to create an instance of the model and to check against its attributes.
     * @param string $csvData
     *            csv data as string.
     * @param string[] $defaultKeyValueMap
     *            the default values used if an attribute name is not declared in the given csv data. ['<name>' => 'value']
     * @param boolean $safe
     *            if true, only the safe attributes are parsed
     * @return array['result' => $modelClass[], 'errors' => string[]] the resulting created models and errors.
     */
    public static function parse($csvDelimiter, $propertyDelimiter, $modelClass, $csvData, $defaultKeyValueMap)
    {
        // explode does not work properly here as we cannot be sure of the systems EOL the file was generated with
        $lines = preg_split("/\\r\\n|\\r|\\n/", $csvData);
        $retVal = array();
        if (sizeof($lines) < 1) {
            return $retVal;
        }
        $keySet = str_getcsv($lines[0], $csvDelimiter);
        
        $modelInstance = new $modelClass();
        $attributes = $modelInstance->safeAttributes();
        
        unset($lines[0]);
        $retVal['result'] = array();
        $retVal['errors'] = array();
        
        foreach ($keySet as $givenKey) {
            if (! in_array($givenKey, $attributes))
                $retVal['errors'][] = ErrorGenerator::generateErrorMessage(Yii::t('MassuserimportModule.base', 'Column named %columnName% does not exist in model %modelClass%.', [
                    '%columnName%' => $givenKey,
                    '%modelClass%' => $modelClass
                ]));
        }
        
        $counter = 1;
        foreach ($lines as $line) {
            $counter ++;
            $model = new $modelClass();
            $values = str_getcsv($line, $csvDelimiter);
            if (empty($line) || trim($line) == false) {
                $retVal['errors'][] = ErrorGenerator::generateErrorMessage(Yii::t('MassuserimportModule.base', 'An empty line was ignored.'), $counter, null, ErrorGenerator::INFO);
                continue;
            }
            if (sizeof($values) != sizeof($keySet)) {
                $retVal['errors'][] = ErrorGenerator::generateErrorMessage(Yii::t('MassuserimportModule.base', 'Number of entries in CSV line not equal to entries in properties line (first line).'), $counter);
                continue;
            }
            // contains all values from the csv lines, associated with their keys, excluding keys that are not present in the model. If a key is not existing in the csv line, it is filled with the given default value.
            $keyValueMap = array_intersect_key(array_merge($defaultKeyValueMap, array_combine($keySet, $values)), array_flip($attributes));
            foreach ($keyValueMap as $key => $value) {
                if (! empty($value)) {
                    $keys = explode($propertyDelimiter, $key);
                    if (sizeof($keys) == 1) {
                        $model->$key = $value;
                    } else {
                        $model->{$keys[0]}->{$keys[1]} = $value;
                    }
                }
            }
            $retVal['result'][] = $model;
        }
        
        return $retVal;
    }
}