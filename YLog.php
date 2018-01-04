<?php
// Author: 杨玉奇
// email: yangyuqi@sina.com
// copyright yangyuqi
// 著作权归作者 杨玉奇 所有。商业转载请联系作者获得授权，非商业转载请注明出处。
// date: 2018-01-02
// 默认日志文件路径在 /tmp/phplogs/ 目录下

namespace minimum_frame;

class YLog
{
	const LEVEL_DEBUG = 0;
	const LEVEL_INFO = 1;
	const LEVEL_WARN = 2;
	const LEVEL_ERROR = 3;
	const LEVEL_FATAL = 4;

	////////////////////////////////// 构造 与 属性 函数 ///////////////////////////////////
	
	public function __construct( $logLevel = YLog::LEVEL_DEBUG, $logRootPath = '/tmp/phplogs' )
	{
		$this->m_logLevel = $logLevel;
		$this->m_logRootPath = $logRootPath;
	}

	public function setLogLevel( $logLevel )
	{
		$this->m_logLevel = $logLevel;
	}
	public function getLogLevel( )
	{
		return $this->m_logLevel;
	}

	public function setLogRootPath( $logRootPath )
	{
		$this->m_logRootPath = $logRootPath;
	}
	public function getLogRootPath( )
	{
		return $this->m_logRootPath;
	}

	////////////////////////////////// 写 日志 函数 ///////////////////////////////////
	public function logDebug( $log )
	{
		if( $this->m_logLevel > YLog::LEVEL_DEBUG )
			return;
	
		$this->logDateItem($log, 'debug-');
	}
	
	public function logInfo( $log )
	{
		if( $this->m_logLevel > YLog::LEVEL_INFO )
			return;
	
		$this->logDateItem($log, 'info-');
	}
	
	public function logWarn( $log )
	{
		if( $this->m_logLevel > YLog::LEVEL_WARN )
			return;
	
		$this->logDateItem($log, 'warn-');
	}
	
	public function logError( $log )
	{
		if( $this->m_logLevel > YLog::LEVEL_ERROR )
			return;
	
		$this->logDateItem($log, 'error-');
	}
	
	public function logFatal( $log )
	{
		if( $this->m_logLevel > YLog::LEVEL_FATAL )
			return;
	
		$this->logDateItem($log, 'fatal-');
	}
	
	////////////////////////////////// 非 public 函数 ///////////////////////////////////
	protected function logDateItem( $log, $pre )
	{
		$curdate = date('Y-m-d-H');
		$curtime = date('Y-m-d H:i:s');
	
		$conts = $curtime . ' |' . comm_get_user_ip() . '| ' . $log . "\r\n";
		
		$file_path = $this->m_logRootPath . DIRECTORY_SEPARATOR . $pre . $curdate . '.log';
		$this->writeToFile($file_path, $conts);
	}
	
	protected function writeToFile($filePath, $data)
	{
		$dirName = dirname($filePath);
		// $res = mkdir( iconv('UTF-8', 'GBK', $dirName), 0777, true);
		if( !is_dir($dirName) )
		{
			$res = mkdir( $dirName, 0777, true);
			if( !$res )
			{
				throw new Exception( "Make dir: {$dirName} Fail!" ); 
			}
		}

		return file_put_contents($filePath, $data, FILE_APPEND);
	}

	private $m_logLevel;
	private $m_logRootPath;
}

?>