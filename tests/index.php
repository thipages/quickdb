<?php
require ('../src/thipages/quick/QDb.php');
require('../vendor/autoload.php');
use thipages\quick\QDb;
use thipages\quick\QSql;
use thipages\sqlitecli\SqliteCli;

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
$create= QDb::create(
    [
        'user'=>'name TEXT #INDEX',
        'message'=>[
            'content TEXT',
            'date INTEGER',
            'userId INTEGER NOT NULL #FK_user',
            'uniqueField INTEGER #UNIQUE'
        ]
    ]
);
//
function fieldName($t,$f) {
    global $preField;
    return $preField ? $t."_".$f : $f;
}
function U($f) {return fieldName('user',$f);}
function M($f) {return fieldName('message',$f);}
function isOk($c) {return $c?'ok':'nok';}
$cli=new SqliteCli('test.db');
print_r($create);
$res=$cli->execute($create);
$preField=false;
$sqlList= [
    'PRAGMA foreign_keys=ON;',
    QSql::insert('user',[U('name')=>'tit']),
    QSql::insert('message',[M('uniqueField')=>'tit', M('userId')=>1]),
    'select created_at from message;'
];
$res=$cli->execute($sqlList);
echo "TEST CREATE + INSERT ";
echo(isOk($res[0]));
echo("\nTEST UTC DATE ".isOk($res[1][0]-time()===0));
sleep(2);
$res=$cli->execute(QDb::update('user',['name'=>'tit2'],'id=1'));