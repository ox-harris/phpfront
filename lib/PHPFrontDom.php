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
 * @used-by		PHPFront
 */	 




/** 
 * The PHPFrontDom.
 * Extends the PHP DOMDocument and provides methods that are natively available.
 *
 */
Class PHPFrontDom extends DOMDocument
{
	/**
     * Handle to all XPath queries on the currently loaded template.
     *
     * @var object $xpath
	 *
	 * @see setTemplate()
	 * @see getElementsBySelector()
	 * 
     * This is used internally
     *
     * @access private
     */
	private $xpath;
	
	
	/**
     * Stores all XPath queries ever run on the DOM. This includes CSS selectors that were converted to XPath.
     *
     * @var array $queries
	 *
	 * @see getQueries()
	 * @see getElementsBySelector()
	 * 
     * This is used internally
     *
     * @access private
     */
	private $queries = array();


	/**
     * A configuration property that tells PHPFrontDom how to interprete element selectors.
	 * Options are: css (the default), xpath.
     *
	 * Element selector string must be a valid css selector if this setting is 'css', or a valid xpath query if otherwise.
	 * To override this global setting and interprete certain selectors as a different type, selector strings must
	 * be enclosed in parentheses prefixed by the selector type, as if a function call to the type. css(#valid-css-selector), xpath(/valid/xpath/query).
	 *
     * @var string $default_element_selector_type
	 *
	 * @see getElementsBySelector()
	 * 
	 * This property will not change until a major release.
	 *
	 * @api
	 *
     * @access public
     */
	public $default_element_selector_type = 'css';
	
	public $events_normal = array();
	public $events_before = array();
	public $events_after = array();
	public $events_global = array();
	
	public $data = array();
	
	
	
	
	public $CACHE = array();
	
	/**
     * Loads a HTML file into DOMDocument
     * 
     * @param string	$source A relative or absolute path to a HTML file
	 *
	 * This method will not change until a major release.
	 *
	 * @api
	 *
	 * @uses DOMDocument::loadHTMLFile().
	 * @return bool TRUE on success or FALSE on error.
     */
	public function load($source, $options = null)
	{
		// Loads a HTML string
		$return = parent::load($source);
		
		// xpath handle on this document
		$this->xpath = new DOMXpath($this);
		
		// Return original return value
		return $return;
	}



	/**
     * Loads a HTML string into DOMDocument
     * 
     * @param string	$source A valid HTML markup string
	 *
	 * This method will not change until a major release.
	 *
	 * @api
	 *
	 * @uses DOMDocument::loadHTML().
	 * @return bool TRUE on success or FALSE on error.
     */
	public function loadHTML($source, $options = null)
	{
		// Loads a HTML string
		$return = parent::loadHTML($source);
		$this->encoding = 'UTF-8';
		
		// xpath handle on this document
		$this->xpath = new DOMXpath($this);
		
		// Return original return value
		return $return;
	}



	/**
     * Loads a HTML file into DOMDocument
     * 
     * @param string	$source A relative or absolute path to a HTML file
	 *
	 * This method will not change until a major release.
	 *
	 * @api
	 *
	 * @uses DOMDocument::loadHTMLFile().
	 * @return bool TRUE on success or FALSE on error.
     */
	public function loadHTMLFile($filename, $options = null)
	{
		// Loads a HTML file
		$return = parent::loadHTMLFile($filename);
		$this->encoding = 'UTF-8';

		// xpath handle on this document
		$this->xpath = new DOMXpath($this);
		
		// Return original return value
		return $return;
	}
	
	
	public static function judiciousSplit($str, $delim)
	{
		/**
		  * Regex to split css expressions by spaces, ignoring spaces within brackets, even nested.
		  * Old Expression: [ ](?=[^\]\)"\']*?(?:\[\(|$))
		  * Fails with the combiniation of words + brackets
		  *
		  * New Expression: \[[^\]]*\](*SKIP)(*F)|\s+
		  * Thanks to http://stackoverflow.com/questions/1209415/regex-to-remove-all-whitespaces-except-between-brackets - zx81
		  * Works for square brackets '[]' - even nested
		  * Obtained 27-07-16
		  * 
		  * Now modified to also support parenthesis '()'
		  * Modified 27-07-16
		  *
		  * '/[\(\[][^\]\)]*[\]\)](*SKIP)(*F)|\s+/i'
		  */
		$preg = '/[\(\[][^\]\)]*[\]\)](*SKIP)(*F)|'.$delim.'+/i';
		// Split
		return preg_split($preg, $str);
	}
	
	
	/**
     * Get a DOMNodelist of element in the currently loaded template using an element selector.
	 *
	 * Also used by PHPFrontNodeList::populateNodeList().
	 * Notice how css selectors are converted to respective xpath query equivalent.
     *
     * @param string	$element_selector	A valid css selector or xpath query. {@see PHPFront::$default_element_selector_type}
     * @param string	$contexts		An xpath query string specifying from which element to find requested element.
     * @param string	$run_query		In case a css selector was provide, whether or not to run the xpath query it was translated into or return xpath equivalent string.
	 *
	 * @throws Exception
	 *
	 * This method will not change until a major release.
	 *
	 * @api
	 *
	 * @return	string|DOMNodelist			XPath query string or DOMNodelist depending on the $run_query flag.
     */
    public function getElementsBySelector($element_selectors, $contexts = '//', $run_query = true)
	{
		// Clean up
		$element_selectors = strtolower(trim($element_selectors));
       
			
		$multi_selector_array = array();
		// Returns an array of matched elements
		if (($this->default_element_selector_type == 'xpath' && substr($element_selectors, 0, 4) != 'css:') || (/*$this->default_element_selector_type == 'css' && */substr($element_selectors, 0, 6) == 'xpath:'))
		{
			if (substr($element_selectors, 0, 6) == 'xpath:')
			{
				$query = substr(trim($element_selectors), 6);
			}
			
			$multi_selector_array[] = $query;
		}
		elseif (($this->default_element_selector_type == 'css' && substr($element_selectors, 0, 6) != 'xpath:') || (/*$this->default_element_selector_type == 'xpath' && */substr($element_selectors, 0, 4) == 'css:'))
		{
			if (substr($element_selectors, 0, 4) == 'css:')
			{
				$element_selectors = substr(trim($element_selectors), 4);
			}
			
			// Multiselectors...
			$_element_selectors = explode(',', $element_selectors);
			for ($s = 0; $s < count($_element_selectors); $s ++)
			{
				$selector_build = '';
				$element_selector = $_element_selectors[$s];
				
				// Sanitize string
				$element_selector = str_replace(array('  ', '::'/**/), array(' ', ':'/**/), trim($element_selector));
				// Ususally found in attributes...
				$element_selector = str_replace(array(' = ', '= ', ' ='), '=', $element_selector);
				
				$selector = $element_selector;
				// Split distinct selector part separated with spaces. But ignore spaces within barckets and parentheses.
				$whole_element_selectors = PHPFrontDom::judiciousSplit($selector, '\s');
					
				for($i = 0; $i < count($whole_element_selectors); $i ++)
				{
					$selector = $whole_element_selectors[$i];
					
					$psuedo_modifier = null;
					// Capture the last evaluated item in $whole_element_selectors... we'll test if it was a relationship_modifier modifying the current item.
					$relationship_modifier = isset($whole_element_selectors[$i - 1]) ? $whole_element_selectors[$i - 1] : null;
					
					// If this current item is a relationship_modifier, ignore. Next item evaluation will handle this as previous item.
					if (!in_array($selector, array('~', '+', '>')))
					{
						# 1. Work with :psuedo_modifiers
						if (strpos($selector, ':') !== false)
						{
							// This regex may be needed later: get everything between the first and last parenthesis; whether nested or not.
							// (?<=\().*(?=\))
							$selector_and_psuedo = explode(':', $selector);
	
							// $selector changes from now on
							$selector = $selector_and_psuedo[0];
							// Rewrte once
							$selector_rewritten = $this->rewrite($selector);
							
							// Handle each psuedo
							for ($k = 1; $k < count($selector_and_psuedo); $k ++)
							{
								$psuedo_modifier = isset($selector_and_psuedo[$k]) ? $selector_and_psuedo[$k] : null;
								$sub_selector = null;
								if (strpos($psuedo_modifier, '(') !== false)
								{
									$sub_selector = substr($psuedo_modifier, strpos($psuedo_modifier, '(') + 1, -1);
									// $psuedo_modifier changes from now on
									$psuedo_modifier = substr($psuedo_modifier, 0, strpos($psuedo_modifier, '('));
								}
								
								if (!empty($psuedo_modifier))
								{
									if ($psuedo_modifier == 'first-child')
									{
										// Get first node (relative to parent - not relative to result set, as in first-of-type()),
										// test that it is self of this selector. Loop with original $selector.
										$selector = '*[1]/self::'.$this->getElementsBySelector($selector, null, false);
									}
									elseif ($psuedo_modifier == 'last-child')
									{
										// Get last node (relative to parent - not relative to result set, as in last-of-type()),
										// test that it is self of this selector. Loop with original $selector.
										$selector = '*[last()]/self::'.$this->getElementsBySelector($selector, null, false);
									}
									elseif ($psuedo_modifier == 'nth-child')
									{
										// Get nth node (relative to parent - not relative to result set, as in nth-of-type()),
										// test that it is self of this selector. Loop with original $selector.
										if ($sub_selector == 'odd' || $sub_selector == 'even')
										{
											$selector = '*[position() mod 2 = '.($sub_selector == 'even' ? 0 : 1).']/self::'.$this->getElementsBySelector($selector, null, false);
										}
										else
										{
											$selector = '*['.$sub_selector.']/self::'.$this->getElementsBySelector($selector, null, false);
										}
									}
									
									elseif ($psuedo_modifier == 'first-of-type')
									{
										$selector = $selector_rewritten.'[1]';
									}
									elseif ($psuedo_modifier == 'last-of-type')
									{
										$selector = $selector_rewritten.'[last()]';
									}
									elseif ($psuedo_modifier == 'nth-of-type')
									{
										$selector = $selector_rewritten.'['.(int)$sub_selector.']';
									}
									elseif ($psuedo_modifier == 'not')
									{
										$sub_selector = $this->getElementsBySelector($sub_selector, null, false);
										
										$sub_selector = 'self::'.$sub_selector;
										$selector = $selector_rewritten.'[not('.$sub_selector.')]';
									}
									elseif ($psuedo_modifier == 'empty')
									{
										$selector = $selector_rewritten.'[count(*)=0 and not(text())]';
									}
								}
							}
						}
						else
						{
							$selector = $this->rewrite($selector);
						}
						
						# 2. If no tagname is present, use a wildcard *. So we get *[...].
						if (substr($selector, 0, 1) === '[')
						{
							$selector = '*'.$selector;
						}
						
						# 3. Work with relationship_modifiers
						
						// Parent > Direct Child
						if ($relationship_modifier === '>')
						{
							$selector_build .= '/'.$selector;
						}
						// Element + immediate sibling
						elseif ($relationship_modifier === '+')
						{
							$selector_build .= '/following-sibling::'.$selector.'[1]';
						}
						// Element ~ any sibling
						elseif ($relationship_modifier === '~')
						{
							$selector_build .= '/following-sibling::'.$selector;
							//$selector_build .= '/../'.$selector;
						}
						else
						{
							if (!empty($selector_build))
							{
								// If previous evaluated $selector (prev_selector [tested as $modifier]) was an actual selector and not a modifier, there should be double slash before this current selector (cur_selector)
								// prev_selector//cur_selector i.e all elements that are descendants of prev_selector
								$selector_build .= '//'.$selector;
							}
							else
							{
								// If it's not subquery - e.g queries enclosed in ::not()...
								// Return as is!
								$selector_build .= $selector;
							}
						}
					}
				}
				
				// One full selector processed
				if (!$run_query)
				{
					$multi_selector_array[] = $selector_build;
				}
				else
				{
					$contexts_array = explode('|', str_replace(array(' |', ' | ', '| '), '|', $contexts)); // $contexts could have been multiple contexts delimited by '|'
					$selector_build = implode($selector_build.' | ', $contexts_array).$selector_build;
					$multi_selector_array[] = $selector_build;
				}
			}
			
			$query = implode(' | ', $multi_selector_array);
			if (!$run_query)
			{
				return $query;
			}
		}
		
		$elements_nodelist = $this->xpath->query($query);
		
		if ($elements_nodelist)
		{
			// Save to list
			$query_string = $query.' -> ('.$elements_nodelist->length.')';
			$this->queries[] = '>> '.$query_string;
			
			$PHPFrontNodeList = new PHPFrontNodeList($elements_nodelist);
			$PHPFrontNodeList->ownerDoc($this); // Set
			
			// The returned DOMNodeList is actually an instance of PHPFrontNodeList
			// with some custom methods...
			return $PHPFrontNodeList;
		}
		else
		{
			trigger_error(__METHOD__ .': Malformed XPath query: '.$query.' !', E_USER_WARNING);
			return;
		}
    }
	
	
		
	/*
	 *-----------------------
	 * PRIVATE WORKING METHODS
	 *-----------------------
	*/
	
	
	
	/**
     * Rewrite a unit of css selector into a valid XPath query. (Last updated: 10-09-16)
	 *
     * @param string	$css_selector		A css selector to be translated into xpath.
	 *
     * This is used internally
     *
     * @access private
	 *
	 * @return	string			XPath query string.
     */
	public function rewrite($css_selector)
	{
		# 1. Attribute Selectors
		if (strpos($css_selector, '[') !== false && strpos($css_selector, ']') !== false)
		{
			# Simple Attribute First Before introducing complex brackets
			# ---------------------------
			
			// Find any opening brackets (ignore those between quotes... as in attribute values).
			$match = '/[\'"][^\'"]*[\'"](*SKIP)(*F)|\[+/i';
			// Split
			$css_selector_array = preg_split($match, $css_selector);
			
			// Now we have no more problems with angle brackets appearing within attributes values
			// Let's parse individual attribute selector
			
			$css_selector = '';
			for ($i = 0; $i < count($css_selector_array); $i ++)
			{
				// Notes:
				# Opening bracket was lost to the split above.
				# String can still exist after closing bracket, as in [attr="val"]._trailing-string
				# Selector unit like ._leading-string[attr="val"] will result in first item in array an invalid attribute selector.
				// Test for ending brackets for genuine attribute selectors.
				if (strpos($css_selector_array[$i], ']') === false)
				{
					$css_selector .= $css_selector_array[$i];
				}
				else
				{
					$attr_string_withing_brackets = substr($css_selector_array[$i], 0, strrpos($css_selector_array[$i], ']'));
					$trailing_string_after_attr_end = substr($css_selector_array[$i], strrpos($css_selector_array[$i], ']') + 1);
					// Parse and reassemble
					$parsed = $this->parseAttr($attr_string_withing_brackets);
					$css_selector .= $parsed.$trailing_string_after_attr_end;
				}
			}
			// Test: #ffff.kkkkk[ggg="#dd[  gg]dd"][ggg="#dd[  gg]dd"]
			// Result: #ffff.kkkkk[@ggg="#dd[  gg]dd"][@ggg="#dd[  gg]dd"]
		}
		
		# 2. ID Selectors
		if (strpos($css_selector, '#') !== false)
		{
			// Find any hash character (ignore those between brackets... as in attribute selectors).
			// Capture any string and stop at the begining of another selector
			$css = '/[\[][^\]]*[\]](*SKIP)(*F)|\#([^\[\#\.]*)/i';
			$xpath = '[@id="$1"]';
			$css_selector = preg_replace($css, $xpath, $css_selector);
			// Test: #ffff.kkkkk[ggg="#dd[  gg]dd"][ggg="#dd[  gg]dd"]
			// Result: [@id="ffff"].kkkkk[ggg="#dd[  gg]dd"][ggg="#dd[  gg]dd"]
		}
		
		# 3. Class Selectors
		if (strpos($css_selector, '.') !== false)
		{
			// Find any dot character (ignore those between brackets... as in attribute selectors).
			// Capture any string and stop at the begining of another selector
			$css = '/[\[][^\]]*[\]](*SKIP)(*F)|\.([^\[\#\.]*)/i';
			$xpath = '[contains(concat(" ", @class, " "), " $1 ")]';
			$css_selector = preg_replace($css, $xpath, $css_selector);
			// Test: #ffff.kkkkk[ggg="#dd[  gg]dd"][ggg="#dd[  gg]dd"]
			// Result: #ffff[contains(concat(" ", @class, " "), " kkkkk ")][ggg="#dd[  gg]dd"][ggg="#dd[  gg]dd"]
		}
		
		return $css_selector;
	}
	
	
	
	public function parseAttr($css_selector)
	{
		# Complex Attribute Selectors
		# --------------------------
		// Attr Starts With
		if (strpos($css_selector, '^=') !== false)
		{
			$css = '/^([^\^]*)\^=(.*)$/i';
			$xpath = '[starts-with(@$1, "$2")]';
			$css_selector = preg_replace($css, $xpath, $css_selector);
		}
		// Attr Ends With
		elseif (strpos($css_selector, '$=') !== false)
		{
			$css = '/^([^\$]*)\$=(.*)$/i';
			//$xpath = '[ends-with(@$1, "$2")]';
			// ends-with only available on xpath 2.0. Thanks to http://stackoverflow.com/questions/22436789/xpath-ends-with-does-not-work
			$xpath = '[substring(@$1, string-length(@$1) - string-length($2) +1) = $2]';
			$css_selector = preg_replace($css, $xpath, $css_selector);
		}
		// Attr Contains String
		elseif (strpos($css_selector, '*=') !== false)
		{
			$css = '/^([^\*]*)\*=(.*)$/i';
			$xpath = '[contains(@$1, $2)]';
			$css_selector = preg_replace($css, $xpath, $css_selector);
		}
		// Attr Has Dash-delimited Word
		elseif (strpos($css_selector, '|=') !== false)
		{
			$css = '/^([^\|]*)\|=(.*)$/i';
			$xpath = '[contains(concat("-", @$1, "-"), "-$2-")]';
			$css_selector = preg_replace($css, $xpath, $css_selector);
		}
		// Attr Contains Word
		elseif (strpos($css_selector, '~=') !== false)
		{
			$css = '/^([^\~]*)\~=(.*)$/i';
			$xpath = '[contains(concat(" ", @$1, " "), " $2 ")]';
			$css_selector = preg_replace($css, $xpath, $css_selector);
		}
		// Attr Equals
		elseif (strpos($css_selector, '=') !== false)
		{
			$css = '/^([^\=]*)=(.*)$/i';
			$xpath = '[@$1=$2]';
			$css_selector = preg_replace($css, $xpath, $css_selector);
		}
		// Just attr name
		else
		{
			$css_selector = '[@'.$css_selector.']';
		}
		
		return $css_selector;
	}
	
	
	
	/**
     * Returns all XPath queries ever run on the DOM. This includes CSS selectors that were converted to XPath.
     *
     * @return array
     */
	public function getQueries()
	{
		return implode("\r\n", $this->queries);
	}
	
	
	
	
	public function isXml($str)
	{
		return substr($str, 0, 1) === '<'; // Tags begining of string
		return preg_match("/<[^<].*>/", $str); // Tags anywhere in string
	}
	
	

	public function toNode($input, $expected_from_type = 'from_xml_or_selector', $clone_nodes = true)
	{
		if ($input instanceof DOMNode)
		{
			// Retunr here
			return $input;
		}
		
		$documentFragment = null;
		if (!is_object($input))
		{
			if ($expected_from_type === 'from_xml_or_text' && !$this->isXml($input))
			{
				// Process text - auto encoded entities.
				$documentFragment = $this->createTextNode($input);
			}
			elseif ($expected_from_type === 'from_xml_or_cdata' && !$this->isXml($input))
			{
				// Process text - no auto-encoding... jast as is.
				$documentFragment = $this->createCDATASection($input);
			}
			else
			{
				// $documentFragment to use when $input is xml or selector or even DOMNodeList
				$documentFragment = $this->createDocumentFragment();
				if ($expected_from_type === 'from_xml_or_selector' && !$this->isXml($input))
				{
					// It's selector and $input becomes DOMNodeList...
					// Processed the same as when original $input wasn't string but DOMNodeList. 
					$input = $this->getElementsBySelector($input);
				}
				elseif ($this->isXml($input))
				{
					// Obviously, its XML.
					$documentFragment->appendXML($input);
				}
			}
		}
		
		if (!$documentFragment)
		{
			$documentFragment = $this->createDocumentFragment();
		}
		
		// If from_xml_or_selector or nodelist
		if ($input instanceof PHPFrontNodeList || $input instanceof DOMNodeList)
		{
			$node_length = $input->length;
			for($i = 0; $i < $node_length; $i ++)
			{
				$new_node = $input->item($i);
				if ($new_node->ownerDocument && !$new_node->ownerDocument->isSameNode($this))
				{
					$new_node = $this->importNode($new_node, true);
				}
				elseif ($clone_nodes)
				{
					$new_node = $new_node->cloneNode(true);
				}
				
				$documentFragment->appendChild($new_node);
			}
		}
		
		// Retun here
		return $documentFragment;
	}
	
	
	
	public function toType($node, $type)
	{
		if (!is_object($type) && strlen($type) && $this->nodeName !== $type)
		{
			// Only if this condition is met
			$new_element = $this->createElement($type, ' ');
			
			// Transfer attributes
			if ($node->hasAttributes())
			{
				foreach ($node->attributes as $attr)
				{
					$new_element->setAttribute($attr->nodeName, $attr->nodeValue);
				}
			}
			
			// Transfer children
			if ($node->hasChildNodes())
			{
				$nodes_length = $node->childNodes->length;
				for($i = 0; $i < $nodes_length; $i ++)
				{
					$new_element->appendChild($node->childNodes->item($i)->cloneNode(true));
				}
			}
			
			return $new_element;
		}
		
		return $node;
	}
	
	
	
	/**
     * Duplicate an element in the main document. Mainly used to complete calls_list
     *
     * @param DOMNode 	$node 						A DOMNode element.
	 * @param string 	$insert_duplicate_as 		Specifies how to place the duplicated element relative to its source.
	 * @param bool 		$insert_before 				The numeric key location of an item in list before which to add this newly duplicated $node.
     * 
	 * @return void
     */
	public function duplicateNode($node, $repeat_fn = PHPFront::FN_AFTER, $ref_node = null)
	{
		if (!$ref_node)
		{
			$ref_node = $node;
		}
		
		// Copy and insert the main node
		$node_copy = $node->cloneNode(true);
		
		$parent = $ref_node->parentNode;
		if ($repeat_fn === PHPFront::FN_AS_CHILD)
		{
			// Insert $node_copy as last child
			$ref_node->appendChild($node_copy);
		}
		elseif (!empty($parent))
		{
			if ($repeat_fn === PHPFront::FN_BEFORE)
			{
				// Insert $node_copy
				$parent->insertBefore($node_copy, $ref_node);
			}
			elseif ($repeat_fn === PHPFront::FN_AFTER && !empty($ref_node->nextSibling))
			{
				// Insert $node_copy
				$parent->insertBefore($node_copy, $ref_node->nextSibling);
			}
			else
			{
				// Insert $node_copy as last child
				$parent->appendChild($node_copy);
			}
			
			// See if we can first add a contextual line break or space. Not always the case anyways
			if ($this->formatOutput && is_object($parent->firstChild) && $parent->firstChild->nodeName == '#text' && is_null(trim($parent->firstChild->nodeValue)))
			{
				$white_space = $parent->firstChild->cloneNode(true);
				$parent->insertBefore($white_space, $node_copy);
			}
		}
		
		return $node_copy;
	}
	
	
	
	/**
     * Fill PHPFrontDom::$node_list with DOMNodeList objects. This is usually called by PHPFront.
     *
	 * @param array 	$element_selectors 	A list of element selectors provided by PHPFront.
     * @param DOMNode 	$source_element 	A DOMNode element from which to source elements for the population.
     * @param bool	 	$repeat_fn		 	The repeat function to use.
     * @param bool	 	$is_switch_to_self	Sets whether to populate data on element itself or child elements.
     * 
	 * @see				PHPFront::setElementData().
	 *
	 * @return void
     */
	public function createNodeListFromSelectors(array $element_selectors, $source_element = null, $repeat_fn = null, $switch_population_target = false)
	{
		$source_element = !empty($source_element) ? $source_element : $this;
		return new PHPFrontNodeList($element_selectors, $source_element, $repeat_fn, $switch_population_target);
	}
	
	
	
	
	public function saveHTML($element_selector = null)
	{
		if (!empty($element_selector))
		{
			if (is_string($element_selector))
			{
				$markup_strings = array();
				
				$elements = $this->getElementsBySelector($element_selector);
				if ($elements)
				{
					foreach($elements as $element)
					{
						$markup_strings[] = str_replace('%5C&amp;', '&', parent::saveHTML($element));
					}
				}
				
				return implode(($this->formatOutput ? "\r\n" : ""), $markup_strings);
			}
			
			return str_replace('%5C&amp;', '&', parent::saveHTML($element_selector));
		}
		
		return str_replace('%5C&amp;', '&', parent::saveHTML());
	}
}
