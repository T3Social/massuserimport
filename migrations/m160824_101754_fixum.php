<?php

use yii\db\Migration;

class m160824_101754_fixum extends Migration
{
    public function up()
    {
        $this->update('contentcontainer', ['class' => \humhub\modules\user\models\User::className()], ['class' => \humhub\modules\massuserimport\models\MassuserimportUser::className()]);
    }

    public function down()
    {
        echo "m160824_101754_fixum cannot be reverted.\n";

        return false;
    }

    /*
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
    */
}
