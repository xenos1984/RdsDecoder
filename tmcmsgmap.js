var osmdata;

function init()
{
	if(!document.getElementById("map"))
		return;

	map = new OpenLayers.Map ("map", {
		controls:[
			new OpenLayers.Control.Navigation(),
			new OpenLayers.Control.PanZoomBar(),
			new OpenLayers.Control.LayerSwitcher(),
			new OpenLayers.Control.ScaleLine(),
			new OpenLayers.Control.Attribution()],
		maxExtent: new OpenLayers.Bounds(-20037508.34,-20037508.34,20037508.34,20037508.34),
		maxResolution: 156543.0399,
		numZoomLevels: 20,
		units: 'm',
		projection: new OpenLayers.Projection("EPSG:900913"),
		displayProjection: new OpenLayers.Projection("EPSG:4326"),
		zoomMethod: null
	} );

	map.addLayer(new OpenLayers.Layer.OSM.Mapnik("Mapnik"));

	var dataExtent;
	var setExtent = function()
	{
		if(dataExtent)
			dataExtent.extend(this.getDataExtent());
		else
			dataExtent = this.getDataExtent();
		map.zoomToExtent(dataExtent);
	};

	var geojson = new OpenLayers.Format.GeoJSON({
		internalProjection: new OpenLayers.Projection("EPSG:900913"),
		externalProjection: new OpenLayers.Projection("EPSG:4326")
	});

	function styleTMC(feature) {
		if((feature.attributes.message.indexOf('traffic') > -1) && (feature.attributes.message.indexOf('construction') > -1))
			feature.style.strokeDashstyle = 'dashdot';
		else if(feature.attributes.message.indexOf('traffic') > -1)
			feature.style.strokeDashstyle = 'dash';
		else if(feature.attributes.message.indexOf('construction') > -1)
			feature.style.strokeDashstyle = 'longdash';
		else
			feature.style.strokeDashstyle = 'solid';

		if((feature.attributes.message.indexOf('danger') > -1) && (feature.attributes.message.indexOf('closed') > -1))
			feature.style.fillColor = feature.style.strokeColor = '#ff00ff';
		else if(feature.attributes.message.indexOf('danger') > -1)
			feature.style.fillColor = feature.style.strokeColor = '#ff0000';
		else if(feature.attributes.message.indexOf('closed') > -1)
			feature.style.fillColor = feature.style.strokeColor = '#8000ff';
		else
			feature.style.fillColor = feature.style.strokeColor = '#ff8000';
	};

	if(osmdata.features.length > 0)
	{
		var ltmc = new OpenLayers.Layer.Vector("TMC message", {
			style: {pointRadius: 7.5, strokeWidth: 2.5, strokeOpacity: 1, fillOpacity: 0.6},
			onFeatureInsert: styleTMC
		});
		ltmc.events.register("featuresadded", ltmc, setExtent);
		ltmc.addFeatures(geojson.read(osmdata));
		map.addLayer(ltmc);
		layers.push(ltmc);
	}

	if(!map.getCenter())
		map.setCenter(null, null);
}
