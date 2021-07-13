
create table if not exists user (
    uid integer primary key autoincrement,
    id var(32),
    name varchar(64),
    email varchar(64)
);

create table if not exists room (
    uid integer primary key autoincrement,
    id varchar(32),
    creator_uid int,
    name varchar(32),
    alias varchar(32)
);

create table if not exists member (
    uid integer primary key autoincrement,
    room_uid int,
    user_uid int
);
