<?php
// Author: 杨玉奇
// email: yangyuqi@sina.com
// copyright yangyuqi
// 著作权归作者 杨玉奇 所有。商业转载请联系作者获得授权，非商业转载请注明出处。
// date: 2018-01-02

/** php.ini 配置
* 1 使用 PDO
* extension=php_pdo_mssql.dll 去掉前面的 ";" 号
* 
* 2 打开php的安全模式
* safe_mode = on
* safe_mode_gid = off
* safe_mode_exec_dir = 我们网页目录，例如：D:/usr/www
*
* 3 控制php脚本能访问的目录
* open_basedir = D:/usr/www
*
* 4 关闭危险函数
* disable_functions = system,passthru,exec,shell_exec,popen,phpinfo
*
* 5 关闭PHP版本信息在http头中的泄漏
* expose_php = Off
*
* 6 防止SQL注入，自动把提交的查询进行转换，例如：把 ' 转为 \'等
* magic_quotes_gpc = On
*
* 7 禁止错误提示
* display_errors = Off
* log_errors = On
* error_log = D:/usr/local/apache2/logs/php_error.log
*
*/

use minimum_frame\YLog;
use minimum_frame\YMySql;
use minimum_frame\YMemcache;

require_once('YLog.php');
require_once('YMySql.php');
require_once('YMemcache.php');

$g_YLog = null;
$g_YMySql = null;
$g_YMemcache = null;
$g_run_config = array('log_io' => false);

define('PBKDF2_ITERATIONS', 1000);
define('PBKDF2_LENGTH', 512);


//////////////////////////////////  全局默认对象 函数 ///////////////////////////////////
function comm_create_default_log()
{
    global $g_YLog;
    if( !$g_YLog )
    {
        $g_YLog = new YLog;
    }
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
}

function comm_get_default_memcache()
{
    global $g_YMemcache;
    return $g_YMemcache;
}

// 设置运行期配置
// 目前支持的有：'log_io'，默认为 false，如果设置为 true 可以以 logDebug 方式记录：调用方法、参数、返回值
function comm_set_run_config( $run_config )
{
    global $g_run_config;

    foreach( $run_config as $key => $value )
    {
        $g_run_config[$key] = $value;
    }
}

////////////////////////////////// 框架运行 主 函数 ///////////////////////////////////
/**
 * 框架主路由函数
 * ------- 所有的应答返回值都封装到 json 串中：{"err":0, "err_msg":"", "data":{}} ----------
 * err 为应答码，0 表示成功，其它值表示失败
 * err_msg 为具体错误信息
 * data 为返回的数据
 * @param array $route_functions 允许执行的路由函数名
 * @param string $parameter_method_name URL 中标识调用方法的变量名
 * @param string $parameter_args_name URL 中标识调用方法的参数的变量名( json 格式 )。也可以不写这个参数，而是把所有参数都用 post 方法提交
 * @return null 但会用 "json 返回值" 写页面内容
 */
function comm_frame_main( $route_functions, $parameter_method_name = 'm', $parameter_args_name = 'args' )
{
    global $g_run_config;
    // 创建全局 YLog 对象
    comm_create_default_log();

    // 定义返回值
    $result = array( 'err' => 0, 'err_msg' => '', 'data' => array() );
    
    // 设置跨域。此步骤不是必须
    if( 0 == comm_make_xcros() )
    {
        exit(0);
    }

    while(true)
    {
        if( !isset($_GET[$parameter_method_name]) )
        {
            $result['err'] = -1;
            $result['err_msg'] = 'URL parameter wrong';
            break;
        }
    
        $api_name = $_GET[$parameter_method_name];

        if( !$api_name || !in_array($api_name, $route_functions) || !function_exists($api_name) )
        {
            $result['err'] = -2;
            $result['err_msg'] = 'api_name wrong';
            break;
        }

        if( $g_run_config['log_io'] )
        {
            comm_get_default_log()->logDebug( 'api_name is: ' . $api_name );
        }

        $params = comm_get_parameters( $parameter_args_name );

        try
        { 
            $result = call_user_func( $api_name, $params );
        }
        catch( xception $e )
        {
            $result['err'] = -9;
            $result['err_msg'] = $e->getMessage();
        }

        break;
    }

    $output = json_encode($result, JSON_UNESCAPED_UNICODE);
    if( $g_run_config['log_io'] )
    {
        comm_get_default_log()->logDebug( 'output is: ' . $output );
    }

    echo $output;
}

