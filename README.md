# quickdb
Quick SQLite database sql creation builder

### Installation
**composer** require thipages\quickdb

### Usage of QDb class
through the static method create
```php
    Qdb::create($definition, $options=[]):Array<String>
```
`$defintion` is an associative array <tableName,fieldDefinition>
fieldDefinition follows SQLite definition rules but supports prefixes
- `#INDEX` or `#UNIQUE` for adding an index (unique) to the field,
- `#FK_parentTable` for associated to the field a foreign key to the primary key of its parent table

Primary keys are automatically created with `id` name

`$options` is an associated array for customization (one key currently)
- `preField` : boolean (default:false). If true : all fields are prefixed by table name
#### Example
```php
QDb::create(
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
returns
Array
(
    [0] => DROP TABLE IF EXISTS user;
    [1] => CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT);
    [2] => DROP INDEX IF EXISTS user_name_idx;
    [3] => CREATE  INDEX user_name_idx ON user (name);
    [4] => DROP TABLE IF EXISTS message;
    [5] => CREATE TABLE message (id INTEGER PRIMARY KEY AUTOINCREMENT,content TEXT,date INTEGER,uniqueField INTEGER,userId INTEGER NOT NULL ,FOREIGN KEY(userId) REFERENCES user(id));
    [6] => DROP INDEX IF EXISTS message_uniqueField_idx;
    [7] => CREATE UNIQUE INDEX message_uniqueField_idx ON message (uniqueField);
)


*/
```
