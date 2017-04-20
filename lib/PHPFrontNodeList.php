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
 * The PHPFrontElement class.
 * Adds custom methods to the DOMElement class.
 *
 */
Class PHPFrontNodeList implements IteratorAggregate
{
	private $_uniqid;
	private $_owner_doc;
	
    protected $nodeList;



    public function __construct($nodeList)
	{
        $this->nodeList = $nodeList;
    }

    public function getIterator()
	{
        return $this->nodeList;
    }

    public function __get($name)
	{
        if ($name == 'length')
		{
			return $this->nodeList instanceof DOMNodeList ? $this->nodeList->length : count($this->nodeList);
        }
		
        throw new Exception('Undefined attribute '.$name);
    }

    public function item($index)
	{
        return $this->nodeList instanceof DOMNodeList ? $this->nodeList->item($index) : $this->nodeList[$index];
    }





	// ---------------------------------------------------------------
	
	
	
	
	public function uniqId($_uniqid = null)
	{
		if ($_uniqid)
		{
			$this->_uniqid = $_uniqid;
		}
		
		if (empty($this->_uniqid))
		{
			// a unique id, then recapture all together.
			$this->_uniqid = uniqid('', true);
			
			// REBUILD NODELIST
			// First, add the currently available elements.
			// Uniqid setting here must come second after having repeated on them.
			$this->each(function($node)
			{
				$node->setAttr('data-phpfront-tempid', $this->_uniqid, PHPFront::FN_APPEND);
			});
		}
		
		return $this->_uniqid;
	}
	
	
	public function getContext()
	{
		return '//*[contains(concat(" ", @data-phpfront-tempid, " "), " '.$this->uniqId().' ")]';
	}
	
	
	public function ownerDoc($_owner_doc = null)
	{
		if ($_owner_doc)
		{
			$this->_owner_doc = $_owner_doc;
			
			return $this;
		}
		else
		{
			return $this->_owner_doc;
		}
	}
	
	
	
	// ---------------------------------------------------------------



	public function each($callback)
	{
		$length = $this->nodeList instanceof DOMNodeList ? $this->length : count($this->nodeList);
		for($i = 0; $i < $length; $i ++)
		{
			$node = $this->nodeList instanceof DOMNodeList ? $this->item($i) : $this->nodeList[$i];
			call_user_func($callback, $node);
		}
		
		return $this;
	}
	
	
	
	// Non-standard
	public function repeat($repeat_fn = PHPFront::FN_AFTER, $test = true)
	{
		if (!$test)
		{
			return $this;
		}
		
		// Maneuver... give both current and auto-generated nodes
		// a unique id, then recapture all together.
		$nodeList = array();			
		$this->each(function($node) use ($repeat_fn, & $nodeList)
		{
			$new_node = $this->ownerDoc()->duplicateNode($node, $repeat_fn);
			$nodeList[] = $new_node;
		});
		
		$PHPFrontNodeList = new PHPFrontNodeList($nodeList);
		$PHPFrontNodeList->ownerDoc($this->ownerDoc()); // Set
		
		// Return the just repeated elements
		return $PHPFrontNodeList;
	}
	
	
	private function _clone($true_clone = true)
	{
		$this_copy_of_nodes = $this->ownerDoc()->toNode($this, null/*expected_from_type*/, $true_clone)->childNodes;
		$clone = new PHPFrontNodeList($this_copy_of_nodes);
		$clone->ownerDoc($this->ownerDoc());
		
		return $clone;
	}
	
	
	public function clone()
	{
		return $this->_clone(true);
	}
	
	
	// Non-standard
	public function copy()
	{
		return $this->_clone(true);
	}
	
	
	// Non-standard
	public function cut()
	{
		return $this->_clone(false);
	}
	
	
	// Non-standard
	public function map($content_list, $callback, $map_pattern = null)
	{
		if ($this->length && !empty($content_list))
		{
			if ($this->length < count((array)$content_list))
			{
				// Maneuver... give both current and auto-generated nodes
				$this->uniqId();
				
				// Second, generate new ones to fill the gap
				$this_length = $this->length;
				$dup_node = $this->item($this_length -1);
				for ($i = 0; $i < count($content_list) - $this_length; $i ++)
				{
					// The uniqid set on current elements also follow each repeated element.
					$new_node = $dup_node = $this->ownerDoc()->duplicateNode($dup_node, true/*as_sibling*/);
				}
				
				// We should now work with the nodes on this nodelist plus the newly created ones together.
				// A new nodelist has to do this.
				$nodeList = $this->ownerDoc()->getElementsBySelector('xpath:'.$this->getContext())->map($content_list, $callback, $map_pattern);
				$nodeList->uniqId($this->_uniqid);
				
				return $nodeList;
			}
			
			$this->each(function($node) use (& $content_list, $callback)
			{
				if (!empty($content_list))
				{
					$content_list = (array)$content_list;
					$content = array_shift($content_list);
					call_user_func($callback, $node, $content);
				}
			});
		}
		
		return $this;
	}
	
	
	
	// Non-standard
	public function mapHtml($content_list, $insert_fn = PHPFront::FN_REPLACE)
	{
		return $this->map($content_list, function($element, $item) use ($insert_fn)
		{
			$element->setHtml($item, $insert_fn);
		});
	}
	
	
	// Non-standard
	public function mapAttr($attr_list, $insert_fn = PHPFront::FN_REPLACE)
	{
		return $this->map($attr_list, function($element, $item) use ($insert_fn)
		{
			foreach ($item as $attr_name => $attr_val)
			{
				$element->setAttr($attr_name, $attr_val, $insert_fn);
			}
		});
	}
	
	
	/*
	 * Tests
	 *
	 */
	public function is($selector)
	{
		$self_exists_as = $this->ownerDoc()->getElementsBySelector('xpath:'.$this->getContext().'/self::'.$this->ownerDoc()->getElementsBySelector($selector, null, false));

		return $self_exists_as->length;
	}

	
	/*
	 * ----------------------------------------------------
	 * Selectors
	 * ----------------------------------------------------
	 */
	 
	 
	public function not()
	{
	}


	public function find($selector)
	{
		return $this->ownerDoc()->getElementsBySelector($selector, $this->getContext().'//'/*context*/);
	}
	
	
	
	public function filter()
	{
	}


	public function children($selector = '*', $min_len = null)
	{
		return $this->ownerDoc()->getElementsBySelector($selector, $this->getContext().'/'/*context*/);
	}
	
	
	public function parents($selector = '*')
	{
		if ($selector == '*')
		{
			return $this->ownerDoc()->getElementsBySelector('xpath:'.$this->getContext().'/parent::*');
		}
		
		return $this->ownerDoc()->getElementsBySelector('xpath:'.$this->getContext().'/ancestor::'.$this->ownerDoc()->getElementsBySelector($selector, null, false));
	}
	
	
	public function siblings($selector = '*')
	{
		return $this->ownerDoc()->getElementsBySelector('xpath:'.$this->getContext().'/siblings::'.$this->ownerDoc()->getElementsBySelector($selector, null, false));
	}
	
	
	public function first()
	{
		return $this->item(0);
	}
	
	
	public function index($i)
	{
		return $this->length ? $this->item((int)$i) : null;
	}
	
	
	
	/*
	 * ----------------------------------------------------
	 * Setters/Setter-getters
	 * ----------------------------------------------------
	 */
	 
	 
	public function html($assignment = null)
	{
		if (func_num_args() == 0)
		{
			$html = null;
			if ($this->length > 0)
			{
				$html = $this->item(0)->getHtml();
			}
		
			return $html;
		}
		else
		// If (is_callable($assignment)/*callback provided*/
		// || (is_string($assignment)) 
		{
			$i = 0;
			$this->each(function($node) use ($i, $assignment)
			{
				if (is_callable($assignment))
				{
					// Consult callback for html
					$callback = $assignment;
					$html = call_user_func($callback, $i, $node->getHtml());
					if ($html !== false)
					{
						$node->setHtml($html);
					}
				}
				else
				{
					$node->setHtml($assignment);
				}
				
				$i ++;
			});
		}
		
		return $this;
	}
	
	
	public function text($assignment = null)
	{
		if (func_num_args() == 0 && $this->length)
		{
			$html = $this->item(0)->getText();
			
			return $html;
		}
		else
		{
			$this->each(function($node) use ($assignment)
			{
				$node->setText($assignment);
			});
			
			return $this;
		}
	}
	
	
	public function append($assignment)
	{
		$this->each(function($node) use ($assignment)
		{
			$node->setHtml($assignment, PHPFront::FN_APPEND);
		});
		
		return $this;
	}
	
	
	public function prepend($assignment)
	{
		$this->each(function($node) use ($assignment)
		{
			$node->setHtml($assignment, PHPFront::FN_PREPEND);
		});
		
		return $this;
	}
	
	
	public function empty()
	{
		$this->each(function($node)
		{
			$node->nodeValue = '';
		});
		
		return $this;
	}
	
	
	/*
	 * ----------------------------------------------------
	 * Manipulators
	 * Manipulate the current placement of elements in this list
	 * ----------------------------------------------------
	 */
	 
	
	/*
	 * Move this list to the innerHtml of target
	 * After last-child of target
	 */
	public function appendTo($target)
	{
		if (is_string($target))
		{
			$target = $this->ownerDoc()->getElementsBySelector($target);
		}
		
		$this->each(function($node) use ($target)
		{
			$target->append($node);
		});
		
		return $this;
	}
	
	
	/*
	 * Move this list to the innerHtml of target
	 * Before first-child of target
	 */
	public function prependTo($target)
	{
		if (is_string($target))
		{
			$target = $this->ownerDoc()->getElementsBySelector($target);
		}
		
		$this->each(function($node) use ($target)
		{
			$target->prepend($node);
		});
		
		return $this;
	}
	
	
	/*
	 * Move this list to a sibbling position of target
	 * Before target
	 */
	public function before($target)
	{
		if (is_string($target))
		{
			$target = $this->ownerDoc()->getElementsBySelector($target);
		}
		
		$this->each(function($node) use ($target)
		{
			$target->each(function($target_node) use ($node)
			{
				$target_node->parentNode->insertBefore($node, $target_node);
			});
		});
		
		return $this;
	}
	
	
	/*
	 * Move this list to a sibbling position of target
	 * After target
	 */
	public function after($target)
	{
		if (is_string($target))
		{
			$target = $this->ownerDoc()->getElementsBySelector($target);
		}
		
		$this->each(function($node) use ($target)
		{
			$target->each(function($target_node) use ($node)
			{
				if (!empty($target_node->nextSibling))
				{
					$target_node->parentNode->insertBefore($node, $target_node->nextSibling);
				}
				else
				{
					$target_node->parentNode->appendChild($node);
				}
			});
		});
		
		return $this;
	}
	
	
	public function replaceWith($replacement)
	{
		$replacement = $this->ownerDoc()->toNode($replacement);
		if ($replacement && $replacement instanceof DOMNode/* or DOMDocumentFragment which is actually also DOMNode*/)
		{
			$this->each(function($node) use ($replacement)
			{
				$node->parentNode->replaceChild($replacement->cloneNode(true), $node);
			});
		}
		
		return $this;
	}


	public function remove()
	{
		$this->each(function($node)
		{
			// Remove element from DOM
			$node->parentNode->removeChild($node);
		});
	}

	
	
	public function unwrap()
	{
		$this->each(function($node)
		{
			if ($node->hasChildNodes())
			{
				$toNode = $this->ownerDoc()->toNode($node->childNodes);
				
				// If any of the two cases above were true
				// And $documentFragment is in the current DOM
				if ($toNode instanceof DOMDocumentFragment)
				{
					$node->parentNode->replaceChild($toNode/*new*/, $node/*old*/);
				}
			}
		});
		
		return $this;
	}
	
	
	public function toType($type)
	{
		$nodeList = array();			
		$this->each(function($node) use ($type, & $nodeList)
		{
			$new_element = $this->ownerDoc()->toType($node, $type);
			$node->parentNode->replaceChild($new_element/*new*/, $node/*old*/);
			$nodeList[] = $new_element;
		});
		
		$PHPFrontNodeList = new PHPFrontNodeList($nodeList);
		$PHPFrontNodeList->ownerDoc($this->ownerDoc()); // Set
		
		// Return the just manipulated elements
		return $PHPFrontNodeList;
	}




	public function attr($attr_name, $attr_val = null)
	{
		if (is_string($attr_name) && func_num_args() == 1)
		{
			$attr = null;
			if ($this->length > 0)
			{
				$attr = $this->item(0)->getAttribute($attr_name);
			}
			
			return $attr;
		}
		elseif (is_array($attr_name)/*attr_list provided*/ && func_num_args() == 1)
		{
			foreach($attr_name as $name => $val)
			{
				$this->attr($name, $val);
			}
		}
		elseif (is_string($attr_name) && ((is_string($attr_val) || is_numeric($attr_val)) || (!is_string($attr_val) && is_callable($attr_val))/*callback provided*/))
		{
			$i = 0;
			$this->each(function($node) use ($attr_name, $attr_val, $i)
			{
				if (!is_string($attr_val) && is_callable($attr_val))
				{
					// Consult callback for value
					$callback = $attr_val;
					$attr_val = call_user_func($callback, $i, $node->getAttr($attr_name));
					if ($attr_val !== false)
					{
						$node->setAttr($attr_name, $attr_val);
					}
				}
				else
				{
					$node->setAttr($attr_name, $attr_val);
				}
				
				$i ++;
			});
		}
		
		return $this;
	}
	
	
	private function _addAttr($attr_name, $attr_val, $delimiter = ' ', $placement = PHPFront::FN_AFTER)
	{
		$attr_key_vals = $attr_name;
		if (!is_array($attr_key_vals))
		{
			$attr_key_vals = array($attr_name => $attr_val);
		}
		
		foreach ($attr_key_vals as $attr_name => $attr_val)
		{
			$this->attr($attr_name, function($i, $current_val) use ($attr_val, $delimiter, $placement)
			{
				$callback_called = false;
				if (!is_string($attr_val) && is_callable($attr_val))
				{
					$callback_called = true;
					$attr_val = call_user_func($attr_val, $i, $current_val);
				}
				
				if (($callback_called && $attr_val === false)/* && is_string($attr_val)*/)
				{
					return false;
				}
				elseif (strpos($delimiter.trim($current_val).$delimiter, $delimiter.trim($attr_val).$delimiter) === false)
				{
					return $placement === PHPFront::FN_BEFORE ? implode($delimiter, array_filter(array($attr_val, $current_val))) : implode($delimiter, array_filter(array($current_val, $attr_val)));
				}
			});
		}
		
		return $this;
	}
	
	
	public function addAttr($attr_name, $attr_val = null)
	{
		return $this->_addAttr($attr_name, $attr_val, ' '/*delimiter*/);
	}
	
	
	// Non-standard
	public function appendAttr($attr_name, $attr_val = null, $delimiter = ' ')
	{
		return $this->_addAttr($attr_name, $attr_val, $delimiter);
	}
	
	
	// Non-standard
	public function prependAttr($attr_name, $attr_val = null, $delimiter = ' ')
	{
		return $this->_addAttr($attr_name, $attr_val, $delimiter, PHPFront::FN_BEFORE);
	}
	
	
	public function removeAttr($attr_name)
	{
		$this->each(function($node) use ($attr_name)
		{
			$node->removeAttr($attr_name);
		});
		
		return $this;
	}
	
	
	public function addClass($class_item)
	{
		$class_items = explode(' ', $class_item);
		for ($i = 0; $i < count($class_items); $i ++)
		{
			$this->_addAttr('class', $class_items[$i]);
		}
		
		return $this;
	}
	
	
	public function removeClass($class_item)
	{
		$this->each(function($node) use ($class_item)
		{
			$classes_list = $node->getAttr('class');
			$classes_list_array = explode(' ', str_replace('  ', ' ', trim($classes_list)));
			
			$class_items = explode(' ', $class_item);
			for ($i = 0; $i < count($class_items); $i ++)
			{
				if (in_array($class_items[$i], $classes_list_array))
				{
					$class_item_key = array_search($class_items[$i], $classes_list_array);
					if ($class_item_key > -1)
					{
						unset($classes_list_array[$class_item_key]);
					}
				}
			}
			
			$node->setAttr('class', implode(' ', $classes_list_array));
		});
		
		return $this;
	}
	
	
	public function hasClass($class_item)
	{
		$has = false;
		$this->each(function($node) use ($class_item)
		{
			$classes_list = $node->getAttr('class');
			$classes_list_array = explode(' ', str_replace('  ', ' ', trim($classes_list)));
			if (in_array($class_item, $classes_list_array))
			{
				$has = true;
			}
		});
		
		return $has;
	}
	
	
	// Non-standard
	public function css($property_name, $property_value = null)
	{
		if (is_string($property_name) && func_num_args() == 1 && $this->length)
		{
			$property_list = str_replace(' ', '', $this->item(0)->getAttr('style'));
			if (strpos($property_list, $property_name.':') !== false)
			{
				$property_halves = explode($property_name.':', $property_list);
				
				// Function returns on first found element
				return explode(';', $property_halves[1])[0];
			}
		}
		elseif (is_array($property_name)/*attr_list provided*/ && func_num_args() == 1)
		{
			foreach($property_name as $name => $val)
			{
				$this->css($name, $val);
			}
		}
		elseif (is_string($property_name) && (is_string($property_value) || is_numeric($property_value)))
		{
			$this->each(function($node) use ($property_name, $property_value)
			{
				$property_list = str_replace(' ', '', $this->item(0)->getAttr('style'));
				$property_list_array = explode(';', $property_list);
						
				if (strpos($property_list, $property_name.':') !== false)
				{
					for ($i = 0; $i < count($property_list_array); $i ++)
					{
						// Split distinct css name-value pairs separated with colons ':'. But ignore colons within barckets and parentheses.
						//$property_pair = explode(':', $property_list_array[$i]); // This doesn't work with background-image:url(localhost:8004) as the ':' within that parenthesis is meant to be ignored
						$property_pair = PHPFrontDom::judiciousSplit($property_list_array[$i], '\:');
						
						if ($property_pair[0] == $property_name)
						{
							$property_pair[1] = $property_value;
							$property_list_array[$i] = implode(':', $property_pair);
						}
					}
				}
				else
				{
					$property_list_array[] = $property_name.':'.$property_value;
				}
				
				// Set the modified string back to element
				$node->setAttr('style', implode('; ', array_filter($property_list_array)));
			});
			
			return $this;
		}
	}
	
	


	public function load($import_location, $insert_fn = 'replace', $cache_control = null)
	{
		if (!empty($import_location))
		{
			// Default working values.
			$PHPFrontDom = $this->ownerDoc();
			$fragment_selector = null;
			
			// Sanitize string
			$import_location = /*strtolower*/(trim($import_location));
			$import_node_list = null;
			
			// Use CACHE?
			if (array_key_exists($import_location, $this->ownerDoc()->CACHE) && ($cache_control != 'must_revalidate' && $cache_control != 'no_cache'))
			{
				// array_key_exists... yes - even if null.
				// If first attempt resulted in a cached null, then fine. No need for another wasted effort.
				$import_node_list = $this->ownerDoc()->CACHE[$import_location]; // Node List
			}
			else
			{
				// The specific element to retrieve from file
				if (strpos($import_location, '#') !== false)
				{
					// The specific element to be retrieved
					$fragment_selector = substr($import_location, strrpos($import_location, '#'));
					// $url changes from now on
					$import_location = substr($import_location, 0, strrpos($import_location, '#'));
				}
				else
				{
					// Since no specific element is wanted
					// Our desired content will be wrapped in <html><body></body></html>
					// So let's specify $fragment_selector as the contents of 'body'
					$fragment_selector = 'body > *';
				}
				
				// Error
				if (!is_readable($import_location))
				{
					trigger_error(__METHOD__ .': Could not read the import location: '.$import_location, E_USER_WARNING);
				}
				
				// Request a file through HTTP
				if (substr($import_location, 0, 4) == 'http')
				{
					$opts = array(
						'http' => array(
							'user_agent' => 'PHP libxml agent',
						)
					);
					
					$context = stream_context_create($opts);
					libxml_set_streams_context($context);
				}
				
				// IF NOT FOR $fragment_selector, I could just have done:
				// $import_content = file_get_contents($import_location);
				
				// Is what will be used whichever case
				$PHPFrontDom = new PHPFrontDom; 
				$PHPFrontDom->formatOutput = true;
				
				// Load
				$PHPFrontDom->loadHTMLFile($import_location);
				$import_node_list = $PHPFrontDom->getElementsBySelector($fragment_selector);
			}
			
			// CACHE?
			// -----------------------
			if ($cache_control != 'no_cache' && $cache_control != 'no_store')
			{
				$this->ownerDoc()->CACHE[$import_location] = $import_node_list;
			}
			// -----------------------
			
			// Use the import as xml string
			if ($import_node_list)
			{
				if ($insert_fn == 'append')
				{
					$this->append($import_node_list);
				}
				elseif ($insert_fn == 'prepend')
				{
					$this->prepend($import_node_list);
				}
				else
				{
					$this->html($import_node_list);
				}
			}
		}
		
		return $this;
	}
	


	public function data($key, $content = null)
	{
		if (func_num_args() == 1 && $this->length)
		{
			return $this->item(0)->getData($key);
		}
		elseif (!empty($key))
		{
			$this->each(function($node) use ($key, $content)
			{
				$node->setData($key, $content);
			});
			
			return $this;
		}
	}
	
	
	

	public function on($event_type, $event_handler, $event_order = null)
	{
		if (!empty($event_type))
		{
			$event_order = empty($event_order) ? 'normal' : $event_order;
			$this->each(function($node) use ($event_type, $event_handler, $event_order)
			{
				$node->addEventListener($event_type, $event_handler, $event_order);
			});
		}
		
		return $this;
	}



	
	public function off($event_type, $event_handler = null)
	{
		$this->each(function($node) use ($event_type, $event_handler)
		{
			$node->removeEventListener($event_type, $event_handler);
		});
		
		return $this;
	}



	
	public function trigger($event_type, & $args = null)
	{
		$this->each(function($node) use ($event_type, $args)
		{
			if (!empty($event_type))
			{
				$node->fireEventListener($event_type, $args);
			}
		});
		
		//return $return_value;
		return $this;
	}
}
