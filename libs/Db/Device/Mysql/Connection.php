<?php

/*
 * This file is part of the 'octris/db-mysql' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Db\Device\Mysql;

/**
 * MySQL connection handler.
 *
 * @copyright   copyright (c) 2012-2018 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Connection extends \mysqli implements \Octris\Db\Device\IConnection
{
    /**
     * Device the connection belongs to.
     *
     * @type    \Octris\Db\Device\Mysql
     */
    protected $device;

    /**
     * Constructor.
     *
     * @param   \Octris\Db\Device\Mysql         $device             Device the connection belongs to.
     * @param   array                           $options            Connection options.
     */
    public function __construct(\Octris\Db\Device\Mysql $device, array $options)
    {
        $this->device = $device;

        parent::__construct($options['host'], $options['username'], $options['password'], $options['database'], $options['port']);

        if ($this->errno != 0) {
            throw new \Exception('unable to connect to host');
        }
    }

    /**
     * Release a connection.
     */
    public function release()
    {
        if ($this->more_results()) {
            while ($this->next_result()) {
                $this->use_result()->close();
            }
        }

        $this->autocommit(true);

        $this->device->release($this);
    }

    /**
     * Check availability of a connection.
     *
     * @return  bool                                        Returns true if connection is alive.
     */
    public function isAlive()
    {
        return $this->ping();
    }

    /**
     * Resolve a database reference.
     *
     * @param   \Octris\Db\Type\DbRef                               $dbref      Database reference to resolve.
     * @return  bool                                                            Returns false always due to missing implementagtion.
     * @todo    Add implementation.
     */
    public function resolve(\Octris\Db\Type\DbRef $dbref)
    {
        return false;
    }

    /**
     * Return instance of collection object.
     *
     * @param   string          $name                               Name of collection to return instance of.
     * @return  \Octris\Db\Device\Mysql\Collection                  Instance of a MySQL collection.
     * @todo    Add implementation.
     */
    public function getCollection($name)
    {
    }

    /**
     * Query the database. The query will handle deadlocks and perform several tries up
     * to \Octris\Db\Device\Mysql::DEADLOCK_ATTEMPTS until a deadlock is considered
     * to be unresolvable.
     *
     * @param   string              $sql                    SQL query to perform.
     * @return  \Octris\Db\Device\Mysql\Result              Query result.
     */
    public function query($sql, $resultmode = NULL)
    {
        for ($i = 0; $i < \Octris\Db\Device\Mysql::DEADLOCK_ATTEMPTS; ++$i) {
            $res = $this->real_query($sql);

            if ($res !== false || ($this->errno != 1205 && $this->errno != 1213)) {
                break;
            }
        }

        if ($res === false) {
            throw new \Exception($this->error, $this->errno);
        }

        return new \Octris\Db\Device\Mysql\Result(new \mysqli_result($this, MYSQLI_STORE_RESULT));
    }

    /**
     * Performs asynchronous query.
     *
     * @param   string              $sql                    SQL query to perform.
     * @return  \Octris\Db\Device\Mysql\Async               Asynchronous query object.
     */
    public function asyncQuery($sql)
    {
        $this->query($sql, MYSQLI_ASYNC);

        $async = \Octris\Db\Device\Mysql\Async::getInstance();
        $async->addLink($this);

        return $async;
    }

    /**
     * Execute one or multiple queries separated by a ';'.
     *
     * @param   string              $sql                    SQL query to perform.
     */
    public function multiQuery($sql)
    {
        for ($i = 0; $i < \Octris\Db\Device\Mysql::DEADLOCK_ATTEMPTS; ++$i) {
            $res = $this->multi_query($sql);

            if ($res !== false || ($this->errno != 1205 && $this->errno != 1213)) {
                break;
            }
        }

        if ($res === false) {
            throw new \Exception($this->error, $this->errno);
        }

        return new \Octris\Db\Device\Mysql\Result(new \mysqli_result($this, MYSQLI_STORE_RESULT));
    }

    /**
     * Initialize prepared statement.
     *
     * @param   string              $sql                    SQL query to prepare.
     * @return  \Octris\Db\Device\Mysql\Statement           Instance of a prepared statement.
     */
    public function prepare($sql)
    {
        $stmt = new \Octris\Db\Device\Mysql\Statement($this, $sql);

        if ($stmt->errno > 0) {
            throw new \Exception($stmt->sqlstate . ' ' . $stmt->error, $stmt->errno);
        }

        return $stmt;
    }
}
