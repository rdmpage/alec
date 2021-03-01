//----------------------------------------------------------------------------
function get_entity_types(entity) {
  var types = [];

  if (entity['@graph']) {
	if (Array.isArray(entity['@graph'][0]['@type'])) {
	  types = entity['@graph'][0]['@type'];
	}
	else {
	  types.push(entity['@graph'][0]['@type']);
	}

  }
  else {
	if (Array.isArray(entity['@type'])) {
	  types = entity['@type'];
	}
	else {
	  types.push(entity['@type']);
	}
  }

  // map to schema
  var schema_types = [];

  for (var i in types) {
	switch (types[i]) {

	  // Person
	  case 'http://www.wikidata.org/entity/Q5':
		schema_types.push('http://schema.org/Person');
		break;

		// article
	  case 'http://www.wikidata.org/entity/Q1348305': // erratum
	  case 'http://www.wikidata.org/entity/Q13442814': // scholarly article
	  case 'http://www.wikidata.org/entity/Q18918145': // academic journal article
	  case 'http://www.wikidata.org/entity/Q191067': // article
		schema_types.push('http://schema.org/ScholarlyArticle');
		break;

		// obituary
	  case 'http://www.wikidata.org/entity/Q309481':
		// eat this
		break;

		// Taxon
	  case 'http://www.wikidata.org/entity/Q16521': // taxon
	  case 'http://www.wikidata.org/entity/Q310890': // monotypic taxon e.g., Notostraca
	  case 'http://www.wikidata.org/entity/Q713623': // clade e.g., Testudinata
	  case 'http://www.wikidata.org/entity/Q23038290': // fossil taxon e.g., Triceratops
	  case 'http://www.wikidata.org/entity/Q1040689': // synonym (taxonomy)
	  case 'http://www.wikidata.org/entity/Q17276484': // later homonym
		schema_types.push('http://schema.org/Taxon'); // BioSchemas
		break;

		// journal
	  case 'http://www.wikidata.org/entity/Q5633421': // scientific journal
	  case 'http://www.wikidata.org/entity/Q1002697': // periodical
	  case 'http://www.wikidata.org/entity/Q737498': // academic journal
	  case 'http://www.wikidata.org/entity/Q1700470': // monographic series
		schema_types.push('http://schema.org/Periodical');
		break;

		// organsiations (for now, pretty much everything is an organisation)

		// museum, herbarium, etc.
	  case 'http://www.wikidata.org/entity/Q1970365': // natural history museum
	  case 'http://www.wikidata.org/entity/Q17431399': // national museum
	  case 'http://www.wikidata.org/entity/Q33506': // museum
		schema_types.push('http://schema.org/Organization');
		schema_types.push('http://schema.org/Museum');
		break;

	  case 'http://www.wikidata.org/entity/Q181916': // herbarium
	  case 'http://www.wikidata.org/entity/Q167346': // botanical garden
		schema_types.push('http://schema.org/Organization');
		break;

		// scientific society
	  case 'http://www.wikidata.org/entity/Q748019': // scientific society
	  case 'http://www.wikidata.org/entity/Q955824': // learned society
		schema_types.push('http://schema.org/Organization');
		break;

		// publisher
	  case 'http://www.wikidata.org/entity/Q2085381': // publisher
	  case 'http://www.wikidata.org/entity/Q45400320': // open-access publisher
		schema_types.push('http://schema.org/Organization');
		break;

		// organization
	  case 'http://www.wikidata.org/entity/Q43229': // organization
		schema_types.push('http://schema.org/Organization');
		break;

		// book
		// note that "book" is controversial in Wikidata
	  case 'http://www.wikidata.org/entity/Q47461344': // written work
	  case 'http://www.wikidata.org/entity/Q571': // book
	  case 'http://www.wikidata.org/entity/Q3331189': // version, edition, or translation
		schema_types.push('http://schema.org/Book');
		break;

		// thesis
	  case 'http://www.wikidata.org/entity/Q1266946': // thesis
	  case 'http://www.wikidata.org/entity/Q187685': // PhD thesis
		schema_types.push('http://schema.org/Thesis');
		break;

		// book chapter
	  case 'http://www.wikidata.org/entity/Q1980247':
		schema_types.push('http://schema.org/Chapter'); // book chapter
		break;

		// specimen
	  case 'http://www.wikidata.org/entity/Q51255340': // type specimen
	  case 'http://www.wikidata.org/entity/Q2075980': // biological specimen
	  case 'http://www.wikidata.org/entity/Q2114846': // zoological specimen
		schema_types.push('http://rs.tdwg.org/dwc/terms/PreservedSpecimen'); // specimen
		break;


	  default:
		break;

	}
  }

  // If we haven't got a type we know something about...
  if (schema_types.length == 0) {
	schema_types.push('http://schema.org/Thing');
  }


  return schema_types;
}

