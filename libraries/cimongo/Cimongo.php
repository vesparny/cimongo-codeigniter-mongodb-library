<?php

if (!defined('BASEPATH'))
        exit('No direct script access allowed');
require_once('Cimongo_cursor.php');
require_once('Cimongo_extras.php');
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
 * @version		Version 1.1.0
 *
 */

/**
 * Cimongo
 *
 * Provide CI active record like methods to interact with MongoDB
 * @since v1.0
 */
class Cimongo extends Cimongo_extras {

        private $_inserted_id = FALSE;
        public $debug = FALSE;

        /**
         * Construct a new Cimongo
         *
         * @since v1.0.0
         */
        public function __construct() {
                parent::__construct();
        }

        /**
         * Fake close function so you can bind $this->db=$this->cimongo
         *
         */
        public function close() {
                
        }

        /**
         * Get the documents based upon the passed parameters
         *
         * @since v1.0.0
         */
        public function get($collection = "", $limit = FALSE, $offset = FALSE) {
                if (empty($collection)) {
                        //FIXME theow exception instead show error
                        show_error("In order to retreive documents from MongoDB, a collection name must be passed", 500);
                }
                $cursor = $this->db->selectCollection($collection)->find($this->wheres, $this->selects);
                $cimongo_cursor = new Cimongo_cursor($cursor);

                $this->limit = ($limit !== FALSE && is_numeric($limit)) ? $limit : $this->limit;
                if ($this->limit !== FALSE) {
                        $cimongo_cursor->limit($this->limit);
                }

                $this->offset = ($offset !== FALSE && is_numeric($offset)) ? $offset : $this->offset;
                if ($this->offset !== FALSE) {
                        $cimongo_cursor->skip($this->offset);
                }
                if (!empty($this->sorts) && count($this->sorts) > 0) {
                        $cimongo_cursor->sort($this->sorts);
                }

                $this->_clear();

                return $cimongo_cursor;
        }

        /**
         * Get the documents based upon the passed parameters
         *
         * @since v1.0.0
         */
        public function get_where($collection = "", $where = array(), $limit = FALSE, $offset = FALSE) {
                return $this->where($where)->get($collection, $limit, $offset);
        }

        /**
         * Determine which fields to include (_id is always returned)
         *
         * @since v1.0.0
         */
        public function select($includes = array()) {
                if (!is_array($includes)) {
                        $includes = array();
                }
                if (!empty($includes)) {
                        foreach ($includes as $col) {
                                $this->selects[$col] = TRUE;
                        }
                }
                return $this;
        }

        /**
         * where clause:
         *
         * Passa an array of field=>value, every condition will be merged in AND statement
         * e.g.:
         * $this->cimongo->where(array('foo'=> 'bar', 'user'=>'arny')->get("users")
         *
         * if you need more complex clause you can pass an array composed exactly like mongoDB needs, followed by a boolean TRUE parameter.
         * e.g.:
         * $where_clause = array(
         * 						'$or'=>array(
         * 							array("user"=>'arny'),
         * 							array("facebook.id"=>array('$gt'=>1,'$lt'=>5000)),
         * 							array('faceboo.usernamek'=>new MongoRegex("/^arny.$/"))
         * 	 					),
         * 						email"=>"a.arnodo@gmail.com"
         * 					);
         *
         *
         * $this->cimongo->where($where_clause, TRUE)->get("users")
         *
         * @since v1.0.0
         *
         *
         */
        public function where($wheres = array(), $native = FALSE) {
                if ($native === TRUE && is_array($wheres)) {
                        $this->wheres = $wheres;
                } elseif (is_array($wheres)) {
                        foreach ($wheres as $where => $value) {
                                $this->_where_init($where);
                                $this->wheres[$where] = $value;
                        }
                }
                return $this;
        }

        /**
         * Get the documents where the value of a $field may be something else
         *
         * @since v1.0.0
         */
        public function or_where($wheres = array()) {
                $this->_where_init('$or');
                if (is_array($wheres) && count($wheres) > 0) {
                        foreach ($wheres as $wh => $val) {
                                $this->wheres['$or'][] = array($wh => $val);
                        }
                }
                return $this;
        }

        /**
         * Get the documents where the value of a $field is in a given $in array().
         *
         * @since v1.0.0
         */
        public function where_in($field = "", $in = array()) {
                $this->_where_init($field);
                $this->wheres[$field]['$in'] = $in;
                return $this;
        }

        /**
         * Get the documents where the value of a $field is not in a given $in array().
         *
         * @since v1.0.0
         */
        public function where_not_in($field = "", $in = array()) {
                $this->_where_init($field);
                $this->wheres[$field]['$nin'] = $in;
                return $this;
        }

