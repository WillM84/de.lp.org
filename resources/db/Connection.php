<?php
namespace db
{
	use \PDO;
	use \PDOException;
	
	/**
	 * Class for managing PDO connections and prepared statement queries.
	 *
	 * This class creates a MySQL based PDO and executes prepared statement queries
	 * against it, returning information relevant to each query.
	 */
	class Connection 
	{
		
		/**
		 * Reference to the PDO connection.
		 * @access private
		 *
		 * @var	\PDO
		 */
	    private $connection;
		/**
		 * The database name on the host.
		 * @access private
		 *
		 * @var	string
		 */
		private $database;
		
		//////
		
		/**
		 * Construct a new connection and initialize it.
		 *
		 * Loads the dbconfig.php file to define a DB host, user, and 
		 * password.  Then instantiates a new PDO with the given parameters.
		 *
		 * @param	string	$databaseName	The name of the database on DB_HOST to connect to.
		 */
		public function __construct($databaseName) 
		{
			// dbconfig.php defines database constants
			require_once 'dbconfig.php';
			
			// set the database name and instantiate the PDO connection
			$this->set_database($databaseName);
		}
	      
		/**
		 * Prepares a SQL query and executes it, returning the number of rows affected.
		 *
		 * This function will prepare the provided SQL statement and run it against the
		 * instantiated PDO connection with the provided parameters.  It will return
		 * the number of rows affected by the query or <code>false</code> if the query
		 * fails for any reason.  This function should be used for UPDATE and DELETE queries.
		 *
		 * @param	string	$preparedString	The SQL statement to execute with escaped parameters
		 * for the prepared statement parser.
		 * @param	array	$params			An associative array of parameters corresponding to 
		 * the escaped values in the <code>$preparedString</code> parameter.
		 * @return	integer					Returns the number of rows affected by the executed query.
		 */
		public function query($preparedString, $params = array()) 
		{
			// run the query
			$stmt = $this->executePreparedStatement($preparedString,$params);
			if ($stmt) // if the query succeeded
				// return the affected row count
				return $stmt->rowCount();
			else return false;
		}
		
		/**
		 * Runs a SQL query and returns the resulting rows.
		 *
		 * This function runs a provided SQL query against the instantiated PDO connection
		 * with the provided parameters.  It will return the appropriate rows from the database
		 * or <code>false</code> if the query fails.  Should be used for SELECT queries.
		 * 
		 * @param	string	$preparedString	The SQL statement to execute with escaped parameters
		 * for the prepared statement parser.
		 * @param	array	$params			An associative array of parameters corresponding to 
		 * the escaped values in the <code>$preparedString</code> parameter.
		 * @return	array					Returns an array of associative arrays for each record.
		 */
		public function querySelect($preparedString, $params = array()) 
		{
			// run the query
			$stmt = $this->executePreparedStatement($preparedString,$params);
			if ($stmt) // if execution succeeded
				// fetch and return the rows
				return $stmt->fetchAll(PDO::FETCH_ASSOC);
			else return false;
		}
		
		/**
		 * Runs a SQL query and returns the last inserted primary key.
		 *
		 * This function runs a provided SQL statement against the instantiated PDO connection
		 * with the provided parameters.  It will return the primary key value for the last
		 * record inserted or <code>false</code> if the statement fails.  Should be used for
		 * INSERT queries.
		 *
		 * @param	string	$sql			The SQL statement to execute with escaped parameters
		 * for the prepared statement parser.
		 * @param	array	$params			An associative array of parameters corresponding to 
		 * the escaped values in the <code>$sql</code> parameter.
		 * @return	integer					Returns the primary key value of the last inserted record.
		 */
		public function insert_query($sql, $params = array()) 
		{
			// run the query
			$stmt = $this->executePreparedStatement($sql,$params);
			if ($stmt) // if query succeeded
				// return the last inserted primary key value
				return $this->connection->lastInsertId();
			else return false;
		}
		
