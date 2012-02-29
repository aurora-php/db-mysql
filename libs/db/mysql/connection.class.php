<?php

/*
 * This file is part of the 'org.octris.core' package.
 *
 * (c) Harald Lapp <harald@octris.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace org\octris\core\db\mysql {
	/**
	 * MySQL connection handler.
	 *
	 * @octdoc		c:mysql/connection
     * @copyright   copyright (c) 2012 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
	class connection extends \mysqli implements \org\octris\core\db\connection_if, \org\octris\core\db\pool_if
	/**/
	{
		use \org\octris\core\db\pool_tr;

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
		 * to \org\octris\core\db\mysql::T_DEADLOCK_ATTEMPTS until a deadlock is considered
		 * to be unresolvable.
		 *
		 * @octdoc  m:connection/query
		 * @param 	string 				$sql 					SQL query to perform.
		 * @param 	bool 				$multi 					Optional setting to indicate, that multiple
		 *														queries should be performed.
		 * @return 	\org\octris\core\db\mysql\result 			Query result.
		 */
		public function query($sql, $multi = \org\octris\core\db\mysql::T_QUERY_SINGLE)
		/**/
		{
			for ($i = 0; $i < \org\octris\core\db\mysql::T_DEADLOCK_ATTEMPTS; ++$i) {
				$res = ($multi === \org\octris\core\db\mysql::T_QUERY_MULTI
						? $this->multi_query($sql)
						: $this->real_query($sql));

				if ($res !== false || ($this->errno != 1205 && $this->errno != 1213)) {
					break;
				}
			}

			if ($res === false) {
				throw new \Exception($this->error, $this->errno);
			}

			return new \org\octris\core\db\mysql\result($this);
    	}

    	/**
    	 * Initialize prepared statement.
    	 *
    	 * @octdoc  m:connection/prepare
    	 * @param 	string 				$sql 					SQL query to prepare.
    	 * @return 	\org\octris\core\db\mysql\statement 		Instance of a prepared statement.
    	 */
    	public function prepare($sql)
    	/**/
    	{
	        $stmt = new \org\octris\core\db\mysql\statement($this, $sql);

	        if ($stmt->errno > 0) {
     	       throw new \Exception($stmt->sqlstate . ' ' . $stmt->error, $stmt->errno);
        	}
        
        	return $stmt;
    	}
	}
}
