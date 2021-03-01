<?php

error_reporting(E_ALL);
require_once (dirname(__FILE__) . '/config.inc.php');

require_once (dirname(__FILE__) . '/wikidata_api.php');
require_once (dirname(__FILE__) . '/wikidata_search.php');


// for dev environment we do the job of .htaccess 
if(preg_match('/^\/api.php/', $_SERVER["REQUEST_URI"])) return false;
if(preg_match('/^\/api_utils.php/', $_SERVER["REQUEST_URI"])) return false;
if(preg_match('/^\/images/', $_SERVER["REQUEST_URI"])) return false;


// https://stackoverflow.com/a/8891890/9684
//----------------------------------------------------------------------------------------
function url_origin( $s, $use_forwarded_host = false )
{
    $ssl      = ( ! empty( $s['HTTPS'] ) && $s['HTTPS'] == 'on' );
    $sp       = strtolower( $s['SERVER_PROTOCOL'] );
    $protocol = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );
    $port     = $s['SERVER_PORT'];
    $port     = ( ( ! $ssl && $port=='80' ) || ( $ssl && $port=='443' ) ) ? '' : ':'.$port;
    $host     = ( $use_forwarded_host && isset( $s['HTTP_X_FORWARDED_HOST'] ) ) ? $s['HTTP_X_FORWARDED_HOST'] : ( isset( $s['HTTP_HOST'] ) ? $s['HTTP_HOST'] : null );
    $host     = isset( $host ) ? $host : $s['SERVER_NAME'] . $port;
    return $protocol . '://' . $host;
}

//----------------------------------------------------------------------------------------
function full_url( $s, $use_forwarded_host = false )
{
    return url_origin( $s, $use_forwarded_host ) . $s['REQUEST_URI'];
}

//----------------------------------------------------------------------------------------
// Literals may be strings, objects (e.g., a []@language, @value] pair), or an array.
// Handle this and return a string
function get_literal($key, $language='en')
{
	$literal = '';
	
	if (is_string($key))
	{
		$literal = $key;
	}
	else
	{
		if (is_object($key) && !is_array($key))
		{
			$literal = $key->{'@value'};
		}
		else
		{
			if (is_array($key))
			{
				$values = array();
				
				foreach ($key as $k)
				{
					if (is_object($k))
					{
						$values[] = $k->{'@value'};
					}
				}
				
				$literal = join(" / ", $values);
			}
		}
	}
	
	return $literal;
}

//----------------------------------------------------------------------------------------
function display_entity($id)
{
	global $config;
		
	$ok = false;	
		
	// get entity
	$obj = get_item($id);
	
	if ($obj)
	{
		$ok = isset($obj->{'@graph'}[0]->{'@id'});
	}
	
	if (!$ok)
	{
		// bounce
		header('Location: ' . $config['web_root'] . '?error=Record not found' . "\n\n");
		exit(0);
	}	
	
	$title 		= '';
	$meta 		= '';
	$script 	= '';
	
	
	// get title
	$literal = get_literal($obj->{'@graph'}[0]->name);
	if ($literal == '')
	{
		$literal = 'Untitled';
	}	
	$title = $literal;
	
	// Open Graph tags
	$og_list = array();
	
	$og_list['og:title'] = $title;	
	$og_list['og:type'] = 'website';	
	$og_list['og:url'] = full_url($_SERVER);
	$og_list['og:image'] = url_origin($_SERVER) . $config['web_root'] . 'image.php?qid=' . $id;	
	$description = get_literal($obj->{'@graph'}[0]->description);
	if ($description != '')
	{
		$og_list['og:description'] = $description;			
	}
	
	//image.php?qid=
	
	$og_tags = array();
	foreach ($og_list as $k => $v)
	{
		$og_tags[] = 				
			'<meta property="' . $k . '" content="' . htmlentities($v, ENT_COMPAT | ENT_HTML5, 'UTF-8') . '" />';
	}
	
	$meta = join("\n", $og_tags);	
	
	// JSON-LD for structured data in HTML
	$script = '<script type="application/ld+json">' . "\n"
		. json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    	. "\n" . '</script>';	
	
 	display_html_start($title, $meta, $script, '');
	
	display_search_bar('');	
	
	display_main_start();

	echo '
	  <!-- main panel -->
      <div class="col s12 m9">
       <div class="row">
		  <div id ="output">
		  </div>
		</div>
	  </div><!-- end main panel -->

	  <!-- side panel -->
      <div id="sidepanel" class="col s12 m3" >
        <div class="card-panel lime lighten-5" id="dbpedia"></div>
      	<div id="blame"></div>
      </div>';
      
      
    echo '<script>show_record(' . json_encode($obj) . ');</script>';
		  	
		
	display_main_end();		
	
	display_footer();
	
	display_html_end();	
}

