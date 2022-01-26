<?php

class CreateMail extends Ruckusing_Migration_Base
{
    public function up()
    {
        $table = $this->create_table('mail', ['id' => false]);
        $table->column('uid', 'integer', ['extra' => 'primary key autoincrement']);
        $table->column('id', 'string', ['limit' => 128, 'null' => false]);
        $table->column('file_id', 'string', ['limit' => 32, 'null' => false]);
        $table->column('fail_code', 'integer', ['null' => false]);
        $table->column('action', 'integer', ['null' => false]);
        $table->column('last_try', 'datetime', ['null' => false]);
        $table->column('account_uid', 'integer', ['null' => false]);
        $table->finish();
    }//up()

    public function down()
    {
        $this->drop_table('mail');
    }//down()
}
