function dbpedia_summary(wikipedia, element_id) {
	$.getJSON('dbpedia_proxy.php?query=' + encodeURIComponent('DESCRIBE <http://dbpedia.org/resource/' + wikipedia + '>'),
	//$.getJSON('http://dbpedia.org/sparql?default-graph-uri=http://dbpedia.org&query=DESCRIBE <http://dbpedia.org/resource/' + wikipedia + '>&format=application/json-ld',
		function(data){
		  if (data) {
			var html = '';
			for (var i in data) {
				if (data[i]['http://www.w3.org/2000/01/rdf-schema#comment']) {	
					for (var j in data[i]['http://www.w3.org/2000/01/rdf-schema#comment'])	{	  			
						if (data[i]['http://www.w3.org/2000/01/rdf-schema#comment'][j].lang == 'en') {
							html = 
							data[i]['http://www.w3.org/2000/01/rdf-schema#comment'][j].value 
							+ ' ' 
							+ '(from <a href="https://en.wikipedia.org/wiki/' + wikipedia + '" target="_new">Wikipedia</a>)'
							;
						}
					}
				}
			}
			 document.getElementById(element_id).innerHTML = html;
		  }
		}
	);
}