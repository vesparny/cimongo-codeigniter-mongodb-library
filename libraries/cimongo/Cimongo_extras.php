<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once('Cimongo_cursor.php');
require_once('Cimongo_base.php');
/**
 * CodeIgniter MongoDB Library
 *
 * A library to interact with the NoSQL database MongoDB.
 * For more information see http://www.mongodb.org
 *
 * Inspired by https://github.com/alexbilbie/codeigniter-mongodb-library
 *
 * @package		CodeIgniter
 * @author		Alessandro Arnodo | a.arnodo@gmail.com | @vesparny
 * @copyright	Copyright (c) 2012, Alessandro Arnodo.
 * @license		http://www.opensource.org/licenses/mit-license.php
 * @link
 * @version		Version 1.1.0
 *
 */

/**
 * Cimongo_extras
 *
 * Provide extra methods to interact with MongoDB
 * Thanks to Alex Bilbie's work, i will improve that :)
 * UNDER DEVELOPMENT
 * @since v1.0.0
 */

class Cimongo_extras extends Cimongo_base{


	/**
	 * Construct a new Cimongo_extras
	 *
	 * @since v1.0.0
	 */
	public function __construct(){
		parent::__construct();
	}

	/**
	 *   Runs a MongoDB command (such as GeoNear).
	 *	See the MongoDB documentation for more usage scenarios:
	 *	http://dochub.mongodb.org/core/commands
	 *   @usage : $this->cimongo->command(array('geoNear'=>'buildings', 'near'=>array(53.228482, -0.547847), 'num' => 10, 'nearSphere'=>true));
	 *   @since v1.0.0
	 */
	public function command($query = array()){
		try{
			$run = $this->db->command($query);
			return $run;
		}catch (MongoCursorException $e){
			show_error("MongoDB command failed to execute: {$e->getMessage()}", 500);
		}
	}

	/**
	*   Runs a MongoDB Aggregate.
	*  See the MongoDB documentation for more usage scenarios:
	*  http://docs.mongodb.org/manual/core/aggregation
	*   @usage : $this->cimongo->aggregate('users', array(array('$project' => array('_id' => 1))));
	*   @since v1.0.0
	*/
	public function aggregate($collection = "", $opt) {
		if (empty($collection)) {
			show_error("No Mongo collection selected to insert into", 500);
		}
		try{
			$c = $this->db->selectCollection($collection);
			return $c->aggregate($opt);
		} catch (MongoException $e) {
			show_error("MongoDB failed: {$e->getMessage()}", 500);
		}
	}

	/**
	 *	Ensure an index of the keys in a collection with optional parameters. To set values to descending order,
	 *	you must pass values of either -1, FALSE, 'desc', or 'DESC', else they will be
	 *	set to 1 (ASC).
	 *
	 * @usage : $this->cimongo->ensure_index($collection, array('first_name' => 'ASC', 'last_name' => -1), array('unique' => TRUE));
	 * @since v1.0.0
	 */
	public function ensure_index($collection = "", $keys = array(), $options = array()){
		if(empty($collection)){
			show_error("No Mongo collection specified to add index to", 500);
		}
		if(empty($keys) || !is_array($keys)){
			show_error("Index could not be created to MongoDB Collection because no keys were specified", 500);
		}
		foreach ($keys as $col => $val){
			if($val == -1 || $val === FALSE || strtolower($val) == 'desc'){
				$keys[$col] = -1;
			}else{
				$keys[$col] = 1;
			}
		}
		if ($this->db->{$collection}->ensureIndex($keys, $options) == TRUE){
			$this->_clear();
			return $this;
		}
		else{
			show_error("An error occured when trying to add an index to MongoDB Collection", 500);
		}
	}


	/**
	 *	Remove an index of the keys in a collection. To set values to descending order,
	 *	you must pass values of either -1, FALSE, 'desc', or 'DESC', else they will be
	 *	set to 1 (ASC).
	 *
	 * @usage : $this->cimongo->remove_index($collection, array('first_name' => 'ASC', 'last_name' => -1));
	 * @since v1.0.0
	 */
	public function remove_index($collection = "", $keys = array()){
		if (empty($collection))	{
			show_error("No Mongo collection specified to remove index from", 500);
		}
		if (empty($keys) || !is_array($keys))	{
			show_error("Index could not be removed from MongoDB Collection because no keys were specified", 500);
		}
		if ($this->db->{$collection}->deleteIndex($keys, $options) == TRUE){
			$this->_clear();
			return $this;
		}
		else{
			show_error("An error occured when trying to remove an index from MongoDB Collection", 500);
		}
	}

