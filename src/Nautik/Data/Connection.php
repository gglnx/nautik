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
class Connection {
	/**
	 *
	 */
	private static $__server = null;
	
	/**
	 *
	 */
	private static $__database = null;
	
	/**
	 *
	 */
	private static $__mongo = null;
	
	/**
	 *
	 */
	private static $__collection = array();

	/**
	 *
	 */
	public static function init($connection) {
		// Init the server and database
		self::$__server = $connection[0];
		self::$__database = $connection[1];
	}
	
	/**
	 *
	 */
	public static function connect() {
		// Check if the mongo extension is installed
		if ( false == extension_loaded( 'mongo' ) )
			throw new \Nautik\Core\Exception('Please install the mongo extension for PHP.');

		// Connect if needed
		if ( false == is_object( self::$__mongo ) ):
			// Try to connect to the MongoDB
			try {
				self::$__mongo = new \Mongo(self::$__server);
			} catch( \MongoConnectionException $e ) {
				throw new \Nautik\Core\Exception('Could not connect to the mongo database, error message: ' . $e->getMessage());
			}
		endif;
	}
	
	/**
	 *
	 */
	public static function getCollection($collection) {
		// Connect to MongoDB if needed
		self::connect();
		
		// Select the collection
		if ( false == self::$__collection[$collection] ):
			try {
				self::$__collection[$collection] = new \MongoCollection(self::$__mongo->selectDB(self::$__database), $collection);
			} catch( \MongoCursorException $e ) {
				throw new \Nautik\Core\Exception('Could not select the collection: %s', $e->getMessage());
			}
		endif;
		
		// Return MongoCollection object
		return self::$__collection[$collection];
	}
	
	/**
	 *
	 */
	public static function dropCollection($collection) {
		// Drop the collection
		try {
			self::getCollection($collection)->drop();
		} catch( \MongoCursorException $e ) {
			throw new \Nautik\Core\Exception('Could not drop the collection: %s', $e->getMessage());
		}
	}
}
