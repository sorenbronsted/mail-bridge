<?php

class Room extends Ruckusing_Migration_Base
{
    public function up()
    {
        $table = $this->create_table('room', ['id' => false]);
        $table->column('uid', 'integer', ['extra' => 'primary key autoincrement']);
        $table->column('id', 'string', ['limit' => 64, 'null' => false, 'extra' => 'collate nocase']);
        $table->column('name', 'string', ['limit' => 32,'null' => false, 'extra' => 'collate nocase']);
        $table->column('alias', 'string', ['limit' => 32, 'extra' => 'collate nocase']);
        $table->column('creator_uid', 'integer', ['null' => false]);
        $table->finish();

        $this->add_index('room', 'id', 'unique');
    }//up()

    public function down()
    {
        $this->drop_table('room');
    }//down()
}
