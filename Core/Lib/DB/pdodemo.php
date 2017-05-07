<?php
/**
 * auther soulence
 * 调用数据类文件
 * modify 2015/06/12
 */
class DBConnect
{
    private $dbname = null;
    private $pdo = null;
    private $persistent = false;
    private $statement = null;
    private $lastInsID = null;
    private static $_instance = [];
 
    private function __construct($dbname,$attr)
    {
        $this->dbname = $dbname;
        $this->persistent = $attr;
    }
 
    public static function db($flag='r',$persistent=false)
    {
        if(!isset($flag)){
            $flag = 'r';
        }
         
        if (!class_exists('PDO'))
        {
            throw new Exception('not found PDO');
            return false; 
        }
        $mysql_server = Yaf_Registry::get('mysql');
        if(!isset($mysql_server[$flag])){
            return false;
        }
     
        $options_arr = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES '.$mysql_server[$flag]['charset'],PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC);
        if($persistent === true){
            $options_arr[PDO::ATTR_PERSISTENT] = true;
        }
         
        try { 
            $pdo = new PDO($mysql_server[$flag]['connectionString'],$mysql_server[$flag]['username'],$mysql_server[$flag]['password'],$options_arr);
        } catch (PDOException $e) {  
            throw new Exception($e->getMessage()); 
            //exit('连接失败:'.$e->getMessage()); 
            return false; 
        }
 
        if(!$pdo) { 
            throw new Exception('PDO CONNECT ERROR'); 
            return false; 
        } 
 
        return $pdo;
    }
 
    /**
     * 得到操作数据库对象
     * @param string $dbname 对应的数据库是谁
     * @param bool $attr  是否长连接
     * return false说明给定的数据库不存在
     */
    public static function getInstance($dbname = 'r',$attr = false)
    {
        $mysql_server = Yaf_Registry::get('mysql');
        if(!isset($mysql_server[$dbname])){
            return false;
        }
        $key = md5(md5($dbname.$attr,true));
        if (!isset(self::$_instance[$key]) || !is_object(self::$_instance[$key]))
            self::$_instance[$key] = new self($dbname,$attr);
        return self::$_instance[$key];
    }
 
    private function getConnect(){
        $this->pdo = self::db($this->dbname,$this->persistent);
    }
 
    /**
     * 查询操作 
     * @param string $sql   执行查询的sql语句
     * @param array $data  查询的条件  格式为[':id'=>$id,':name'=>$name](推荐)或者为[1=>$id,2=>$name]
     * @param bool $one   是否返回一条内容  默认为否
     */
    public function query($sql, $data = [], $one = false)
    {
        if (!is_array($data) || empty($sql) || !is_string($sql))
            return false;
 
        $this->free();
 
        return $this->queryCommon($data,$sql,$one);
    }
 
    /**
     * 内部查询的共用方法
     */
    private function queryCommon($data,$sql,$one)
    {
        $this->pdoExec($data,$sql);
 
        if ($one){
            return $this->statement->fetch(PDO::FETCH_ASSOC);
        }else{
            return $this->statement->fetchAll(PDO::FETCH_ASSOC);
        }
    }
 
    /**
     * 多条SQL语句的查询操作 
     * @param array $arr_sql   执行查询的sql语句数组 格式为[$sql1,$sql2]
     * @param array $arr_data  查询与$arr_sql对应的条件  格式为[[':id'=>$id,':name'=>$name],[':id'=>$id,':name'=>$name]](推荐)或者为[[1=>$id,2=>$name],[1=>$id,2=>$name]]
     * @param bool $one   是否返回一条内容  默认为否  这里如果设置为true那么每一条sql都只返回一条数据
     */
    public function queryes($arr_sql, $arr_data = [], $one = false)
    {
        if(!is_array($arr_sql) || empty($arr_sql) || !is_array($arr_data))
            return false;
 
        $this->free();
 
        $res = [];$i = 0;
        foreach ($arr_sql as $val) {
            if(!isset($arr_data[$i]))
                $arr_data[$i] = [];
            elseif(!is_array($arr_data[$i]))
                throw new Exception('Error where queryes sql:'.$val.' where:'.$arr_data[$i]);
                 
            $res[] = $this->queryCommon($arr_data[$i],$val,$one);
            $i++;
        }
        return $res;
    }
 
    /**
     * 分页封装 
     *
     * @param string $sql
     * @param int $page  表示从第几页开始取
     * @param int $pageSize 表示每页多少条
     * @param array $data 查询的条件
     */
    public function limitQuery($sql, $page=0, $pageSize=20, $data = [])
    {
        $page = intval($page);
        if ($page < 0) {
            return [];
        }
        $pageSize = intval($pageSize);
        if ($pageSize > 0) { // pageSize 为0时表示取所有数据
            $sql .= ' LIMIT ' . $pageSize;
            if ($page > 0) {
                $start_limit = ($page - 1) * $pageSize;
                $sql .= ' OFFSET ' . $start_limit;
            }
        }
        return $this->query($sql, $data);
    }
 
