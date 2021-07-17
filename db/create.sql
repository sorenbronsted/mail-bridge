
create table if not exists user (
    uid integer primary key autoincrement,
    id varchar(32) collate nocase,
    name varchar(64) collate nocase,
    email varchar(64) collate nocase
);

create table if not exists room (
    uid integer primary key autoincrement,
    id varchar(32) collate nocase,
    creator_uid int,
    name varchar(32) collate nocase,
    alias varchar(32) collate nocase
);

create table if not exists member (
    uid integer primary key autoincrement,
    room_uid int,
    user_uid int
);
