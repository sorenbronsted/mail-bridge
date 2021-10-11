<?php

class Member extends Ruckusing_Migration_Base
{
    public function up()
    {
        $table = $this->create_table('member', ['id' => false]);
        $table->column('uid', 'integer', ['extra' => 'primary key autoincrement']);
        $table->column('room_uid', 'integer', ['null' => false]);
        $table->column('user_uid', 'integer', ['null' => false]);
        $table->finish();

        $this->execute('create unique index member_idx on member(room_uid, user_uid)');
    }//up()

    public function down()
    {
        $this->drop_table('member');
    }//down()
}
