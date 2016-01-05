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
 * Query result object.
 *
 * @copyright   copyright (c) 2016 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Result implements \Iterator, \Countable
{
    /**
     * Database object that instantiated this class.
     *
     * @type    \Octris\Core\Db\Device\Mysql\Connection|\Octris\Core\Db\Device\Mysql\Statement
     */
    protected $link;

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
     * Cache for rewinding cursor.
     *
     * @type    array
     */
    protected $cache = array();

    /**
     * Valid result row.
     *
     * @type    bool
     */
    protected $valid;

    /**
     * Constructor.
     *
     * @param   \Octris\Core\Db\Device\Mysql\Connection|\Octris\Core\Db\Device\Mysql\Statement     $link       Database link.
     */
    public function __construct($link)
    {
        $this->link = $link;

        if ($link instanceof \Octris\Core\Db\Device\Mysql\Statement) {
            // was constructed from a prepared statement - collect result set fields
            if (!($metadata = $link->result_metadata())) {
                return;
            }

            $metadata->fetch_fields();

            var_dump($metadata);
        } elseif ($link instanceof \Octris\Core\Db\Device\Mysql\Connection) {
            // was constructed from a query
            $this->result = new \mysqli_result($link, MYSQLI_STORE_RESULT);
        } else {
            throw new \InvalidArgumentException('Invalid object type');
        }

        $this->next();
    }

    /**
     * Count number of items in the result set.
     *
     * @return  int                                         Number of items in the result-set.
     */
    public function count()
    {
        return $this->result->num_rows;
    }

    /**
     * Return current item of the search result.
     *
     * @return  array                                       Row data.
     */
    public function current()
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
    public function next()
    {
        $this->valid = (++$this->position < $this->result->num_rows);
    }

    /**
     * Returns the cursor position.
     *
     * @return  int                                      Cursor position.
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Rewind cursor.
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * Tests if cursor position is valid.
     *
     * @return  bool                                        Returns true, if cursor position is valid.
     */
    public function valid()
    {
        return $this->valid;
    }
}
