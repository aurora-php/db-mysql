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

namespace Octris\Db\Device;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/**
 * MySQL database device.
 *
 * @copyright   copyright (c) 2012-present by Harald Lapp
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
    public function __construct(array|\Octris\PropertyCollection $options)
    {
        parent::__construct();

        $this->addHost(Type::MASTER, $options);
    }

    /**
     * {@inheritDoc}
     */
    protected function validateOptions(\Octris\PropertyCollection $options): bool
    {
        if ($options->has('host')) {
            // todo
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function createConnection(\Octris\PropertyCollection $options): Mysql\Connection
    {
        $cn = new Mysql\Connection($this, $options);

        return $cn;
    }
}
