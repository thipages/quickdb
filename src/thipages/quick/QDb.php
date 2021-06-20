<?php
namespace thipages\quick;
use ArrayObject;

class QDb {
    const prefield='prefield';
    const omnifields='omnifields';
    const primarykey="primarykey";
    const database="db";
    protected $options;
    public function __construct($options=[]) {
        $db=$options[self::database]??'sqlite';
        $this->options=array_merge(self::defaultOptions($db),$options);
    }
    public function sql_switchFKs($on) {
        return $this->options[self::database]==='sqlite'
        ?'PRAGMA foreign_keys='.($on?'ON':'OFF').';'
        :'SET FOREIGN_KEY_CHECKS='.($on?"1":"0").';';
    }
    public function getOptions() {
        return (new ArrayObject($this->options))->getArrayCopy();
    }
    public function defaultOptions($db) {
        $specific=($db==='sqlite')
            ? [
                QDb::database=>'sqlite',
                QDb::omnifields=>[
                    "created_at INTEGER  not null default (strftime('%s','now'))",
                    "modified_at INTEGER not null default (strftime('%s','now'))"
                ],
                QDb::primarykey=>"id INTEGER PRIMARY KEY AUTOINCREMENT"
            ]
            : [
                QDb::database=>'mysql',
                QDb::omnifields=>[
                    "created_at TIMESTAMP  not null default CURRENT_TIMESTAMP",
                    "modified_at TIMESTAMP not null default CURRENT_TIMESTAMP ON UPDATE current_timestamp"
                ],
                QDb::primarykey=>"id INTEGER PRIMARY KEY AUTO_INCREMENT"
            ];
        
        return array_merge(
            [self::prefield=>false],
            $specific
        );
    }
    // todo : add a second parameter $dropTable (default : false)
    public function create($definition) {
        $sql=[$this->sql_switchFKs(false)];
        foreach($definition as $tableName=>$fields) {
            $sql=array_merge($sql,self::_create($tableName,$fields));
        }
        $sql[]=$this->sql_switchFKs(true);
        return $sql;
    }
    public function insert($tableName, $keyValues) {
        return QSql::insert($tableName, $keyValues);
    }
    public function update($tableName, $keyValues, $where) {
        $fields=[];
        foreach ($this->options[self::omnifields] as $d) $fields[]=self::getFieldFromDefinition($d);
        if ($this->options[self::database]=='sqlite' && in_array('modified_at',$fields) ) $keyValues['modified_at']=time();
        return QSql::update($tableName, $keyValues,$where);
    }
    public function delete($tableName, $where) {
        return QSql::delete($tableName,$where);
    }
    private static function drop($name, $type='TABLE') {
        return "DROP $type IF EXISTS $name;";
    }
    private static function clean($source) {
        return trim(preg_replace('/\s+/', ' ',$source));
    }
    public static function extractIndex($source) {
        $source=self::clean($source);
        $pos=strpos($source,'#INDEX');
        if (!$pos===FALSE) {
            $index=1;
            $s=[str_replace('#INDEX','',$source)];
        } else {
            $s=explode('#UNIQUE', $source);
            $index= count($s)>1?2:0;
        }
        return [$index,join(' ',$s)];
    }
    public static function extractFks($source) {
        $s=self::clean($source);
        $pos=strpos($s,'#FK_');
        if ($pos===FALSE) {
            return null;
        } else {
            $s1=explode(' ',substr($s, $pos+4));
            if (count($s1)===1) {
                return [substr($s, 0, $pos),substr($s, $pos+4)];
            } else {
                $parentTable=array_shift($s1);
                return [substr($s,0,$pos).join(' ',$s1),$parentTable];
            }
        }
    }
    private static function under(...$a) {
        return join('_',$a);
    }
    private function preField($tableName,$fieldName) {
        return $this->options[self::prefield]
            ? self::under($tableName, $fieldName)
            : $fieldName;
    }
    private static function getFieldFromDefinition($d) {
        return explode(' ',$d)[0];
    }
    private function primaryKey($tableName) {
        return $this->preField($tableName, 'id');
    }
    private function _create($tableName, $fields) {
        if (!is_array($fields)) $fields=[$fields];
        array_unshift($fields,$this->options[self::primarykey]);
        if ($this->options[self::omnifields]!=null) {
            foreach($this->options[self::omnifields] as $field) array_push($fields,$field);
        }
        $create=[];
        $fks=[];
        $indexes=[];
        foreach ($fields as $field) {
            $indexOut=self::extractIndex($field);
            $index=$indexOut[0];
            $f=$indexOut[1];
            $f=self::clean($f);
            $childKey=$this->preField($tableName,self::getFieldFromDefinition($f));
            $fksOut=self::extractFks($indexOut[1]);
            if ($fksOut!==null) {
                $f=$fksOut[0];
                array_shift($fksOut);
                array_push($fksOut,$childKey);
                $fks[]=$fksOut;
            }
            if ($index!==0) {
                $unique=$index===2?'UNIQUE':'';
                $iName=self::under($tableName,$childKey,"idx");
                if ($this->options[self::database]==='sqlite') $indexes[]=self::drop($iName,'INDEX');
                $indexes[]="CREATE $unique INDEX $iName ON $tableName ($childKey);";
            }
            $create[]=$this->preField($tableName,$f);
            
        }
        if ($fks!=null) {
            foreach ($fks as $fk) {
                $parentKey=$this->primaryKey($fk[0]);
                $create[]="FOREIGN KEY($fk[1]) REFERENCES $fk[0]($parentKey)";
                $iName=self::under($tableName,$fk[1],"idx");
                if ($this->options[self::database]==='sqlite') $indexes[]=self::drop($iName,'INDEX');
                $indexes[]="CREATE INDEX $iName ON $tableName ($fk[1]);";
            }
        }
        return array_merge(
        [
            self::drop($tableName),
            "CREATE TABLE $tableName (".join(",", $create).");"
        ],
            $indexes
        );
    }

}