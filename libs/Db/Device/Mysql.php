<?php

/*
 * This file is part of the 'octris/db-mysql' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Db\Device;

/**
 * MySQL database device.
 *
 * @copyright   copyright (c) 2012-2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Mysql extends \Octris\Db\Device
{
    /**
     * Configuration of attempts a query should be executed, till a deadlock is actually
     * recognized and query is failing.
     */
    const DEADLOCK_ATTEMPTS = 5;

    /**
     * Constructor.
     *
     * @param   string          $host               Host of database server.
     * @param   int             $port               Port of database server.
     * @param   string          $database           Name of database.
     * @param   string          $username           Username to use for connection.
     * @param   string          $password           Optional password to use for connection.
     */
    public function __construct($host, $port, $database, $username, $password = '')
    {
        parent::__construct();

        $this->addHost(\Octris\Db::DB_MASTER, array(
            'host'     => $host,
            'port'     => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password
        ));
    }

    /**
     * Create database connection.
     *
     * @param   array                       $options                Host configuration options.
     * @return  \Octris\Db\Device\Mysql\Connection                  Connection to a mysql database.
     */
    public function createConnection(array $options)
    {
        $cn = new \Octris\Db\Device\Mysql\Connection($this, $options);

        return $cn;
    }
}
