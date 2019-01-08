<?php
// Author: 杨玉奇
// email: yangyuqi@sina.com
// copyright yangyuqi
// 著作权归作者 杨玉奇 所有。商业转载请联系作者获得授权，非商业转载请注明出处。
// date: 2018-01-02

namespace minimum_frame;
use \PDO;

class YMySql
{
	////////////////////////////////// 构造 与 属性 函数 /////////////////////////////////// 
    /**
     * 构造对象，出入 PDO 连接信息
     * @access public
     * @param string $dsn 连接的服务器信息
     * @param string $userName 用户名
     * @param string $password 口令
     * @return null
     */
	public function __construct( $hostName, $dbName, $userName, $password, $hostPort = 3306 )
	{
        $this->m_pdo = new PDO('mysql:host=' . $hostName . ';dbname=' . $dbName . ';port=' . $hostPort, $userName, $password); 
        if( !$this->m_pdo )
        {
            throw new Exception( "Connect MySql: {$hostName} Fail!" );
        }

        $this->m_pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->m_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->m_pdo->exec('set names utf8');
    }

    public function __destruct()
    {
        $this->m_stmt = null;
        $this->m_pdo = null;
    }

    public function getLastInsertID()
    {
        $this->m_lastInsertID = $this->m_pdo->lastInsertId();
        return $this->m_lastInsertID;
    }

    /////////////////////////////////// 高级函数 ///////////////////////////////////
    /**
     * 构造对象，出入 PDO 连接信息
     * @access public
     * @param string $table 表名
     * @param array $whereFields WHERE 字段名
     * @param array $whereValues WHERE 条件对应的值
     * @return array(array()) 多行结果集
     */
    public function selectDataEx( $table, $whereFields = array(), $whereValues = array() )
    {
        $sql = "SELECT * FROM {$table}";
        for( $i = 0; $i < count($whereFields); $i++ )
        {
            if( 0 === $i )
                $sql = $sql . " WHERE {$whereFields[$i]} = ?";
            else
                $sql = $sql . " AND {$whereFields[$i]} = ?";
        }

        return $this->selectData($sql, $whereValues);
    }

    /**
     * 插入数据
     * @access public
     * @param string $table 表名
     * @param array& $row 更新的对象。函数内会更改该对象的 “关键字” 字段值为：“插入 ID”
     * @param string $primaryKeyName “关键字” 字段名
     * @return int 成功：返回插入后的主键 id。失败：返回 0
     */
    public function insertDataEx( $table, &$row, $primaryKeyName )
    {
        $fields = [];
        $values = [];
    
        foreach( $row as $key => $value )
        {
            if( $key === $primaryKeyName )
                continue;
    
            $fields[] = $key;
            $values[] = $value;
        }
    
        $row[$primaryKeyName] = $this->insertData( $table, $fields, $values );
        return $row[$primaryKeyName];
    }

    /**
     * 更新数据
     * @access public
     * @param string $table 表名
     * @param array $row 更新的对象。
     * @param string $primaryKeyName “关键字” 字段名
     * @return bool
     */
    public function updateDataEx( $table, $row, $primaryKeyName )
    {
        $where = $primaryKeyName . ' = ?';
        $fields = [];
        $values = [];
        $whereValues = [];
        foreach( $row as $key => $value )
        {
            if( $key === $primaryKeyName )
            {
                $whereValues[] = $value;
                continue;
            }
    
            $fields[] = $key;
            $values[] = $value;
        }
    
        return $this->updateData( $table, $fields, $values, $where, $whereValues );
    }

    /**
     * 执行事务集
     * @access public
     * @param array( string sql => array bindParam ) $sqlAndBindArray 事务脚本数组。由 sql 语句 与 对应的 bindParam 组成 
     * @return bool
     */
    public function doTransaction( $sqlAndBindArray )
    {
        try
        {
            $this->m_pdo->beginTransaction();
    
            foreach( $sqlAndBindArray as $sql => $bindParam )
            {
                // $affected_rows = $this->m_pdo->exec( $sql );
                $affected_rows = $this->executeSql( $sql, $bindParam );
                if(!$affected_rows)
                    throw new PDOException('执行：'. $sql . ' 失败。');
            }
    
            return $this->m_pdo->commit();
        }
        catch(PDOException $ex)
        {
            $this->m_pdo->rollBack();
            comm_get_default_log()->logError( 'doTransaction except: ' . $ex->getMessage() );
            return false;
        }     
    }

