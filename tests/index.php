<?php
require ('../src/thipages/quick/QDb.php');
require('../vendor/autoload.php');
require('Test_Database.php');

use thipages\quick\QDb;

// FK EXTRACTION TESTS
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

$success=[];
$success['FK CASE 1']=$res[0]?'ok':'nok';
$success['FK CASE 2']=$res[1]?'ok':'nok';
if (false) {
    // SQLite
    $test=new Test_Database();
    $success=array_merge($success,$test->run());
} else {
    // MySql/MariaDB
    $test=new Test_Database(['db'=>'mysql']);
    $success=array_merge($success,$test->run());
}
print_r($success);