    /**
     * 这个是用来进行添加 删除  修改操作  使用事务操作
     * @param string $sql   执行查询的sql语句
     * @param array $data  查询的条件  格式为[':id'=>$id,':name'=>$name](推荐)或者为[1=>$id,2=>$name]
     * @param bool $Transaction  是否事务操作  默认为否
     */
    public function executeDDL($sql, $data = [],$Transaction = false){
        if (!is_array($data) || !is_string($sql))
            return false;
 
        $this->free();
 
        if($Transaction)
            $this->pdo->beginTransaction();//开启事务
        try{
            $this->execRes($data,$sql);
            if($Transaction)
                $this->pdo->commit();//事务提交
            return $this->lastInsID;
        } catch (Exception $e) {
            if($Transaction)
                $this->pdo->rollBack();//事务回滚
            throw new Exception('Error DDLExecute <=====>'.$e->getMessage());
            return false;
        } 
    }
 
    /**
     * 这个是用来进行添加 删除  修改操作  使用事务操作 
     * 它是执行多条的
     * @param array $arr_sql  需要执行操作的SQL语句数组
     * @param array $arr_data  与数组对应SQL语句的条件
     * @param bool $Transaction  是否事务操作  默认为否
     */
    public function executeDDLes($arr_sql, $arr_data = [],$Transaction = false){
 
        if(!is_array($arr_sql) || empty($arr_sql) || !is_array($arr_data))
            return false;
 
        $res = [];
 
        $this->free();
 
        if($Transaction)
            $this->pdo->beginTransaction();//开启事务
        try{
            $i = 0;
            foreach($arr_sql as $val){
                if(!isset($arr_data[$i]))
                    $arr_data[$i] = [];
                elseif(!is_array($arr_data[$i])){
                    if($Transaction)
                        $this->pdo->rollBack();//事务回滚
                    throw new Exception('Error where DDLExecutees sql:'.$val.' where:'.$arr_data[$i]);
                }
 
                $this->execRes($arr_data[$i],$val);
                $res[] = $this->lastInsID;
                $i++;
            }
 
            if($Transaction)
                $this->pdo->commit();//事务提交
 
            return $res;
        } catch (Exception $e) {
            if($Transaction)
                $this->pdo->rollBack();//事务回滚
            throw new Exception('Error DDLExecutees array_sql:'.json_encode($arr_sql).' <=====>'.$e->getMessage());
            return false;
        } 
        return $res;
    }
 
    /**
     * 此方法是用来计算查询返回的条数   注意 它只支持SELECT COUNT(*) FROM TABLE...或者SELECT COUNT(0) FROM TABLE...方式
     * @param string $sql  查询的sql语句
     * @param array $data  SQL语句的条件
     */
    public function countRows($sql,$data = []){
        if (!is_array($data) || empty($sql) || !is_string($sql))
            return false;
        $this->free();
 
        $res = $this->pdoExec($data,$sql);
 
        if($res == false)
            return false;
 
        return $this->statement->fetchColumn();
    }
 
    /**
     * 此方法是用来计算查询返回的条数   它是执行多条SQL
     * @param string $sql  查询的sql语句
     * @param array $data  SQL语句的条件
     */
    public function countRowses($arr_sql,$arr_data = []){
 
        if(!is_array($arr_sql) || empty($arr_sql) || !is_array($arr_data))
            return false;
 
        $res = [];
 
        $this->free();
        $i = 0;
        foreach ($arr_sql as $val) {
            if(!isset($arr_data[$i]))
                $arr_data[$i] = [];
            elseif(!is_array($arr_data[$i]))
                throw new Exception('Error where CountRowses sql:'.$val.' where:'.$arr_data[$i]);
 
            $res1 = $this->pdoExec($arr_data[$i],$val);
 
            if($res1 == false)
                $res[] = false;
            else
                $res[] = $this->statement->fetchColumn();
        }
 
        return $res;
    }
 
    /**
     * 这里再提供一个方法   由于项目中会有很多需要提供开启事务  然后再进行操作  最后提交
     * @param bool $Transaction  是否事务操作  默认为否
     */
    public function getDB($Transaction=false)
    {
        $this->Transaction = $Transaction;
        $this->getConnect();
        if($Transaction === true)
            $this->pdo->beginTransaction();//开启事务
        return $this;
    }
 
