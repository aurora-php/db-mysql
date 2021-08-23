<?php

/*
 * This file is part of the 'octris/db-mysql' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Octris\Db\Device\Mysql;

/**
 * MySQL connection handler.
 *
 * @copyright   copyright (c) 2012-present by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Connection implements \Octris\Db\Device\ConnectionInterface
{
    protected \mysqli $mysqli;

    protected \Octris\PropertyCollection $options;

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
     * @param   \Octris\PropertyCollection      $options            Connection options.
     */
    public function __construct(\Octris\Db\Device\Mysql $device, \Octris\PropertyCollection $options)
    {
        $this->device = $device;

        $this->mysqli = new \mysqli(
            $options->get('host'),
            $options->get('username'),
            $options->get('password'),
            $options->get('database'),
            $options->get('port')
        );

        $this->options = $options;

        if ($this->mysqli->errno != 0) {
            throw new \Exception('unable to connect to host');
        }
    }

    /**
     * Access mysqli properties:
     * affected_rows, errno, error_list, error, field_count,
     * insert_id, num_rows, param_count, sqlstate
     *
     * @param   string          $name               Name of property to return.
     * @return  mixed                               Value of property.
     */
    public function __get(string $name): mixed
    {
        switch ($name) {
            case 'affected_rows':
            case 'errno':
            case 'error_list':
            case 'error':
            case 'field_count':
            case 'insert_id':
            case 'num_rows':
            case 'param_count':
            case 'sqlstate':
                $return = $this->mysqli->{$name};
                break;
            default:
                throw new \InvalidArgumentException('Undefined property: ' . __CLASS__ . '::$' . $name);
        }

        return $return;
    }

    /**
     * Whether pooling is enabled.
     *
     * @return bool
     */
    public function doPooling(): bool
    {
        return !!($this->options->get('pooling', 0));
    }

    /**
     * Close connection.
     */
    public function close(): void
    {
        $this->mysqli->close();
    }

    /**
     * Release a connection.
     */
    public function release(): void
    {
        if ($this->mysqli->more_results()) {
            while ($this->mysqli->next_result()) {
                $this->mysqli->use_result()->close();
            }
        }

        $this->mysqli->autocommit(true);

        $this->device->release($this);
    }

    public function beginTransaction(): void
    {
        $this->mysqli->autocommit(false);
    }

    public function endTransaction(): void
    {
        $this->mysqli->autocommit(true);
    }

    public function rollback(): void
    {
        $this->mysqli->rollback();
    }

    /**
     * Check availability of a connection.
     *
     * @return  bool                                        Returns true if connection is alive.
     */
    public function isAlive(): bool
    {
        return $this->mysqli->ping();
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
    public function query(string $sql, int $resultmode = NULL): \Octris\Db\Device\Mysql\Result
    {
        for ($i = 0; $i < \Octris\Db\Device\Mysql::DEADLOCK_ATTEMPTS; ++$i) {
            $res = $this->mysqli->real_query($sql);

            if ($res !== false || ($this->mysqli->errno != 1205 && $this->mysqli->errno != 1213)) {
                break;
            }
        }

        if ($res === false) {
            throw new \Exception($this->mysqli->error, $this->mysqli->errno);
        }

        return new \Octris\Db\Device\Mysql\Result(new \mysqli_result($this->mysqli, MYSQLI_STORE_RESULT));
    }

    /**
     * Performs asynchronous query.
     *
     * @param   string              $sql                    SQL query to perform.
     * @return  \Octris\Db\Device\Mysql\Async               Asynchronous query object.
     */
    public function asyncQuery(string $sql)
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
    public function multiQuery(string $sql)
    {
        for ($i = 0; $i < \Octris\Db\Device\Mysql::DEADLOCK_ATTEMPTS; ++$i) {
            $res = $this->mysqli->multi_query($sql);

            if ($res !== false || ($this->mysqli->errno != 1205 && $this->mysqli->errno != 1213)) {
                break;
            }
        }

        if ($res === false) {
            throw new \Exception($this->mysqli->error, $this->mysqli->errno);
        }

        return new \Octris\Db\Device\Mysql\Result(new \mysqli_result($this->mysqli, MYSQLI_STORE_RESULT));
    }

    /**
     * Initialize prepared statement.
     *
     * @param   string              $sql                    SQL query to prepare.
     * @return  \Octris\Db\Device\Mysql\Statement           Instance of a prepared statement.
     */
    public function prepare(string $sql): Statement
    {
        $stmt = new \Octris\Db\Device\Mysql\Statement($this->mysqli, $sql);

        if ($stmt->errno > 0) {
            throw new \Exception($stmt->sqlstate . ' ' . $stmt->error, $stmt->errno);
        }

        return $stmt;
    }
}
