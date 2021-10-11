<?php

class Account extends Ruckusing_Migration_Base
{
    public function up()
    {
        $table = $this->create_table('account', ['id' => false]);
        $table->column('uid', 'integer', ['extra' => 'primary key autoincrement']);
        $table->column('name', 'string', ['limit' => 32, 'null' => true]);
        $table->column('data', 'text', ['null' => true]);
        $table->column('user_uid', 'integer', ['null' => false]);
        $table->finish();

        $this->add_index('account', 'user_uid');
    }//up()

    public function down()
    {
        $this->drop_table('account');
    }//down()
}
