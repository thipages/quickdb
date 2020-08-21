<?php
require ('../src/thipages/quick/QDb.php');
require('../vendor/autoload.php');
use thipages\quick\QDb;
use thipages\quick\QSql;
//
$A1=[
    QDb::extractFks('fk INTEGER #FK_user'),
    QDb::extractFks('fk INTEGER #FK_user #INDEX')
];
$A2=[
    ['fk INTEGER ','user'],
    ['fk INTEGER #INDEX','user']
];
$res=[];
for ($i=0;$i<count($A1);$i++) {
    if ($A1[$i]===null || $A2[$i]===null) {
        $res[]=$A1[$i]===null && $A2[$i]===null;
    } else {
        $res[]= $A1[$i][0]===$A2[$i][0] && $A1[$i][1]===$A2[$i][1]; 
    }
}
print_r(array_map(function ($a,$i) use($A1){return $a?'ok':'nok:'.join('@',$A1[$i]);},$res, array_keys($res)));
//
$db=new QDb();
$db->addTable('user','name TEXT #INDEX');
$db->addTable('message',[
    'content TEXT',
    'date INTEGER',
    'unique INTEGER #UNIQUE_INDEX',
    'userId INTEGER #FK_user'
]);
//
$cli=new \thipages\sqlitecli\SqliteCli('test.db');
//$subset=array_slice($db->getSql(),0,6);
$res=$cli->execute($db->getSql());
$sqlList= [
    QSql::insert('user',['user_name'=>'tit']),
    QSql::insert('message',['message_unique'=>'tit'])
];
$res=$res=$cli->execute($sqlList);
print_r($res);