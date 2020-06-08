<?php

/**
 * @file tree.php
 *
 * Basic tree class, based on C++ code I wrote ages ago
 *
 */

define ('CHILD', 0);
define ('SIB', 1);

//--------------------------------------------------------------------------------------------------
/**
 * @brief Node in a tree
 *
 * Node has pointers to child, sibling, and ancestral node, these pointers are
 * NULL if corresponding node doesn't exist. Has label as a field, all other values
 * are stored in an key-value array of attributes.
 */
class Node
{
	var $ancestor;
	var $child;
	var $sibling;
	var $label;
	var $id;
	var $attributes = array();
	var $cluster = array();
	
	
	//----------------------------------------------------------------------------------------------
	function Node($label = '')
	{
		$this->ancestor = NULL;
		$this->child = NULL;
		$this->sibling = NULL;
		$this->label = $label;
		$this->cluster = array();
	}
	
	//----------------------------------------------------------------------------------------------
	function ClearCluster()
	{
		$this->cluster = array();
	}
	
	//----------------------------------------------------------------------------------------------
	function AddToCluster($item)
	{
		array_push($this->cluster, $item);
	}
	
	//----------------------------------------------------------------------------------------------
	// Number of elements in cluster
	function GetClusterSize()
	{
		return count($this->cluster);
	}
	
	//----------------------------------------------------------------------------------------------
	function SetCluster($c)
	{
		$this->cluster = $c;
	}
	
	//----------------------------------------------------------------------------------------------
	function IsLeaf()
	{
		return ($this->child == NULL);
	}
	
	function AddWeight($w)
	{
		$w0 = $this->GetAttribute('weight');
		$this->SetAttribute('weight', $w0 + $w);
	}
	
	//----------------------------------------------------------------------------------------------
	function Dump()
	{
		echo "---Dump Node---\n";
		echo "   Label: " . $this->label . "\n";
		echo "      Id: " . $this->id . "\n";
		echo "   Child: ";
		if ($this->child == NULL)
		{
			echo "NULL\n";
		}
		else
		{
			echo $this->child->label . "\n";
		}
		echo " Sibling: ";
		if ($this->sibling == NULL)
		{
			echo "NULL\n";
		}
		else
		{
			echo $this->sibling->label . "\n";
		}
		echo "Ancestor: ";
		if ($this->ancestor == NULL)
		{
			echo "NULL\n";
		}
		else
		{
			echo $this->ancestor->label . "\n";
		}
		echo "Attributes:\n";
		print_r($this->attributes);
		echo "Cluster:\n";
		print_r($this->cluster);

	}
	
	function GetAncestor() { return $this->ancestor; }	

	function GetAttribute($key) { return $this->attributes[$key]; }	

	function GetChild() { return $this->child; }	

	function GetId() { return $this->id; }	

	function GetLabel() { return $this->label; }	

	function GetSibling() { return $this->sibling; }	
	
	function SetAncestor($p)
	{
		$this->ancestor = $p;
	}
	
	function SetAttribute($key, $value)
	{
		$this->attributes[$key] = $value;
	}
	
	
	function SetChild($p)
	{
		$this->child = $p;
	}
	
	function SetId($id)
	{
		$this->id = $id;
	}
	

	function SetSibling($p)
	{
		$this->sibling = $p;
	}
	
	// Children of node (as array)
	function GetChildren()
	{
		$children = array();
		$p = $this->child;
		if ($p)
		{
			array_push($children, $p);
			$p = $p->sibling;
			while ($p)
			{
				array_push($children, $p);
				$p = $p->sibling;
			}
		}
		return $children;
	}
	
	
}


//--------------------------------------------------------------------------------------------------
/**
 * @brief A rooted tree
 *
 */
class Tree
{
	var $root;
	var $num_nodes;
	var $label_to_node_map = array();
	var $nodes = array();	
	
	//----------------------------------------------------------------------------------------------
	function Tree()
	{
		$this->root = NULL;;
		$this->num_nodes = 0;
	}
	
	//----------------------------------------------------------------------------------------------
	function GetRoot() { return $this->root; }
	
	//----------------------------------------------------------------------------------------------
	function SetRoot($root)
	{
		$this->root = $root;
	}
	
	//----------------------------------------------------------------------------------------------
	function NodeWithLabel($label)
	{
		$p = NULL;
		if (in_array($label, $this->label_to_node_map))
		{
			$p = $this->nodes[$this->label_to_node_map[$label]];
		}
		return $p;
	}
	
	//----------------------------------------------------------------------------------------------
	function NewNode($label = '')
	{
		$node = new Node($label);
		$node->id = $this->num_nodes++;
		$this->nodes[$node->id] = $node;
		if ($label != '')
		{
			$this->label_to_node_map[$label] = $node->id;
		}
		return $node;
	}
	
	//----------------------------------------------------------------------------------------------
	function Dump()
	{
		print_r($this->label_to_node_map);
		
		foreach ($this->nodes as $node)
		{
			echo $node->GetLabel() . "\n";
		}
		
		$n = new NodeIterator ($this->root);
		$a = $n->Begin();
		while ($a != NULL)
		{
			//echo "Node=\n:";
			$a->Dump();
			$a = $n->Next();
		}
	}
	
