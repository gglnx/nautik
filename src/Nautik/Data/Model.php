<?php
/**
 * @package     Nautik
 * @version     1.0-$Id$
 * @link        http://github.com/gglnx/nautik
 * @author      Dennis Morhardt <info@dennismorhardt.de>
 * @copyright   Copyright 2012, Dennis Morhardt
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 */

namespace Nautik\Data;

/**
 * 
 */
class Model {
	/**
	 *
	 */
	private $__data = array();
	
	/**
	 *
	 */
	public $__collection = null;
	
	/**
	 *
	 */
	private $__class = null;
	
	/**
	 *
	 */
	private $__saved = true;
	
	/**
	 *
	 */
	private $__new = false;
	
	/**
	 *
	 */
	public function __construct($data = array(), $raw = false, $saved = false) {
		// Name of the collection
		$this->__class = get_called_class();
		if ( null == $this->__collection )
			$this->__collection = \Nautik\Core\Inflector::tableize($this->__class);
		
		// Set the data
		if ( true == $raw )
			$this->__data = $this->__data + (array) $data;
		else foreach( $data as $parameter => $value )
			$this->__set($parameter, $value);
	
		// Set the states
		$this->__saved = $saved;
		$this->__new = !$raw;
	}
	
	/**
	 *
	 */
	public function __get($parameter) {
		// MongoId is requested, but didn't exists
		if ( '_id' == $parameter && false == isset( $this->__data['_id'] ) )
			$value = $this->__set('_id', new \MongoId());
		// String representation of MongoId is requested, but didn't exists
		elseif ( 'id' == $parameter && false == isset( $this->__data['_id'] ) )
			$value = $this->__set('_id', new \MongoId())->__toString();
		// String representation of MongoId is requested
		elseif ( 'id' == $parameter )
			$value = $this->__data['_id']->__toString();
		// Simple parameter is requested
		elseif ( isset( $this->__data[$parameter] ) )
			$value = $this->__data[$parameter];
		// Parameter was not found
		else
			$value = null;
			
		// Return a DataTime object instand of a MongoDate object
		if ( $value instanceof \MongoDate ):
			$value = new \DateTime("@{$value->sec}");
			$value->setTimezone(new \DateTimeZone(\App\Application::$defaultTimezone));
		endif;
			
		// Run getter
		if ( 'id' == $parameter && method_exists( $this, "get_id" ) )
			$value = $this->get_id($value);	
		elseif ( method_exists( $this, $getter = "get_{$parameter}" ) )
			$value = $this->$getter($value);
		
		// Return null if parameter has not been found
		return $value;
	}
	
	/**
	 *
	 */
	public function __set($parameter, $value) {
		// Transform 'id' to a MongoId
		if ( 'id' == $parameter || '_id' == $parameter ):
			$parameter = '_id';
			$value = ( $value instanceof \MongoId ) ? $value : new \MongoId($value);
		endif;
		
		// Transform timestamp or DateTime objects to MongoDate
		if ( 'created_at' == $parameter || 'updated_at' == $parameter )
			$value = \MongoDate((int) $value);
		elseif ( $value instanceof \DateTime )
			$value = \MongoDate($value->getTimestamp());
		
		// If value is an array
		if ( is_array( $value ) )
			$value = new \ArrayObject($value, \ArrayObject::ARRAY_AS_PROPS);
	
		// Run setter
		if ( '_id' == $parameter && method_exists( $this, "set_id" ) )
			$value = $this->set_id($value);
		elseif ( method_exists( $this, $setter = "set_{$parameter}" ) )
			$value = $this->$setter($value);
	
		// Set the saved state to false and save the parameter
		$this->__saved = false;
		$this->__data[$parameter] = $value;
		
		// Return the value
		return $value;
	}
	
	/**
	 *
	 */
	public function __isset($parameter) {
		// Translate 'id' to '_id' and check if it exists
		if ( 'id' == $parameter && isset( $this->__data['_id'] ) )
			return true;
			
		// Check if the parameter exists
		if ( isset( $this->__data[$parameter] ) )
			return true;

		// Return false if the parameter has not been found
		return false;
	}
	
