<?php
/**
 * File SessionAdapterInterface.php
 *
 * @author Tuan Duong <duongthaso@gmail.com>
 * @package Cze
 * @version 1.0
 */
namespace Cze\Session\Adapter;

/**
 * Interface SessionAdapterInterface
 * @package Cze\Session\Adapter
 */
interface SessionAdapterInterface
{
    /**
     * Open Session
     *
     * @param string $savePath
     * @return mixed
     */
    public function open($savePath);

    /**
     * Load session data
     *
     * @param  string $id
     * @return mixed
     */
    public function load($id);

    /**
     * Save session data
     * @param mixed $data
     * @param string $id
     * @param array $tags
     * @param int $lifeTime
     * @return mixed
     */
    public function save($data, $id, $tags = array(), $lifeTime);

    /**
     * Remove session data
     *
     * @param $id
     * @return mixed
     */
    public function remove($id);
}