	//----------------------------------------------------------------------------------------------
	function WriteDot()
	{
		$dot = "digraph{\n";
		$n = new NodeIterator ($this->root);
		$a = $n->Begin();
		while ($a != NULL)
		{
			if ($a->GetAncestor())
			{
				$dot .= "\"" . $a->GetAncestor()->GetLabel() . "\" -> \"" . $a->GetLabel() . "\";\n";
			}
			$a = $n->Next();
		}
		$dot .= "}\n";
		return $dot;
	}
		
	
	//----------------------------------------------------------------------------------------------
	function ToSQL()
	{
		$n = new NodeIterator ($this->root);
		$a = $n->Begin();
		while ($a != NULL)
		{
			$sql = 'INSERT INTO t (name, id, parent_id, weight, path, is_leaf) VALUES(';
			$sql .= '"' . $a->GetLabel() . '",'
				. $a->GetId() . ',';
			if ($a->GetAncestor())
			{
				$sql .= $a->GetAncestor()->GetId();
			}
			else
			{
				$sql .= '0';
			}
			$sql .= ',' . $a->GetAttribute('weight');
			$sql .= ',"' . $a->GetAttribute('path') . '"';
			$sql .= ',';
			if ($a->IsLeaf())
			{
				$sql .= '1';
			}
			else
			{
				$sql .= '0';
			}
			$sql .= ');';
			echo $sql . "\n";
			$a = $n->Next();
		}	
	}
	
	

	
	
	//----------------------------------------------------------------------------------------------
	// Take a set of paths of the form "/1/2/2/3/4" and construct corresponding trees
	function PathsToTree($paths)
	{
		$this->root = $this->NewNode('/');
		$this->root->SetAttribute('path', '/');
		
		$nodes = array();
		
		// Ensure paths are unique
		$paths = array_unique($paths);
		
		// Algorithm assmumes that we will always encounter a parent before it's
		// child, so to ensure this sort paths
		array_multisort($paths);
		
		foreach ($paths as $label => $path)
		{
			$p = $this->root;
			$q = $p->GetChild();
			
			$relationship = CHILD;	
			$done = false;
			
			// Search for place to add node corresponding to this path
			while (($q != NULL) && !$done)
			{
				// compare path strings (need to do this as an array of integers,
				// not as a string (unless we ensured that numbers had leading zeros)
				$a1 = explode("/", $path);
				$a2 = explode("/", $q->GetAttribute('path'));
				
				$intersection = array();
				$n = min(count($a1), count($a2));
				$i = 0;
				while (($i < $n) && ($a1[$i] == $a2[$i]))
				{
					$intersection[$i] = $a1[$i];
					$i++;
				}
				
				if (count($intersection) == count($a2))
				{
					// Path to be added is a descendant of q, so keep searching children
					$p = $q;
					$q = $q->GetChild();
					$relationship = CHILD;
				}
				else
				{
					// Path is not a descendant of q
					$p = $q;
					$q = $q->GetSibling();
					$relationship = SIB;
				}		
			}
			
			if ($q == NULL)
			{
				$nunode = $this->NewNode($label);
				$nunode->SetAttribute('path', $path);
				
				switch ($relationship)
				{
					case CHILD:
						$p->SetChild($nunode);
						$nunode->SetAncestor($p);
						break;
						
					case SIB:
						$p->SetSibling($nunode);
						$nunode->SetAncestor($p->GetAncestor());
						break;
						
					default:
						break;
				}
				array_push($nodes, $nunode);
			}
			
		}
	}
	
	//----------------------------------------------------------------------------------------------
	// Build weights
	function BuildWeights($p)
	{
		if ($p)
		{
			$p->SetAttribute('weight', 0);
			
			$this->BuildWeights($p->GetChild());
			$this->BuildWeights($p->GetSibling());
			
			if ($p->Isleaf())
			{
				$p->SetAttribute('weight', 1);
			}
			if ($p->GetAncestor())
			{
				$p->GetAncestor()->AddWeight($p->GetAttribute('weight'));
			}
		}
	}

	//----------------------------------------------------------------------------------------------
	// Build clusters
	// set cluster for 
	function BuildClusters($p)
	{
		if ($p)
		{			
			$this->BuildClusters($p->GetChild());
			$this->BuildClusters($p->GetSibling());

			if ($p->GetAncestor())
			{
				$anc = $p->GetAncestor();

				$c = array_unique(array_merge ($p->cluster, $anc->cluster));
				$anc->SetCluster($c);
			}
		}
	}
	
