<?php
// Author: 杨玉奇
// email: yangyuqi@sina.com
// copyright yangyuqi
// 著作权归作者 杨玉奇 所有。商业转载请联系作者获得授权，非商业转载请注明出处。
// date: 2018-01-02

namespace minimalist_frame;
use \Memcache;

class YMemcache
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
	public function __construct( $hostIP, $hostPort = 11211 )
	{
        $this->m_memcache = new Memcache;
        $res = $this->m_memcache->connect($hostIP, $hostPort);
        if( !$res )
        {
            throw new Exception( "Connect Memcache: {$hostIP}:{$hostPort} Fail!" );
        }
    }

    public function __destruct()
    {
        if( $this->m_memcache )
        {
            $this->m_memcache->close();
            $this->m_memcache = null;
        }
    }
    
    public function setValue( $mkey, $value, $expire = 3600 )
    {
        return $this->m_memcache->set($mkey, $value, 0, $expire);
    }

    public function getValue( $mkey )
    {
        return $this->m_memcache->get($mkey);
    }
    
    public function delete( $mkey )
    {
        return $this->m_memcache->delete($mkey);
    }

    public function version( )
    {
        return $this->m_memcache->getVersion();
    }

    protected $m_memcache;
}

?>