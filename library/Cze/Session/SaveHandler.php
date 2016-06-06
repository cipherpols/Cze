<?php
/**
 * File SaveHandler.php
 *
 * @author Tuan Duong <duongthaso@gmail.com>
 * @package Cze
 * @version 1.0
 */
namespace Cze\Session;

use Cze\Session\Adapter\SessionAdapterInterface;

/**
 * Class SaveHandler
 * @package Cze\Session
 */
class SaveHandler implements \Zend_Session_SaveHandler_Interface
{
    const PREFIX_PATTERN = '%s_SESSION';
    const SESSION_TIMEOUT = 1800;
    const SESSION_NAME = 'cze_session';

    /**
     * @var SessionAdapterInterface
     */
    protected $sessionAdapter = null;

    /**
     * @var string
     */
    protected $sessionName = '';

    /**
     * @var int
     */
    protected $lifeTime = self::SESSION_TIMEOUT;

    /**
     * @param SessionAdapterInterface $sessionAdapter
     * @param int $lifeTime
     */
    public function __construct(SessionAdapterInterface $sessionAdapter, $lifeTime = self::SESSION_TIMEOUT)
    {
        $this->sessionAdapter = $sessionAdapter;
        $this->lifeTime = $lifeTime;
    }

    /**
     * Open Session - retrieve resources
     *
     * @param string $save_path
     * @param string $name
     * @return bool
     */
    public function open($save_path, $name)
    {
        $this->sessionName = $name;
        return true;
    }

    /**
     * Close Session - free resources
     *
     */
    public function close()
    {
    }

    /**
     * Read session data
     *
     * @param string $id
     * @return string|false
     */
    public function read($id)
    {
        $key = $this->sessionName . '_' . $id;

        return $this->sessionAdapter->load($key);
    }

    /**
     * Write Session - commit data to resource
     *
     * @param string $id
     * @param mixed $data
     * @return true
     */
    public function write($id, $data)
    {
        $key = $this->sessionName . '_' . $id;
        $this->sessionAdapter->save($data, $key, array(), $this->lifeTime);

        return true;
    }

    /**
     * Destroy Session - remove data from resource for
     * given session id
     *
     * @param string $id
     * @return bool
     */
    public function destroy($id)
    {
        $key = $this->sessionName . '_' . $id;
        $this->sessionAdapter->remove($key);

        return true;
    }

    /**
     * Garbage Collection - remove old session data older
     * than $maxlifetime (in seconds)
     *
     * @param int $maxlifetime
     * @return true
     */
    public function gc($maxlifetime)
    {
        return true;
    }

    /**
     * @return string
     */
    public static function getSessionPrefix()
    {
        return strtoupper(sprintf(static::PREFIX_PATTERN, 'CZE'));
    }
}
