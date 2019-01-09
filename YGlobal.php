<?php
// Author: 杨玉奇
// email: yangyuqi@sina.com
// copyright yangyuqi
// 著作权归作者 杨玉奇 所有。商业转载请联系作者获得授权，非商业转载请注明出处。
// date: 2019-01-09

require_once('YLog.php');
require_once('YMySql.php');
require_once('YMemcache.php');

use minimum_frame\YLog;
use minimum_frame\YMySql;
use minimum_frame\YMemcache;

$g_run_config = array('log_io' => false, 'cross_origin' => false, 'sql_injecte_loose' => false);

$g_YLog = null;
$g_YMySql = null;
$g_YMemcache = null;

//////////////////////////////////  全局默认对象 函数 ///////////////////////////////////
function comm_create_default_log()
{
    global $g_YLog;
    if( !$g_YLog )
    {
        $g_YLog = new YLog;
    }
    return $g_YLog;
}

function comm_get_default_log()
{
    global $g_YLog;
    return $g_YLog;
}

function comm_create_default_mysql( $hostName, $dbName, $userName, $password, $hostPort = 3306 )
{
    global $g_YMySql;
    if( $g_YMySql )
    {
        $g_YMySql = null;
    }

    $g_YMySql = new YMySql( $hostName, $dbName, $userName, $password, $hostPort );
    return $g_YMySql;
}

function comm_get_default_mysql()
{
    global $g_YMySql;
    return $g_YMySql;
}

/**
 * 构造默认全局 YMemcache 对象。设置 YMemcache 连接信息
 * @param string $hostIP 服务器 IP 地址
 * @param string $hostPort 服务器端口号
 * @param int $expire 默认过期时间（秒）。小于 0 无效。
 * @return null
 */
function comm_create_default_memcache( $hostIP, $hostPort = 11211, $expire = 0 )
{
    global $g_YMemcache;
    if( $g_YMemcache )
    {
        $g_YMemcache = null;
    }

    $g_YMemcache = new YMemcache( $hostIP, $hostPort, $expire );
    return $g_YMemcache;
}

function comm_get_default_memcache()
{
    global $g_YMemcache;
    return $g_YMemcache;
}

/**
 * 设置运行期配置
 * @param array $run_config
 * 目前支持的有：
 * 1 'log_io'，默认为 false，如果设置为 true 会以 logDebug 方式记录：调用方法、参数、返回值。
 * 2 'cross_origin', 默认为 false，如果设置为 true 会允许跨域访问。
 * 3 'sql_injecte_loose', 默认为 false，以严格模式检查 SQL 语句，要求运算符右边必须是 '?'；
 *        如果设置为 true 会以宽松模式检查 SQL 语句，只要运算符右侧不是数字就可以通过。
 * 
 * @return null
 */
function comm_set_run_config( $run_config )
{
    global $g_run_config;

    foreach( $run_config as $key => $value )
    {
        $g_run_config[$key] = $value;
    }
}

// 创建全局 YLog 对象
comm_create_default_log();