<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter MongoDB Library
 *
 * A library to interact with the NoSQL database MongoDB.
 * For more information see http://www.mongodb.org
 *
 * @package		CodeIgniter
 * @author		Alessandro Arnodo | a.arnodo@gmail.com | @vesparny
 * @copyright	Copyright (c) 2012, Alessandro Arnodo.
 * @license		http://www.opensource.org/licenses/mit-license.php
 * @link
 * @version		Version 1.1.1
 *
 */

/**
 *
 * @uses This class provide a connection object to interact with MongoDB
 * @since v1.0.0
 *
 */

class Cimongo_base {

	protected $CI;

	protected $connection;
	protected $db;
	private $connection_string;

	private $host;
	private $port;
	private $user;
	private $pass;
	private $dbname;
	protected $query_safety;

	protected $selects = array();
	protected  $wheres = array();
	protected $sorts = array();
	protected $updates = array();

	protected $limit = FALSE;
	protected $offset = FALSE;

	/**
	 * Connect to MongoDB
	 *
	 * @since v1.0.0
	 */
	public function __construct(){
		if (!class_exists('Mongo')){
			show_error("The MongoDB PECL extension has not been installed or enabled", 500);
		}
		$this->CI =& get_instance();
		$this->connection_string();
		$this->connect();
	}

	/**
	 * Switch DB
	 *
	 * @since v1.0.0
	 */
	public function switch_db($database = ''){
		if (empty($database)){
			show_error("To switch MongoDB databases, a new database name must be specified", 500);
		}
		$this->dbname = $database;
		try{
			$this->db = $this->connection->{$this->dbname};
			return (TRUE);
		}catch (Exception $e){
			show_error("Unable to switch Mongo Databases: {$e->getMessage()}", 500);
		}
	}

	/**
	 * Drop DB
	 *
	 * @since v1.0.0
	 */
	public function drop_db($database = ''){
		if (empty($database)){
			show_error('Failed to drop MongoDB database because name is empty', 500);
		}else{
			try{
				$this->connection->{$database}->drop();
				return TRUE;
			}catch (Exception $e){
				show_error("Unable to drop Mongo database `{$database}`: {$e->getMessage()}", 500);
			}

		}
	}

	/**
	 * Drop collection
	 *
	 * @since v1.0.0
	 */
	public function drop_collection($db = "", $col = ""){
		if (empty($db)){
			show_error('Failed to drop MongoDB collection because database name is empty', 500);
		}
		if (empty($col)){
			show_error('Failed to drop MongoDB collection because collection name is empty', 500);
		}else{
			try{
				$this->connection->{$db}->{$col}->drop();
				return TRUE;
			}catch (Exception $e)
			{
				show_error("Unable to drop Mongo collection '$col': {$e->getMessage()}", 500);
			}
		}

		return $this;
	}


	/**
	 * Connect to MongoDB
	 *
	 * @since v1.0.0
	 */
	private function connect(){
		$options = array();
		try{
			$this->connection = new MongoClient($this->connection_string, $options);
			$this->db = $this->connection->{$this->dbname};
			return $this;
		}catch (MongoConnectionException $e){
			show_error("Unable to connect to MongoDB: {$e->getMessage()}", 500);
		}
	}

	/**
	 * Create connection string
	 *
	 * @since v1.0.0
	 */
	private function connection_string(){
		$this->CI->config->load("cimongo");
		$this->host	= trim($this->CI->config->item('host'));
		$this->port = trim($this->CI->config->item('port'));
		$this->user = trim($this->CI->config->item('user'));
		$this->pass = trim($this->CI->config->item('pass'));
		$this->dbname = trim($this->CI->config->item('db'));
		$this->query_safety = $this->CI->config->item('query_safety');
		$dbhostflag = (bool)$this->CI->config->item('db_flag');

		$connection_string = "mongodb://";

		if (empty($this->host)){
			show_error("The Host must be set to connect to MongoDB", 500);
		}

		if (empty($this->dbname)){
			show_error("The Database must be set to connect to MongoDB", 500);
		}

		if ( ! empty($this->user) && ! empty($this->pass)){
			$connection_string .= "{$this->user}:{$this->pass}@";
		}

		if (isset($this->port) && ! empty($this->port)){
			$connection_string .= "{$this->host}:{$this->port}";
		}else{
			$connection_string .= "{$this->host}";
		}

		if ($dbhostflag === TRUE){
			$this->connection_string = trim($connection_string) . '/' . $this->dbname;
		}else{
			$this->connection_string = trim($connection_string);
		}
	}


	/**
	 * Reset class variables
	 *
	 * @since v1.0.0
	 */
	protected function _clear()
	{
		$this->selects	= array();
		$this->updates	= array();
		$this->wheres	= array();
		$this->limit	= FALSE;
		$this->offset	= FALSE;
		$this->sorts	= array();
	}

	/**
	 * Initializie where clause for the specified field
	 *
	 * @since v1.0.0
	 */
	protected function _where_init($param){
		if (!isset($this->wheres[$param])){
			$this->wheres[$param] = array();
		}
	}

	/**
	 * Initializie update clause for the specified method
	 *
	 * @since v1.0.0
	 */
	protected function _update_init($method){
		if ( ! isset($this->updates[$method])){
			$this->updates[$method] = array();
		}
	}

	/**
	 * Handler for exception
	 *
	 * @since v1.1.0
	 */
	protected function _handle_exception($message,$as_object=TRUE){
		if($as_object){
			$res =  new stdClass();
			$res->has_error=TRUE;
			$res->error_message=$message;
		}else{
			$res =  array(
				"has_error"=>TRUE,
				"error_message"=>$message
			);
		}
		return $res;

	}

}
