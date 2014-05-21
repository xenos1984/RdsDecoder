var opurl;

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

	var ltmc = new OpenLayers.Layer.Vector("TMC message", {
		protocol: new OpenLayers.Protocol.HTTP({
			url: opurl,
			format: new OpenLayers.Format.OSM()
		}),
		strategies: [new OpenLayers.Strategy.Fixed()],
		style: {strokeColor: "red", strokeWidth: 2, strokeOpacity: 1, fillColor: "orange", fillOpacity: 0.5, pointRadius: 7.5},
		projection: new OpenLayers.Projection("EPSG:4326")
	});
	ltmc.events.register("loadend", ltmc, setExtent);
	map.addLayer(ltmc);

	if(!map.getCenter())
		map.setCenter(null, null);
}