/**
 * 内部函数。获取上传参数。首先以 GET 方法获取 'args' 参数，如果没获得，那么获取 body 中的参数
 * @param string $parameter_args_name URL 中标识调用方法的参数的变量名( json 格式 )。也可以不写这个参数，而是把所有参数都用 post 方法提交
 * @return array 返回参数数组
 */
function comm_get_parameters( $parameter_args_name = 'args' )
{
    global $g_run_config;

    if( $raw_arg = @$_REQUEST[$parameter_args_name] )
    {
        // 获取 url 后面的参数
        $url_decode_arg = urldecode($raw_arg);

        $input = str_replace('\\', '', $url_decode_arg);
    }
    else
    {
        // 获取 body 中的参数
        $input = @file_get_contents('php://input');
    }

    if( $g_run_config['log_io'] )
    {
        comm_get_default_log()->logDebug( 'input is: ' . $input );
    }

    $params = json_decode($input, true);
    return $params; 
}

/**
 * 检查是否需要的参数都存在
 * @param array $args 收到的参数
 * @param array $mast_exist_parameters 为一个必须存在的变量名数组
 * @param string $parameter_args_name URL 中标识调用方法的参数的变量名( json 格式 )。也可以不写这个参数，而是把所有参数都用 post 方法提交
 * @return array $result 成功 $result['err'] = 0，缺少参数，$result['err'] 为 -4, $result['data'] 为 缺少的参数名数组
 */
function comm_check_parameters( $args, $mast_exist_parameters )
{
    $result = array('err' => 0, 'err_msg' => '', 'data' => array() );
    foreach( $mast_exist_parameters as $k => $v )
    {
        if( !isset($args[$v]) )
            $result['data'][] = $v;
    }

    if( 0 < count($result['data']) )
    {
        $result['err'] = -3;
        $result['err_msg'] = 'Parameter incomplete';
    }

    return $result;
}

/**
 * 生成所有参数的 ‘校验和哈希值’
 * @param array $args 为参数数组
 * @param array $secret 为一个特殊值，可以每个用户分配一个唯一的串
 * @return string 校验和哈希值 
*/
function comm_generate_args_signature( $args, $secret = '' )
{
    ksort($args); // 所有参数按字母升序排列
    $data = $secret;
    foreach ($args as $k => $v) {
        if(is_null($v) || ('' == $v)){
            continue;
        }
        $data = $data . $k . $v;
    }

    return md5($data);
}

/**
 * 检查所有参数的 ‘校验和哈希值’
 * 注意：该函数会去除 args 中的 'signature' 元素！
 * @param array $args 为参数数组。$args['signature'] 必须存在，用于校验
 * @param array $secret 为一个特殊值，可以每个用户分配一个唯一的串
 * @return bool
*/
function comm_check_args_signature( $args, $secret = '' )
{
    if( isset($args['signature']) && !empty($args['signature']) )
    {
        $signature = $args['signature'];
        unset($args['signature']);
        $server_signature = comm_generate_args_signature($args, $secret);
        if( $signature != $server_signature )
            return false;
    }

    return true;
}

/**
 * 去掉 array 中不允许的键
 * @param array& $source： 需要处理的数组
 * @param array $allow_keys： 允许保留的 key
 * @return null
 */
function comm_set_array_limit( &$source, $allow_keys )
{
    foreach( $source as $key => $value )
    {
        if( !in_array($key, $allow_keys) )
        {
            unset( $source[$key] );
        }
    }
}

function comm_get_user_ip()
{
	$usrip = '';
	// iis
	if( !empty($_SERVER['REMOTE_ADDR']) )
		$usrip = $_SERVER['REMOTE_ADDR'];
	else if( !empty($_SERVER['HTTP_CLIENT_IP']) )
		$usrip = $_SERVER['HTTP_CLIENT_IP'];
	else if( !empty($_SERVER['HTTP_X_FORWARDED_FOR']) )
		$usrip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	else if( !empty($HTTP_SERVER_VARS['REMOTE_ADDR']) )
		$usrip = $HTTP_SERVER_VARS['REMOTE_ADDR'];

	return $usrip;
}

