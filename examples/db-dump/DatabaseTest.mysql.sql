create database if not exists todos;
use todos;
create table if not exists task(
    `id` int unsigned primary key auto_increment,
    `title` varchar(255) not null default '',
    `description` text not null default '',
    `created` int unsigned not null default 0,
    `updated` int unsigned not null default 0
);