        /**
         *
         * 	Get the documents where the (string) value of a $field is like a value. The defaults
         * 	allow for a case-insensitive search.
         *
         * 	@param $flags
         * 	Allows for the typical regular expression flags:
         * 		i = case insensitive
         * 		m = multiline
         * 		x = can contain comments
         * 		l = locale
         * 		s = dotall, "." matches everything, including newlines
         * 		u = match unicode
         *
         * 	@param $enable_start_wildcard
         * 	If set to anything other than TRUE, a starting line character "^" will be prepended
         * 	to the search value, representing only searching for a value at the start of
         * 	a new line.
         *
         * 	@param $enable_end_wildcard
         * 	If set to anything other than TRUE, an ending line character "$" will be appended
         * 	to the search value, representing only searching for a value at the end of
         * 	a line.
         *
         * 	@usage : $this->cimongo->like('foo', 'bar', 'im', FALSE, TRUE);
         * 	@since v1.0.0
         *
         */
        public function like($field = "", $value = "", $flags = "i", $enable_start_wildcard = TRUE, $enable_end_wildcard = TRUE) {
                $field = (string) trim($field);
                $this->_where_init($field);
                $value = (string) trim($value);
                $value = quotemeta($value);

                if ($enable_start_wildcard !== TRUE) {
                        $value = "^" . $value;
                }
                if ($enable_end_wildcard !== TRUE) {
                        $value .= "$";
                }
                $regex = "/$value/$flags";
                $this->wheres[$field] = new MongoRegex($regex);
                return $this;
        }

        /**
         * The same as the aboce but multiple instances are joined by OR:
         *
         * @since v1.0.0
         */
        public function or_like($field, $like = array(),$flags = "i") {
                $this->_where_init('$or');
                        if (is_array($like) && count($like) > 0) {
                                foreach ($like as $admitted) {
                                $this->wheres['$or'][] = array($field => new MongoRegex("/$admitted/$flags"));
                                }
                        } else {
                        $this->wheres['$or'][] = array($field => new MongoRegex("/$like/$flags"));
                        }
                return $this;
        }

        /**
         * The same as the aboce but multiple instances are joined by NOT LIKE:
         *
         * @since v1.0.0
         */
        public function not_like($field, $like = array()) {
                $this->_where_init($field);
                if (is_array($like) && count($like) > 0) {
                        foreach ($like as $admitted) {
                                $this->wheres[$field]['$nin'][] = new MongoRegex("/$admitted/");
                        }
                }
                return $this;
        }

        /**
         *
         * 	Sort the documents based on the parameters passed. To set values to descending order,
         * 	you must pass values of either -1, FALSE, 'desc', or 'DESC', else they will be
         * 	set to 1 (ASC).
         *
         * 	@usage : $this->cimongo->order_by(array('name' => 'ASC'))->get('users');
         *  @since v1.0.0
         */
        public function order_by($fields = array()) {
                foreach ($fields as $field => $val) {
                        if ($val === -1 || $val === FALSE || strtolower($val) === 'desc') {
                                $this->sorts[$field] = -1;
                        }
                        if ($val === 1 || $val === TRUE || strtolower($val) === 'asc') {
                                $this->sorts[$field] = 1;
                        }
                }
                return $this;
        }

        /**
         *
         * 	Count all the documents in a collection
         *
         *  @usage : $this->cimongo->count_all('users');
         *  @since v1.0.0
         */
        public function count_all($collection = "") {
                if (empty($collection)) {
                        show_error("In order to retreive a count of documents from MongoDB, a collection name must be passed", 500);
                }

                $cursor = $this->db->selectCollection($collection)->find();
                $cimongo_cursor = new Cimongo_cursor($cursor);
                $count = $cimongo_cursor->count(TRUE);
                $this->_clear();
                return $count;
        }

        /**
         *
         * 	Count the documents based upon the passed parameters
         *
         *  @since v1.0.0
         */
        public function count_all_results($collection = "") {
                if (empty($collection)) {
                        show_error("In order to retreive a count of documents from MongoDB, a collection name must be passed", 500);
                }

                $cursor = $this->db->selectCollection($collection)->find($this->wheres);
                $cimongo_cursor = new Cimongo_cursor($cursor);
                if ($this->limit !== FALSE) {
                        $cimongo_cursor->limit($this->limit);
                }
                if ($this->offset !== FALSE) {
                        $cimongo_cursor->skip($this->offset);
                }
                $this->_clear();
                return $cimongo_cursor->count(TRUE);
        }

