<?php

/** @property PDO $db */
class CModel {

    protected static $db = null;
    protected static $errorlist = [];   // store all errors happeniung in runtime
            
            
    function __construct() {

        if (!$this->isConnected()) {

            $properties = get_param(CController::$cfg, 'mysql', []);

            try {

                // init mysql connection
                self::$db = new PDO(
                        sprintf("mysql:host=%s;dbname=%s", $properties['host'], $properties['base'])
                        , $properties['user']
                        , $properties['pass']
                        , [
                    PDO::ATTR_TIMEOUT => 5,
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'set names utf8',
                ]);

                self::$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                //this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                self::$db->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
                self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
            } catch (Exception $exc) {

                self::$db = null;
                echo 'База данных не доступна! ' . $exc->getMessage() . "<br/>\n";
            }
        }
        self::$errorlist = []; // clear all errors
    }

    public function getErrors() {
        
        return self::$errorlist;
    }
    
    protected function select($query, $param = array()) {

        if (!self::isConnected())
            return [
                'error' => 'Not connected',
            ];

        /** 
         * модернизируем запрос на лету
         * если есть параметры в виде массивов
         * такие параметры будем заменять на конструкцию in
         * 
         * where field in :[ARRAY] => where field in (x1,x2,...)
         */
                
        $cnt = 0;
        foreach ($param as $key => $value) {
            if (gettype($value) === 'array') {
                $condition = "(";
                foreach ($value as $item) {
                    
                    $condition .= $cnt ? "," : ""; // если не первый параметр, то добавим запятую
                    $vparam = "_X" . ++$cnt;
                    $condition .= " :$vparam";
                    
                    // а параметр подмассива перекидываем в основной массив
                    // елементы вложенного массива не должны быть сами массивами, иначе хрень будет
                    $param[$vparam] = $item;
                }
                $condition .= ") ";
                $query = str_replace(":$key", $condition, $query);
                unset($param[$key]);
            }
        }
        
        $sth = self::$db->prepare($query);

        foreach ($param as $key => $value) {
            $type = strtolower(gettype($value));
            $cast = null;
            switch ($type) {
                case 'integer': $cast = PDO::PARAM_INT;
                    break;
                case 'null': $cast = PDO::PARAM_NULL;
                    break;
                case 'boolean': $cast = PDO::PARAM_BOOL;
                    break;
                default: $cast = PDO::PARAM_STR;
                    break;
            }
            $sth->bindValue($key, $value, $cast);
        }

        $sth->execute();
        $error = $sth->errorInfo();
        $emessasge = get_param($error, 2);
        self::$errorlist[] = $emessasge;

        $data = $sth->fetchAll();

        return [
            'data' => $data,
            'error' => $emessasge,
        ];
    }

    public static function isConnected() {

        return self::$db !== null;
    }
    
    public function startTransaction() {
        
        self::$db->beginTransaction();
    }
    
    public function stopTransaction($success = true) {
        
        if ($success)
            self::$db->commit();
        else
            self::$db->rollBack();
    }
    
    /*
    public static function getDB() {
        return $this->db;
    }
    */ 

}
