<?php

class User extends Ruckusing_Migration_Base
{
    public function up()
    {
        $table = $this->create_table('user', ['id' => false]);
        $table->column('uid', 'integer', ['extra' => 'primary key autoincrement']);
        $table->column('id', 'string', ['limit' => 64, 'null' => false, 'extra' => 'collate nocase']);
        $table->column('name', 'string', ['limit' => 64, 'extra' => 'collate nocase']);
        $table->column('email', 'string', ['limit' => 128, 'null' => false, 'extra' => 'collate nocase']);
        $table->finish();

        $this->add_index('user', 'id', 'unique');
    }//up()

    public function down()
    {
        $this->drop_table('user');
    }//down()
}