//----------------------------------------------------------------------------
function show_feed_works(id) {

  $.getJSON('api_person_works.php?id=' +
	id +
	'&callback=?',
	function(data) {
	  if (data) {
		if (data['@graph'].length > 0) {

		  // which template to use?
		  if (data['@graph'][0].dataFeedElement.length > 10) {
			render(template_decade_feed, {
			  item: data
			}, 'feed_works');
		  }
		  else {
			render(template_datafeed, {
			  item: data
			}, 'feed_works');
		  }

		}
	  }
	}
  );
}

//----------------------------------------------------------------------------
function show_feed_person_is_subject(id) {
  $.getJSON('api_person_is_subject.php?id=' +
	id +
	'&callback=?',
	function(data) {
	  if (data) {
		if (data['@graph'].length > 0) {
		  render(template_datafeed, {
			item: data
		  }, 'feed_is_subject');
		}
	  }
	}
  );
}

//----------------------------------------------------------------------------
function show_feed_person_taxon_names(id) {
  $.getJSON('api_person_taxon_names.php?id=' +
	id +
	'&callback=?',
	function(data) {
	  if (data) {
		if (data['@graph'].length > 0) {
		  render(template_datafeed_thumbnails, {
			item: data
		  }, 'feed_taxon_names');
		}
	  }
	}
  );
}


//----------------------------------------------------------------------------
function show_feed_cites(id) {
  $.getJSON('api_work_cites.php?id=' +
	id +
	'&callback=?',
	function(data) {
	  if (data) {
		if (data['@graph'].length > 0) {
		  render(template_datafeed_collapsible, {
			item: data
		  }, 'feed_cites');
		}
	  }
	}
  );
}


//----------------------------------------------------------------------------
function show_feed_cited_by(id) {
  $.getJSON('api_work_cited_by.php?id=' +
	id +
	'&callback=?',
	function(data) {
	  if (data) {
		if (data['@graph'].length > 0) {
		  render(template_datafeed_collapsible, {
			item: data
		  }, 'feed_cited_by');
		}
	  }
	}
  );
}

//----------------------------------------------------------------------------
function show_feed_related_work(id) {
  $.getJSON('api_work_related.php?id=' +
	id +
	'&callback=?',
	function(data) {
	  if (data) {
		if (data['@graph'].length > 0) {
		  render(template_datafeed_collapsible, {
			item: data
		  }, 'feed_related_work');
		}
	  }
	}
  );
}

//----------------------------------------------------------------------------
function show_children(id) {
  $.getJSON('api_taxon_children.php?id=' +
	id +
	'&callback=?',
	function(data) {
	  if (data) {
		if (data['@graph'].length > 0) {
		  render(template_thumbnail_feed, {
			item: data
		  }, 'children');
		}
	  }
	}
  );
}


//----------------------------------------------------------------------------
function show_parts(id) {
  $.getJSON('api_periodical_works.php?id=' +
	id +
	'&callback=?',
	function(data) {
	  if (data) {
		if (data['@graph'].length > 0) {
		  render(template_decade_feed, {
			item: data
		  }, 'parts');
		}
	  }
	}
  );
}


//----------------------------------------------------------------------------
function show_chapters(id) {
  $.getJSON('api_book_chapters.php?id=' +
	id +
	'&callback=?',
	function(data) {
	  if (data) {
		if (data['@graph'].length > 0) {
		  render(template_datafeed, {
			item: data
		  }, 'parts');
		}
	  }
	}
  );
}

//----------------------------------------------------------------------------
function show_feed_work_taxon_names(id) {
  $.getJSON('api_work_taxon_names.php?id=' +
	id +
	'&callback=?',
	function(data) {
	  if (data) {
		if (data['@graph'].length > 0) {
		  render(template_datafeed_thumbnails, {
			item: data
		  }, 'feed_work_taxon_names');
		}
	  }
	}
  );
}

