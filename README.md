# quickdb
Quick SQLite database sql creation builder

### Installation
**composer** require thipages\quickdb

### Dependency
[quicksql](https://github.com/thipages/quicksql)

### QDb class API

```php
    // Creates sql database creation statements
    create($definition, $options=[]):Array<string>
    // From quicksql, creates insert/update/delete sql statements
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
[
    "created_at INTEGER  not null default (strftime('%s','now'))",
    "modified_at INTEGER  not null default (strftime('%s','now'))"
]
```
Note 1 : if `modified_at` definition is present in omnifields options, it will be automatically updated on `update`

Note 2 : `strftime('%s','now')` stores UTC unixtime
#### Example
```php
$db=new QDb();
$db->create(
    [
        'user'=>'name TEXT #INDEX',
        'message'=>[
            'content TEXT',
            'date INTEGER',
            'uniqueField INTEGER #UNIQUE',
            'userId INTEGER NOT NULL #FK_user'
        ]
    ]
);
/*
Array
(
    [0] => DROP TABLE IF EXISTS user;
    [1] => CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT,created_at INTEGER not null default (strftime('%s','now')),modified_at INTEGER not null default (strftime('%s','now')));
    [2] => DROP INDEX IF EXISTS user_name_idx;
    [3] => CREATE  INDEX user_name_idx ON user (name);
    [4] => DROP TABLE IF EXISTS message;
    [5] => CREATE TABLE message (id INTEGER PRIMARY KEY AUTOINCREMENT,content TEXT,date INTEGER,userId INTEGER NOT NULL ,uniqueField INTEGER,created_at INTEGER not null default (strftime('%s','now')),modified_at INTEGER not null default (strftime('%s','now')),FOREIGN KEY(userId) REFERENCES user(id));
    [6] => DROP INDEX IF EXISTS message_uniqueField_idx;
    [7] => CREATE UNIQUE INDEX message_uniqueField_idx ON message (uniqueField);
    [8] => DROP INDEX IF EXISTS message_userId_idx;
    [9] => CREATE INDEX message_userId_idx ON message (userId);

)
*/
```
