<?php
use thipages\quick\QDb;
use thipages\quick\QSql;
use thipages\sqlitecli\SqliteCli;
require ('credentials.php'); // $username and $pass access
//
class Test_Database {
    private $options;
    private $create;
    private $db;
    private function fieldName($t,$f) {return $this->options[QDb::prefield] ? $t."_".$f : $f;}
    private function U($f) {return $this->fieldName('user',$f);}
    private function M($f) {return $this->fieldName('message',$f);}
    private function isOk($c) {return $c?'ok':'nok';}
    public function __construct($options=[]) {
        $schema= [
            'user'=>'name VARCHAR(10) #INDEX', // VARCHAR(xx) for mysql or TEXT for sqlite/MARIADB
            'message'=>[
                'content TEXT',
                'userId INTEGER NOT NULL #FK_user',
                'category TEXT #UNIQUE'
            ]
        ];
        $this->db=new QDb($options);
        $this->options=$this->db->getOptions();
        $this->create= $this->db->create($schema);
        print_r($this->create);
    }
    public function execute($db,$sqlList) {
        global $username,$pass;
        if ($db==='sqlite') {
            $cli=new SqliteCli('test.db');
            $res= $cli->execute($sqlList);
        } else {
            // jungling in order to match SqliteCli formalism
            // todo : apply PDO to both databases
            $pdo=new PDO('mysql:host=localhost;dbname=test',$username,$pass);
            foreach ($sqlList as $sql) {
                if (strtolower(substr(trim($sql),0,6))==='select') {
                    $s=$pdo->query($sql);
                    $res=$s->fetchAll();
                    $res=[count($res)!==0,[$res[0][0]]];
                } else {
                    $res=$pdo->exec($sql);
                    $res=[($res!==false),$res];
                }
            }
        }
        /*echo("\nnew\n");
        print_r($sqlList);
        echo("result\n");
        print_r($res);
        echo("\nend\n");*/
        return $res;
    }
    private function trans($keysValues) {
        $preField=$this->options[QDb::prefield];
        $kv=[];
        foreach ($keysValues as $k=>$v) {
            $temp=$preField?'tableName_'.$k:$k;
            $kv[$temp]=$v;
        }    
        return $kv;
    }
    public function run() {
        $success=[];
        $db=$this->options['db'];
        $res=$this->execute($db,$this->create);
        $success['CREATE_'.$db]=$this->isOk($res[0]);
        $sqlList=[];
        if ($db==='sqlite') $sqlList[]='PRAGMA foreign_keys=ON;';
        $sqlList[]=QSql::insert('user', $this->trans(['name' => 'tit']));
        $sqlList[]=QSql::insert('message', $this->trans(['content'=>'Something','category' => 'tit', 'userId' => 1]));
        $cat='created_at';
        if ($db==='mysql') $cat='UNIX_TIMESTAMP('.$cat.')';
        $sqlList[]="select $cat from message;";  
        
        $res = $this->execute($db,$sqlList);
        $success['INSERT_'.$db]= $this->isOk($res[0] && time()-$res[1][0]>=0);
        sleep(2);
        $mat='created_at';
        if ($db==='mysql') $mat='UNIX_TIMESTAMP('.$mat.')';
        $res = $this->execute($db,[
            $this->db->update('user', $this->trans(['name' => 'tit20']), join('',$this->trans(['id=1']))),
            "select $mat from user;"
        ]);
        $success['UPDATE_'.$db]= $this->isOk($res[0] && time()-$res[1][0]>=0);
        return $success;
    }
    
}
