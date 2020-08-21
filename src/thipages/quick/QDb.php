<?php
namespace thipages\quick;
class QDb {
    private static $options;
    public static function defaultOptions() {
        return [
            'preField'=>false
        ];
    }
    public static function create($definition, $options=[]) {
        self::$options=$options==null?self::defaultOptions():$options;
        $sql=[];
        foreach($definition as $tableName=>$fields) {
            $sql=array_merge($sql,self::_create($tableName,$fields));
        }
        return $sql;
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
    private static function preField($tableName,$fieldName) {
        return self::$options['preField']
            ? self::under($tableName, $fieldName)
            : $fieldName;
    }
    private static function primaryKey($tableName) {
        return self::preField($tableName, 'id');
    }
    private static function _create($tableName, $fields) {
        $refId=self::primaryKey($tableName);
        $create=["$refId INTEGER PRIMARY KEY AUTOINCREMENT"];
        $indexes=[];
        $fks=[];
        if (!is_array($fields)) $fields=[$fields];
        foreach ($fields as $field) {
            $indexOut=self::extractIndex($field);
            $index=$indexOut[0];
            $f=$indexOut[1];
            $f=self::clean($f);
            $childKey=self::preField($tableName,explode(' ',$f)[0]);
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
            $create[]=self::preField($tableName,$f);
            if ($fks!=null) {
                foreach ($fks as $fk) {
                    $parentKey=self::primaryKey($fk[0]);
                    $create[]="FOREIGN KEY($childKey) REFERENCES $fk[0]($parentKey)";
                }
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