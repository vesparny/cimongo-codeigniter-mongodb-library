<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
 * MY_Form_validation
 *
 * Override core methods forcing them to interact with  MongoDB.
 * @since v1.1
 */
class MY_Form_validation extends CI_Form_validation{

    public function __construct($rules = array())
    {
        log_message('debug', '*** Hello from MY_Session ***');
        $this->CI =& get_instance();
        $this->CI->load->library("cimongo/cimongo");
        parent::__construct($rules);
    }
    
    /**
     * is_unique
     *
     */
    public function is_unique($str, $field)
    {
    	list($table, $field)=explode('.', $field);
    	$query = $this->CI->cimongo->limit(1)->get_where($table, array($field => $str));
    
    	return $query->num_rows() === 0;
    }

}  