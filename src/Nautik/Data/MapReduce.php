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
class MapReduce {
	/**
	 *
	 */  
	private $query = array();
  
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
	private $keepOutput = false;

	/**
	 *
	 */
	public function __construct($model, $collection) {
		$this->model = $model;
		$this->collection = $collection;
		
		return $this;
	}

	/**
	 *
	 */
	public function select() {
		// Generate output name
		if ( false == isset( $this->query["out"] ) )
			$this->query["out"] = "_" . md5($this->collection . rand(0, 1000));

		// Add collection name to MapReduce query
		$this->query = array("mapreduce" => $this->collection) + $this->query;

		// Send query to database
		$response = Connection::command($this->query);

		// 
		if ( !isset( $response["errmsg"] ) && 1 === (int) $response["ok"] ):
			// Get data
			$data = array();
			foreach ( Connection::getCollection($response["result"])->find() as $d ):
				$data[] = $d;
			endforeach;
		else:
			throw new \Nautik\Core\Exception('Error on MapReduce: ' . $response["errmsg"]);
		endif;

		// Delete temp collection
		if ( false == $this->keepOutput )
			Connection::dropCollection($this->query["out"]);

		return $data;
	}

	/**
	 *
	 */
	public function map($map) {
		// Add map to query
		$this->query["map"] = new \MongoCode($map);

		return $this;
	}

	/**
	 *
	 */
	public function reduce($reduce) {
		// Add reduce to query
		$this->query["reduce"] = new \MongoCode($reduce);

		return $this;
	}

	/**
	 *
	 */
	public function finalize($finalize) {
		// Add finalize to query
		$this->query["finalize"] = new \MongoCode($finalize);

		return $this;
	}

	/**
	 *
	 */
	public function query($query) {
		// Add query to query
		$this->query["query"] = $query;

		return $this;
	}

	/**
	 *
	 */
	public function keepOutput($keepOutput = true) {
		// Keep output?
		$this->keepOutput = $keepOutput;
	
		return $this;
	}

	/**
	 *
	 */
	public function sort($sort) {
		// Add sort to query
		$this->query["sort"] = $sort;

		return $this;
	}

	/**
	 *
	 */
	public function limit($limit) {
		// Add limit to query
		$this->query["limit"] = $limit;

		return $this;
	}

	/**
	 *
	 */
	public function scope($scope) {
		// Add scope to query
		$this->query["scope"] = $scope;

		return $this;
	}

	/**
	 *
	 */
	public function outputCollection($outputCollection) {
		// Add outputCollection to query
		$this->query["out"] = $outputCollection;

		return $this;
	}
}
