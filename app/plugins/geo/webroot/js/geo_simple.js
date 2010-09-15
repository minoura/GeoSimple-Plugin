function geo_simple_link(href){
	var gps;
	if(navigator.geolocation){
		gps = navigator.geolocation;
	}else if(google){
		gps = google.gears.factory.create('beta.geolocation');
	}else{
		alert('位置情報APIが利用できません');
	}
	gps.getCurrentPosition(function(p){
		var lat, lng;
		if(p.coords){
			lat = p.coords.latitude;
			lng = p.coords.longitude;
		}else{
			lat = p.latitude;
			lng = p.longitude;
		}
		if(href.indexOf('?')){
			href += '&';
		}else{
			href += '?';
		}
		
		href += 'gpslat=' + lat;
		href += '&';
		href += 'gpslng=' + lng;
		location.href = href;
	});
}
