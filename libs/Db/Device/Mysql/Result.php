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
 * Query result object.
 *
 * @copyright   copyright (c) 2016-present by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Result implements \Octris\Db\Device\ResultInterface
{
    /**
     * Instance of statement class.
     *
     * @type    \Octris\Db\Device\Mysql\Statement
     */
    protected $stmt;

    /**
     * Instance of database result class.
     *
     * @type    \mysqli_result
     */
    protected $result;

    /**
     * Cursor position.
     *
     * @type    int
     */
    protected $position = -1;

    /**
     * Valid result row.
     *
     * @type    bool
     */
    protected $valid;

    /**
     * Constructor.
     *
     * @param   \mysqli_result                         $result         Result instance.
     * @param   \Octris\Db\Device\Mysql\Statement      $stmt           Optional instance of prepared statement.
     */
    public function __construct(\mysqli_result $result, Statement $stmt = null)
    {
        $this->stmt = $stmt;
        $this->result = $result;

        $this->next();
    }

    /**
     * Count number of items in the result set.
     *
     * @return  int                                         Number of items in the result-set.
     */
    public function count(): int
    {
        return $this->result->num_rows;
    }

    /**
     * Return current item of the search result.
     *
     * @return  array                                       Row data.
     */
    public function current(): array
    {
        if ($this->valid) {
            $this->result->data_seek($this->position);

            $return = $this->result->fetch_assoc();
        } else {
            $return = false;
        }

        return $return;
    }

    /**
     * Advance cursor to the next item.
     */
    public function next(): void
    {
        $this->valid = (++$this->position < $this->result->num_rows);
    }

    /**
     * Returns the cursor position.
     *
     * @return  int                                      Cursor position.
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * Rewind cursor.
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * Tests if cursor position is valid.
     *
     * @return  bool                                        Returns true, if cursor position is valid.
     */
    public function valid(): bool
    {
        return $this->valid;
    }
}
