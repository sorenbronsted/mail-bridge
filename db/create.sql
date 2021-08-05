
create table if not exists user (
    uid integer primary key autoincrement,
    id varchar(32) collate nocase,
    name varchar(64) not null collate nocase,
    email varchar(64) not null collate nocase
);
create unique index if not exists user_id_idx on user (id);

create table if not exists room (
    uid integer primary key autoincrement,
    id varchar(32) collate nocase,
    creator_uid int,
    name varchar(32) not null collate nocase,
    alias varchar(32) collate nocase
);
create unique index if not exists room_id_idx on room (id);

create table if not exists member (
    uid integer primary key autoincrement,
    room_uid int not null,
    user_uid int not null
);
create unique index if not exists member_uniq_idx on member (room_uid, user_uid);

create table if not exists account (
    uid integer primary key autoincrement,
    data text not null,
    user_uid int not null
);
create unique index if not exists account_uniq_idx on account (user_uid);
