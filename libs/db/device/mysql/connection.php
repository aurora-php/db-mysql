<?php

/*
 * This file is part of the 'octris/core' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace octris\core\db\device\mysql {
    /**
     * MySQL connection handler.
     *
     * @octdoc      c:mysql/connection
     * @copyright   copyright (c) 2012 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class connection extends \mysqli implements \octris\core\db\device\connection_if, \octris\core\db\pool_if
    /**/
    {
        use \octris\core\db\pool_tr;

        /**
         * Constructor.
         *
         * @octdoc  m:connection/__construct
         * @param   array                       $options            Connection options.
         */
        public function __construct(array $options)
        /**/
        {
            parent::__construct($options['host'], $options['username'], $options['password'], $options['database'], $options['port']);

            if ($this->errno != 0) {
                throw new \Exception('unable to connect to host');
            }
        }

        /**
         * Release a connection.
         *
         * @octdoc  m:connection/release
         */
        public function release()
        /**/
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
         * to \octris\core\db\mysql::T_DEADLOCK_ATTEMPTS until a deadlock is considered
         * to be unresolvable.
         *
         * @octdoc  m:connection/query
         * @param   string              $sql                    SQL query to perform.
         * @return  \octris\core\db\mysql\result            Query result.
         */
        public function query($sql)
        /**/
        {
            for ($i = 0; $i < \octris\core\db\mysql::T_DEADLOCK_ATTEMPTS; ++$i) {
                $res = $this->real_query($sql);

                if ($res !== false || ($this->errno != 1205 && $this->errno != 1213)) {
                    break;
                }
            }

            if ($res === false) {
                throw new \Exception($this->error, $this->errno);
            }

            return new \octris\core\db\mysql\result($this);
        }

        /**
         * Performs asynchronous query.
         *
         * @octdoc  m:connection/asyncQuery
         * @param   string              $sql                    SQL query to perform.
         * @return  \octris\core\db\mysql\async             Asynchronous query object.
         */
        public function asyncQuery($sql)
        /**/
        {
            $this->query($sql, MYSQLI_ASYNC);

            $async = \octris\core\db\mysql\async::getInstance();
            $async->addLink($this);

            return $async;
        }

        /**
         * Execute one or multiple queries separated by a ';'.
         *
         * @octdoc  m:connection/multiQuery
         * @param   string              $sql                    SQL query to perform.
         */
        public function multiQuery($sql)
        /**/
        {
            for ($i = 0; $i < \octris\core\db\mysql::T_DEADLOCK_ATTEMPTS; ++$i) {
                $res = $this->multi_query($sql);

                if ($res !== false || ($this->errno != 1205 && $this->errno != 1213)) {
                    break;
                }
            }

            if ($res === false) {
                throw new \Exception($this->error, $this->errno);
            }

            return new \octris\core\db\mysql\result($this);
        }

        /**
         * Initialize prepared statement.
         *
         * @octdoc  m:connection/prepare
         * @param   string              $sql                    SQL query to prepare.
         * @return  \octris\core\db\mysql\statement         Instance of a prepared statement.
         */
        public function prepare($sql)
        /**/
        {
            $stmt = new \octris\core\db\mysql\statement($this, $sql);

            if ($stmt->errno > 0) {
               throw new \Exception($stmt->sqlstate . ' ' . $stmt->error, $stmt->errno);
            }
        
            return $stmt;
        }
    }
}