//----------------------------------------------------------------------------------------
// Start of HTML document
function display_html_start($title = '', $meta = '', $script = '', $onload = '')
{
	global $config;
	
	echo '<!DOCTYPE html>
<html lang="en">
<head>';

	echo '<meta charset="utf-8">';
	echo '<title>' . htmlentities($title, ENT_HTML5). '</title>';

	echo '<base href="' . $config['web_root'] . '" /><!--[if IE]></base><![endif]-->';
	
	if ($meta != '')
	{
		echo $meta;
	}
	
	echo '
	<!--Import Google Icon Font-->
	<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"> 
	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.4/jquery.js"></script>
	<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.css"> 
	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/ejs@2.6.1/ejs.min.js" integrity="sha256-ZS2YSpipWLkQ1/no+uTJmGexwpda/op53QxO/UBJw4I=" crossorigin="anonymous"></script>
	<!--Let browser know website is optimized for mobile-->
	<meta name="viewport" content="width=device-width, initial-scale=1.0" /> 
	';
	
	echo '<!-- altmetric -->
	<script type="text/javascript" src="//d1bxh8uas1mnw7.cloudfront.net/assets/embed.js"></script>';


	echo '
		<script type="text/javascript" src="js/dbpedia.js">
		</script>
		<script type="text/javascript" src="js/blame.js">
		</script>
		<script type="text/javascript" src="js/viz.js">
		</script>

<!-- templates -->
		<script src="views/partials/utils.ejs">
		</script>
		<script src="views/decade_feed.ejs">
		</script>		
		<script src="views/feed.ejs">
		</script>
		<script src="views/feed-collapsible.ejs">
		</script>
		<script src="views/feed-with-thumbnails.ejs">
		</script>
		<script src="views/thing.ejs">
		</script>
		<script src="views/organisation.ejs">
		</script>		
		<script src="views/periodical.ejs">
		</script>		
		<script src="views/person.ejs">
		</script>
		<script src="views/search.ejs">
		</script>		
		<script src="views/specimen.ejs">
		</script>		
		<script src="views/taxon.ejs">
		</script>
		<script src="views/thumbnails.ejs">
		</script>
		<script src="views/work.ejs">
		</script>';
		
		echo '
		<script type="text/javascript">
		
		
			//----------------------------------------------------------------------------
			function render(template, data, element_id) {
			
				// Render template 	
				html = ejs.render(template, data);
				
				// Display
				document.getElementById(element_id).innerHTML = html;
			}
			
		</script>';
		
		
	echo '<script src="main.js"></script>';
		
		

	if ($script != '')
	{
		echo $script;
	}
		
	echo '<style type="text/css">
			/* body and main styles to give us a fixed footer, see https://materializecss.com/footer.html */	
			body {
			    display: flex;
			    min-height: 100vh;
			    flex-direction: column;
			    background: white; 
			  }	
			main {
    			flex: 1 0 auto;
  			}
  			
 /* me */
			 
/* https://stackoverflow.com/a/2160005/9684 */
.section:after {
  content: "Foo";
  visibility: hidden;
  display: block;
  height: 0px;
  clear: both;
}
	 
  
.mycard {
	padding:12px;
	border-radius:4px;
	margin-bottom:20px;
	background:white;			
}

.mycard-title {
	font-size:1.5em;
}

.mycard-thumbnail {
	padding:10px;
	float:left;
	background:white;
	width:100px;
	height:100px;
	object-fit: cover;
	object-position: 50% 50%;
}

.mycard-details {
	display:block;
	border-top:1px solid rgb(192,192,192);
}
  
  
/* https://codepen.io/furnace/pen/PGygEd */
nav.clean {
  background: none;
  box-shadow: none;
  height:2em;
  line-height:2em;
  
}
nav.clean .breadcrumb {
  color: black;
  font-size:1em;
}
nav.clean .breadcrumb:before {
  color: rgba(0, 0, 0, 0.7);
}

@media only screen and (max-width : 992px) {
  header, main, footer {
	padding-left: 0;
	padding-right: 0px;
  }
}

main {
flex: 1 0 auto;

padding:10px;
}			

/* hack for missing "square" class for avatars */
.square {
position:absolute;
width:48px;
height:48px;
left:15px;
display:inline-block;
vertical-align:middle;
border-radius: 4px;
}
	

.code {
	font-family:monospace;
	white-space:pre;
	line-height:0.9em;
	font-size:0.7em;
	color:rgb(64,64,64);
	overflow-y:auto;
	padding:10px;
	/* border:1px solid rgb(192,192,192); */

}
  
  
.logo  {
	margin: 10px;

		display: block; 
		float:left;
  
  /*text-indent: -9999px;*/
  width: 48px;
  height: 48px;
  opacity: 0.6; 
  border-radius: 4px;
  /* border:1px solid rgb(192,192,192); */
}

.none {
background-color: black;
}

.ipni {
  background: url(images/logos/ipni.svg);
  background-size: contain;
}  

.oclc {
  background: url(images/logos/oclc.svg);
  background-size: contain;
}  
  
.orcid {
  background: url(images/logos/orcid.svg);
  background-size: contain;
}  

.zoobank {
  background: url(images/logos/zoobank.svg);
  background-size: contain;
} 

.bionomia {
  background: url(images/logos/bionomia.svg);
  background-size: contain;
} 

.flickr {
  background: url(images/logos/flickr.svg);
  background-size: contain;
} 

.twitter {
  background: url(images/logos/twitter.svg);
  background-size: contain;
} 

.rg {
  background: url(images/logos/researchgate.svg);
  background-size: contain;
} 

.gbif {
  background: url(images/logos/gbif.svg);
  background-size: contain;
} 

.eol {
  background: url(images/logos/eol.svg);
  background-size: contain;
} 

.fossilworks {
  background: url(images/logos/fossilworks.svg);
  background-size: contain;
} 

.ncbi {
  background: url(images/logos/ncbi.svg);
  background-size: contain;
} 

.worms {
  background: url(images/logos/worms.svg);
  background-size: contain;
} 

.wsc {
  background: url(images/logos/world-spider-catalog.svg);
  background-size: contain;
} 

.doi {
  background: url(images/logos/doi.svg);
  background-size: contain;
} 
.handle {
  background: url(images/logos/hdl.svg);
  background-size: contain;
} 
.pmc {
  background: url(images/logos/pmc.svg);
  background-size: contain;
} 
.internetarchive {
  background: url(images/logos/internetarchive.svg);
  background-size: contain;
} 
.jstor {
  background: url(images/logos/jstor.svg);
  background-size: contain;
} 
.bhl {
  background: url(images/logos/bhl.svg);
  background-size: contain;
} 
.link {
  background: url(images/logos/link.svg);
  background-size: contain;
} 
.cnki {
  background: url(images/logos/cnki.svg);
  background-size: contain;
} 
.cnkiapp{
  background: url(images/logos/cnkiapp.svg);
  background-size: contain;
} 
.rss {
  background: url(images/logos/rss.svg);
  background-size: contain;
} 

.scholia {
  background: url(images/logos/scholia.svg);
  background-size: contain;
} 
.wikipedia {
  background: url(images/logos/wikipedia.svg);
  background-size: contain;
} 
.wikispecies {
  background: url(images/logos/wikispecies.svg);
  background-size: contain;
} 
.inaturalist {
  background: url(images/logos/inaturalist.svg);
  background-size: contain;
} 
.ror {
  background: url(images/logos/ror.svg);
  background-size: contain;
} 
.persee {
  background: url(images/logos/persee.svg);
  background-size: contain;
} 
.bold {
  background: url(images/logos/bold.svg);
  background-size: contain;
} 

.sudoc {
  background: url(images/logos/sudoc.svg);
  background-size: contain;
} 		

.wfo {
  background: url(images/logos/wfo.svg);
  background-size: contain;
}  
	

.license  {
	display: block; 
	float:left;
	margin: 4px;

  width: 24px;
  height: 24px;
}

.cc {
  background: url(images/cc-icons-png/cc.xlarge.png);
  background-size: contain;
}  			
.by {
  background: url(images/cc-icons-png/by.xlarge.png);
  background-size: contain;
}  			
.nc {
  background: url(images/cc-icons-png/nc.xlarge.png);
  background-size: contain;
}  			
.nd {
  background: url(images/cc-icons-png/nd.xlarge.png);
  background-size: contain;
}  			
.sa {
  background: url(images/cc-icons-png/sa.xlarge.png);
  background-size: contain;
}  		

.item {
position:relative;
float:left;
width:100px;
height:100px;
margin:4px;
background-color:rgb(228,228,228); 
}

.item img {
width:100%;
height:100%;
object-fit: cover;
object-position: 50% 50%;
}

.item span {
font-size:10px;
position:absolute;
left:0px;
top:0px;
width:100%;
height:44%;
background-color:rgba(0, 0, 0, 0.4);
color:white;
z-index:10;

/* https://stackoverflow.com/a/20505159 */
/* make sure padding does not expand size of div */
box-sizing: border-box;
padding:4px;
}				

/* keep our action buttons nicely spaced */
/* https://stackoverflow.com/a/6507081/9684 */
#actions > * {
/* display: block; */
margin: 5px 5px;
}
  			
  			
	</style>';
	
	echo '</head>';
	
	if ($onload == '')
	{
		echo '<body>';
	}
	else
	{
		echo '<body onload="' . $onload . '">';
	}

}

