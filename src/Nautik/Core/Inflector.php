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
namespace Nautik\Core;

/**
 * Utility for modifying format of words. Change singular to plural and vice versa.
 * Under_score a CamelCased word and vice versa. Replace spaces and special characters.
 * Create a human readable word from the others. Used when consistency in naming
 * conventions must be enforced.
 */
class Inflector {
	/**
	 * Indexed array of words which are the same in both singular and plural form
	 */
	protected static $_uninflected = array(
		'Amoyese', 'bison', 'Borghese', 'bream', 'breeches', 'britches', 'buffalo', 'cantus',
		'carp', 'chassis', 'clippers', 'cod', 'coitus', 'Congoese', 'contretemps', 'corps',
		'debris', 'diabetes', 'djinn', 'eland', 'elk', 'equipment', 'Faroese', 'flounder',
		'Foochowese', 'gallows', 'Genevese', 'Genoese', 'Gilbertese', 'graffiti',
		'headquarters', 'herpes', 'hijinks', 'Hottentotese', 'information', 'innings',
		'jackanapes', 'Kiplingese', 'Kongoese', 'Lucchese', 'mackerel', 'Maltese', 'media',
		'mews', 'moose', 'mumps', 'Nankingese', 'news', 'nexus', 'Niasese', 'People',
		'Pekingese', 'Piedmontese', 'pincers', 'Pistoiese', 'pliers', 'Portuguese',
		'proceedings', 'rabies', 'rice', 'rhinoceros', 'salmon', 'Sarawakese', 'scissors',
		'sea[- ]bass', 'series', 'Shavese', 'shears', 'siemens', 'species', 'swine', 'testes',
		'trousers', 'trout','tuna', 'Vermontese', 'Wenchowese', 'whiting', 'wildebeest',
		'Yengeese'
	);

	/**
	 * Contains the list of singularization rules
	 */
	protected static $_singular = array(
		'/(s)tatuses$/i' => '\1\2tatus',
		'/^(.*)(menu)s$/i' => '\1\2',
		'/(quiz)zes$/i' => '\\1',
		'/(matr)ices$/i' => '\1ix',
		'/(vert|ind)ices$/i' => '\1ex',
		'/^(ox)en/i' => '\1',
		'/(alias)(es)*$/i' => '\1',
		'/(alumn|bacill|cact|foc|fung|nucle|radi|stimul|syllab|termin|viri?)i$/i' => '\1us',
		'/(cris|ax|test)es$/i' => '\1is',
		'/(shoe)s$/i' => '\1',
		'/(o)es$/i' => '\1',
		'/ouses$/' => 'ouse',
		'/uses$/' => 'us',
		'/([m|l])ice$/i' => '\1ouse',
		'/(x|ch|ss|sh)es$/i' => '\1',
		'/(m)ovies$/i' => '\1\2ovie',
		'/(s)eries$/i' => '\1\2eries',
		'/([^aeiouy]|qu)ies$/i' => '\1y',
		'/([lr])ves$/i' => '\1f',
		'/(tive)s$/i' => '\1',
		'/(hive)s$/i' => '\1',
		'/(drive)s$/i' => '\1',
		'/([^fo])ves$/i' => '\1fe',
		'/(^analy)ses$/i' => '\1sis',
		'/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\1\2sis',
		'/([ti])a$/i' => '\1um',
		'/(p)eople$/i' => '\1\2erson',
		'/(m)en$/i' => '\1an',
		'/(c)hildren$/i' => '\1\2hild',
		'/(n)ews$/i' => '\1\2ews',
		'/^(.*us)$/' => '\\1',
		'/s$/i' => ''
	);

	/**
	 * Contains the list of pluralization rules
	 */
	protected static $_plural = array(
		'/(s)tatus$/i' => '\1\2tatuses',
		'/(quiz)$/i' => '\1zes',
		'/^(ox)$/i' => '\1\2en',
		'/([m|l])ouse$/i' => '\1ice',
		'/(matr|vert|ind)(ix|ex)$/i'  => '\1ices',
		'/(x|ch|ss|sh)$/i' => '\1es',
		'/([^aeiouy]|qu)y$/i' => '\1ies',
		'/(hive)$/i' => '\1s',
		'/(?:([^f])fe|([lr])f)$/i' => '\1\2ves',
		'/sis$/i' => 'ses',
		'/([ti])um$/i' => '\1a',
		'/(p)erson$/i' => '\1eople',
		'/(m)an$/i' => '\1en',
		'/(c)hild$/i' => '\1hildren',
		'/(buffal|tomat)o$/i' => '\1\2oes',
		'/(alumn|bacill|cact|foc|fung|nucle|radi|stimul|syllab|termin|vir)us$/i' => '\1i',
		'/us$/' => 'uses',
		'/(alias)$/i' => '\1es',
		'/(ax|cri|test)is$/i' => '\1es',
		'/s$/' => 's',
		'/^$/' => '',
		'/$/' => 's'
	);
	
	/**
	 * Contains the list of irregular pluralizations
	 */
	protected static $_irregular = array(
		'atlas' => 'atlases', 'beef' => 'beefs', 'brother' => 'brothers',
		'child' => 'children', 'corpus' => 'corpuses', 'cow' => 'cows',
		'ganglion' => 'ganglions', 'genie' => 'genies', 'genus' => 'genera',
		'graffito' => 'graffiti', 'hoof' => 'hoofs', 'loaf' => 'loaves', 'man' => 'men',
		'money' => 'monies', 'mongoose' => 'mongooses', 'move' => 'moves',
		'mythos' => 'mythoi', 'numen' => 'numina', 'occiput' => 'occiputs',
		'octopus' => 'octopuses', 'opus' => 'opuses', 'ox' => 'oxen', 'penis' => 'penises',
		'person' => 'people', 'sex' => 'sexes', 'soliloquy' => 'soliloquies',
		'testis' => 'testes', 'trilby' => 'trilbys', 'turf' => 'turfs'
	);
	
