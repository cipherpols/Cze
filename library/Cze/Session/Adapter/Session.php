<?php
/**
 * File Session.php
 *
 * @author Tuan Duong <duongthaso@gmail.com>
 * @package Cze
 * @version 1.0
 */
namespace Cze\Session\Adapter;

/**
 * Class Session - store session in default
 * @package Cze\Session\Adapter
 */
class Session implements SessionAdapterInterface
{
    /**
     * @var \Zend_Session_Namespace
     */
    private $sessionHandler;

    /**
     * Session constructor.
     * @param string $name
     */
    public function __construct($name)
    {
        $this->sessionHandler = new \Zend_Session_Namespace($name);
    }

    /**
     * @inheritdoc
     */
    public function open($path)
    {
    }

    /**
     * @inheritdoc
     */
    public function load($id)
    {
        return $this->sessionHandler->{$id};
    }

    /**
     * @inheritdoc
     */
    public function save($data, $id, $tags = array(), $lifeTime)
    {
        return $this->sessionHandler->{$id} = $data;
    }

    /**
     * @inheritdoc
     */
    public function remove($id)
    {
        unset($this->sessionHandler->{$id});
    }
}
