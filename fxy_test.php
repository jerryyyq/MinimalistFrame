<?php
include_once('fxy.php');
use minimum_frame\YLog;

echo '-', __NAMESPACE__, '-<br />', "\n"; 

comm_create_default_log();
comm_get_default_log()->setLogLevel( YLog::LEVEL_WARN );
echo comm_get_default_log()->getLogLevel( );
comm_get_default_log()->logError( 'fxy_test' );

comm_create_default_mysql( 'localhost', 'test', 'test', 'test' );
comm_create_default_memcache( '127.0.0.1', 11211, 24 * 3600 );

echo 'memcache version: ' . comm_get_default_memcache()->version(). "\n";

$mysql = comm_get_default_mysql();
$user = $mysql->selectDataEx('users', array('name', 'email'), array('lisi', 'lisi2@sina.com'));
print_r( $user );

$users = $mysql->selectDataEx('users');
print_r( $users );

comm_get_default_memcache()->setValue('users', $users);

$users2 = comm_get_default_memcache()->getValue('users');
print_r( $users2 );

$user = $users2[0];
$user['name'] = 'lisi';
$user['email'] = 'lisi3@sina.com';

$mysql->insertDataEx( 'users', $user, 'id' );
echo 'lisi insert id: ' . $mysql->getLastInsertID(). "\n";

$user['email'] = 'wangwu' . $user['id'] . '@sina.com';
$mysql->updateDataEx( 'users', $user, 'id' );

$user['name'] = 'delete';
$user['email'] = 'delete@sina.com';
$mysql->insertDataEx( 'users', $user, 'id' );
echo 'insert delete users id = ' . $user['id'] . "\n";

$mysql->deleteData( 'users', 'id = ?', array($user['id']) );

$_SERVER['HTTP_HOST'] = 'www.aaa.com/';
$_SERVER['SCRIPT_NAME'] = '/fjkdsl/jfdislajf/aaa.php';
echo comm_get_current_path_uri(), "\n";

?>