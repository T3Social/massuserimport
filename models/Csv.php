<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2015 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */
namespace humhub\modules\massuserimport\models;

use yii\base\Model;

/**
 * Container for a csv file and an array of errors.
 * No corresponding table in the database.
 *
 * @package humhub.modules.massuserimport.models
 * @since 1.0
 * @author Sebastian Stumpf
 */
class Csv extends Model
{

    public $csvFile;

    public $errors = array();

    public function rules()
    {
        return [
            // username and password are both required
            [
                [
                    'csvFile'
                ],
                'required'
            ]
        ];
    }

    /**
     *
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        $extendedLabels = [
            'csvFile' => 'Upload CSV data'
        ];
        return array_merge(parent::attributeLabels(), $extendedLabels);
    }
}