<?php/** * @package     Nautik * @version     1.0-$Id$ * @link        http://github.com/gglnx/nautik * @author      Dennis Morhardt <info@dennismorhardt.de> * @copyright   Copyright 2012, Dennis Morhardt * * Permission is hereby granted, free of charge, to any person * obtaining a copy of this software and associated documentation * files (the "Software"), to deal in the Software without * restriction, including without limitation the rights to use, * copy, modify, merge, publish, distribute, sublicense, and/or sell * copies of the Software, and to permit persons to whom the * Software is furnished to do so, subject to the following * conditions: * * The above copyright notice and this permission notice shall be * included in all copies or substantial portions of the Software. * * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR * OTHER DEALINGS IN THE SOFTWARE. */namespace Nautik\Action;/** * The Request class stores every information about the request. */class Request {	/**	 *	 */	protected static $parameters;		/**	 *	 */	protected static $putDelete;		/**	 * Stores the cached URI string	 */	protected static $requestUri = '';		/**	 *	 */	public static function __callStatic($function, $args) {		// Get key from function name		$key = strtolower(substr($function, 3));		// Try to find key in parameters		if ( 'get' == substr( $function, 0, 3 ) && isset( self::$parameters[$key] ) )			return self::$parameters[$key];					// Return null if key was not found		return NULL;	}	/**	 * Returnes the request URI string	 */	public static function getRequestUri() {		// Return cached requested URI		if ( '' != self::$requestUri )			return self::$requestUri;		// Remove the script name (e.g. index.php) from the url		self::$requestUri = $_SERVER['REQUEST_URI'];		if ( "index.php" == $_SERVER['SCRIPT_NAME'] )			self::$requestUri = str_replace($_SERVER['SCRIPT_NAME'], "", self::$requestUri);		// If the base url not /, filter it from the url		if ( "/" != \App\Application::$urlBase )			self::$requestUri = str_replace(\App\Application::$urlBase, "", self::$requestUri);				// Filter parameters from the url out		if ( stristr( self::$requestUri, "?" ) ):			$urlarray = explode("?", self::$requestUri);			self::$requestUri = $urlarray[0];		endif;				// Add a slash, if not exists		if ( "/" != substr( self::$requestUri, "-1" ) )			self::$requestUri = self::$requestUri . "/";				// To many slashs?		if ( "//" == self::$requestUri || "///" == self::$requestUri )			self::$requestUri = "/";				return self::$requestUri;	}		/**	 *	 */	public static function getReferrer($internal = false) {		// Check if a referrer is set		if ( false == $_SERVER["HTTP_REFERER"] )			return false;			// Escape		$referrer = $_SERVER["HTTP_REFERER"];				// Get the internal referrer		if ( true == $internal ):			$url = parse_url($referrer);			$referrer = $url['path'];			if ( '/' != $referrer && '/' != self::$config['base'] ):				$referrer = str_replace(\App\Application::$urlBase, '', $referrer);			endif;		endif;				// Return the referrer		return $referrer;	}	/**	 *	 */		public static function setParameters($parameters) {		self::$parameters = $parameters;	}	/**	 *	 */		public static function getParameters() {		return self::$parameters;	}	/**	 *	 */		public static function isDelete() {		return strtolower( $_SERVER['REQUEST_METHOD'] ) == 'delete';	}	/**	 *	 */		public static function isGet() {		return strtolower( $_SERVER['REQUEST_METHOD'] ) == 'get';	}	/**	 *	 */		public static function isPost() {		return strtolower( $_SERVER['REQUEST_METHOD'] ) == 'post';	}	/**	 *	 */		public static function isPut() {		return strtolower( $_SERVER['REQUEST_METHOD'] ) == 'put';	}	/**	 *	 */		public static function isAjax() {		return isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest';	}}