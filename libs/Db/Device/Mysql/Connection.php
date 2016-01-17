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
 * @copyright   copyright (c) 2012-2016 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Connection extends \mysqli implements \Octris\Core\Db\Device\IConnection, \Octris\Core\Db\Device\IDialect
{
    /**
     * Device the connection belongs to.
     *
     * @type    \Octris\Core\Db\Device\Mysql
     */
    protected $device;

    /**
     * Constructor.
     *
     * @param   \Octris\Core\Db\Device\Mysql    $device             Device the connection belongs to.
     * @param   array                           $options            Connection options.
     */
    public function __construct(\Octris\Core\Db\Device\Mysql $device, array $options)
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
     * @param   \Octris\Core\Db\Type\DbRef                          $dbref      Database reference to resolve.
     * @return  bool                                                            Returns false always due to missing implementagtion.
     * @todo    Add implementation.
     */
    public function resolve(\Octris\Core\Db\Type\DbRef $dbref)
    {
        return false;
    }

    /**
     * Return instance of collection object.
     *
     * @param   string          $name                               Name of collection to return instance of.
     * @return  \Octris\Core\Db\Device\Mysql\Collection             Instance of a MySQL collection.
     * @todo    Add implementation.
     */
    public function getCollection($name)
    {
    }

    /**
     * Query the database. The query will handle deadlocks and perform several tries up
     * to \Octris\Core\Db\Device\Mysql::DEADLOCK_ATTEMPTS until a deadlock is considered
     * to be unresolvable.
     *
     * @param   string              $sql                    SQL query to perform.
     * @return  \Octris\Core\Db\Device\Mysql\Result         Query result.
     */
    public function query($sql)
    {
        for ($i = 0; $i < \Octris\Core\Db\Device\Mysql::DEADLOCK_ATTEMPTS; ++$i) {
            $res = $this->real_query($sql);

            if ($res !== false || ($this->errno != 1205 && $this->errno != 1213)) {
                break;
            }
        }

        if ($res === false) {
            throw new \Exception($this->error, $this->errno);
        }

        return new \Octris\Core\Db\Device\Mysql\Result(new \mysqli_result($this, MYSQLI_STORE_RESULT));
    }

    /**
     * Performs asynchronous query.
     *
     * @param   string              $sql                    SQL query to perform.
     * @return  \Octris\Core\Db\Device\Mysql\Async          Asynchronous query object.
     */
    public function asyncQuery($sql)
    {
        $this->query($sql, MYSQLI_ASYNC);

        $async = \Octris\Core\Db\Device\Mysql\Async::getInstance();
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
        for ($i = 0; $i < \Octris\Core\Db\Device\Mysql::DEADLOCK_ATTEMPTS; ++$i) {
            $res = $this->multi_query($sql);

            if ($res !== false || ($this->errno != 1205 && $this->errno != 1213)) {
                break;
            }
        }

        if ($res === false) {
            throw new \Exception($this->error, $this->errno);
        }

        return new \Octris\Core\Db\Device\Mysql\Result(new \mysqli_result($this, MYSQLI_STORE_RESULT));
    }

    /**
     * Initialize prepared statement.
     *
     * @param   string              $sql                    SQL query to prepare.
     * @return  \Octris\Core\Db\Device\Mysql\Statement      Instance of a prepared statement.
     */
    public function prepare($sql)
    {
        $stmt = new \Octris\Core\Db\Device\Mysql\Statement($this, $sql);

        if ($stmt->errno > 0) {
            throw new \Exception($stmt->sqlstate . ' ' . $stmt->error, $stmt->errno);
        }

        return $stmt;
    }

    /** Interface: IDialect **/

    /**
     * Return LIMIT string.
     *
     * @param   int             $limit                          Limit rows.
     * @param   int             $offset                         Optional offset.
     */
    public function getLimitString($limit, $offset = null)
    {
        $return = sprintf('LIMIT %d', $limit);

        if (!is_null($offset)) {
            $return .= sprintf(' OFFSET %d', $offset);
        }

        return $return;
    }
}