	/**
	 *	Remove all indexes from a collection
	 *
	 * @since v1.0.0
	 */
	public function remove_all_indexes($collection = ""){
		if (empty($collection)){
			show_error("No Mongo collection specified to remove all indexes from", 500);
		}
		$this->db->{$collection}->deleteIndexes();
		$this->_clear();
		return $this;
	}

	/**
	 *	List all indexes in a collection
	 *
	 * @since v1.0.0
	 */
	public function list_indexes($collection = ""){
		if (empty($collection)){
			show_error("No Mongo collection specified to remove all indexes from", 500);
		}
		return $this->db->{$collection}->getIndexInfo();
	}

	/**
	 *	Get mongo object from database reference using MongoDBRef
	 *
	 * @usage : $this->cimongo->get_dbref($object);
	 * @since v1.0.0
	 */
	public function get_dbref($obj){
		if (empty($obj) OR !isset($obj)){
			show_error('To use MongoDBRef::get() ala get_dbref() you must pass a valid reference object', 500);
		}

		if ($this->CI->config->item('mongo_return') == 'object'){
			return (object) MongoDBRef::get($this->db, $obj);
		}
		else{
			return (array) MongoDBRef::get($this->db, $obj);
		}
	}

	/**
	 *	Create mongo dbref object to store later
	 *
	 * @usage : $this->cimongo->create_dbref($collection, $id);
	 * @since v1.0.0
	 */
	public function create_dbref($collection = "", $id = "", $database = FALSE ){
		if (empty($collection))	{
			show_error("In order to retreive documents from MongoDB, a collection name must be passed", 500);
		}
		if (empty($id) OR !isset($id))
		{
			show_error('To use MongoDBRef::create() ala create_dbref() you must pass a valid id field of the object which to link', 500);
		}

		$db = $database ? $database : $this->db;

		if ($this->CI->config->item('mongo_return') == 'object'){
			return (object) MongoDBRef::create($collection, $id, $db);
		}else{
			return (array) MongoDBRef::get($this->db, $obj);
		}
	}

	/**
	 *	Get the documents where the value of a $field is greater than $x
	 *  @since v1.0.0
	 */
	public function where_gt($field = "", $x){
		$this->_where_init($field);
		$this->wheres[$field]['$gt'] = $x;
		return $this;
	}

	/**
	 *  Get the documents where the value of a $field is greater than or equal to $x
	 *  @since v1.0.0
	 */
	public function where_gte($field = "", $x){
		$this->_where_init($field);
		$this->wheres[$field]['$gte'] = $x;
		return $this;
	}

	/**
	 *  Get the documents where the value of a $field is less than $x
	 *  @since v1.0.0
	 */
	public function where_lt($field = "", $x){
		$this->_where_init($field);
		$this->wheres[$field]['$lt'] = $x;
		return $this;
	}

	/**
	 *  Get the documents where the value of a $field is less than or equal to $x
	 *  @since v1.0.0
	 */
	public function where_lte($field = "", $x){
		$this->_where_init($field);
		$this->wheres[$field]['$lte'] = $x;
		return $this;
	}

	/**
	 *  Get the documents where the value of a $field is between $x and $y
	 *  @since v1.0.0
	 */
	public function where_between($field = "", $x, $y){
		$this->_where_init($field);
		$this->wheres[$field]['$gte'] = $x;
		$this->wheres[$field]['$lte'] = $y;
		return $this;
	}

	/**
	 *  Get the documents where the value of a $field is between but not equal to $x and $y
	 *  @since v1.0.0
	 */
	public function where_between_ne($field = "", $x, $y){
		$this->_where_init($field);
		$this->wheres[$field]['$gt'] = $x;
		$this->wheres[$field]['$lt'] = $y;
		return $this;
	}

	/**
	 *  Get the documents where the value of a $field is not equal to $x
	 *  @since v1.0.0
	 */
	public function where_ne($field = '', $x){
		$this->_where_init($field);
		$this->wheres[$field]['$ne'] = $x;
		return $this;
	}

	/**
	 *  Get the documents nearest to an array of coordinates (collection must have a geospatial index)
	 *  @since v1.0.0
	 */
	function where_near($field = '', $co = array()){
		$this->__where_init($field);
		$this->where[$what]['$near'] = $co;
		return $this;
	}

