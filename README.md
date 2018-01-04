# minimum_frame
一个极简单的、面向过程的、以 json 为输入输出的 php 框架。 在 yyq_minimalist_frame 基础上做了基础组件对象化，并且将配置文件从组件中剥离了出来。

* 包含的功能如下：
  * 数据库支持
  * Memcache 支持
  * log 支持
  * 函数路由
  * 方法名检查
  * 参数检查
  * 参数签名检查
  * password 标准生成方法
  * 以 json 封装的统一格式的输入与输出

* composer 注册在： https://packagist.org/packages/yyq/minimum_frame

## URL 调用接口
一个 location 支持多个 api 调用，调用的 api 名称由 URL 参数 m 标明，调用参数统一以 json 格式写到 agrs 参数中，格式为：  
http://域名/myapi?m=api名&args={"aaa":1,"bbb":2}  
例如：http://my.com/myapi?m=test2&args={"aaa":1,"bbb":2}

## 返回值
* 所有的应答返回值都封装到 json 串中，格式如下：{"err":0, "err_msg":"", "data":{}}
    * err 为错误码，0 表示成功，其它值表示失败。应用错误码最好从 -100 开始。
    * err_msg 为具体错误信息
    * data 为返回的数据，具体名称由各自的数据决定
* 框架已用错误码：  

| 错误码 | 含义 |
| ---- | ---- |
| 0 | 成功 |
| -1 | URL 参数错误 |
| -2 | 调用方法名错误 |
| -3 | 缺失必须的参数 |
| -4 | Signature 校验失败 |

| -9 | 执行错误 |


## 开发环境需要安装 composer
```
$ sudo apt install composer
```

## 使用方法
1. 建立工程目录，到工程目录下，编写： composer.json
```
{
    "require": {
        "yyq/minimum_frame": ">=0.1.0"
    }
}
```

2. 执行命令安装组件
```shell
$ composer install
更新可以执行：
$ composer update
```
如果报错缺：php_xmlrpc  
可以安装：$ sudo apt install php7.0-xmlrpc  
如果被墙，可以按下面的命令使用 composer 国内镜像：  
```shell
$ composer config repo.packagist composer https://packagist.phpcomposer.com
$ composer clearcache
$ composer install
```


3. 编写 test.php
``` php
<?php
require 'vendor/autoload.php';
// 上面为标准写法，也可以替换为下面的：
// require_once('vendor/yyq/minimum_frame/fxy.php');

$allowed_funtion = array(
    'test',
    'test2'
);

// 如果需要设置时区，可以在这里调用
date_default_timezone_set('Asia/Shanghai');

// 如果需要使用数据库，可以在这里配置
comm_create_default_mysql( $hostName, $dbName, $userName, $password, $hostPort = 3306 );

// 如果需要使用 Memcache，可以在这里配置
comm_create_default_memcache( $hostIP, $hostPort = 11211 );

// 如果需要 session 需要把这行写到 comm_frame_main 函数前；如果不需要可以不写。
session_start();

// 调用主路由函数
comm_frame_main( $allowed_funtion );

// 以下为实现自己的功能函数
function test( $args )
{
    $result = comm_check_parameters( $args, array('email', 'password', 'signature') );
    if( 0 != $result['err'] )
        return $result;

    // 有签名参数，校验签名值
    if( !comm_check_args_signature( $args )
    {
        $result['err'] = -4;
        $result['err_msg'] = 'Signature 校验失败';
        return $result;
    }

    // do something...

    // 使用数据库
    $mysql = comm_get_default_mysql();
    $users = $mysql->selectDataEx( 'user', array('id', 'name'), array(1, 'yyq') );

    // 使用 Memcache
    comm_get_default_memcache()->memSetValue('users', $users);

    // 使用 Log
    comm_get_default_log()->setLogLevel( YLog::LEVEL_WARN );

    return $result;
}

function test2( $args )
{
    $result = comm_check_parameters( $args, array('aaa', 'bbb') );
    if( 0 != $result['err'] )
        return $result;

    // do something...

    return $result;
}

?>
```

4. 配置  
框架可以配置一套默认的全局 YMysql、YMemcache、YLog 对象，其中 YLog 已默认创建。
YMysql 与 YMemcache 需要调用 

## 文件说明
* fxy.php 主功能文件，包含有 comm_frame_main 等函数
* YMySql.php 对 Mysql 功能的类实现
* YMemcache.php 对 Memcache 功能的类实现
* YLog.php 对日志功能的类实现