//----------------------------------------------------------------------------------------
// Footer
function display_footer()
{
	echo '<div>
	</main>';

	echo'
		<footer class="page-footer white black-text" >
			<div class="container">
            	<div class="row">
  					[<a href=".">Home</a>] About this project...
            	</div>
  			</div>
		
		</footer>';	
}

//----------------------------------------------------------------------------------------
// End of HTML document
function display_html_end()
{
	global $config;
	
	echo 
'<script>
<!-- any end of document script goes here -->
</script>';

	echo '</body>';
	echo '</html>';
}

//----------------------------------------------------------------------------------------
function display_search_bar($q)
{
	global $config;

	echo '
<div class="navbar-fixed">

	<nav>
		<div class="nav-wrapper white black-text">
			<form action="' . $config['web_root'] . '">
				<div class="input-field">
					<input type="search" id="query" name="q" placeholder="Enter search term" value="' . $q . '">
					<label class="label-icon" for="search"><i class="material-icons">search</i></label> <i class="material-icons">close</i> 
				</div>
			</form>
		</div>
	</nav>
</div>';

}

//----------------------------------------------------------------------------------------
function display_main_start()
{
	echo '
	<main>
		<div class="row">';	
	
}

//----------------------------------------------------------------------------------------
function display_main_end()
{
	echo '</div>
	</main>';
}