	/**
	 *  Increments the value of a field
	 *  @since v1.0.0
	 */
	public function inc($fields = array(), $value = 0){
		$this->_update_init('$inc');
		if (is_string($fields)){
			$this->updates['$inc'][$fields] = $value;
		}elseif(is_array($fields)){
			foreach ($fields as $field => $value){
				$this->updates['$inc'][$field] = $value;
			}
		}
		return $this;
	}

	/**
	 *  Decrements the value of a field
	 *  @since v1.0.0
	 */
	public function dec($fields = array(), $value = 0){
		$this->_update_init('$dec');
		if (is_string($fields)){
			$this->updates['$dec'][$fields] = $value;
		}elseif (is_array($fields)){
			foreach ($fields as $field => $value){
				$this->updates['$dec'][$field] = $value;
			}
		}
		return $this;
	}

	/**
	 *  Unset the value of a field(s)
	 *  @since v1.0.0
	 */

	public function unset_field($fields){
		$this->_update_init('$unset');
		if (is_string($fields)){
			$this->updates['$unset'][$fields] = 1;
		}elseif (is_array($fields)){
			foreach ($fields as $field)	{
				$this->updates['$unset'][$field] = 1;
			}
		}
		return $this;
	}

	/**
	 *  Adds value to the array only if its not in the array already
	 *
	 *	@usage: $this->cimongo->where(array('blog_id'=>123))->addtoset('tags', 'php')->update('blog_posts');
	 *	@usage: $this->cimongo->where(array('blog_id'=>123))->addtoset('tags', array('php', 'codeigniter', 'mongodb'))->update('blog_posts');
	 *   @since v1.0.0
	 */
	public function addtoset($field, $values){
		$this->_update_init('$addToSet');
		if (is_string($values)){
			$this->updates['$addToSet'][$field] = $values;
		}elseif (is_array($values)){
			$this->updates['$addToSet'][$field] = array('$each' => $values);
		}
		return $this;
	}

	/**
	 *	Pushes values into a field (field must be an array)
	 *
	 *	@usage: $this->cimongo->where(array('blog_id'=>123))->push('comments', array('text'=>'Hello world'))->update('blog_posts');
	 *	@usage: $this->cimongo->where(array('blog_id'=>123))->push(array('comments' => array('text'=>'Hello world')), 'viewed_by' => array('Alex')->update('blog_posts');
	 * @since v1.0.0
	 */

	public function push($fields, $value = array()){
		$this->_update_init('$push');
		if (is_string($fields)){
			$this->updates['$push'][$fields] = $value;
		}elseif (is_array($fields)){
			foreach ($fields as $field => $value){
				$this->updates['$push'][$field] = $value;
			}
		}
		return $this;
	}

	/**
	 *	Pushes  ALL values into a field (field must be an array)
	 *
	 * @since v1.0.0
	 */
	public function push_all($fields, $value = array()) {
		$this->_update_init('$pushAll');
		if (is_string($fields)){
			$this->updates['$pushAll'][$fields] = $value;
		}elseif (is_array($fields)){
			foreach ($fields as $field => $value){
				$this->updates['$pushAll'][$field] = $value;
			}
		}
		return $this;
	}

	/**
	 *	Pops the last value from a field (field must be an array
	 *
	 * 	@usage: $this->cimongo->where(array('blog_id'=>123))->pop('comments')->update('blog_posts');
	 *	@usage: $this->cimongo->where(array('blog_id'=>123))->pop(array('comments', 'viewed_by'))->update('blog_posts');
	 *   @since v1.0.0
	 */
	public function pop($field){
		$this->_update_init('$pop');
		if (is_string($field)){
			$this->updates['$pop'][$field] = -1;
		}
		elseif (is_array($field)){
			foreach ($field as $pop_field){
				$this->updates['$pop'][$pop_field] = -1;
			}
		}
		return $this;
	}

	/**
	 * Removes by an array by the value of a field
	 *
	 *	@usage: $this->cimongo->pull('comments', array('comment_id'=>123))->update('blog_posts');
	 *  @since v1.0.0
	 */
	public function pull($field = "", $value = array()){
		$this->_update_init('$pull');
		$this->updates['$pull'] = array($field => $value);
		return $this;
	}

	/**
	 * Removes ALL by an array by the value of a field
	 *
	 *  @since v1.0.0
	 */
	public function pull_all($field = "", $value = array()){
		$this->_update_init('$pullAll');
		$this->updates['$pullAll'] = array($field => $value);
		return $this;
	}

	/**
	 * Rename a field
	 *
	 *  @since v1.0.0
	 */
	public function rename_field($old, $new){
		$this->_update_init('$rename');
		$this->updates['$rename'][] = array($old => $new);
		return $this;
	}
}