    /**
     * 此方法可以执行多次  它是执行DDL语句的
     * 注意  它是需要配合getDB和sQCommit一起使用  不能单独使用哦
     * 如果没有开启事务  sQCommit方法可以不调用
     * @param string $sql  查询的sql语句
     * @param array $data  SQL语句的条件
     */
    public function execSq($sql,$data = [])
    {
        if($this->checkParams($sql,$data) === false)
            return false;
 
        try{
            $this->execRes($data,$sql);
            return $this->lastInsID;
        } catch (Exception $e) {
            if(isset($this->Transaction) && $this->Transaction === true)
                $this->pdo->rollBack();//事务回滚
            throw new Exception('Error execSq<=====>'.$e->getMessage());
            return false;
        } finally {
            if (!empty($this->statement))
            {
                $this->statement->closeCursor();
                unset($this->statement);
            }
        }
    }
 
    /**
     * 执行查询的方法  它需要传一个连接数据库对象
     * @param string $sql   执行查询的sql语句
     * @param array $data  查询的条件  格式为[':id'=>$id,':name'=>$name](推荐)或者为[1=>$id,2=>$name]
     * @param bool $one   是否返回一条内容  默认为否
     */
    public function querySq($sql,$data = [],$one = false)
    {
        if($this->checkParams($sql,$data) === false)
            return false;
 
        return $this->pdoExecSq($sql,$data,[1,$one]);
    }
 
    /**
     * 分页封装 
     *
     * @param string $sql
     * @param int $page  表示从第几页开始取
     * @param int $pageSize 表示每页多少条
     * @param array $data 查询的条件
     */
    public function limitQuerySq($sql, $page=0, $pageSize=20, $data = [])
    {
        $page = intval($page);
        if ($page < 0) {
            return [];
        }
        $pageSize = intval($pageSize);
        if ($pageSize > 0) { // pageSize 为0时表示取所有数据
            $sql .= ' LIMIT ' . $pageSize;
            if ($page > 0) {
                $start_limit = ($page - 1) * $pageSize;
                $sql .= ' OFFSET ' . $start_limit;
            }
        }
        return $this->querySq($sql, $data);
    }
 
    /**
     * 此方法是用来计算查询返回的条数   注意 它只支持SELECT COUNT(*) FROM TABLE...或者SELECT COUNT(0) FROM TABLE...方式
     * @param string $sql  查询的sql语句
     * @param array $data  SQL语句的条件
     */
    public function countRowsSq($sql,$data = []){
        if($this->checkParams($sql,$data) === false)
            return false;
        return $this->pdoExecSq($sql,$data,[2]);
    }
 
    /**
     * 这里再提供一个方法 这是最后提交操作 如果没有开启事务  此方法最后可以不调用的
     */
    public function sQCommit()
    {
        if(empty($this->pdo) || !is_object($this->pdo))
            return false;
        if(isset($this->Transaction) && $this->Transaction === true)
            $this->pdo->commit();//提交事务
        unset($this->pdo);
    }
 
    /**
     * 内部调用方法  
     */
    public function checkParams($sql,$data)
    {
        if (empty($this->pdo) || !is_object($this->pdo) || !is_array($data) || empty($sql) || !is_string($sql))
            return false;
         
        return true;
    }
 
    /**
     * 内部调用方法  
     */
    private function pdoExecSq($sql,$data,$select = []){
        try{
            $res = $this->pdoExec($data,$sql);
            if(empty($select))
                return $res;
            else{
                if($select[0] === 1){
                    if($select[1] === true)
                        return $this->statement->fetch(PDO::FETCH_ASSOC);
                    else
                        return $this->statement->fetchAll(PDO::FETCH_ASSOC);
                }elseif($select[0] === 2)
                    return $this->statement->fetchColumn();
                else
                    return false;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
            return false;
        } finally {
            if (!empty($this->statement))
            {
                $this->statement->closeCursor();
                unset($this->statement);
            }
        }
    }
 
    /**
     * 内部调用方法  
     */
    private function execRes($data,$sql){
 
        $res = $this->pdoExec($data,$sql);
         
        $in_id = $this->pdo->lastInsertId();
 
        if (preg_match("/^\s*(INSERT\s+INTO|REPLACE\s+INTO)\s+/i", $sql) && !empty($in_id))
            $this->lastInsID = $in_id;
        else
            $this->lastInsID = $res;
    }
 
    /**
     * 内部调用方法  用来直接执行SQL语句的方法
     */
    private function pdoExec($data,$sql){
        $this->statement = $this->pdo->prepare($sql);
        if (false === $this->statement)
            return false;
        if (!empty($data))
        {
            foreach ($data as $k => $v)
            {
                $this->statement->bindValue($k, $v);
            }
        }
        $res = $this->statement->execute();
        if (!$res)
        {
            throw new Exception('sql:'.$sql.'<====>where:'.json_encode($data).'<====>error:'.json_encode($this->statement->errorInfo()));
        }else{
            return $res;
        }
    }
     
    /**
     * 内部调用方法  用来释放的
     */
    private function free()
    {
        if (is_null($this->pdo))
            $this->getConnect();
 
        if (!empty($this->statement))
        {
            $this->statement->closeCursor();
            $this->statement = null;
        }
    }
}
?>