	/**
	 *
	 */
	public function isNewRecord() {
		// Return if the record is new
		return $this->__new;
	}
	
	/**
	 *
	 */
	public function isSaved() {
		// Return if the record has been saved
		return $this->__saved;
	}
	
	/**
	 *
	 */
	public static function find($findOne = false) {
		// Create a new query object
		return new Query(get_called_class(), strtolower(\Nautik\Core\Inflector::tableize(get_called_class())), $findOne);
	}
	
	/**
	 *
	 */
	public static function findOne() {
		// Get from database only a single object
		return self::find(true);
	}
	
	/**
	 *
	 */
	public static function findById($id_or_ids) {
		// Create a new Query object
		$query = self::find(!is_array($id_or_ids));
		
		// Select multiple objects if we got an array
		if ( is_array( $id_or_ids ) )
			return $query->in('id', $id_or_ids);

		// Return a single object
		return $query->is('id', $id_or_ids);
	}
	
	/**
	 *
	 */
	public function delete() {
		// Run 'before_delete' callback
		if ( method_exists( $this, $setter = "before_delete" ) )
			$this->$setter;
		
		// Delete the item
		$state = \Nautik\Data\Connection::getCollection($this->__collection)->remove(array('_id' => $this->__data['_id']));
		
		// Destory the model
		if ( true == $state ):
			$this->__saved = true;
			$this->__new = true;
			$this->__data = array();
		endif;
		
		// Run 'after_delete' callback
		if ( method_exists( $this, $setter = "after_delete" ) )
			$this->$setter;
		
		return $state;
	}
	
	/**
	 *
	 */
	public function save() {
		// Model is already saved
		if ( true == $this->__saved )
			return true;
			
		// Run 'before_save' callback
		if ( method_exists( $this, $setter = "before_save" ) )
			$this->$setter;
		
		// Create or update database references
		/*foreach ( $this->__data as $key => $value ):
			if ( is_array( $value ) && !\MongoDBRef::isRef( $value ) ):
				$this->__data[$key] = $this->createOrUpdateDbRefs($value);
			elseif ( $value instanceof \Nautik\Data\Model ):
				$value->save();
				$this->__data[$key] = \MongoDBRef::create($value->__collection, $value->id);
			endif;
		endforeach;*/
		
		// Update or create?
		if ( false == $this->__new )
			$data = $this->__update();
		else
			$data = $this->__create();
		
		// Check if nothing failed
		if ( false == $data )
			throw new \Nautik\Exception('Updating or creating of record has failed.');
			
		// Set the state of the model
		$this->__saved = true;
		$this->__new = false;
		
		// Run 'after_save' callback
		if ( method_exists( $this, $setter = "after_save" ) )
			$this->$setter;
		
		// Everything fine.
		return true;
	}
	
	/**
	 *
	 */
	private function __update() {
		// Run 'before_update' callback
		if ( method_exists( $this, $setter = "before_update" ) )
			$this->$setter;
		
		// Set 'updated_at'
		$this->__data['updated_at'] = new \MongoDate();
		
		// Update
		$result = \Nautik\Data\Connection::getCollection($this->__collection)->update(array('_id' => $this->__data['_id']), $this->__data);
		
		// Run 'after_update' callback
		if ( method_exists( $this, $setter = "after_update" ) )
			$this->$setter;
		
		// Return the result
		return $result;
	}
	
	/**
	 *
	 */
	private function __create() {
		// Run 'before_create' callback
		if ( method_exists( $this, $setter = "before_create" ) )
			$this->$setter;
		
		// Set 'updated_at' & 'created_at'
		$this->__data['updated_at'] = new \MongoDate();
		$this->__data['created_at'] = new \MongoDate();
		
		// Insert into the collection
		$result = \Nautik\Data\Connection::getCollection($this->__collection)->insert($this->__data);
		
		// Run 'after_create' callback
		if ( method_exists( $this, $setter = "after_create" ) )
			$this->$setter;
		
		// Return the result
		return $result;
	}
}
