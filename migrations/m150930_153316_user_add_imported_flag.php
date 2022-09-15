<?php

use yii\db\Schema;
use yii\db\Migration;

class m150930_153316_user_add_imported_flag extends Migration
{
    public function up()
    {
        $this->addColumn('user', 'imported', 'int(1)');
        $this->update('user', ['imported' => 0]);
    }

    public function down()
    {
        echo "m150930_153316_user_add_imported_flag cannot be reverted.\n";

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
