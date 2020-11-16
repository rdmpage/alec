<?php 

/**
 * @file graph.php
 *
 */

//----------------------------------------------------------------------------------------
class Graph {
	
	var $edges;
	var $nodes;
	var $directed;

	//------------------------------------------------------------------------------------
	function __construct ()
	{		
		$this->edges = array();
		$this->nodes = array();
		$this->directed = true;		
	}

	//------------------------------------------------------------------------------------
	function wrap_label($text)
	{
		$words = explode(' ', $text);
		
		$tokens = array();
		$length = 0;
		
		foreach ($words as $w)
		{
			$tokens[] = $w;
			
			$length += mb_strlen($w) + 1;
			
			if ($length > 20)
			{
				$tokens[] = '\n';
				$length = 0;
			}
			else
			{
				$tokens[] = ' ';
			}
		
		}
		
		return trim(join('', $tokens));
	}


	//------------------------------------------------------------------------------------
	function AddNode ($id, $label = '')
	{
		if (!isset($this->nodes[$id] ))
		{	
			$node = new stdclass;
			$node->id = $id;
			
			if ($label == '')
			{
				$node->label = $id;
			}
			else
			{
				$node->label = $this->wrap_label($label);
			}

			$this->nodes[$node->id] = $node;
		}
		else
		{
			$node = $this->nodes[$id];
			
			// update label
			if ($label != '')
			{
				$node->label = $this->wrap_label($label);
			}		
		}
	}
	

	//------------------------------------------------------------------------------------
	function AddEdge ($source_id, $target_id, $label = '')
	{
		$key = $source_id . '-' . $target_id;
		
		$edge = new stdclass;
		$edge->source_id = $source_id;
		$edge->target_id = $target_id;
		
		if ($label != '')
		{
			$edge->label = $label;
		}
		
		$this->edges[$key] = $edge;
	}

	//------------------------------------------------------------------------------------
	function WriteDot ()
	{
		global $config;
		
		$dot = '';
		
		if ($this->directed)
		{
			$dot .= "digraph G {\n";
		}
		else
		{
			$dot .= "graph G {\n";
		}
		
		//$dot .= "size=\"3,3\";\n"; 
		
		$dot .= "node [fontsize=10, fontname=\"Helvetica\", shape=\"box\"];\n";
		$dot .= "edge [fontsize=10, fontname=\"Helvetica\"];\n";
		
		foreach ($this->nodes as $node)
		{		
			$label = $node->id;
			
			if (isset($node->label))
			{
				$label = $node->label;
			}
		
			$dot .= "node" . $node->id . " [label=\"" . $label .  "\"";
						
			$dot .= "];\n";
		}
		
		foreach ($this->edges as $edge)
		{
			$dot .= "node" . $edge->source_id;
			
			if ($this->directed)
			{
				$dot .= " -> ";
			}
			else
			{
				$dot .= " -- ";
			}
			
				
			$dot .= "node" . $edge->target_id;
				
			if (isset($edge->label))
			{
				$dot .= " [label=\"" . $edge->label . "\"]";
			}
			
			$dot .= ";\n";
		}
		
		$dot .= "}\n\n";
		
		return $dot;
	}
	
	//------------------------------------------------------------------------------------
	function WriteDotToFile($filename)
	{
		$fd = fopen($filename, "w");
		fwrite($fd, $this->WriteDot());
		fclose($fd);
	}
	
/*	function Html($id)
	{
		global $config;
		
		$filename = "tmp/" . $id . ".dot";
		$this->WriteDotToFile($filename);	
		
		$html = '<img src="' . $config['webdot'] . '/' . $config['webroot'] . $filename . '.png" usemap="#G" border="0" alt="graph"/>';
		
		// Image map
		
		$map_file_name = "tmp/" . $id . ".map";
		
		$command = $config['neato'] . " -Tcmapx -o$map_file_name " .  $filename;
		system($command);
		
		// Include image map
		$map_file = @fopen($map_file_name, "r") or die("could't open file \"$map_file_name\"");
		$map = @fread($map_file, filesize ($map_file_name));
		fclose($map_file);
		
		$html .= $map;
		
		return $html;	
	}
*/

}


?>