	//----------------------------------------------------------------------------------------------
	function WriteTree($tags, $ids = NULL, $url = '')
	{
		global $config;
		
		$html = '<div>';
		
		$stack = array();
		$cur = $this->root;
		
		while ($cur)
		{
			if ($cur->GetChild())
			{
				$html .= '<div style="margin-left:8px;';
				if ($cur->GetSibling())
				{
					$html .= 'border-left:2px solid rgb(192,192,192);';
				}
				$html .= '">';
				if ($cur->GetSibling() == NULL)
				{
					$html .= '<span style="padding-left:10px;';
					$html .= 'background-image: url(' . $config['web_root'] . 'images/last16.gif);background-repeat: no-repeat;';
				}
				else
				{
					$html .= '<span style="padding-left:8px;';
					$html .= 'background-image: url(' . $config['web_root'] . 'images/notlast16.gif);background-repeat: no-repeat;';
				}
				
				$is_tag = false;
				if (in_array($cur->GetLabel(), $tags))
				{
					$is_tag = true;
				}
				
				if (!$is_tag)
				{
					$html .= 'color:rgb(192,192,192);';
				}
				
				$html .= '">';
				
				$linked = ($ids != NULL) && $is_tag;
				if ($linked)
				{
//					$html .= '<a href="' . $config['web_root'] . $url . $ids[$cur->GetLabel()] . '">';
					$html .= '<a href="' . $config['web_root'] . $url . $cur->GetLabel() . '">';
				}				
				$html .= $cur->GetLabel();
				if ($linked)
				{
					$html .= '</a>';
				}				
				$html .= '</span>';
				array_push($stack, $cur);
				$cur = $cur->GetChild();
			}
			else
			{
				$html .= '<div style="margin-left:8px;';
				if ($cur->GetSibling())
				{
					$html .= 'border-left:2px solid rgb(192,192,192);';
				}
				$html .= '">';
				if ($cur->GetSibling() == NULL)
				{
					$html .= '<span style="padding-left:10px;';
					$html .= 'background-image: url(' . $config['web_root'] . 'images/last16.gif);background-repeat: no-repeat;';
				}
				else
				{
					$html .= '<span style="padding-left:8px;';
					$html .= 'background-image: url(' . $config['web_root'] . 'images/notlast16.gif);background-repeat: no-repeat;';
				}
				$is_tag = false;
				if (in_array($cur->GetLabel(), $tags))
				{
					$is_tag = true;
				}
				
				if (!$is_tag)
				{
					$html .= 'color:rgb(192,192,192);';
				}
				
				$html .= '">';

				$linked = ($ids != NULL) && $is_tag;
				if ($linked)
				{
//					$html .= '<a href="' . $config['web_root'] . $url . $ids[$cur->GetLabel()] . '">';
					$html .= '<a href="' . $config['web_root'] . $url . $cur->GetLabel() . '">';
				}				
				$html .= $cur->GetLabel();
				if ($linked)
				{
					$html .= '</a>';
				}				
				$html .= '</span>';
				$html .= '</div>';
				while ((count($stack) > 0) && ($cur->GetSibling() == NULL))
				{
					$html .= '</div>' . "\n";
					$cur = array_pop($stack);
				}
				if (count($stack) == 0)
				{
					$cur = NULL;
				}
				else
				{
					$cur = $cur->GetSibling();
				}
			}
		}
		$html .= '</div>';
		return $html;
	}
		
}

//--------------------------------------------------------------------------------------------------
/**
 * @brief Node iterator 
 *
 * Iterator that visits nodes in a tree in post order. Uses a stack to keep
 * track of place in tree. 
 *
 */
class NodeIterator
{
	var $root;
	var $cur;
	var $stack = array();
	var $visit;
	
	//----------------------------------------------------------------------------------------------
	/**
	 * @brief Takes the root of the tree as a parameter.
	 *
     * @param r the root of the tree
	 */
	function NodeIterator($r)
	{
		$this->root = $r;
		$this->visit = 0;
	}
	
	//----------------------------------------------------------------------------------------------
	/**
	 * @brief Initialise iterator and returns the first node.
	 *
	 * Initialises the 
	 * @return The first node of the tree
	 */
	function Begin()
	{
		$this->cur = $this->root;
		while ($this->cur->GetChild())
		{
			array_push($this->stack, $this->cur);			
			$this->cur = $this->cur->GetChild();
		}
		return $this->cur;	
	}
	
	//----------------------------------------------------------------------------------------------
 	/**
	 * @brief Move to the next node in the tree.
	 *
	 * @return The next node in the tree, or NULL if all nodes have been visited.
	 */
	function Next()
	{
		if (count($this->stack) == 0)
		{
			$this->cur = NULL;
		}
		else
		{
			if ($this->cur->GetSibling())
			{
				$p = $this->cur->GetSibling();
				while ($p->GetChild())
				{
					array_push($this->stack, $p);
					$p = $p->GetChild();
				}
				$this->cur = $p;
			}
			else
			{
				$this->cur = array_pop($this->stack);
			}
		}
		return $this->cur;
	}
}


/*
$p = new Node('A');
$q = new Node('B');
$r = new Node('C');

$p->SetChild($q);
$q->SetSibling($r);

$n = new NodeIterator	($p);

$a = $n->Begin();
while ($a != NULL)
{
	echo "Node=" . $a->label ."\n";
	$a = $n->Next();
}
*/

?>