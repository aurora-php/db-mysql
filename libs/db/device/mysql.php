<?php

/*
 * This file is part of the 'octris/core' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace octris\core\db\device;

/**
 * MySQL database device.
 *
 * @octdoc      c:device/mysql
 * @copyright   copyright (c) 2012 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class mysql extends \octris\core\db\device
{
    /**
     * Configuration of attempts a query should be executed, till a deadlock is actually
     * recognized and query is failing.
     *
     * @octdoc  d:mysql/T_DEADLOCK_ATTEMPTS
     */
    const T_DEADLOCK_ATTEMPTS = 5;
    /**/

    /**
     * Host of database server.
     *
     * @octdoc  p:mysql/$host
     * @type    string
     */
    protected $host;
    /**/
    
    /**
     * Port of database server.
     *
     * @octdoc  p:mysql/$port
     * @type    int
     */
    protected $port;
    /**/

    /**
     * Name of database to connect to.
     *
     * @octdoc  p:mysql/$database
     * @type    string
     */
    protected $database;
    /**/

    /**
     * Username to use for connection.
     *
     * @octdoc  p:mysql/$username
     * @type    string
     */
    protected $username;
    /**/
    
    /**
     * Password to use for connection.
     *
     * @octdoc  p:mysql/$password
     * @type    string
     */
    protected $password;
    /**/

    /**
     * Constructor.
     *
     * @octdoc  m:mysql/__construct
     * @param   string          $host               Host of database server.
     * @param   int             $port               Port of database server.
     * @param   string          $database           Name of database.
     * @param   string          $username           Username to use for connection.
     * @param   string          $password           Optional password to use for connection.
     */
    public __construct($host, $port, $database, $username, $password = '')
    {
        $this->host     = $host;
        $this->port     = $port;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Create database connection.
     *
     * @octdoc  m:mysql/getConnection
     * @return  \octris\core\db\device\mysql\connection             Connection to a mysql database.
     */
    public getConnection()
    {
        $cn = new \octris\core\db\device\mysql\connection(
            array(
                'host'     => $this->host,
                'port'     => $this->port,
                'database' => $this->database,
                'username' => $this->username,
                'password' => $this->password
            )
        );

        return $cn;
    }
}

