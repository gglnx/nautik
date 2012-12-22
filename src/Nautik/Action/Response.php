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

namespace Nautik\Action;

/**
 * Manages the response date for a request
 */
class Response {
	/**
	 * HTTP header codes
	 */
	protected static $reasons = array(
		100 => 'Continue', 'Switching Protocols',
		200 => 'OK', 'Created', 'Accepted', 'Non-Authoritative Information',
		       'No Content', 'Reset Content', 'Partial Content',
		300 => 'Multiple Choices', 'Moved Permanently', 'Found', 'See Other',
		       'Not Modified', 'Use Proxy', '(Unused)', 'Temporary Redirect',
		400 => 'Bad Request', 'Unauthorized', 'Payment Required','Forbidden', 'Not Found',
		       'Method Not Allowed', 'Not Acceptable', 'Proxy Authentication Required',
		       'Request Timeout', 'Conflict', 'Gone', 'Length Required', 'Precondition Failed',
		       'Request Entity Too Large', 'Request-URI Too Long', 'Unsupported Media Type',
		       'Requested Range Not Satisfiable', 'Expectation Failed',
		500 => 'Internal Server Error', 'Not Implemented', 'Bad Gateway', 'Service Unavailable',
		       'Gateway Timeout', 'HTTP Version Not Supported'
	);
	
	/**
	 * List of supported minetypes
	 */
	protected static $minetypes = array(
		'xml' => 'application/xml',
		'json' => 'application/javascript',
		'serialized' => 'application/vnd.php.serialized',
		'plain' => 'text/plain',
		'html' => 'text/html',
		'csv' => 'application/csv',
		'css' => 'text/css',
		'js' => 'text/javascript'
	);
	
	/**
	 *
	 */
	protected static $minetype = "html";
	
	/**
	 * Current HTTP status code of the document
	 */
	protected static $status = 200;
	
	/**
	 * Current HTTP status message of the document
	 */
	protected static $reason = 'OK';
	
	/**
	 * Current HTTP headers
	 */
	protected static $headers = array();
	
	/**
	 * Template to use
	 */
	protected static $template;
	
	/**
	 *
	 */
	protected static $isOutput = true;
	
	/**
	 *
	 */
	protected static $useTemplate = true;
	
	/**
	 *
	 */
	public static function getReason($status) {
		// Return the reason if it exists
		return isset( self::$reason[$status] ) ? self::$reason[$status] : '';
	}
	
	/**
	 *
	 */
	public static function setTemplate($template, $minetype = 'html') {
		// Change the configuration
		self::$template = $template;
		
		// Set the minetype
		self::setMinetype($minetype);
	}
	
	/**
	 *
	 */
	public static function getTemplate() {
		// Return current set template
		return self::$template;
	}
	
	/**
	 *
	 */
	public static function enableOutput() {
		// Enable output for this request
		self::$isOutput = true;
	}
	
	/**
	 *
	 */
	public static function disableOutput() {
		// Disable output for this request
		self::$isOutput = false;
	}
	
	/**
	 *
	 */
	public static function isOutput() {
		// Get the current setting
		return self::$isOutput;
	}
	
	/**
	 *
	 */
	public static function enableTemplate() {
		// Enable use for template for this request
		self::$useTemplate = true;
	}
	
	/**
	 *
	 */
	public static function disableTemplate() {
		// Disable use for template for this request
		self::$useTemplate = false;
	}
	
	/**
	 *
	 */
	public static function useTemplate() {
		// Get the current setting
		return self::$useTemplate;
	}
	
	/**
	 *
	 */
	public static function setMinetype($shortform, $minetype = null) {
		// Check if minetype exists
		if ( false == in_array( $shortform, array_keys( self::$minetypes ) ) && false == $minetype )
			return false;
			
		// Add custom minetype if given
		if ( false != $minetype )
			self::$minetypes[$shortform] = $minetype;

		// Change the configuration
		self::$minetype = $shortform;
	}
	
	/**
	 *
	 */
	public static function getMinetype($useShortform = true) {
		// Get the current minetype
		return $useShortform ? self::$minetype : self::$config[self::$minetype];
	}
	
	/**
	 *
	 */
	public static function getStatus() {
		// Get current status
		return self::$status;
	}
	
	/**
	 *
	 */
	public static function setStatus($status, $reason = NULL) {
		// Set the status and the reason
		self::$status = $status;
		self::$reason = isset( $reason ) ? $reason : self::getReason($status);
	}
	
	/**
	 *
	 */
	public static function setCacheHeaders($cache = 'no-store') {
		// Set cache control header
		self::addHeader('Cache-Control', $cache);
	}
	
	/**
	 *
	 */
	public static function addHeader($key, $value) {
		// Add header to the storage
		self::$headers[$key] = $value;
	}
	
	/**
	 *
	 */
	public static function sendHeaders() {
		// Send minetype
		self::addHeader('Content-Type', self::$minetypes[self::$minetype]);
	
		// Send the status header
		if ( isset( self::$status ) )
			self::sendHeader(sprintf('HTTP/1.1 %d %s', self::$status, self::$reason), true, self::$status);

		// Send the other headers
		foreach ( self::$headers as $k => $v )
			self::sendHeader("$k: $v");
	}
	
	/**
	 *
	 */
	public static function redirect($location, $status = 302, $sendIt = true) {
		// Set the headers and the status code...
		self::setStatus($status);
		self::addHeader('Location', $location);
		
		// Send the headers if requested
		if ( true == $sendIt )
			self::sendHeaders();
	}
	
	/**
	 *
	 */
	private static function sendHeader($header, $replace = false, $status = null) {
		if ( isset( $status ) )
			header($header, $replace, $status);
		else
			header($header, $replace);
	}
}
