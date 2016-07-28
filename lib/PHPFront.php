<?php

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 * 
 * @file
 * @package		PHPFront
 * @author		Oxford Harrison <phpfront@gmail.com>
 * @copyright	2016 Ox-Harris Creative
 * @license		GPL-3.0
 * @version		Release: 1.0.0
 * @link		https://www.facebook.com/PHPFront/
 * @uses		PHPFrontNodelist	to retrieve elements of a Nodelist or auto-generated elements in a particular order.
 * @see			PHPFrontNodelist
 */	 



/**
 * Try loading the PHPFrontDom class.
 * If found, then there may be some global autoloader like Composer.
 * If not, we manually include the core lib classess.
 */
if (!class_exists('PHPFrontDom'))
{
     require_once dirname(__FILE__) . '/PHPFrontDom.php';
     require_once dirname(__FILE__) . '/PHPFrontNodeList.php';
}







/** 
 * The PHPFront Machine.
 * Builds up data stack with every data associated with an element,
 * accepts a HTML template and renders data into it.
 * Built upon the PHP DOMDocument.
 *
 * @todo	Some regex operations within getElementsBySelector().
 *			Currently, they intentional throw Exception as reminders.
 */
Class PHPFront
{
	/**
     * Holds the current PHPFront version.
     *
     * @var string $version
	 *
	 * This is used internally. But readonly via magic __get()
	 *
	 * @access private
     */
	private static $version = '1.0.1';
	
	/**
     * Holds the mininum required PHP version.
     *
     * @var string $min_php_version
	 *
	 * This is used internally. But readonly via magic __get()
	 *
	 * @access private
     */
	private static $min_php_version = '5.3.0';
	
	/**
     * Holds an array of element selectors with their respective handlers.
     *
     * @var array $value_modifiers
	 *
	 * @see setValueModifier().
	 *
	 * This is used internally. But readonly via magic __get()
	 *
	 * @access private
     */
	private $value_modifiers;
	
	/**
     * The main tree of all assigned data. Usually a multidimensional array
	 * of element identifiers and their respective data.
     *
     * @var array $data_stack
	 *
	 * @see assign().
	 * @see obtainData().
	 * @see renderTemplate().
	 *
     * This is used internally. But readonly via obtainData() or magic __get()
     *
     * @access private
     */
	private $data_stack = array();
	
	/**
     * A linear array
	 * of element identifiers and their respective paths to data to be included.
	 *
     * @var array $includes_stack
	 *
	 * @see setInclude().
	 * @see assign().
	 * @see processAllIncludes().
	 *
     * This is used internally. But readonly via magic __get()
     *
     * @access private
     */
	private $includes_stack = array();
			
	/**
     * The PHPFrontDom of the currently loaded template.
     *
     * @var object $PHPFrontDom
	 *
	 * @see setTemplate()
	 * @see __destruct()
     */
    public $PHPFrontDom;


	/**
     * A configuration property that tells PHPFront whether to format the html output of the rendered template.
	 * This will attempt proper indentation and balancing of html tags.
     *
     * @var bool $allow_html_formatting
	 *
	 * @see PHPFrontNodeList::duplicateNode()
	 * @see setTemplate()
	 * 
	 * This property will not change until a major release.
	 *
	 * @api
	 *
     * @access public
     */
	public $allow_html_formatting = true;
	
	/**
     * A linear array of repeat function names to be followed 
	 * when repeating on the x-axis - that is, going deeper in a multidimensional array.
	 * It is especially used by PHPFrontNodeList.
     *
     * @var array $repeat_fn
	 *
	 * @see setRepeatFunctions()
	 * 
     * This is used internally. But readonly via magic __get()
     *
     * @access private
     */
	public $repeat_fn = 'repeat-last';
	
	/**
     * A linear array of repeat function names to be followed 
	 * when repeating on the y-axis - that is, when looping over items in an array.
	 * It is especially used by PHPFrontNodeList.
     *
     * @var array $repeat_fn_y
	 *
	 * @see setRepeatFunctions()
	 * 
     * This is used internally. But readonly via magic __get()
     *
     * @access private
     */
	public $repeat_fn_y;
	
	/**
     * This records how many levels deep the parser has gone in a multidimensional array.
     *
     * @var int $repeat_depth_count
	 *
	 * @see elemSetContent()
	 * 
     * This is used internally. But readonly via magic __get()
     *
     * @access private
     */
	private $repeat_depth_count = 0;
	
	/**
     * A status flag set by the parser indicating whether or not it is
	 * re-parsing the whole template over again.
	 * Reparse usually happens when PHPFront::$parse_inserted_data is set to true.
	 * On reparse, PHPFront skips already parsed elements (because it is able to recognize them), but looks
	 * out for new elements that were inserted into the template on the previous
	 * renderTemplate() operation, which are now part of the whole PHPFrontDom, and which have data assigned to them on PHPFront::$data_stack.
     *
     * @var bool $on_reparse
	 *
	 * @see PHPFront::$parse_inserted_data
	 * @see renderTemplate()
	 *
     * This is used internally. But readonly via magic __get()
     *
     * @access private
     */
	private $on_reparse = false;
	
	/**
     * A status flag set by the parser indicating whether or not render() has be called on this document.
	 * getRendered() will needs this flag.
     *
     * @var bool $is_template_rendered
	 *
	 * @see render()
	 *
	 * This property will not change until a major release.
	 *
	 * @api
	 *
     * @access public
     */
	public $is_template_rendered = false;
	
	/**
     * A configuration property set to tell PHPFront what to do to an element when
	 * the element's assigned data is empty.
	 * Recognized values are: clear, set_flag, clear_and_set_flag, no_render, do_nothing (the default).
     *
     * @var string $on_content_empty
	 *
	 * @see elemSetContent()
	 * 
     * This is used internally. But readonly via magic __get()
     *
	 * This property will not change until a major release.
	 *
	 * @api
	 *
     * @access public
     */
	public $on_content_empty = "do_nothing";
	
	/**
     * A configuration property that tells PHPFront whether to reparse the whole template
	 * after having inserted data on the first parse.
     *
	 * Reparsing finds only newly inserted elements on the template which have data assigned to them for whenever they are available
	 * on the document.
	 *
     * @var bool $parse_inserted_data
	 *
	 * @see PHPFront::$on_reparse
	 * 
	 * This property will not change until a major release.
	 *
	 * @api
	 *
     * @access public
     */
	public $parse_inserted_data = false;




	/*
	 *--------------
	 * MAGIC METHODS
	 *--------------
	*/
	
	
	
	
	
	/**
     * Constructor for PHPFront.
     *
     * Checks version compatibilty.
     * 
	 * @throws Exception
	 *
	 * @return void
     */
	public function __construct()
	{
		//Check the application's minimum supported PHP version.
		if (version_compare(PHP_VERSION, self::$min_php_version, '<'))
		{
			die('You need to use PHP '.self::$min_php_version.' or higher to run this version of PHPFront!');
		}
    }

	/**
     * Destructor for PHPFront.
     *
     * Frees up all memory used. May not be neccessary.
     * 
	 * @return void
     */
    public function __destruct()
	{
        // Frees memory associated with the PHPFrontDom
		unset($this->PHPFrontDom);
    }
	
	/**
     * Magic get for PHPFront.
     *
     * Retrieves a private of protected property. This makes them readonly.
     * 
	 * @throws Exception
     * 
	 * @return void
     */
    public function __get($param_name)
	{
		if (isset($this->{$param_name}))
		{
			return $this->{$param_name};
		}
		
		throw new Exception('Property '.$param_name.' not found! It must be undeclared!');
    }
	
	/**
     * Gives information about the current installation of PHPFront.
     *
	 * @api
     * 
	 * @return void
     */
    public static function info()
	{
		echo 'PHPFront - v'.self::$version."<br />\r\n";
		echo 'Current PHP version - v'.PHP_VERSION."<br />\r\n";
		echo 'Min PHP version - v'.self::$min_php_version."<br />\r\n";
    }
	
	
	
	/*
	 *-----------------------
	 * PUBLIC SET METHODS
	 *-----------------------
	*/
	
	
	
	/**
     * Give PHPFront a template to use for rendering.
     *
     * Ok to have used the assign() method before calling this.
	 * But be sure that setTemplate() has been called before the render() method;
	 * this makes the template available for render()
     * 
     * @param string	$template A valid HTML markup string, or a relative or absolute path to a HTML file
	 *
	 * @see PHPFront::assign()
	 * @see PHPFront::render()
	 *
	 * This method will not change until a major release.
	 *
	 * @api
	 *
	 * @throws Exception
	 *
	 * @uses PHPFrontDom to load a HTML template.
	 * @return void
     */
	public function setTemplate($template)
	{
		// Loads the PHPFrontDom 
		$doc = new PHPFrontDom('1.0', 'UTF-8'); 
		$doc->formatOutput = $this->allow_html_formatting;
		
		//$doc->preserveWhiteSpace = false;
		//$doc->recover = true;
		$doc->substituteEntities = false;
		
		// Suppress errors 
		libxml_use_internal_errors(true);
		
		// Load template
		if (preg_match("/<[^<].*>/", $template))
		{
			// $template is a markup string
			$doc->loadHTML($template);
		}
		elseif (!empty($template))
		{
			if (is_file($template))
			{
				// $template is a file
				$doc->loadHTMLFile($template);
			}
			else
			{
				throw new Exception('Template file not found: '.$template.'!');
			}
		}
		else
		{
			throw new Exception("No HTML data provided!"); 
		}
	
		$this->PHPFrontDom = $doc;
		$this->is_template_rendered = false; 
	}
	


	/**
     * Assign data to an element using an element selector as key.
     *
     * Use this to build up the total data stack for elements in the template.
	 * Can be called many times for an element selector to either add to already assigned data, or replace it.
     * 
     * @param string		$element_selector	A valid css selector or xpath query. {@see PHPFront::$default_element_selector_type}
     * @param string|array	$data				Simple string or compound array of data for an element.
     * @param bool			$replace			Whether to replace data already assigned or to add to it.
     * @param bool			$recurse			Whether to replace data recursely when $replace is set to true.
     * @param bool			$is_include_path	Whether the string $data is normal data or a path to a file to include on an element.
	 *
	 * This method will not change until a major release.
	 *
	 * @api
	 *
	 * @return	self							For method chaining
     */
	public function assign($element_selector, $data, $replace = true, $recurse = true, $is_include_path = false)
	{
		$data_stack = $is_include_path ? 'includes_stack' : 'data_stack';
		
		if ($replace)
		{
			if ($recurse)
			{
				$this->{$data_stack} = array_replace_recursive($this->{$data_stack}, array($element_selector => $data));
			}
			else
			{
				$this->{$data_stack} = array_replace($this->{$data_stack}, array($element_selector => $data));
			}
		}
		else
		{
			if ($recurse)
			{
				$this->{$data_stack} = array_merge_recursive($this->{$data_stack}, array($element_selector => $data));
			}
			else
			{
				$this->{$data_stack} = array_merge($this->{$data_stack}, array($element_selector => $data));
			}
		}
		
		return $this;
	}
	
	

	/**
	 * Alias of PHPFront::assign().
	 *
	 * @depreciated
	 */
	public function assignData($element_selector, $data, $replace = true, $recurse = true, $is_include_path = false)
	{
		//throw new ErrorException(__METHOD__." has been depreciated! Use PHPFront::assign() instead.", 0, E_USER_DEPRECIATED);
		return $this->assign($element_selector, $data, $replace, $recurse, $is_include_path);
	}
	
	
	
	/**
     * Assign whole data to PHPFront.
     *
     * This doesn't build up the data on the stack but sets a pre-built data stack to it. It replaces any value already set here.
	 * This could be very convenient if your application already has a data builder like PHPFront's. You simply obtain that data and set it here.
     * 
     * @param string|array	$data_stack			Simple strings or compound array of data for all elements.
	 *
	 * This method will not change until a major release.
	 *
	 * @api
	 *
	 * @return	self							For method chaining
     */
	public function assignStack($data_stack)
	{
		$this->data_stack = $data_stack;
		
		return $this;
	}



	/*
	 *-----------------------
	 * ADVANCED PUBLIC SET METHODS
	 *-----------------------
	*/
	
	
	
	/**
     * Assign an include path to an element using an element selector as key.
     *
     * Use this to build up the total include stack for elements in the template.
	 * Can be called many times for an element selector to either add to already assigned path, or replace it.
     * 
     * @param string		$element_selector	A valid css selector or xpath query. {@see PHPFront::$default_element_selector_type}
     * @param string|array	$data				Simple string or compound array of data for an element.
     * @param bool			$replace			Whether to replace paths already assigned or to add to it.
     * @param bool			$recurse			Whether to replace paths recursely when $replace is set to true.
	 *
	 * This method will not change until a major release.
	 *
	 * @api
	 *
	 * @uses	PHPFront::assign()
	 * @return	self							For method chaining
     */
	public function setInclude($element_selector, $data, $replace = true, $recurse = true)
	{
		return $this->assign($element_selector, $data, $replace, $recurse, true);
	}
	
	
	
	/**
     * Assign an external handler to an element using an element selector as key.
     *
	 * Can be called many times for an element selector to build up an array of handlers.
     * 
     * @param string				$element_selector	A valid css selector or xpath query. {@see PHPFront::$default_element_selector_type}
     * @param string|array|closure	$handler			A callable function.
	 *													String: function name (must be globally visible).
	 *													Array: class method in the form of array(class_name_or_object, method_name).
	 *													Closure: an anonymous function or inline function.
	 *
	 * This method will not change until a major release.
	 *
	 * @api
	 *
	 * @return	self									For method chaining
     */
	public function setValueModifier($element_selector, $handler)
	{
		$this->value_modifiers[$element_selector][] = $handler;
		
		return $this;
	}
	


	/**
     * Assign repeat function(s) globally.
	 *
	 * Override this setting on specific elements by
	 * specifying repeat functions inside the [@params] key element's compound data.
     *
	 * Repeat functions are used to set the particular algorithm or pattern to follow 
	 * when elements have to be repeated in order to render any extra items in data array when data items outnumbers element count.
	 *
     * @param string	$repeat_fn		An algarithm name from PHPFront's predefined types.
     * @param string	$repeat_fn_y	An algarithm specifically for repeating on the y-axis. If not supplied, $repeat_fn is used.
	 *
	 * This method will not change until a major release.
	 *
	 * @api
	 *
	 * @return	self					For method chaining
     */
	public function setRepeatFunctions($repeat_fn, $repeat_fn_y = null)
	{
		$this->repeat_fn = $repeat_fn;
		
		if (!empty($repeat_fn_y))
		{
			$this->repeat_fn_y = $repeat_fn;
		}
		
		return $this;
    }
	
	
	
	/*
	 * PUBLIC GET METHODS
	 */
	 
	
	 
	/**
     * Obtain data assigned to an element using an element selector as key.
     *
     * @param string	$element_selector	A valid css selector or xpath query. {@see PHPFront::$default_element_selector_type}
     * @param string	$var				A reference to a variable into which to set the obtained data. Note $this is returned instead of the obtained value.
     * @param string	$is_include_path	Whether to obtain from PHPFront::$data_stack or PHPFront::$include_stack.
	 *
	 * This method will not change until a major release.
	 *
	 * @api
	 *
	 * @return	string|array|self			String or array depending on the data type obtained. But $this for method chaining, when the $var parameter is specified.
     */
	public function getAssigned($element_selector = null, & $var = null, $is_include_path = false)
	{
		$data_stack = $is_include_path ? 'includes_stack' : 'data_stack';
		
		if (empty($element_selector))
		{
			if (empty($var))
			{
				return $this->{$data_stack};
			}
			
			$var = $this->{$data_stack};
			
			return $this;
		}
		elseif (array_key_exists($element_selector, $this->{$data_stack}))
		{
			if (empty($var))
			{
				return $this->{$data_stack}[$element_selector];
			}
			
			$var = $this->{$data_stack}[$element_selector];
			
			return $this;
		}
	}
	
	
	/**
	 * Alias of PHPFront::getAssigned().
	 *
	 * @depreciated
	 */
	public function obtainData($element_selector = null, & $var = null, $is_include_path = false)
	{
		//throw new ErrorException(__METHOD__." has been depreciated! Use PHPFront::getAssigned() instead.", 0, E_USER_DEPRECIATED);
		return $this->getAssigned($element_selector, $var, $is_include_path);
	}
	
	
	
	/**
     * Obtain include paths assigned to an element using an element selector as key.
     *
     * @param string	$element_selector	A valid css selector or xpath query. {@see PHPFront::$default_element_selector_type}
     * @param var		$var				A reference to a variable into which to set the obtained data. Note $this is returned instead of the obtained value.
	 *
	 * This method will not change until a major release.
	 *
	 * @api
	 *
	 * @return	array|self					Array of include paths. But $this for method chaining, when the $var parameter is specified.
     */
	public function getIncluded($element_selector = null, & $var = null)
	{
		return $this->getAssigned($element_selector, $var, true);
	}
	
	
		
	/*
	 *-----------------------
	 * PRIVATE WORKING METHODS
	 *-----------------------
	*/



	/**
     * Loop through PHPFront::$includes_stack and include all data using their associated element selector.
	 *
	 * @throws Exception
	 *
     * This is used internally
     *
     * @access private
	 *
	 * @return	void
     */
	private function processAllIncludes()
	{
		foreach((array)$this->includes_stack as $element_selector => $indlude_path)
		{
			$fn = 'append';
			if (substr(trim($element_selector), -8) === '::before' || substr(trim($element_selector), -7) === '::after')
			{
				if (substr(trim($element_selector), -8) === '::before')
				{
					$fn = 'prepend';
					$element_selector = substr(trim($element_selector), 0, -8);
				}
				else
				{
					$element_selector = substr(trim($element_selector), 0, -7);
				}
			}
			
			$elements = $this->PHPFrontDom->getElementsBySelector($element_selector);
			
			if ($elements)
			{
				foreach($elements as $element)
				{
					if ($element)
					{
						if (!is_file($indlude_path))
						{
							throw new ErrorException("The include path: ".$indlude_path." is not valid!", 0, E_USER_NOTICE);
						}
						
						$include_data = file_get_contents($indlude_path);
						$documentFragment = $this->PHPFrontDom->createDocumentFragment();
						$documentFragment->appendXML($include_data);
						
						if (!$documentFragment->hasChildNodes())
						{
							throw new ErrorException("Document for the include path: ".$indlude_path." is malformed or has errors!", 0, E_USER_NOTICE);
						}
						else
						{
							if ($fn == 'prepend' && is_object($element->firstChild))
							{
								$element->insertBefore($documentFragment, $element->firstChild);
							}
							else
							{
								$element->appendChild($documentFragment);
							}
						}
					}
				}
			}
		}
	}
	
	
	
	/**
     * Insert content into an element - content encountered while looping within renderTemplate().
	 *
     * @param DOMNode	$element					An instance of DOMNode representing the element.
     * @param string	$data_value					Data to be inserted.
     * @param string	$value_insert_fn			A flag to either replace element's already existing data, to prepend or append it.
     * @param array		$sub_runtime_props			List of insertion settings compiled for this element.
	 *
	 * @see PHPFrontNodeList::populateNodeList()
	 *
     * This is used internally
     *
     * @access private
	 *
	 * @return	null|DOMNode				Null on error. The originally provided DOMNode on success.
     */
	private function elemSetContent($element, $data_value, $value_insert_fn = 'replace', $sub_runtime_props = array())
	{
		if (empty($element))
		{
			return;
		}
		
		# 2. $data_value is string; insert into matched elements
		
		// This is crucial!
		// This element may have already been parsed. Its content - the inserted data - may be also be parseable as well.
		// If this is so, a loop call to reparse the document may already have captured this parseable data in a nodeList.
		// Insert-replacing this element's parseable data will send the data out of the current document and make it unavailable.
		// To avoid this problem, all already-parsed elements have the 'data-phpfront-no_parse' attribute set. Detect this attribute and return!
		if ($this->on_reparse && $element->hasAttribute("data-phpfront-no_parse"))
		{
			$element->removeAttribute("data-phpfront-no_parse");
			
			return;
		}

		if (!empty(trim($data_value)))
		// Insert the real data
		{
			// How should we create the element's content?
			$content_type = $this->elemGetParam('content_type', $sub_runtime_props, $element);
			
			// If $data_value is plain text - Check if $data_value contains HTML tags
			if (!preg_match("/<[^<].*>/", $data_value) || strtoupper($content_type) == 'TEXT' || strtoupper($content_type) == 'CDATA')
			{
				if (strtoupper($content_type) == 'TEXT')
				{
					// Create TextNode and append
					$documentFragment = $this->PHPFrontDom->createTextNode($data_value); // Encodes entities - even though already encoded
				}
				else
				{
					// Create CDATA Section and append. CDATA is the default where content has not HTML tags
					$documentFragment = $this->PHPFrontDom->createCDATASection($data_value);
				}
			}
			
			else
			{
				$documentFragment = $this->PHPFrontDom->createDocumentFragment();
				$documentFragment->appendXML($data_value);
			}
			
			// appendChild() throws error if $documentFragment is empty
			if (!empty($documentFragment))
			{
				if ($value_insert_fn == "prepend" && $element->hasChildNodes())
				{
					$element->insertBefore($documentFragment, $element->firstChild);
				}
				else
				{
					if ($value_insert_fn == "replace")
					{
						$element->nodeValue = "";
					}
					
					$element->appendChild($documentFragment);
				}
			}
		}
		else
		// Content is empty
		{
			$on_content_empty = $this->elemGetParam('on_content_empty', $sub_runtime_props, $element);

			if ($on_content_empty == "clear" || $on_content_empty == "clear_and_set_flag")
			{
				$element->nodeValue = "";
			}
			elseif ($on_content_empty == "set_flag" || $on_content_empty == "clear_and_set_flag")
			{
				$element->setAttribute("data-phpfront-content_empty", "true");
			}
			elseif ($on_content_empty == "no_render")
			{
				$parentNode = $element->parentNode;
				$parentNode->removeChild($element);
				// This element hanle now changes to parent... This is what will be returned
				$element = $parentNode;
			}
			else
			{
				$element->nodeValue = $data_value;
			}
		}
		
		// This whole documen may be parsed again to parse inserted data
		// When this happens, the currently parsed elements should not be parsed again... so lets set the flag
		if ($this->parse_inserted_data && !$this->on_reparse && !isset($parentNode)/*Elements can sometimes be deleted with the on_content_empty command*/)
		{
			$element->setAttribute("data-phpfront-no_reparse", "true");
		}
		
		return $element;
	}
	
	
	
	/**
     * Affects the existence of an element: 
	 * remove it altogether, remove it but empty its children to its parent, or simply change tag name.
	 *
     * @param DOMNode		$element			An instance of DOMNode representing the element.
     * @param bool|string	$as_value			Specifies the render option.
	 *
     * This is used internally
     *
     * @access private
	 *
	 * @return	null|DOMNode				Null or DOMNode - the new render type on change of tag name.
     */
	private function elemRenderAs($element, $as_value)
	{
		if ($as_value === false)
		{
			// Do not render
			// Remove element from DOM
			$element->parentNode->removeChild($element);
			// Remove var
			unset($element);
		}
		elseif (is_string($as_value))
		{
			$documentFragment = $this->PHPFrontDom->createDocumentFragment();
			
			if (empty(trim($as_value)) && $element->hasChildNodes())
			{
				// Empty content of this element to its parent
				// Use for instead of foreach - foreach is making $element unvailable even before completing loop
				for($i = 0; $i < $element->childNodes->length; $i ++)
				{
					$documentFragment->appendChild($element->childNodes->item($i));
				}
			}
			else
			{
				// Render as: new tag name
				// Get element markup
				$node_markup = $this->PHPFrontDom->saveHTML($element);
				// Determine original tag name and replace it
				if ($element->nodeName && substr($node_markup, 0, 1) == '<')
				{
					echo trim($as_value);
					$node_name_length = strlen($element->nodeName);
					// Recreate start and end tags
					$new_node_markup = '<'.trim($as_value)
					.substr($node_markup, $node_name_length + 1/*cuts out angle bracket + start tag name*/, - (int)($node_name_length + 1)/*cuts out end tag name + angle bracket*/)
					.trim($as_value).'>';
					
					$documentFragment->appendXML($new_node_markup);
				}
			}
			
			$element->parentNode->replaceChild($documentFragment/*new*/, $element/*old*/);
			
			// Return $documentFragment if still in the current DOM
			return !empty($documentFragment->ownerDocument) ? $documentFragment : null;
		}
	}
	
	
	
	
	/**
     * Insert data into an element's attribute node - data encountered while looping within renderTemplate().
	 *
     * @param DOMNode	$element			An instance of DOMNode representing the element.
     * @param array		$attr_name			Name of an element attribute.
     * @param string	$data_value			Data to be inserted.
     * @param string	$value_insert_fn	A flag to either replace element's already existing data, to prepend or append it.
	 *
     * This is used internally
     *
     * @access private
	 *
	 * @return	null|DOMNode				Null on error. The originally provided DOMNode on success.
     */
	private function elemSetAttribute($element, $attr_name, $attr_value, $value_insert_fn = 'replace')
	{
		if (empty($element) || !$element instanceof DOMNode)
		{
			return;
		}
		
		if ($value_insert_fn == 'append')
		{
			$current_attr_value = $element->getAttribute($attr_name);
			$attr_value = !empty($current_attr_value) ? $current_attr_value. ' ' .$attr_value : $attr_value;
		}
		elseif ($value_insert_fn == 'prepend')
		{
			$current_attr_value = $element->getAttribute($attr_name);
			$attr_value = !empty($current_attr_value) ? $attr_value. ' ' .$current_attr_value : $attr_value;
		}

		$element->setAttribute($attr_name, $attr_value);
		
		return $element;
	}
	
	
	
	/**
     * Locate external data from the specified import location - location path encountered while looping within renderTemplate().
	 * 
	 * This external could live in and retrieved from any of the places below:
	 * Local or remote machine with a URL. The URL string must be enclosed within url(...) parentheses.
	 * Local or remote filesystem with a relative or absolute path-to-file. Path-to-file string must be enclosed within file(...) parentheses.
	 * Inside the currently loaded PHPFrontDom with an xpath query string. A valid xpath query string must be enclosed within xpath(...) parentheses.
	 * Inside the currently loaded PHPFrontDom with a css stye of element id selector. A unique id attribute of the element which must be prefixed with #.
	 *
     * @param string		$import_location		Path to the importable data.
	 *
	 * @throws Exception
	 *
     * This is used internally
     *
     * @access private
	 *
	 * @return	null|string							Null on error. The retrieved markup string of the element on success.
     */
	private function getReferencedImport($import_location)
	{
		if (!empty($import_location))
		{
			// Default working values.
			$PHPFrontDom = $this->PHPFrontDom;
			$fragment_id = null;
			
			// Sanitize string
			$import_location = strtolower(trim($import_location));
			
			// Is this an external resource?
			if ((substr($import_location, 0, 4) == 'url:' || substr($import_location, 0, 5) == 'file:'))
			{
				$is_url_or_file = 'url';
				if (substr($import_location, 0, 4) == 'url:')
				{
					$url_or_file = substr($import_location, 4);
				}
				elseif (substr($import_location, 0, 5) == 'file:')
				{
					$is_url_or_file = 'file';
					$url_or_file = substr($import_location, 5);
				};
				
				if (strpos($url_or_file, '#') !== false)
				{
					// Save this for retrieval
					$fragment_id = substr($url_or_file, strrpos($url_or_file, '#'));
					// $url changes from now on
					$url_or_file = substr($url_or_file, 0, strrpos($url_or_file, '#'));
				}
				
				if (!is_readable($url_or_file))
				{
					throw new Exception("The import location ".$import_location." is not valid!");
				}

				$PHPFrontDom = new PHPFrontDom(); 
				$PHPFrontDom->formatOutput = true;
				//$url_content = file_get_contents($url_or_file);
				//$PHPFrontDom->loadHTML($url_content);
				$PHPFrontDom->loadHTMLFile($url_or_file);
			}
			
			// Whatever the case, obtain content
			$content = $PHPFrontDom->saveHTML($fragment_id);
			
			if (!empty($content))
			{
				return $content;
			}
		}
	}
	
	
	
	/**
     * Get list of params associated with an element.
	 * 
	 * Properties could be set on an element in its HTML markup, using data-phpfront-* attributes.
	 * They could be included on the [@param] key of some compound data assigned to an element.
	 * 
	 * Merge all params on both areas. Give HTML attribute params first priority.
	 *
     * @param string	$key			A string representing the property name.
     * @param array		$params			Properties already encountered within the [@param] key of the element's compound data.
     * @param DOMNode	$element		The element.
	 *
     * This is used internally
     *
     * @access private
	 *
	 * @return	bool|string				false on error. The retrieved property value.
     */
	private function elemGetParam($key, array $params, $element = null)
	{
		$prop = null;
		if (is_object($element) && $element->hasAttribute('data-phpfront-'.$key))
		{
			$prop = $element->getAttribute('data-phpfront-'.$key);
		}
		else
		{
			$prop = array_key_exists($key, (array)$params) ? $params[$key] : (isset($this->{$key}) ? $this->{$key} : null);
		}
		
		return !empty($prop) ? $prop : false;
	}




	
	/*
	 * PUBLIC RENDERERS
	 */
	 
	 
	
	
	/**
     * Loop through the already built PHPFront::$data_stack
	 * and render each data into their respective element.
	 * 
	 * Merge all params on both areas. Give HTML attribute params first priority.
	 *
     * @param array			$sub_data_stack		An array of data to be rendered provided recursively.
	 *											If this is user-provided, this will be used as total data stack for the document.
     * @param DOMNode		$sub_element		The unique xpath to the current element whose data is being rendered on this renderTemplate().
     * @param bool	 		$repeat_fn		 	The repeat function to use.
     * @param bool	 		$self_repeat		Sets whether to populate data on repeatitions of $source_element itself or child elements.
	 *
     * @access private
     */
	private function renderTemplate(array $sub_data_stack = null, DOMNode $sub_element = null, $current_repeat_fn = null, $self_repeat = false)
	{
		# 1. Use the $sub_data_stack as data_stack if looping
		if (!empty($sub_data_stack))
		{
			// This is $data_stack from loop
			$data_stack = $sub_data_stack;
			$source_element = $sub_element;
			$repeat_fn = $current_repeat_fn;
		}
		else
		{
			// This is a root data
			$data_stack = $this->data_stack;
			$source_element = null;
			$repeat_fn = $this->repeat_fn;
			
			// Oh, lets include whatever external document fragments that needs to be included in the main template
			// so that everything is available for processing
			$this->processAllIncludes();
		}
		
		if (!empty($data_stack))
		{
			# 1. Prepare the $element for smart usage
						
			// Use the PHPFrontNodeList to create and/or rearrange element's children in a defined pattern
			$PHPFrontNodeList = $this->PHPFrontDom->createNodeListFromSelectors(array_keys($data_stack)/*$element_selectors*/, $source_element, $repeat_fn, $self_repeat);
			
			foreach($data_stack as $element_selector => $data_value)
			{
				# 2. Obtain the element into which to insert data
				$elements = $PHPFrontNodeList->seek();
				// $elements could be an instance of DOMNodeList or an array of DOMNode
				if (($elements instanceof DOMNodeList && $elements->length) || (is_array($elements) && !empty($elements)))
				{
					// Determine from selector if to append, prepend or replace $data_value on element.
					// This value insert function applies to all properties in compound arguments, except otherwise specified per argument as
					$common_value_insert_fn = 'replace';
					if (substr($element_selector, -8) === '::before' || substr($element_selector, -7) === '::after')
					{
						$common_value_insert_fn = substr($element_selector, -8) === '::before' ? 'prepend' :  'append';
					}
					
					foreach($elements as $element)
					{
						# 4. Work with $value_modifiers
						if (isset($this->value_modifiers[$element_selector]))
						{
							foreach($this->value_modifiers[$element_selector] as $handler)
							{
								// Any handler wanting to modify $element and $data_value would normally accept both of
								// its first and second parameters by reference
								call_user_func($handler, $element, $data_value);
							}
						}
		  
						# 5. Reanalyze $data_value and obtain the different parts when so constructed
						if (!empty($data_value) && is_array($data_value))
						{
							# 1. Obtain params
							
							$runtime_props = array();
							// Obtains $runtime_props
							// IMPORTANT: remove this key from $data_value so it won't be regarded below as content array.
							if (array_key_exists('@params', $data_value))
							{
								$runtime_props = $data_value['@params'];
								// Remove key
								unset($data_value['@params']);
							}
							
							$repeat_fn = $this->elemGetParam('repeat_fn', $runtime_props, $element);
							// Determine if this is a repeat on the X or Y axis - for multidimensional array
							if ($this->repeat_depth_count % 2 != 0)
							{
								$repeat_fn_y = $this->elemGetParam('repeat_fn_y', $runtime_props, $element);
							}
							// Only use $repeat_fn_y where available
							$current_repeat_fn = !empty($repeat_fn_y) ? $repeat_fn_y : $repeat_fn;
				
							// ------------------------------------------------------------------------------------------
							
							// Is $data_value a compound argument? Extract and handle each argument
							$params = array('@content', '@content::after', '@content::before', '@attr', '@attr::after', '@attr::before', '@import', '@import::after', '@import::before', '@children', '@children::after', '@children::before', '@render', '@repeat');
							
							// VERY IMPORTANT: @render can make elements unvailable!
							if (!empty($element) && array_intersect($params, array_keys($data_value)))
							{
								// SO THIS COMPOUND ARGUMENT. Obtain the different parts.
								foreach($params as $param_name)
								{
									// Supports only double colons.
									if (array_key_exists($param_name, $data_value))
									{
										# 1. Get working parts
										$param_type = $param_name;
										$value_insert_fn = $common_value_insert_fn;
										
										if (strpos($param_name, '::before') !== false || strpos($param_name, '::after') !== false)
										{
											$param_type = substr($param_name, 0, strpos($param_name, '::'));
											$value_insert_fn = strpos($param_name, '::before') !== false ? 'prepend' : 'append';
										}

										# 2. Work with attributes
										if ($param_type === '@attr')
										{
											foreach((array)$data_value[$param_name] as $attr_name => $attr_value)
											{
												$this->elemSetAttribute($element, $attr_name, $attr_value, $value_insert_fn);
											}
										}
										
										# 3. Work with imports
										if ($param_type === '@import')
										{
											$import_location = $data_value[$param_name];
											$content = $this->getReferencedImport($import_location);
											
											$this->elemSetContent($element, $content/*$data_value*/, $value_insert_fn, $runtime_props);
										}
										
										# 4. Work with content
										if ($param_type === '@content' || ($param_type === '@children'/* && is_array($data_value[$param_name])*/))
										{
											if (is_array($data_value[$param_name]))
											{
												# 1. Organize a loop if $data_value is an array
												$this->repeat_depth_count ++;
									
												// TRIGGER LOOP... but multiply this element
												// -----------------------------------------
												$this->renderTemplate((array)$data_value[$param_name]/*$data_value*/, $element, $current_repeat_fn);
									
												$this->repeat_depth_count --;
											}
											else
											{
												// Insert string content
												$this->elemSetContent($element, $data_value[$param_name]/*$data_value*/, $value_insert_fn, $runtime_props);
											}
										}
										
										# 5. Remove element OR change element type
										if ($param_type === '@render')
										{
											// $element changes.
											// Take note: $element will be null if it was removed - according to the render param
											$element = $this->elemRenderAs($element, $data_value['@render']);
										}
										
										// From this point inward, we must test for the existence of $element - @render can make it unavailable
										// ----------------------------------------------------------------------------------------------------
										
										# 6. Work with content
										if (!empty($element) && $param_type === '@repeat')
										{
											// TRIGGER LOOP... but multiply this element
											// -----------------------------------------
											$this->renderTemplate((array)$data_value[$param_name]/*$data_value*/, $element, $current_repeat_fn, true);
										}
									}
								}
								
								// If anything remains in array after removing the known parts... throw exception
								// Spoils nothing even if ignored. But enforces consistency
								if (!empty(array_diff(array_keys($data_value), $params)))
								{
									throw new Exception('Some invalid properties found in compound argument: '.implode(', ', array_diff(array_keys($data_value), $params)).' mixed with '.implode(', ', array_intersect(array_keys($data_value), $params)));
								}
							}
							else
							{
								// LOOP
								if (is_array($data_value))
								{
									# 1. Organize a loop if $data_value is an array
									$this->repeat_depth_count ++;
						
									// TRIGGER LOOP...
									// ---------------
									$this->renderTemplate($data_value, $element, $current_repeat_fn);
						
									$this->repeat_depth_count --;
								}
							}
						}
						elseif (!empty($data_value))
						{
							// If $data_value is all a string
							$this->elemSetContent($element, $data_value, $common_value_insert_fn);
						}
					}
				}
			}
		}
	}




	/**
     * Render the template, get it whole or part.
	 * 
     * @param bool		$print				Whether to print rendered document or not.
	 *
	 * This method will not change until a major release.
	 *
	 * @api
     *
     * @access public
	 *
	 * @return	void|string				void if the rendered template is echoed, depending on PHPFront::$auto_print_output_on_render. String if returned.
     */
	public function render($print = true)
	{
		// First, and, maybe, final parse.
		$this->renderTemplate();
		// Normalize
		$this->PHPFrontDom->normalizeDocument();
		// $HTMLDoc
		$HTMLDoc = $this->PHPFrontDom->saveHTML();
		
		// Should this whole document be parsed again to parse inserted data?
		// Then go just one round more... remember to ignore already parsed elements on this first round.
		if ($this->parse_inserted_data)
		{
			// Free memory associated with the current PHPFrontDom; set a new template.
			$this->setTemplate($HTMLDoc);
			
			// Set flag.
			$this->on_reparse = true;
			
			// Final parse.
			$this->renderTemplate();
			// New $HTMLDoc.
			$HTMLDoc = $this->PHPFrontDom->saveHTML();
			
			// Reset flag.
			$this->on_reparse = false;
		}

		// Set the 'rendered' flag
		$this->is_template_rendered = true;
		
		// Re
		if (!$print)
		{
			return $HTMLDoc;
		}
		
		echo $HTMLDoc;
	}
	
	
	
	/**
     * Return part of the template after rendering.
	 * 
     * @param string	$element_selector	An element selector specifying the element to return.
	 *
	 * This method will not change until a major release.
	 *
	 * @api
     *
     * @access public
	 *
	 * @return	string				The specified element if available.
     */
	public function getRendered($element_selector = null)
	{
		if (!$this->is_template_rendered)
		{
			$HTMLDoc = $this->render(false);
			if (empty($element_selector))
			{
				// We already have all we need
				return $HTMLDoc;
			}
		}
		
		return $this->PHPFrontDom->saveHTML($element_selector);
	}
}
