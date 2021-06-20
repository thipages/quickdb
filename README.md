# quickdb
Quick SQLite and MySql/MariaDB database sql creation builder

### Installation
**composer** require thipages\quickdb

### Dependency
[quicksql](https://github.com/thipages/quicksql)

### QDb class API

```php
    // Creates sql database creation statements
    create($definition, $options=[]):Array<string>
    // Creates insert/update/delete sql statements
    insert($tableName, $keyValues):string 
    update($tableName, $keyValues, $where):string
    delete($tableName, $keyValues, $where):string
```
**Primary keys** are automatically created as `id` field name

**Foreign keys** are automatically indexed

**`$defintion`** is an associative array <tableName,fieldDefinition>
fieldDefinition follows SQLite definition rules but supports shortcuts for indexes and foreign keys
- `#INDEX` or `#UNIQUE` to add an index (unique) to the field,
- `#FK_parentTable` to associate the field to the primary key of its parent table (foreign key)

**`$options`** is an associated array for customization by merging with default
- `primarykey : string` defines the primary key common to all tables, default : `id INTEGER PRIMARY KEY AUTOINCREMENT`
- `prefield : boolean` (default:`false`). If true : all fields are prefixed by table name
- `omnifields : array<string>` defines fields present in all tables, default:
```
// For SQLite
[
    "created_at INTEGER  not null default (strftime('%s','now'))",
    "modified_at INTEGER  not null default (strftime('%s','now'))"
]
// For MySql/MariaDB
[
    "created_at TIMESTAMP  not null default CURRENT_TIMESTAMP",
    "modified_at TIMESTAMP not null default CURRENT_TIMESTAMP ON UPDATE current_timestamp"
                ]
```
Note 1 : if `modified_at` definition is present in omnifields options, it will be automatically updated on `update`

Note 2 : `strftime('%s','now')` stores UTC unixtime (sqlite)
#### Example
```php
$db=new QDb();
$db->create(
    [
        // MySql : VARCHAR(xx) is mandatory for MySql indexation
        // MariaDB : TEXT would be ok
        // SQLite : use TEXT instead - Equivalent to VARCHAR(X)
        'user'=>'name VARCHAR(10) #INDEX', 
        'message'=>[
            'content TEXT',
            'userId INTEGER NOT NULL #FK_user',
            'category TEXT #UNIQUE'
        ]
        ]
);
/*
For Sqlite
Array
(
    [0] => DROP TABLE IF EXISTS user;
    [1] => CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT,name VARCHAR(10),created_at INTEGER not null default (strftime('%s','now')),modified_at INTEGER not null default (str
ftime('%s','now')));
    [2] => DROP INDEX IF EXISTS user_name_idx;
    [3] => CREATE  INDEX user_name_idx ON user (name);
    [4] => DROP TABLE IF EXISTS message;
    [5] => CREATE TABLE message (id INTEGER PRIMARY KEY AUTOINCREMENT,content TEXT,userId INTEGER NOT NULL ,category TEXT,created_at INTEGER not null default (strftime('%s','now')),mod
ified_at INTEGER not null default (strftime('%s','now')),FOREIGN KEY(userId) REFERENCES user(id));
    [6] => DROP INDEX IF EXISTS message_category_idx;
    [7] => CREATE UNIQUE INDEX message_category_idx ON message (category);
    [8] => DROP INDEX IF EXISTS message_userId_idx;
    [9] => CREATE INDEX message_userId_idx ON message (userId);
)

For Mysql/MariaDB - varchar(10) for user name for compatibility
Array
(
    [0] => DROP TABLE IF EXISTS user;
    [1] => CREATE TABLE user (id INTEGER PRIMARY KEY AUTO_INCREMENT,name VARCHAR(10),created_at TIMESTAMP not null default CURRENT_TIMESTAMP,modified_at TIMESTAMP not null default CURRENT_TIMESTAMP ON UPDATE current_timestamp);
    [2] => CREATE  INDEX user_name_idx ON user (name);
    [3] => DROP TABLE IF EXISTS message;
    [4] => CREATE TABLE message (id INTEGER PRIMARY KEY AUTO_INCREMENT,content TEXT,userId INTEGER NOT NULL ,category TEXT,created_at TIMESTAMP not null default CURRENT_TIMESTAMP,modified_at TIMESTAMP not null default CURRENT_TIMESTAMP ON UPDATE current_timestamp,FOREIGN KEY(userId) REFERENCES user(id));
    [5] => CREATE UNIQUE INDEX message_category_idx ON message (category);
    [6] => CREATE INDEX message_userId_idx ON message (userId);
)
*/
```