    /////////////////////////////////// 低级函数 ///////////////////////////////////
    public function _check_sql_injection($sql)
    {
        // 先将移位运算符替换掉，不参与检查
        $temp1 = preg_replace('/(>\s*>|<\s*<)/', '@@', $sql);
        // 将所有的 SQL 运算符(==, =, !=, <>, >, <, >=, <=, !<, !>)及其两边的空格全部替换为 '='
        $temp2 = preg_replace('/\s*[=!<>]\s*/', '=', $temp1); // preg_replace('/\s*(==|=|!=|<>|>|<|>=|<=|!<|!>)\s*/', '=', $temp1);
        // 将连续的多个 '=' 替换为一个 '='
        $check = preg_replace('/[=]+/', '=', $temp2);

        // 检查等号右边的字符是不是 '?'
        $times = preg_match('/=[^?]/', $check);
        if(0 == $times)
        {
            return true;
        }

        return false;
    }


    // 返回 bool 值
    public function executeSql($sql, $bindParam = array() )
    {
        if( !_check_sql_injection($sql) )
        {
            return false;
        }

        $this->m_stmt = null;
        $this->m_stmt = $this->m_pdo->prepare( $sql );

        $res = false;
        if( 0 < count($bindParam) )
            $res = $this->m_stmt->execute( $bindParam );
        else
            $res = $this->m_stmt->execute( );
   
        if( !$res )  
        {
            comm_get_default_log()->logError( $sql . "\n" . implode( ', ', $bindParam) );
        }

        return $res;
    }

    // 返回多行数组
    public function selectData( $sql, $bindParam = array() )
    {
        $res = $this->executeSql($sql, $bindParam);
        if( !$res )
            return array();

        return $this->m_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * 插入数据
     * @access public
     * @param string $table 表名
     * @param array $fields 更新的字段名
     * @param array $values 更新的字段对应的值
     * @return int 成功：返回插入后的主键 id。失败：返回 0
     */
    public function insertData( $table, $fields, $values )
    {
        $sql = "INSERT INTO {$table} SET";
        for( $i = 0; $i < count($fields); $i++ )
        {
            if( 0 === $i )
                $sql = $sql . " {$fields[$i]} = ?";
            else
                $sql = $sql . ", {$fields[$i]} = ?";
        }

        if( !$this->executeSql($sql, $values) )
            return 0;

        return $this->getLastInsertID();
    }

    /**
     * 更新数据
     * @access public
     * @param string $table 表名
     * @param array $fields 更新的字段名
     * @param array $values 更新的字段对应的值
     * @param string $where WHERE 条件内容
     * @param array $whereValues WHERE 条件对应的值
     * @return bool
     */
    public function updateData( $table, $fields, $values, $where, $whereValues )
    {
        $sql = "UPDATE {$table} SET";
        for( $i = 0; $i < count($fields); $i++ )
        {
            if( 0 === $i )
                $sql = $sql . " {$fields[$i]} = ?";
            else
                $sql = $sql . ", {$fields[$i]} = ?";
        }

        $sql = $sql . " WHERE {$where}";

        $bindParam = array_merge( $values, $whereValues );
        return $this->executeSql($sql, $bindParam);
    }

    /**
     * 删除数据
     * @access public
     * @param string $table 表名
     * @param string $where WHERE 条件内容
     * @param array $whereValues WHERE 条件对应的值
     * @return bool
     */
    public function deleteData( $table, $where, $whereValues )
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->executeSql($sql, $whereValues);
    } 

    protected $m_pdo;
    protected $m_stmt;
    protected $m_lastInsertID;
}

