<?php

/*
 * This file is part of the 'octris/core' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Octris\Core\Db\Device\Mysql;

/**
 * MySQL connection handler.
 *
 * @copyright   copyright (c) 2012 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Connection extends \mysqli implements \Octris\Core\Db\Device\IConnection, \octris\core\db\pool_if
{
    use \octris\core\db\pool_tr;

    /**
     * Constructor.
     *
     * @param   array                       $options            Connection options.
     */
    public function __construct(array $options)
    {
        parent::__construct($options['host'], $options['username'], $options['password'], $options['database'], $options['port']);

        if ($this->errno != 0) {
            throw new \Exception('unable to connect to host');
        }
    }

    /**
     * Release a connection.
     *
     */
    public function release()
    {
        if ($this->more_results()) {
            while ($this->next_result()) {
                $this->use_result()->close();
            }
        }

        $this->autocommit(true);

        parent::release();
    }

    /**
     * Query the database. The query will handle deadlocks and perform several tries up
     * to \Octris\Core\Db\Mysql::T_DEADLOCK_ATTEMPTS until a deadlock is considered
     * to be unresolvable.
     *
     * @param   string              $sql                    SQL query to perform.
     * @return  \octris\core\db\mysql\result            Query result.
     */
    public function query($sql)
    {
        for ($i = 0; $i < \Octris\Core\Db\Mysql::T_DEADLOCK_ATTEMPTS; ++$i) {
            $res = $this->real_query($sql);

            if ($res !== false || ($this->errno != 1205 && $this->errno != 1213)) {
                break;
            }
        }

        if ($res === false) {
            throw new \Exception($this->error, $this->errno);
        }

        return new \Octris\Core\Db\Mysql\Result($this);
    }

    /**
     * Performs asynchronous query.
     *
     * @param   string              $sql                    SQL query to perform.
     * @return  \octris\core\db\mysql\async             Asynchronous query object.
     */
    public function asyncQuery($sql)
    {
        $this->query($sql, MYSQLI_ASYNC);

        $async = \Octris\Core\Db\Mysql\Async::getInstance();
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
        for ($i = 0; $i < \Octris\Core\Db\Mysql::T_DEADLOCK_ATTEMPTS; ++$i) {
            $res = $this->multi_query($sql);

            if ($res !== false || ($this->errno != 1205 && $this->errno != 1213)) {
                break;
            }
        }

        if ($res === false) {
            throw new \Exception($this->error, $this->errno);
        }

        return new \Octris\Core\Db\Mysql\Result($this);
    }

    /**
     * Initialize prepared statement.
     *
     * @param   string              $sql                    SQL query to prepare.
     * @return  \octris\core\db\mysql\statement         Instance of a prepared statement.
     */
    public function prepare($sql)
    {
        $stmt = new \Octris\Core\Db\Mysql\Statement($this, $sql);

        if ($stmt->errno > 0) {
           throw new \Exception($stmt->sqlstate . ' ' . $stmt->error, $stmt->errno);
        }

        return $stmt;
    }
}
