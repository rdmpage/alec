function show_blame(qid, element_id) {
	$.getJSON('proxy.php?url=' + encodeURIComponent('https://www.wikidata.org/w/rest.php/v1/page/' + qid + '/history'),
		function(data){
		  if (data) {
		  
		  
		  	var users = {};
		  	
		  	for (var i in data.revisions) {
		  		if (data.revisions[i].user.name) {
		  			if (!users[data.revisions[i].user.name]) {
		  				users[data.revisions[i].user.name] = 0;
		  			}
		  			users[data.revisions[i].user.name]++;
		  		}
		  	}
		  	
		  	var html = '';
		  	html += '<h6>Data edited by:</h6>';
		  	html += '<div class="collection">';
		  	for (var i in users) {
		  		html += '<a class="collection-item" href="https://www.wikidata.org/wiki/User:' + i + '">';
		  		html += '<span class="badge">' + users[i] + '</span>';
		  		html += i;
		  		
		  		html += '</a>';
		  	
		  	}
		  	html += '</div>';
		  	
		  
			
			
			//html = JSON.stringify(data);
			
			/*
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
			*/
			 document.getElementById(element_id).innerHTML = html;
		  }
		}
	);
}