	/**
	 * Contains the list of uncountables
	 */
	protected static $_uncountable = array(
		'.*[nrlm]ese', '.*deer', '.*fish', '.*measles', '.*ois', '.*pox', '.*sheep'
	);

	/**
	 * Changes the form of a word from singular to plural.
	 *
	 * @param string $word Word in singular form.
	 * @return string Word in plural form.
	 */
	public static function pluralize($word) {
		// Check if the word is uncountable
		if ( preg_match( '/^((?:' . join( '|', static::$_uncountable ) . '))$/i', $word ) )
			return $word;
		
		// Check if the word has an irregular pluralization
		if ( preg_match( '/(.*)\\b((?:' . join( '|', array_keys( static::$_irregular ) ) . '))$/i', $word, $regs ) )
			return $regs[1] . substr($word, 0, 1) . substr(static::$_irregular[strtolower($regs[2])], 1);
		
		// Match the pluralization rules
		foreach ( static::$_plural as $rule => $replacement )
			if ( preg_match( $rule, $word ) )
				return preg_replace($rule, $replacement, $word);

		// No pluralization possible
		return $word;
	}

	/**
	 * Changes the form of a word from plural to singular.
	 *
	 * @param string $word Word in plural form.
	 * @return string Word in singular form.
	 */
	public static function singularize($word) {
		// Check if the word has an irregular singularization
		if ( preg_match( '/(.*)\\b((?:' . join('|', array_keys( array_flip( static::$_irregular ) ) ) . '))\$/i', $word, $regs ) )
			return $regs[1] . substr($word, 0, 1) . substr($irregular[strtolower($regs[2])], 1);
		
		// Check if the word is uncountable
		if ( preg_match( '/^((?:' . join( '|', static::$_uncountable ) . '))$/i', $word ) )
			return $word;
		
		// Match the singularization rules
		foreach ( static::$_singular as $rule => $replacement )
			if ( preg_match( $rule, $word ) )
				return preg_replace($rule, $replacement, $word);

		// No singularization possible
		return $word;
	}

	/**
	 * Takes a under_scored word and turns it into a CamelCased or camelBack word
	 *
	 * @param string $word An under_scored or slugged word (i.e. `'red_bike'` or `'red-bike'`).
	 * @param boolean $cased If false, first character is not upper cased
	 * @return string CamelCased version of the word (i.e. `'RedBike'`).
	 */
	public static function camelize($word, $cased = true) {
		return preg_replace('/(^|_|-)(.)/e', "strtoupper('\\2')", strval($word));
	}

	/**
	 * Takes a CamelCased version of a word and turns it into an under_scored one.
	 *
	 * @param string $word CamelCased version of a word (i.e. `'RedBike'`).
	 * @return string Under_scored version of the workd (i.e. `'red_bike'`).
	 */
	public static function underscore($word) {
		return static::slugize($word, '_');
	}

	/**
	 * Returns a string with all spaces converted to given replacement and
	 * non word characters removed.
	 *
	 * @param string $string An arbitrary string to convert.
	 * @param string $replacement The replacement to use for spaces.
	 * @return string The converted string.
	 */
	public static function slugize($string, $replacement = '-') {
		$string = preg_replace('/&.+?;/', '', strtolower($string));
		$string = preg_replace('/[^%a-z0-9 _-]/', '', $string);
		$string = preg_replace('/\s+/', $replacement, $string);
		$string = preg_replace('|' . $replacement . '+|', $replacement, $string);
		$string = trim(stripslashes($string), $replacement);

		return $string;
	}

	/**
	 * Takes an under_scored version of a word and turns it into an human readable form
	 * by replacing underscores with a space, and by upper casing the initial character.
	 *
	 * @param string $word Under_scored version of a word (i.e. `'red_bike'`).
	 * @param string $separator The separator character used in the initial string.
	 * @return string Human readable version of the word (i.e. `'Red Bike'`).
	 */
	public static function humanize($word, $separator = '_') {
		return ucwords(str_replace($separator, " ", $word));
	}

	/**
	 * Takes a CamelCased class name and returns corresponding under_scored table name.
	 *
	 * @param string $className CamelCased class name (i.e. `'Post'`).
	 * @return string Under_scored and plural table name (i.e. `'posts'`).
	 */
	public static function tableize($className) {
		// If namespaced name, get only the class name
		if ( false !== strpos( $className, "\\" ) )
			$className = strstr($className, "\\");

		return static::pluralize(static::underscore($className));
	}

	/**
	 * Takes a under_scored table name and returns corresponding class name.
	 *
	 * @param string $tableName Under_scored and plural table name (i.e. `'posts'`).
	 * @return string CamelCased class name (i.e. `'Post'`).
	 */
	public static function classify($tableName) {
		return static::camelize(static::singularize($tableName));
	}
	
	/**
	 *
	 */
	public static function urlize($text) {
		$text = preg_replace('/(?:f|ht)tp:\\/\\/[-a-zA-Z0-9@:%_+.~#?&\\/=]+/', '<a href="\\0">\\0</a>', $text);
		$text = preg_replace('/([[:space:][{}]|^)(www\\.[-a-zA-Z0-9@:%_+.~#?&\\/=]+)/', '\\1<a href="http://\\2">\\2</a>', $text);
		$text = preg_replace('/[_.0-9a-zA-Z-]+@(?:[0-9a-zA-Z][0-9a-zA-Z-]+\\.)+[a-zA-Z]{2,3}/', '<a href="mailto:\\0">\\0</a>', $text);

		return $text;
	}
}