//----------------------------------------------------------------------------
function show_feed_taxon_name_works(id) {
  $.getJSON('api_taxon_name_works.php?id=' +
	id +
	'&callback=?',
	function(data) {
	  if (data) {
		if (data['@graph'].length > 0) {
		  render(template_datafeed, {
			item: data
		  }, 'feed_works');
		}
	  }
	}
  );
}

//----------------------------------------------------------------------------
function show_feed_taxon_name_types(id) {
  $.getJSON('api_taxon_name_types.php?id=' +
	id +
	'&callback=?',
	function(data) {
	  if (data) {
		if (data['@graph'].length > 0) {
		  render(template_datafeed_thumbnails, {
			item: data
		  }, 'feed_types');
		}
	  }
	}
  );
}

//----------------------------------------------------------------------------
function show_feed_types_taxon_name(id) {
  $.getJSON('api_type_taxon_name.php?id=' +
	id +
	'&callback=?',
	function(data) {
	  if (data) {
		if (data['@graph'].length > 0) {
		  render(template_datafeed_thumbnails, {
			item: data
		  }, 'feed_work_taxon_names');
		}
	  }
	}
  );
}

//----------------------------------------------------------------------------
function show_feed_taxon_related(id) {
  $.getJSON('api_taxon_related.php?id=' +
	id +
	'&callback=?',
	function(data) {
	  if (data) {
		if (data['@graph'].length > 0) {
		  render(template_datafeed_thumbnails, {
			item: data
		  }, 'feed_related');
		}
	  }
	}
  );
}



//----------------------------------------------------------------------------
function show_publisher_publications(id) {
  $.getJSON('api_publisher_publications.php?id=' +
	id +
	'&callback=?',
	function(data) {
	  if (data) {
		if (data['@graph'].length > 0) {
		  render(template_datafeed, {
			item: data
		  }, 'feed_publisher_publications');
		}
	  }
	}
  );
}

//----------------------------------------------------------------------------
function show_feed_book_is_subject(id) {
  $.getJSON('api_book_is_subject.php?id=' +
	id +
	'&callback=?',
	function(data) {
	  if (data) {
		if (data['@graph'].length > 0) {
		  render(template_datafeed, {
			item: data
		  }, 'feed_is_subject');
		}
	  }
	}
  );
}

//----------------------------------------------------------------------------
function show_feed_periodical_is_subject(id) {
  $.getJSON('api_periodical_is_subject.php?id=' +
	id +
	'&callback=?',
	function(data) {
	  if (data) {
		if (data['@graph'].length > 0) {
		  render(template_datafeed, {
			item: data
		  }, 'feed_is_subject');
		}
	  }
	}
  );
}

//----------------------------------------------------------------------------
function show_feed_translations(id) {
  $.getJSON('api_work_translations.php?id=' +
	id +
	'&callback=?',
	function(data) {
	  if (data) {
		if (data['@graph'].length > 0) {
		  render(template_datafeed, {
			item: data
		  }, 'feed_translations');
		}
	  }
	}
  );
}

//----------------------------------------------------------------------------
function show_feed_errata(id) {
  $.getJSON('api_work_errata.php?id=' +
	id +
	'&callback=?',
	function(data) {
	  if (data) {
		if (data['@graph'].length > 0) {
		  render(template_datafeed, {
			item: data
		  }, 'feed_errata');
		}
	  }
	}
  );
}

//----------------------------------------------------------------------------
function show_history_graph(id) {
  $.get('api_periodical_history.php?id=' +
	id,
	function(dot) {
	  if (dot) {
		var graph = Viz(dot, "svg", "dot");
		var html = '<b>History</b><br/>';
		html += graph;
		$('#history').html(html);
	  }
	}
  );
}

