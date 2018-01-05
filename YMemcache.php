<?php
// Author: 杨玉奇
// email: yangyuqi@sina.com
// copyright yangyuqi
// 著作权归作者 杨玉奇 所有。商业转载请联系作者获得授权，非商业转载请注明出处。
// date: 2018-01-02

namespace minimum_frame;
use \Memcache;

class YMemcache
{
	////////////////////////////////// 构造 与 属性 函数 /////////////////////////////////// 
    /**
     * 构造对象。设置 Memcache 连接信息
     * @access public
     * @param string $hostIP 服务器 IP 地址
     * @param string $hostPort 服务器端口号
     * @param int $expire 默认过期时间（秒）。小于 0 无效。
     * @return null
     */
	public function __construct( $hostIP, $hostPort = 11211, $expire = 0 )
	{
        $this->m_memcache = new Memcache;
        $res = $this->m_memcache->connect($hostIP, $hostPort);
        if( !$res )
        {
            throw new Exception( "Connect Memcache: {$hostIP}:{$hostPort} Fail!" );
        }

        $this->setDefaultExpire( $expire );
    }

    public function __destruct()
    {
        if( $this->m_memcache )
        {
            $this->m_memcache->close();
            $this->m_memcache = null;
        }
    }

    /**
     * 设置默认过期时间
     * @access public
     * @param int $expire 默认过期时间（秒）。小于 0 无效。
     * @return null
     */
    public function setDefaultExpire( $expire )
    {
        if( 0 < intval($expire) )
        {
            return;
        }

        $this->m_expire = intval($expire);
    }

    public function getDefaultExpire( )
    {
        return $this->m_expire;
    }

    /**
     * 设置 key value
     * @access public
     * @param string $mkey 服务器 IP 地址
     * @param string $hostPort 服务器端口号
     * @param int $expire 过期时间（秒）。如果是 -1 则使用 DefaultExpire 值。
     * @return null
     */
    public function setValue( $mkey, $value, $expire = -1 )
    {
        if( 0 > intval($expire) )
            $expire = $this->m_expire;

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
    protected $m_expire;
}

?>