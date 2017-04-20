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
Class PHPFrontElement extends DOMElement
{
	public function setHtml($assignment, $insert_fn = PHPFront::FN_REPLACE, $from_content_type = 'from_xml_or_cdata')
	{
		// Call Event:beforeInsert
		$continue_with_default = $this->fireEventListener('beforeInsert', $assignment);
		// If false was returned from event handler
		if ($continue_with_default === false || empty($this->ownerDocument))
		{
			return false;
		}
	
		if (!is_array($assignment))
		// Insert the real data
		{
			$assignment_node = $this->ownerDocument->toNode($assignment, $from_content_type);
			if ($assignment_node instanceof DOMDocumentFragment && !$assignment_node->hasChildNodes())
			{
				$assignment_node = null; // Null is easier to understand below than an empty fragment.
				if (!($assignment instanceof PHPFrontNodeList && $assignment->length == 0)) // The problem is from input NodeList. So no complain
				{
					trigger_error(__METHOD__ .': An error occured inserting this content (with Insert Type: XML)'.(!is_object($assignment) ? ': '.$assignment : '').'! Ensure a valid XML with a single root element.', E_USER_NOTICE);
				}
			}

			if ($assignment_node)
			{
				// Insert before or after
				if ($insert_fn === PHPFront::FN_PREPEND && $this->hasChildNodes())
				{
					$this->insertBefore($assignment_node, $this->firstChild);
				}
				else
				{
					if ($insert_fn === PHPFront::FN_REPLACE)
					{
						$this->nodeValue = '';
					}
					
					$this->appendChild($assignment_node);
				}
			}
		}
		elseif ($assignment === '')
		{
			$this->nodeValue = '';
		}
		
		// Call Event:afterInsert
		$this->fireEventListener('afterInsert');
		
		return $this;
	}
	
	
	
	
	public function getHtml()
	{
		if (empty($this->ownerDocument))
		{
			return;
		}
		
		return $this->ownerDocument->saveHtml($this);
	}
	
	
	
	public function setText($assignment, $insert_fn = PHPFront::FN_REPLACE)
	{
		return $this->setHtml($assignment, $insert_fn, 'from_xml_or_text');
	}
	
	
	public function getText()
	{
		if (empty($this->ownerDocument))
		{
			return;
		}
		
		return $this->nodeValue;
	}
	
	
	public function setAttr($attr_name, $attr_val, $insert_fn = PHPFront::FN_REPLACE)
	{
		if (empty($this->ownerDocument))
		{
			return;
		}
		
		// Call Event:beforeAttrChange
		$continue_with_default = $this->fireEventListener('beforeAttrChange', $this, $attr_name, $attr_val);
		// If false was returned from event handler
		if ($continue_with_default === false || empty($this->ownerDocument))
		{
			return false;
		}

		if ($insert_fn == PHPFront::FN_APPEND)
		{
			$current_attr_value = $this->getAttribute($attr_name);
		}
		elseif ($insert_fn == PHPFront::FN_PREPEND)
		{
			$current_attr_value = $this->getAttribute($attr_name);
		}
		
		if ($attr_name)
		{
			$this->setAttribute($attr_name, $attr_val);
		}
		
		// Call Event:afterAttrChange
		$this->fireEventListener('afterAttrChange', $this);
		
		return $this;
	}
	
	
	
	public function getAttr($attr_name)
	{
		if (empty($this->ownerDocument))
		{
			return;
		}
		
		return $this->getAttribute($attr_name);
	}
	
	
	
	public function removeAttr($attr_name)
	{
		if (empty($this->ownerDocument))
		{
			return;
		}
		
		$this->removeAttribute($attr_name);
		
		return $this;
	}
	
	
	
	
	public function addEventListener($event_type, $event_handler, $event_order = null)
	{
		$event_order = empty($event_order) ? 'normal' : $event_order;
		// Make sure we're not dealing with an element already removed from DOM
		if (!empty($this->ownerDocument))
		{
			$node_path = $this->getNodePath();
			if (!isset($this->ownerDocument->{'events_'.$event_order}[$node_path][$event_type]))
			{
				$this->ownerDocument->{'events_'.$event_order}[$node_path][$event_type] = array();
			}
			
			if (!empty($event_handler))
			{
				$this->ownerDocument->{'events_'.$event_order}[$node_path][$event_type][] = $event_handler;
			}
		}
		
		return $this;
	}



	
	public function removeEventListener($event_type, $event_handler = null)
	{
		if (!empty($this->ownerDocument))
		{
			if (!empty($event_type))
			{
				$node_path = $this->getNodePath();
				$events_in_order = array('events_before', 'events_normal', 'events_after');
				for ($i = 0; $i < count($events_in_order); $i ++)
				{
					if (isset($this->ownerDocument->{$events_in_order[$i]}[$node_path][$event_type]))
					{
						if ($event_handler)
						{
							$key = array_search($event_handler, $this->ownerDocument->{$events_in_order[$i]}[$node_path][$event_type]);
							unset($this->ownerDocument->{$events_in_order[$i]}[$node_path][$event_type][$key]);
						}
						else
						{
							unset($this->ownerDocument->{$events_in_order[$i]}[$node_path][$event_type]);
						}
					}
				}
			}
			else
			{
				$this->ownerDocument->{$events_in_order[$i]}[$node_path] = array();
			}
		}
		
		return $this;
	}



	
	public function fireEventListener($event_type, & $args = null)
	{
		$return_value = true;
		// Make sure we're not dealing with an element already removed from DOM
		if (!empty($this->ownerDocument))
		{
			$node_path = $this->getNodePath();
			$events_before = isset($this->ownerDocument->events_before[$node_path][$event_type]) ? $this->ownerDocument->events_before[$node_path][$event_type] : array();
			$events_normal = isset($this->ownerDocument->events_normal[$node_path][$event_type]) ? $this->ownerDocument->events_normal[$node_path][$event_type] : array();
			$events_after = isset($this->ownerDocument->events_after[$node_path][$event_type]) ? $this->ownerDocument->events_after[$node_path][$event_type] : array();
			$events_global = isset($this->ownerDocument->events_global[$event_type]) ? $this->ownerDocument->events_global[$event_type] : array();
			// Group
			$events = array_merge($events_before, $events_normal, $events_after, $events_global);
			
			foreach ($events as $event_handler)
			{
				if (is_callable($event_handler))
				{
					$current_return_value = call_user_func($event_handler, $this, $args);
					
					if ($current_return_value === false)
					{
						$return_value = false;
					}
				}
				else
				{
					trigger_error(__METHOD__ .': Could not call an event handler for element: element_node_path: '.$this->getNodePath().'; event_type: '.$event_type.'.', E_USER_NOTICE);
				}
			}
		}
		
		//return $return_value;
		return $return_value;
	}
	
	
	public function setData($key, $content)
	{
		$node_path = $this->getNodePath();
		$this->ownerDocument->data[$node_path][$key] = $content;
		
		return $this;
	}
	
	
	public function getData($key)
	{
		$content = null;
		$node_path = $this->getNodePath();
		if ($this->hasAttribute('data-phpfront-'.$key))
		{
			$content = $this->getAttribute('data-phpfront-'.$key);
		}
		
		if (isset($this->ownerDocument->data[$node_path]) && array_key_exists($key, (array)$this->ownerDocument->data[$node_path]))
		{
			$content = $this->ownerDocument->data[$node_path][$key];
		}
		
		return $content;
	}
}
