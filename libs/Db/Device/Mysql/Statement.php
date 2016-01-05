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
 * MySQL prepared statement.
 *
 * @copyright   copyright (c) 2012-2014 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
class Statement
{
    /**
     * Instance of mysqli_stmt class.
     *
     * @type    \mysqli_stmt
     */
    protected $instance;

    /**
     * Constructor.
     *
     * @param   \mysqli         $link               Database connection.
     * @param   string          $sql                SQL statement.
     */
    public function __construct(\mysqli $link, $sql)
    {
        $this->instance = new \mysqli_stmt($link, $sql);
    }

    /**
     * Magic getter.
     *
     * @param   string          $name               Name of property to return.
     * @return  mixed                               Value of property.
     */
    public function __get($name)
    {
        switch ($name) {
            case 'errno':
            case 'error':
                $return = $this->instance->{$name};
                break;
            default:
                throw new \InvalidArgumentException('Undefined property: ' . __CLASS__ . '::$' . $name);
        }
    }

    /**
     * Returns number of parameters in statement.
     *
     * @return  int                                 Number of parameters.
     */
    public function paramCount()
    {
        return $this->instance->param_count;
    }

    /**
     * Bind parameters to statement.
     *
     * @param   string          $types              String of type identifiers.
     * @param   array           $values             Array of values to bind.
     */
    public function bindParam($types, array $values)
    {
        if (preg_match('/[^idsb]/', $types)) {
            throw new \InvalidArgumentException('Unknown data type in "' . $types . '"');
        } elseif (($cnt2 = strlen($types)) != ($cnt1 = count($values))) {
            throw new \InvalidArgumentException(
                'Number of specified types (%d) and number of specified values (%d) does not match',
                $cnt2,
                $cnt1
            );
        } elseif ($cnt1 != ($cnt2 = $this->paramCount())) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Number of specified parameters (%d) does not match required parameters (%d)',
                    $cnt1,
                    $cnt2
                )
            );
        } else {
            array_unshift($values, $types);

            $this->instance->bind_param(...$values);
        }
    }

    /**
     * Return metadata of result.
     *
     * @return  \mysqli_result|null
     */
    public function getResultMetadata()
    {
        return $this->instance->result_metadata();
    }

    /**
     * Execute the statement.
     *
     * @return  \Octris\Core\Db\Device\Mysql                Instance of mysql result set.
     */
    public function execute()
    {
        $this->instance->execute();
        $this->instance->store_result();

        $result = new \Octris\Core\Db\Device\Mysql\Result($this->instance);

        return $result;
    }
}
