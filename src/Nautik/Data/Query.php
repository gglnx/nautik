<?php
/**
 * @package		Nautik
 * @version		1.0-$Id$
 * @link		http://github.com/gglnx/nautik
 * @author		Dennis Morhardt <info@dennismorhardt.de>
 * @copyright	Copyright 2012, Dennis Morhardt
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

/**
 * Declare UTF-8 and the namespace
 */
declare(encoding='UTF-8');
namespace Nautik\Data;

/**
 * 
 */
class Query implements \IteratorAggregate {
	/**
	 *
	 */
	private $query = array('fields' => array(), 'sort' => array(), 'options' => array());
	
	/**
	 *
	 */
	private $single = false;
	
	/**
	 *
	 */
	private $count = false;
	
	/**
	 *
	 */
	private $model;
	
	/**
	 *
	 */
	private $collection;
	
	/**
	 *
	 */
	public function __construct($model, $collection, $single = false) {
		$this->model = $model;
		$this->single = $single;
		$this->collection = $collection;
		
		return $this;
	}
	
	/**
	 *
	 */
	public function getIterator() {
		return new \ArrayIterator($this->select());
    }

	/**
	 *
	 */
	public function select($select = true) {
		// Open the query
		$query = Connection::getCollection($this->collection)->find($this->query['fields'], $this->query['options']);
		
		// Sort entries
		$query->sort($this->query['sort']);
		
		// Limit entries
		if ( $this->query['limit'] )
			$query->limit($this->query['limit']);
		
		// Skip entries
		if ( $this->query['skip'] )
			$query->skip($this->query['skip']);
			
		// Get the count
		$this->count = $query->count(($this->query['limit'] || $this->query['skip']));
	
		// Run this query
		if ( false == $select )
			return $this;
			
		// Transform the MongoCursor object to an array
		$objects = array();
		foreach ( $query as $id => $object )
			$objects[] = new $this->model($object, true, true);
			
		// Return the array or only just one object
		return $this->single ? ( $objects[0] ?: false ) : $objects;
    }
	
	/**
	 *
	 */
	public function count() {
		return $this->select(false)->count;
	}
	
	/**
	 *
	 */
	public function remove($justOne = false) {
		return Connection::getCollection($this->collection)->remove($this->query['fields'], $justOne);
	}
	
	/**
	 *
	 */
	public function is($field, $value) {
		if ( 'id' == $field ):
			$field = '_id';
			$value = ( $value instanceof \MongoId ) ? $value : new \MongoId($value);
		endif;
		
		if ( $value instanceof \Nautik\Data\Model )
			$value = \MongoDBRef::create($value->getCollection(), $value->id);
		
		$this->query['fields'][$field] = $value;
		return $this;
	}
	
	/**
	 *
	 */
	public function not($field, $value) {
		if ( 'id' == $field ):
			$field = '_id';
			$value = ( $value instanceof \MongoId ) ? $value : new \MongoId($value);
		endif;
	
		return $this->addOperator('not', $field, $value);
	}
	
	/**
	 *
	 */
	public function in($field, $values) {
		if ( 'id' == $field ):
			$field = '_id';
			$values = ( $value instanceof \MongoId ) ? $value : new \MongoId($value);
		endif;
		
		// Transform $values to an array if needed
		if ( false == is_array( $values ) )
			$values = array($values);
			
		return $this->addOperator('in', $field, $values);
	}
	
	/**
	 *
	 */
	public function notIn($field, $values) {
		if ( 'id' == $field ):
			$field = '_id';
			$values = ( $value instanceof \MongoId ) ? $value : new \MongoId($value);
		endif;
	
		return $this->addOperator('nin', $field, $values);
	}
	
	/**
	 *
	 */
	public function notEqual($field, $value) {
		return $this->addOperator('ne', $field, $value);
	}
	
	/**
	 *
	 */
	public function ref($field, $collection, $id) {
		return $this->is($field, \MongoDBRef::create($collection, $id));
	}
	
	/**
	 *
	 */
	public function gt($field, $value) {
		return $this->addOperator('gt', $field, $value);
	}
	
	/**
	 *
	 */
	public function gte($field, $value) {
		return $this->addOperator('gte', $field, $value);
	}
	
	/**
	 *
	 */
	public function lt($field, $value) {
		return $this->addOperator('lt', $field, $value);
	}
	
	/**
	 *
	 */
	public function lte($field, $value) {
		return $this->addOperator('lte', $field, $value);
	}
	
	/**
	 *
	 */
	public function range($field, $start, $end) {
		return $this->addOperator('gt', $field, $start)->addOperator('lt', $field, $end);
	}
	
	/**
	 *
	 */
	public function size($field, $value) {
		return $this->addOperator('size', $field, $value);
	}
	
	/**
	 *
	 */
	public function exists($field, $exists = true) {
		return $this->addOperator('exists', $field, $exists);
	}
	
	/**
	 *
	 */
	public function all($field, $values) {
		return $this->addOperator('all', $field, $values);
	}
	
	/**
	 *
	 */
	public function mod($field, $value) {
		return $this->addOperator('mod', $field, $value);
	}
	
	/**
	 *
	 */
	public function near($field, $lat, $lng, $maxDistance = null) {
		if ( null !== $maxDistance )
			$this->addOperator('maxDistance', $field, $maxDistance);
		
		return $this->addOperator('near', $field, array($lat, $lng));
	}
	
	/**
	 *
	 */
	public function regex($field, $value) {
		$this->query['fields'][$field] = new \MongoRegex($value);
		
		return $this;
	}
	
	/**
	 *
	 */
	public function like($field, $value) {
		return $this->regex($field, '/.*' . $value . '.*/i');
	}
	
	/**
	 *
	 */
	public function jsfunc($function) {
		$this->query['fields']['$where'] = new \MongoCode($function);
		
		return $this;
	}
	
	/**
	 *
	 */
	public function exclude($field) {
		$this->query['options'][$field] = 0;
		
		return $this;
	}
	
	/**
	 *
	 */
	public function slice($field, $values) {
		$this->query['options'][$field] = $values;
		
		return $this;
	}
	
	/**
	 *
	 */
	public function sort($field, $ascending = true) {
		$this->query['sort'] = array();
		$this->query['sort'][$field] = $ascending ? 1 : -1;
		
		return $this;
	}
	
	/**
	 *
	 */
	public function limit($count) {
		$this->query['limit'] = $count;
		
		return $this;
	}
	
	/**
	 *
	 */
	public function skip($count) {
		$this->query['skip'] = $count;
		
		return $this;
	}
	
	/**
	 *
	 */
	private function addOperator($operator, $field, $value) {
		$this->query['fields'][$field]['$' . $operator] = $value;
		
		return $this;
	}
}