		/**
		 * Prepares and executes a prepared statement.
		 *
		 * This function abstracts the preparation and execution of a SQL prepared statement using
		 * the statement and parameters provided.
		 * @access private
		 *
		 * @param	string	$sql			The SQL statement to execute with escaped parameters
		 * for the prepared statement parser.
		 * @param	array	$params			An associative array of parameters corresponding to 
		 * the escaped values in the <code>$sql</code> parameter.
		 * @return \PDOStatement			Returns the prepared statement
		 */
		private function executePreparedStatement($sql,$params = array())
		{
			$error = '';
			try
			{
				// prepare the query
				$stmt = $this->connection->prepare($sql);
				// execute with params
				if ($stmt->execute($params)) // if query succeeded
					// return the PDOStatement object
					return $stmt;
			}
			catch (PDOException $e) { $error = $e->getMessage(); }
			error_log("Query failed: ".$error."\nQUERY: ".$sql);
			return false;
		}
		
		/**
		* Gets schema data and column names for a table.
		*
		* This function will query the INFORMATION_SCHEMA.COLUMNS table and match the `TABLE_NAME`
		* column against the provided table name.  It will return all of the raw schema data or an 
		* associative array with the `COLUMN_NAME` values from each row in the schema based on the
		* <code>$all_data</code> parameter.
		*
		* @param	string	$table			The name of the table to get columns for.
		* @param	bool	$all_data		Returns raw schema data if true, column names only if false.
		* @return	array					Returns all of the schema data from INFORMATION_SCHEMA.COLUMNS
		* or an associative array containing the column names as keys, depending on the value of 
		* <code>$all_data</code>.
		*/
		public function get_columns($table, $all_data=false) 
		{
			// trim backticks off of the table name
			$table = trim($table, '`');
			// set the query
			$sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME=:table";
			// run the query using the table name
			$data = $this->querySelect($sql,array(':table'=>$table));
			if ($all_data) // if all data is requested
				// return all the raw data
				return $data;
			else // if all data is not requested
				// pull the column names and return
				return $this->getColumnsFromSchema($data);
		}
		
		/**
		 * Pulls the `COLUMN_NAME` column out of a set of results from INFORMATION_SCHEMA.COLUMNS.
		 *
		 * This function assumes the <code>$schema</code> parameter is an array of associative arrays
		 * containing the rows from INFORMATION_SCHEMA.COLUMNS for a single `TABLE_NAME`.  It extracts
		 * the `COLUMN_NAME` values from these records and creates an associative array keyed off of the
		 * `COLUMN_NAME` containing <code>null</code> values.
		 * @access	private
		 *
		 * @param	array	$schema				An array of associative arrays containing the rows
		 * from INFORMATION_SCHEMA.COLUMNS matching a certain table.
		 * @return	array						Returns an associative array of (key,value) pairs using
		 * the `COLUMN_NAME` values from <code>$schema</code> as keys and <code>null</code> as values.
		 */
		private function getColumnsFromSchema($schema)
		{
			// blank slate
			$columns = array();
			// foreach row
			foreach ($schema as $row)
				// add a key to the result set for the `COLUMN_NAME` field
				$columns[$row['COLUMN_NAME']] = null;
			
			// return the result set
			return $columns;
		}
		
		/**
		 * Set a database and re-initialize the PDO for the specified database.
		 *
		 * This function recreates the PDO object to query a different database.
		 *
		 * @param	string	$databaseName		The name of the database to run queries against.
		 */
		public function set_database($databaseName)
		{
			// save the database name to the property
			$this->database = $databaseName;
			try {
				// set driver initialization string
				$driver = "mysql:host=".DB_HOST.";charset=utf8;";
				if (!empty($this->database))
					$driver .= "dbname=".$this->database.";";
				
				// instantiate the PDO using the DB constants, the provided database name, and utf8
				$this->connection = new PDO($driver,DB_USER,DB_PASS,
						array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
				
				// raise exceptions for MySQL errors
				$this->connection->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
				// do not emulate prepared statements in PHP, use MySQL
				$this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);
				
				return true;
			}
			catch (PDOException $exception) {
				error_log('Connection failed: '.$exception->getMessage());
				return false;
			}
		}

	}
}
?>
