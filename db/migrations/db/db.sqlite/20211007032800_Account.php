<?php

class Account extends Ruckusing_Migration_Base
{
    public function up()
    {
        $table = $this->create_table('account', ['id' => false]);
        $table->column('uid', 'integer', ['extra' => 'primary key autoincrement']);
        $table->column('name', 'string', ['limit' => 32, 'null' => true]);
        $table->column('data', 'text', ['null' => true]);
        $table->column('updated', 'datetime', ['null' => true]);
        $table->column('state', 'integer', ['null' => false]);
        $table->column('state_text', 'string', ['limit' => 32, 'null' => true]);
        $table->column('user_id', 'string', ['limit' => 64, 'null' => false]);
        $table->finish();

        $this->add_index('account', 'user_id');
    }//up()

    public function down()
    {
        $this->drop_table('account');
    }//down()
}
