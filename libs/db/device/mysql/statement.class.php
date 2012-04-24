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
	 * MySQL prepared statement.
	 *
	 * @octdoc		c:mysql/statement
     * @copyright   copyright (c) 2012 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
	class statement
	/**/
	{
		/**
		 * Instance of mysqli_stmt class.
		 *
		 * @octdoc  p:statement/$instance
		 * @var     \mysqli_stmt
		 */
		protected $instance;
		/**/

		/**
		 * Constructor.
		 *
		 * @octdoc  m:statement/__construct
		 * @param 	\mysqli 		$link 				Database connection.
		 * @param 	string 			$sql 				SQL statement.
		 */
		public function __construct(\mysqli $link, $sql)
		/**/
		{
			$this->instance = new \mysqli_stmt($link, $sql);
		}

		/**
		 * Returns number of parameters in statement.
		 *
		 * @octdoc  m:statement/paramCount
		 * @return 	int 								Number of parameters.
		 */
		public function paramCount()
		/**/
		{
		    return $this->instance->param_count;
		}

		/**
		 * Bind parameters to statement.
		 *
		 * @octdoc  m:statement/bindParam
		 * @param 	string 			$types 				String of type identifiers.
		 * @param 	array 			$values 			Array of values to bind.
		 */
		public function bindParam($types, array $values)
		/**/
		{
			if (preg_match('/[^idsb]/', $types)) {
				throw new \Exception('Unknown data type in "' . $types . '"');
			} elseif (strlen($types) != ($cnt1 = count($values))) {
				throw new \Exception('Number of specified types and values does not match');
			} elseif ($cnt1 != ($cnt2 = $this->paramCount())) {
				throw new \Exception(
					sprintf(
						'number of specified parameters (%d) does not match required parameters (%d)',
						$cnt1,
						$cnt2
					)
				);
			} else {
				array_unshift($values, $types);

				call_user_func_array(array($this->instance, 'bind_param'), $values);
			}
		}

		/**
		 * Execute the statement.
		 *
		 * @octdoc  m:statement/execute
		 */
		public function execute()
		/**/
		{
			$this->instance->execute();
			$this->instance->store_result();

			$result = new \org\octris\core\db\mysql\result($this->instance);

	        return $result;
		}
	}
}