//----------------------------------------------------------------------------
function show_record(data) {

  document.getElementById('output').innerHTML = '';
  document.getElementById('dbpedia').innerHTML = '';
  document.getElementById('blame').innerHTML = '';

  // hide dbpedia view
  document.getElementById('dbpedia').style.display = "none";

  var id = data['@graph'][0]['@id'];
  id = id.replace(/https?:\/\/www.wikidata.org\/entity\//, '');



  var types = get_entity_types(data);


  var have_type = false;

  //--------------------------------------------------------------------
  if (!have_type && types.indexOf('http://schema.org/ScholarlyArticle') !== -1) {

	document.getElementById('output').innerHTML = `<div id="details"></div>

<div class="row">
<div id="feed_errata">
</div>
<div id="feed_translations">
</div>
<ul class="collapsible white">
<li id="feed_cites">
</li>
<li id="feed_cited_by">
</li>
<li id="feed_related_work">
</li>		
</ul>   
<div id="feed_work_taxon_names">
</div>
</div>`;

	$('.collapsible').collapsible();



	// display article
	render(template_work, {
	  item: data
	}, 'details');

	// related info
	show_feed_cites(id);

	show_feed_cited_by(id);

	show_feed_related_work(id);

	show_feed_translations(id);

	show_feed_errata(id);

	show_feed_work_taxon_names(id);

	_altmetric_embed_init();

	have_type = true;

  }

  //--------------------------------------------------------------------
  if (!have_type && types.indexOf('http://schema.org/Chapter') !== -1) {

	document.getElementById('output').innerHTML = `<div id="details"></div>

<div class="row">
<ul class="collapsible white">
<li id="feed_cites">
</li>
<li id="feed_cited_by">
</li>
<li id="feed_related_work">
</li>		
</ul>   
<div id="feed_work_taxon_names">
</div>
</div>`;

	$('.collapsible').collapsible();



	// display chapter (which for a modern book may have a DOI and citations)
	render(template_work, {
	  item: data
	}, 'details');

	// related info
	show_feed_cites(id);

	show_feed_cited_by(id);

	show_feed_related_work(id);

	//show_feed_translations(id);

	//show_feed_errata(id);

	show_feed_work_taxon_names(id);

	_altmetric_embed_init();

	have_type = true;

  }

  //--------------------------------------------------------------------
  if (!have_type && types.indexOf('http://schema.org/Person') !== -1) {

	document.getElementById('output').innerHTML = '<div id="details"></div><div id="feed_works"></div><div id="feed_is_subject"></div><div id="feed_taxon_names"></div>';

	// display article
	render(template_person, {
	  item: data
	}, 'details');

	// related info

	// works authored
	show_feed_works(id);

	// works about this person (e.g., obituaries)
	show_feed_person_is_subject(id);

	// taxon names in authored publications
	show_feed_person_taxon_names(id);

	have_type = true;

  }

  //--------------------------------------------------------------------
  if (!have_type && types.indexOf('http://schema.org/Taxon') !== -1) {

	document.getElementById('output').innerHTML = '<div id="details"></div><div id="children"></div><div id="feed_types"></div><div id="feed_related"></div><div id="feed_works"></div>';

	// display article
	render(template_taxon, {
	  item: data
	}, 'details');


	// types
	show_feed_taxon_name_types(id);


	// related info
	show_children(id);

	show_feed_taxon_related(id);

	// references
	show_feed_taxon_name_works(id);

	have_type = true;

  }

  //--------------------------------------------------------------------
  if (!have_type && types.indexOf('http://schema.org/Periodical') !== -1) {

	document.getElementById('output').innerHTML = '<div id="details"></div><div id="history"></div><div id="parts"></div><div id="feed_is_subject"></div>';

	// display journal
	render(template_periodical, {
	  item: data
	}, 'details');


	// related info
	show_parts(id);

	show_feed_periodical_is_subject(id);

	show_history_graph(id);

	have_type = true;

  }

  //--------------------------------------------------------------------
  if (!have_type && types.indexOf('http://schema.org/Organization') !== -1) {

	document.getElementById('output').innerHTML = '<div id="details"></div><div id="feed_publisher_publications"></div>';

	// display organisation
	render(template_organisation, {
	  item: data
	}, 'details');


	// related info
	show_publisher_publications(id);

	have_type = true;

  }


  //--------------------------------------------------------------------
  if (!have_type && types.indexOf('http://schema.org/Book') !== -1) {

	document.getElementById('output').innerHTML = '<div id="details"></div><div id="parts"></div><div id="feed_is_subject"></div>';

	// display organisation
	render(template_work, {
	  item: data
	}, 'details');

	// chapters
	show_chapters(id);

	// related info (is subject = reviews, versions, editions, translations, etc.)
	show_feed_book_is_subject(id);

	have_type = true;

  }


  //--------------------------------------------------------------------
  if (!have_type && types.indexOf('http://schema.org/Thesis') !== -1) {

	document.getElementById('output').innerHTML = '<div id="details"></div><div id="feed_is_subject"></div>';

	// display organisation
	render(template_work, {
	  item: data
	}, 'details');


	// related info (is subject = reviews, versions, editions, translations, etc.)
	show_feed_book_is_subject(id);

	have_type = true;

  }

  //--------------------------------------------------------------------
  // http://rs.tdwg.org/dwc/terms/PreservedSpecimen
  if (!have_type && types.indexOf('http://rs.tdwg.org/dwc/terms/PreservedSpecimen') !== -1) {

	document.getElementById('output').innerHTML = '<div id="details"></div><div id="feed_work_taxon_names"></div>';

	// display thing
	render(template_specimen, {
	  item: data
	}, 'details');


	// related info

	// taxa for which this is a type
	show_feed_types_taxon_name(id);


  }




  //--------------------------------------------------------------------
  if (!have_type && types.indexOf('http://schema.org/Thing') !== -1) {

	document.getElementById('output').innerHTML = '<div id="details"></div>';

	// display thing
	render(template_thing, {
	  item: data
	}, 'details');


	// related info

  }

  // blame
  show_blame(id, 'blame');



  // Enhance
  if (data['@graph'][0].sameAs) {
	for (var i in data['@graph'][0].sameAs) {
	  var m = data['@graph'][0].sameAs[i].match(/https:\/\/en.wikipedia.org\/wiki\/(.*)/);
	  if (m) {
		dbpedia_summary(m[1], 'dbpedia');
	  }

	}

  }

}


//--------------------------------------------------------------------------------
/*		function show_cite(csl) {
		
			csl = decodeURIComponent(csl);
			
			var data = new Cite(csl);
									
			var template_cite = `
			<h5>Cite</h5>
			<table>
				<tr>
					<td style="vertical-align:top;font-weight:bold;">APA</td>
					<td>
						<%- data.format('bibliography', {format: 'html', template: 'apa', lang: 'en' }); %>
					</td>
				</tr>
				<tr>
					<td style="vertical-align:top;font-weight:bold;">BibTeX</td>
					<td>
						<div style="font-family:monospace;white-space:pre;">
<%=	data.format('bibtex'); %>
						</div>
					</td>
				</tr>
				<tr>
					<td style="vertical-align:top;font-weight:bold;">RIS</td>
					<td>
						<div style="font-family:monospace;white-space:pre;">
<%=	data.format('ris'); %>
						</div>
					</td>
				</tr>
			</table>										
			`;
			
			var html = ejs.render(template_cite, { data: data });
	
			// Display
			document.getElementById('modal-content').innerHTML = html;
			$('#modal').modal('open');
		} */

//--------------------------------------------------------------------------------
function show_fulltext(links) {

  var data = JSON.parse(decodeURIComponent(links));


  var template_links = `
<h5>Full-text Links</h5>
<div class="row">
<% for (var i in data) { 
	for (var j in data[i]) { 
	   switch (i) {
		 case 'cnkiapp':
			break;
			
		default: %>
			<a href="<%= data[i][j] %>" target="_new"><div class="logo <%= i %>"></div></a>	
		<% break;
	   }
		
	 }  		
 }  %>
</div>

<div class="row">
	<% if (data['cnkiapp']) { %>
		<img src="https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=<%= encodeURIComponent(data['cnkiapp'][0]) %>">				
	<% } %>
</div>

						
`;

  var html = ejs.render(template_links, {
	data: data
  });

  // Display
  document.getElementById('modal-content').innerHTML = html;
  $('#modal').modal('open');
}

//--------------------------------------------------------------------------------
function search() {

  document.getElementById('output').innerHTML = '';
  document.getElementById('sidepanel').innerHTML = '';


  var html = '<div class="progress"><div class="indeterminate"></div></div>';

  document.getElementById('output').innerHTML = html;


  document.activeElement.blur();

  var q = document.getElementById('query').value;

  // Change URL and title to match this query (makes for easier bookmarking)
  history.pushState(null, q, '?q=' + q);
  document.title = q;


  $.getJSON('api.php?q=' +
	encodeURI(q) +
	'&callback=?',
	function(data) {

	  console.log(JSON.stringify(data, null, 2));

	  render(template_search, {
		item: data
	  }, 'output');
	}
  );
}