        /**
         *
         * 	Insert a new document into the passed collection
         *
         *  @since v1.0.0
         */
        public function insert($collection = "", $insert = array()) {
                if (empty($collection)) {
                        show_error("No Mongo collection selected to insert into", 500);
                }

                if (count($insert) == 0) {
                        show_error("Nothing to insert into Mongo collection or insert is not an array", 500);
                }
                $this->_inserted_id = FALSE;
                try {
                        $query = $this->db->selectCollection($collection)->insert($insert, array("w" => $this->query_safety));
                        if (isset($insert['_id'])) {
                                $this->_inserted_id = $insert['_id'];
                                return TRUE;
                        } else {
                                return FALSE;
                        }
                } catch (MongoException $e) {
                        show_error("Insert of data into MongoDB failed: {$e->getMessage()}", 500);
                } catch (MongoCursorException $e) {
                        show_error("Insert of data into MongoDB failed: {$e->getMessage()}", 500);
                }
        }

        /**
         *
         * 	Insert a multiple new document into the passed collection
         *
         *  @since v1.0.0
         */
        public function insert_batch($collection = "", $insert = array()) {
                if (empty($collection)) {
                        show_error("No Mongo collection selected to insert into", 500);
                }
                if (count($insert) == 0) {
                        show_error("Nothing to insert into Mongo collection or insert is not an array", 500);
                }
                try {
                        $query = $this->db->selectCollection($collection)->batchInsert($insert, array("w" => $this->query_safety));
                        if (is_array($query)) {
                                return $query["err"] === NULL;
                        } else {
                                return $query;
                        }
                } catch (MongoException $e) {
                        show_error("Insert of data into MongoDB failed: {$e->getMessage()}", 500);
                } catch (MongoCursorException $e) {
                        show_error("Insert of data into MongoDB failed: {$e->getMessage()}", 500);
                } catch (MongoCursorTimeoutException $e) {
                        show_error("Insert of data into MongoDB failed: {$e->getMessage()}", 500);
                }
        }

        /**
         *
         * Sets a field to a value
         *
         * 	@usage: $this->cimongo->where(array('blog_id'=>123))->set(array('posted'=>1)->update('users');
         *   @since v1.0.0
         */
        public function set($fields = array()) {
                if (is_array($fields)) {
                        $this->_update_init('$set');
                        foreach ($fields as $field => $value) {
                                $this->updates['$set'][$field] = $value;
                        }
                }
                return $this;
        }

        /**
         *
         * Update a single document
         *
         *   @since v1.0.0
         */
        public function update($collection = "", $data = array(), $options = array()) {
                if (empty($collection)) {
                        show_error("No Mongo collection selected to update", 500);
                }
                if (is_array($data) && count($data) > 0) {
                        $this->_update_init('$set');
                        $this->updates['$set'] += $data;
                }
                if (count($this->updates) == 0) {
                        show_error("Nothing to update in Mongo collection or update is not an array", 500);
                }
                try {
                        $options = array_merge(array("w" => $this->query_safety, 'multiple' => FALSE), $options);
                        $this->db->selectCollection($collection)->update($this->wheres, $this->updates, $options);
                        $this->_clear();
                        return TRUE;
                } catch (MongoCursorException $e) {
                        show_error("Update of data into MongoDB failed: {$e->getMessage()}", 500);
                } catch (MongoCursorException $e) {
                        show_error("Update of data into MongoDB failed: {$e->getMessage()}", 500);
                } catch (MongoCursorTimeoutException $e) {
                        show_error("Update of data into MongoDB failed: {$e->getMessage()}", 500);
                }
        }

        /**
         *
         * Update more than one document
         *
         *   @since v1.0.0
         */
        public function update_batch($collection = "", $data = array()) {
                return $this->update($collection, $data, array('multiple' => TRUE));
        }

        /**
         *
         * Delete document from the passed collection based upon certain criteria
         *
         *   @since v1.0.0
         */
        public function delete($collection = "", $options = array()) {
                if (empty($collection)) {
                        show_error("No Mongo collection selected to delete from", 500);
                }
                try {
                        $options = array_merge(array("w" => $this->query_safety), $options);
                        $this->db->selectCollection($collection)->remove($this->wheres, $options);
                        $this->_clear();
                        return TRUE;
                } catch (MongoCursorException $e) {
                        show_error("Delete of data into MongoDB failed: {$e->getMessage()}", 500);
                } catch (MongoCursorTimeoutException $e) {
                        show_error("Delete of data into MongoDB failed: {$e->getMessage()}", 500);
                }
        }

        /**
         *
         * Delete more than one document
         *
         *   @since v1.3.0
         */
        public function delete_batch($collection = "", $options = array()) {
                return $this->delete($collection, array('justOne' => FALSE));
        }

        /**
         *
         * Limit results
         *
         *   @since v1.1.0
         */
        public function limit($limit = FALSE) {
                if ($limit && is_numeric($limit)) {
                        $this->limit = $limit;
                }
                return $this;
        }

        /**
         *
         * Returns the last inserted document's id
         *
         *   @since v1.1.0
         */
        public function insert_id() {
                return $this->_inserted_id;
        }

}