// 返回当前 URL 中协议和域名的部分
function comm_get_domain_uri()
{
    $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';  
    return $http_type . $_SERVER['HTTP_HOST'];
}

// 返回当前 URL 中协议和路径的部分
function comm_get_current_path_uri()
{
    $find = strrpos($_SERVER['SCRIPT_NAME'], '/');
    $path = substr($_SERVER['SCRIPT_NAME'], 0, $find + 1);
    return comm_get_domain_uri() . $path;
}

// 返回当前 URL 中不带参数的部分
function comm_get_current_page_uri()
{
    return comm_get_domain_uri() . $_SERVER['PHP_SELF'];
}

function comm_generate_guid()
{
    if( function_exists('com_create_guid') )
    {
        return com_create_guid();
    }
    else
    {
        mt_srand( (double)microtime() * 10000 );        // optional for php 4.2.0 and up.
        $charid = strtoupper( md5(uniqid(rand(), true)) );
        $hyphen = chr( 45 );      // '-'
        $uuid = substr( $charid, 0, 8 ) . $hyphen
            .substr( $charid, 8, 4 ) . $hyphen
            .substr( $charid, 12, 4 ) . $hyphen
            .substr( $charid, 16, 4 ) . $hyphen
            .substr( $charid, 20, 12 );

        return $uuid;
    }
}

/**
 * 使用 pbkdf2 算法，获得口令哈希值
 * @param string $password 原始口令
 * @param string& $salt 盐值，如果为 ''，会在函数内部生成一个，并写回此参数供调用者使用
 * @return string 口令哈希值
 */
function comm_get_password_hash( $password, &$salt )
{
    if( 1 > strlen($salt) )
    {
        $salt = mcrypt_create_iv(22, MCRYPT_DEV_URANDOM);
        $salt = base64_encode($salt);
        $salt = str_replace('+', '.', $salt);
    }

    return hash_pbkdf2('sha512', $password, $salt, PBKDF2_ITERATIONS, PBKDF2_LENGTH);    
}

// 跨域检查 pls move to common.php
function comm_make_xcros()
{
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : false;
    if( $referer )
    {
        $urls = parse_url($referer);
        $url = $urls['scheme'] . '://' . $urls['host'];
        isset($urls['port']) ? $url .= ':' . $urls['port'] : '';
    }
    else
    {
        $url = '*';
    }
    
    if( $_SERVER['REQUEST_METHOD'] == 'OPTIONS' )
    {
        header('HTTP/1.1 204 No Content');
        header('Access-Control-Allow-Origin: ' . $url);
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Length,Content-Type');
        header('Access-Control-Max-Age: 1728000');
        header('Content-Length: 0');
        return 0;
    }

    header('Access-Control-Allow-Origin: ' . $url);     // 跨域访问
    header('Access-Control-Allow-Credentials: true');
    return 1;
}

function comm_generate_verify_code($len)
{
    $chars_array = array(
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k',
        'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v',
        'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G',
        'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R',
        'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
    );
    $charsLen = count($chars_array) - 1;

    $outputstr = '';
    for( $i=0; $i<$len; $i++ )
    {
        $outputstr .= $chars_array[mt_rand(0, $charsLen)];
    }

    return $outputstr;
}

/**
 * 将 array 转为 xml
 * @param array $args
 */
function comm_array_to_xml( $args )
{
    if(!is_array($args) || count($args) <= 0)
    {
        return '';
    }
    
    $xml = "<xml>";
    foreach ( $args as $key => $val )
    {
        if (is_numeric($val))
        {
            $xml .= "<" . $key . ">" . $val ."</" . $key . ">";
        }
        else
        {
            $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
    }
    $xml .= "</xml>";
    return $xml; 
}

/**
 * 将 xml 转为 array
 * @param string $xml
 */
function comm_xml_to_array( $xml )
{	
    if( !$xml )
    {
        return array();
    }

    //将XML转为array
    //禁止引用外部xml实体
    libxml_disable_entity_loader(true);
    $args = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);		
    return $args;
}

?>
