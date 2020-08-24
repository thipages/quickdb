<?php
namespace thipages\quick;
class QDb {
    const prefield='prefield';
    const omnifields='omnifields';
    const primarykey="primarykey";
    private $options;
    public function __construct($options=[]) {
        $this->options=array_merge(self::defaultOptions(),$options);
    }
    public static function defaultOptions() {
        return [
            self::prefield=>false,
            // https://stackoverflow.com/questions/200309/sqlite-database-default-time-value-now
            self::omnifields=>[
                "created_at INTEGER  not null default (strftime('%s','now'))",
                "modified_at INTEGER not null default (strftime('%s','now'))"
            ],
            self::primarykey=>"id INTEGER PRIMARY KEY AUTOINCREMENT"
        ];
    }
    // todo : add a second parameter $dropTable (default : false)
    public function create($definition) {
        $sql=[];
        foreach($definition as $tableName=>$fields) {
            $sql=array_merge($sql,self::_create($tableName,$fields));
        }
        return $sql;
    }
    public function insert($tableName, $keyValues) {
        return QSql::insert($tableName, $keyValues);
    }
    public function update($tableName, $keyValues, $where) {
        $fields=[];
        foreach ($this->options[self::omnifields] as $d) $fields[]=self::getFieldFromDefinition($d);
        if (in_array('modified_at',$fields) ) $keyValues['modified_at']=time();
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
                $indexes[]=self::drop($iName,'INDEX');
                $indexes[]="CREATE $unique INDEX $iName ON $tableName ($childKey);";
            }
            $create[]=$this->preField($tableName,$f);
            
        }
        if ($fks!=null) {
            foreach ($fks as $fk) {
                $parentKey=$this->primaryKey($fk[0]);
                $create[]="FOREIGN KEY($fk[1]) REFERENCES $fk[0]($parentKey)";
                $iName=self::under($tableName,$fk[1],"idx");
                $indexes[]=self::drop($iName,'INDEX');
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