//----------------------------------------------------------------------------------------
function display_search($q)
{
	global $config;
	
	$title = $q;
	
	$meta = '';
	$script = '';
	
	display_html_start($title, $meta, $script, '');
				
	display_search_bar($q);
	
	display_main_start();

	echo '
	  <!-- main panel -->
      <div class="col s12 m9">
       <div class="row">
		  <div id ="output">
		  </div>
		</div>
	  </div><!-- end main panel -->

	  <!-- side panel -->
      <div id="sidepanel" class="col s12 m3" >
      </div>';
		  	

	
	$obj = wikidata_search($q);	
	
	echo '<script>
render(template_search, {
					item: ' . json_encode($obj) . '
					}, "output");	
	
	</script>';
		
	display_main_end();		
	
	display_footer();

	display_html_end();	
}


//----------------------------------------------------------------------------------------
// Home page, or badness happened
function default_display($error_msg = '')
{
	global $config;
	
	$title = $config['site_name'];
	$meta = '';
	$script = '';
	
	display_html_start($title, $meta, $script, '');
	
	display_search_bar($q);
	
	display_main_start();

	echo '
	  <!-- main panel -->
      <div class="col s12 m9">
       <div class="row">

		  	<div id ="output">
		  	
		  		<p>ALEC (A List of Everything Cool) is a tool to explore biodiversity content in <a href="https://www.wikifdata.org/">Wikidata</a></p>
		  	
					<h6>Examples</h6>
					
					<div class="item">
						<a href="Q57311469">
							<span>List of species of Mammalia sent from the Aru Islands by Mr A.R. Wallace to the British Museum</span>
							<img src="image.php?qid=Q57311469" >
						</a>
					</div>
					
					<div class="item">
						<a href="Q6753679">
							<span>Maoricicada</span>
							<img src="image.php?qid=Q6753679" >
						</a>
					</div>
					
					<div class="item">
						<a href="Q19060876">
							<span>Victoria Ann Funk </span>
							<img src="image.php?qid=Q19060876" >
						</a>
					</div>

					<div class="item">
						<a href="Q89630947">
							<span>Original watercolours donated by Cornelius Sittardus to Conrad Gesner</span>
							<img src="image.php?qid=Q89630947" >
						</a>
					</div>
					
					<div class="item">
						<a href="Q26708122">
							<span>Pterocyclos</span>
							<img src="image.php?qid=Q26708122" >
						</a>
					</div>

				   <div class="item">
						<a href="Q15831345">
							<span>Vojtěch Novotný</span>
							<img src="image.php?qid=Q15831345" >
						</a>
					</div>

				  <div class="item">
						<a href="?q=0000-0002-7975-1450">
							<span>Search for ORCID 0000-0002-7975-1450</span>
						</a>
					</div>

				   <div class="item">
						<a href="Q88304456">
							<span>Four New Species of Land Snails from New Zealand</span>
							<img src="image.php?qid=Q88304456" >
						</a>
					</div>
					

				   <div class="item">
						<a href="Q28535616">
							<span>Transactions of the Royal Society of New Zealand. Zoology</span>
							<img src="image.php?qid=Q28535616" >
						</a>
					</div>

				   <div class="item">
						<a href="Q30582014">
							<span>Finding needles in haystacks: linking scientific names, reference specimens and molecular data for Fungi</span>
							<img src="image.php?qid=Q30582014" >
						</a>
					</div>

				   <div class="item">
						<a href="Q89665527">
							<span>悼念福建省昆虫分类区系研究开拓者马骏超先生</span>
							<img src="image.php?qid=Q89665527" >
						</a>
					</div>

				   <div class="item">
						<a href="Q79381426">
							<span>Sulawesimetopus henryi</span>
							<img src="image.php?qid=Q79381426" >
						</a>
					</div>


				   <div class="item">
						<a href="Q7860244">
							<span>Tylomelania</span>
							<img src="image.php?qid=Q7860244" >
						</a>
					</div>

				   <div class="item">
						<a href="Q44978282">
							<span>Auchenorrhyncha (Insecta: Hemiptera): catalogue</span>
							<img src="image.php?qid=Q44978282" >
						</a>
					</div>
					
				   <div class="item">
						<a href="Q93091690">
							<span>A new family of Laniatores from northwestern South America (Arachnida, Opiliones)</span>
							<img src="image.php?qid=Q93091690" >
						</a>
					</div>
	
						
				   <div class="item">
						<a href="Q21003923">
							<span>Making Mosquito Taxonomy Useful: A Stable Classification of Tribe Aedini that Balances Utility with Current Knowledge of Evolutionary Relationships</span>
							<img src="image.php?qid=Q21003923" >
						</a>
					</div>

				   <div class="item">
						<a href="Q94499626">
							<span>Taxonomy of Australian Mammals</span>
							<img src="image.php?qid=Q94499626" >
						</a>
					</div>

				   <div class="item">
						<a href="Q3817064">
							<span>Kristofer M. Helgen</span>
							<img src="image.php?qid=Q3817064" >
						</a>
					</div>


				   <div class="item">
						<a href="Q1333409">
							<span>Norman I. Platnick</span>
							<img src="image.php?qid=Q1333409" >
						</a>
					</div>


				   <div class="item">
						<a href="Q64023509">
							<span>Diversity of mantids (Dictyoptera: Mantodea) of Sangha-Mbaere Region, Central African Republic, with some ecological data and DNA barcoding</span>
							<img src="image.php?qid=Q64023509" >
						</a>
					</div>
					
					
					<!-- red dot map -->
					<div class="item">
						<a href="Q15354073">
							<span>Psittacanthus cordatus</span>
							<img src="image.php?qid=Q15354073" >
						</a>
					</div>
					
					<!-- malacologist with linked paper listing all his works -->
					<div class="item">
						<a href="Q6791427">
							<span>Matthew William Kemble Connolly</span>
							<img src="image.php?qid=Q6791427" >
						</a>
					</div>
					
					<!-- English translation of Russian paper -->
					<div class="item">
						<a href="Q99837830">
							<span>Two New Species of the Weevil Genus Mecysmoderes...</span>
							<img src="image.php?qid=Q99837830" >
						</a>
					</div>	
					
					<!-- Russian paper -->
					<div class="item">
						<a href="Q99838137">
							<span>ДВА НОВЫХ ВИДА ДОЛГОНОСИКОВ РОДА MECYSMODERES...</span>
							<img src="image.php?qid=Q99838137" >
						</a>
					</div>		
					
					<!-- has erratum -->
					<div class="item">
						<a href="Q99931585">
							<span>The first African record of Artolenzites...</span>
							<img src="image.php?qid=Q99931585" >
						</a>
					</div>	
					
					<!-- death by COVID-19 -->								
					<div class="item">
						<a href="Q21518323">
							<span>Pakshirajan Lakshminarasimhan</span>
							<img src="image.php?qid=Q21518323" >
						</a>
					</div>	

					<!-- good selection of related papers -->								
					<div class="item">
						<a href="Q28300019">
							<span>Herbaria are a major frontier for species discovery</span>
							<img src="image.php?qid=Q28300019" >
						</a>
					</div>	

					<!-- Elsevier journal with lots of cited literature -->								
					<div class="item">
						<a href="Q28300019">
							<span>Molecular phylogenetics of the genus Costularia...</span>
							<img src="image.php?qid=Q52572891" >
						</a>
					</div>	


					<!-- Nice thumbnail -->								
					<div class="item">
						<a href="Q105118008">
							<span>Une nouvelle tribu de Cerambycinae...</span>
							<img src="image.php?qid=Q105118008" >
						</a>
					</div>	

					<!-- Type specimens -->								
					<div class="item">
						<a href="Q21276570">
							<span>Leucothoe amamiensis</span>
							<img src="image.php?qid=Q21276570" >
						</a>
					</div>	
				 				
				 	<!-- person linked to obituaries -->
					<div class="item">
						<a href="Q3562793">
							<span>Volker Mahnert</span>
							<img src="image.php?qid=Q3562793" >
						</a>
					</div>	
		  	</div>
	  </div><!-- end main panel -->

	  <!-- side panel -->
      <div id="sidepanel" class="col s12 m3" >
      </div>';

		
	display_main_end();		
	
	display_footer();

	display_html_end();
}

//----------------------------------------------------------------------------------------
function main()
{
	$query = '';
		
	// If no query parameters 
	if (count($_GET) == 0)
	{
		default_display();
		exit(0);
	}
		
	// Error message
	if (isset($_GET['error']))
	{	
		$error_msg = $_GET['error'];
		
		default_display($error_msg);
		exit(0);			
	}
	
	// Show entity
	if (isset($_GET['id']))
	{	
		$id = $_GET['id'];
						
		display_entity($id);
		exit(0);
	}
		
	// Show search
	if (isset($_GET['q']))
	{	
		$query = $_GET['q'];
		display_search($query);
		exit(0);
	}				
	
}


main();

?>