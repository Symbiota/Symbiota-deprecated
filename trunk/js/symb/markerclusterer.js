//The number of markers to process in one batch.
MarkerClusterer.BATCH_SIZE = 2000;

//The number of markers to process in one batch (IE only).
MarkerClusterer.BATCH_SIZE_IE = 500;

function MarkerClusterer(map, opt_markers, opt_options) {
	this.extend(MarkerClusterer, google.maps.OverlayView);

	opt_markers = opt_markers || [];
	opt_options = opt_options || {};

	this.markers_ = [];
	this.clusters_ = [];
	this.listeners_ = [];
	this.activeMap_ = null;
	this.ready_ = false;

	this.gridSize_ = opt_options.gridSize || 60;
	this.minClusterSize_ = opt_options.minimumClusterSize || 10;
	this.maxZoom_ = 7;
	this.styles_ = opt_options.styles || [];
	this.zoomOnClick_ = true;
	if (opt_options.zoomOnClick !== undefined) {
		this.zoomOnClick_ = opt_options.zoomOnClick;
	}
	this.averageCenter_ = false;
	if (opt_options.averageCenter !== undefined) {
		this.averageCenter_ = opt_options.averageCenter;
	}
	this.ignoreHidden_ = false;
	if (opt_options.ignoreHidden !== undefined) {
		this.ignoreHidden_ = opt_options.ignoreHidden;
	}
	this.calculator_ = opt_options.calculator || MarkerClusterer.CALCULATOR;
	this.batchSize_ = opt_options.batchSize || MarkerClusterer.BATCH_SIZE;
	this.batchSizeIE_ = opt_options.batchSizeIE || MarkerClusterer.BATCH_SIZE_IE;
	this.clusterClass_ = opt_options.clusterClass || "cluster";
  
	if (navigator.userAgent.toLowerCase().indexOf("msie") !== -1) {
		// Try to avoid IE timeout when processing a huge number of markers:
		this.batchSize_ = this.batchSizeIE_;
	}

	this.addMarkers(opt_markers, true);
	this.setMap(map); // Note: this causes onAdd to be called
}

//Extends an object's prototype by another's.
MarkerClusterer.prototype.extend = function (obj1, obj2) {
	return (function (object) {
		var property;
		for (property in object.prototype) {
			this.prototype[property] = object.prototype[property];
		}
		return this;
	}).apply(obj1, [obj2]);
};

//Adds an array of markers to the clusterer. The clusters are redrawn unless <code>opt_nodraw</code> is set to <code>true</code>.
MarkerClusterer.prototype.addMarkers = function (markers, opt_nodraw) {
	var i;
	for (i = 0; i < markers.length; i++) {
		this.pushMarkerTo_(markers[i]);
	}
	if (!opt_nodraw) {
		this.redraw_();
	}
};

//Pushes a marker to the clusterer.
MarkerClusterer.prototype.pushMarkerTo_ = function (marker) {
	// If the marker is draggable add a listener so we can update the clusters on the dragend:
	if (marker.getDraggable()) {
		var cMarkerClusterer = this;
		google.maps.event.addListener(marker, "dragend", function () {
			if (cMarkerClusterer.ready_) {
				this.isAdded = false;
				cMarkerClusterer.repaint();
			}
		});
	}
	marker.isAdded = false;
	this.markers_.push(marker);
};

//Implementation of the onAdd interface method.
MarkerClusterer.prototype.onAdd = function () {
	var cMarkerClusterer = this;

	this.activeMap_ = this.getMap();
	this.ready_ = true;

	this.repaint();

	// Add the map event listeners
	this.listeners_ = [
		google.maps.event.addListener(this.getMap(), "zoom_changed", function () {
			cMarkerClusterer.resetViewport_(false);
			if (this.getZoom() === (this.get("minZoom") || 0) || this.getZoom() === this.get("maxZoom")) {
				google.maps.event.trigger(this, "idle");
			}
		}),
		google.maps.event.addListener(this.getMap(), "idle", function () {
			cMarkerClusterer.redraw_();
		})
	];
};

//Recalculates and redraws all the marker clusters from scratch. Call this after changing any properties.
MarkerClusterer.prototype.repaint = function () {
	var oldClusters = this.clusters_.slice();
	this.clusters_ = [];
	this.resetViewport_(false);
	this.redraw_();

	// Remove the old clusters.
	// Do it in a timeout to prevent blinking effect.
	setTimeout(function () {
		var i;
		for (i = 0; i < oldClusters.length; i++) {
			oldClusters[i].remove();
		}
	}, 0);
};

//Removes all clusters from the map. The markers are also removed from the map if <code>opt_hide</code> is set to <code>true</code>.
MarkerClusterer.prototype.resetViewport_ = function (opt_hide) {
	var i, marker;
	// Remove all the clusters
	for (i = 0; i < this.clusters_.length; i++) {
		this.clusters_[i].remove();
	}
	this.clusters_ = [];

	// Reset the markers to not be added and to be removed from the map.
	for (i = 0; i < this.markers_.length; i++) {
		marker = this.markers_[i];
		marker.isAdded = false;
		if (opt_hide) {
			marker.setMap(null);
		}
	}
};

//Redraws all the clusters.
MarkerClusterer.prototype.redraw_ = function () {
	this.createClusters_(0);
};

//Creates the clusters. This is done in batches to avoid timeout errors in some browsers when there is a huge number of markers.
MarkerClusterer.prototype.createClusters_ = function (iFirst) {
	var i, marker;
	var mapBounds;
	var cMarkerClusterer = this;
	if (!this.ready_) {
		return;
	}

	// Cancel previous batch processing if we're working on the first batch:
	if (iFirst === 0) {
		//This event is fired when the <code>MarkerClusterer</code> begins clustering markers.
		google.maps.event.trigger(this, "clusteringbegin", this);

		if (typeof this.timerRefStatic !== "undefined") {
			clearTimeout(this.timerRefStatic);
			delete this.timerRefStatic;
		}
	}

	// Get our current map view bounds. Create a new bounds object so we don't affect the map. See Comments 9 & 11 on Issue 3651 relating to this workaround for a Google Maps bug:
	if (this.getMap().getZoom() > 3) {
		mapBounds = new google.maps.LatLngBounds(this.getMap().getBounds().getSouthWest(),
			this.getMap().getBounds().getNorthEast());
	} 
	else {
		mapBounds = new google.maps.LatLngBounds(new google.maps.LatLng(85.02070771743472, -178.48388434375), new google.maps.LatLng(-85.08136444384544, 178.00048865625));
	}
	var bounds = this.getExtendedBounds(mapBounds);

	var iLast = Math.min(iFirst + this.batchSize_, this.markers_.length);

	for (i = iFirst; i < iLast; i++) {
		marker = this.markers_[i];
		if (!marker.isAdded && this.isMarkerInBounds_(marker, bounds)) {
			if (!this.ignoreHidden_ || (this.ignoreHidden_ && marker.getVisible())) {
				this.addToClosestCluster_(marker);
			}
		}
	}

	if (iLast < this.markers_.length) {
		this.timerRefStatic = setTimeout(function () {
			cMarkerClusterer.createClusters_(iLast);
		}, 0);
	} 
	else {
		delete this.timerRefStatic;

		//This event is fired when the <code>MarkerClusterer</code> stops clustering markers.
		google.maps.event.trigger(this, "clusteringend", this);
	}
};

//Returns the current bounds extended by the grid size.
MarkerClusterer.prototype.getExtendedBounds = function (bounds) {
	var projection = this.getProjection();

	// Turn the bounds into latlng.
	var tr = new google.maps.LatLng(bounds.getNorthEast().lat(),
		bounds.getNorthEast().lng());
	var bl = new google.maps.LatLng(bounds.getSouthWest().lat(),
		bounds.getSouthWest().lng());

	// Convert the points to pixels and the extend out by the grid size.
	var trPix = projection.fromLatLngToDivPixel(tr);
	trPix.x += this.gridSize_;
	trPix.y -= this.gridSize_;

	var blPix = projection.fromLatLngToDivPixel(bl);
	blPix.x -= this.gridSize_;
	blPix.y += this.gridSize_;

	// Convert the pixel points back to LatLng
	var ne = projection.fromDivPixelToLatLng(trPix);
	var sw = projection.fromDivPixelToLatLng(blPix);

	// Extend the bounds to contain the new bounds.
	bounds.extend(ne);
	bounds.extend(sw);

	return bounds;
};

//Determines if a marker is contained in a bounds.
MarkerClusterer.prototype.isMarkerInBounds_ = function (marker, bounds) {
	return bounds.contains(marker.getPosition());
};

//Adds a marker to a cluster, or creates a new cluster.
MarkerClusterer.prototype.addToClosestCluster_ = function (marker) {
	var i, d, cluster, center;
	var distance = 40000; // Some large number
	var clusterToAddTo = null;
	for (i = 0; i < this.clusters_.length; i++) {
		cluster = this.clusters_[i];
		center = cluster.getCenter();
		if (center) {
			d = this.distanceBetweenPoints_(center, marker.getPosition());
			if (d < distance) {
				distance = d;
				clusterToAddTo = cluster;
			}
		}
	}

	if (clusterToAddTo && clusterToAddTo.isMarkerInClusterBounds(marker)) {
		clusterToAddTo.addMarker(marker);
	} 
	else {
		cluster = new Cluster(this);
		cluster.addMarker(marker);
		this.clusters_.push(cluster);
	}
};

//Creates a single cluster that manages a group of proximate markers. Used internally, do not call this constructor directly.
function Cluster(mc) {
	this.markerClusterer_ = mc;
	this.map_ = mc.getMap();
	this.gridSize_ = mc.getGridSize();
	this.minClusterSize_ = mc.getMinimumClusterSize();
	this.averageCenter_ = mc.getAverageCenter();
	this.markers_ = [];
	this.center_ = null;
	this.bounds_ = null;
	this.clusterIcon_ = new ClusterIcon(this, mc.getStyles());
}

//Returns the value of the <code>gridSize</code> property.
MarkerClusterer.prototype.getGridSize = function () {
	return this.gridSize_;
};

//Returns the value of the <code>minimumClusterSize</code> property.
MarkerClusterer.prototype.getMinimumClusterSize = function () {
	return this.minClusterSize_;
};

//Returns the value of the <code>averageCenter</code> property.
MarkerClusterer.prototype.getAverageCenter = function () {
	return this.averageCenter_;
};

//Returns the value of the <code>styles</code> property.
MarkerClusterer.prototype.getStyles = function () {
	return this.styles_;
};

function ClusterIcon(cluster, styles) {
	cluster.getMarkerClusterer().extend(ClusterIcon, google.maps.OverlayView);

	this.cluster_ = cluster;
	this.className_ = cluster.getMarkerClusterer().getClusterClass();
	this.styles_ = styles;
	this.center_ = null;
	this.div_ = null;
	this.sums_ = null;
	this.visible_ = false;
  
	this.setMap(cluster.getMap()); // Note: this causes onAdd to be called
}

//Returns the <code>MarkerClusterer</code> object with which the cluster is associated.
Cluster.prototype.getMarkerClusterer = function () {
	return this.markerClusterer_;
};

//Returns the value of the <code>clusterClass</code> property.
MarkerClusterer.prototype.getClusterClass = function () {
	return this.clusterClass_;
};

//Returns the map with which the cluster is associated.
Cluster.prototype.getMap = function () {
	return this.map_;
};

//Adds a marker to the cluster.
Cluster.prototype.addMarker = function (marker) {
	var i;
	var mCount;
	var mz;

	if (this.isMarkerAlreadyAdded_(marker)) {
		return false;
	}

	if (!this.center_) {
		this.center_ = marker.getPosition();
		this.calculateBounds_();
	} 
	else {
		if (this.averageCenter_) {
			var l = this.markers_.length + 1;
			var lat = (this.center_.lat() * (l - 1) + marker.getPosition().lat()) / l;
			var lng = (this.center_.lng() * (l - 1) + marker.getPosition().lng()) / l;
			this.center_ = new google.maps.LatLng(lat, lng);
			this.calculateBounds_();
		}
	}

	marker.isAdded = true;
	this.markers_.push(marker);

	mCount = this.markers_.length;
	mz = this.markerClusterer_.getMaxZoom();
	if (mz !== null && this.map_.getZoom() > mz) {
		// Zoomed in past max zoom, so show the marker.
		if (marker.getMap() !== this.map_) {
			marker.setMap(this.map_);
		}
	} 
	else if (mCount < this.minClusterSize_) {
		// Min cluster size not reached so show the marker.
		if (marker.getMap() !== this.map_) {
			marker.setMap(this.map_);
		}
	} 
	else if (mCount === this.minClusterSize_) {
		// Hide the markers that were showing.
		for (i = 0; i < mCount; i++) {
			this.markers_[i].setMap(null);
		}
	} 
	else {
		marker.setMap(null);
	}

	this.updateIcon_();
	return true;
};

//Adds the icon to the DOM.
ClusterIcon.prototype.onAdd = function () {
	var cClusterIcon = this;
	var cMouseDownInCluster;
	var cDraggingMapByCluster;
  
	this.div_ = document.createElement("div");
	this.div_.className = this.className_;
	if (this.visible_) {
		this.show();
	}

	this.getPanes().overlayMouseTarget.appendChild(this.div_);

	// Fix for Issue 157
	google.maps.event.addListener(this.getMap(), "bounds_changed", function () {
		cDraggingMapByCluster = cMouseDownInCluster;
	});

	google.maps.event.addDomListener(this.div_, "mousedown", function () {
		cMouseDownInCluster = true;
		cDraggingMapByCluster = false;
	});

	google.maps.event.addDomListener(this.div_, "click", function (e) {
		cMouseDownInCluster = false;
		if (!cDraggingMapByCluster) {
			var theBounds;
			var mz;
			var mc = cClusterIcon.cluster_.getMarkerClusterer();
      
			//This event is fired when a cluster marker is clicked.
			google.maps.event.trigger(mc, "click", cClusterIcon.cluster_);
			google.maps.event.trigger(mc, "clusterclick", cClusterIcon.cluster_); // deprecated name

			// The default click handler follows. Disable it by setting the zoomOnClick property to false.
			if (mc.getZoomOnClick()) {
				// Zoom into the cluster.
				mz = mc.getMaxZoom();
				theBounds = cClusterIcon.cluster_.getBounds();
				mc.getMap().fitBounds(theBounds);
				// There is a fix for Issue 170 here:
				setTimeout(function () {
					mc.getMap().fitBounds(theBounds);
					// Don't zoom beyond the max zoom level
					if (mz !== null && (mc.getMap().getZoom() > mz)) {
						mc.getMap().setZoom(mz + 1);
					}
				}, 100);
			}

			// Prevent event propagation to the map:
			e.cancelBubble = true;
			if (e.stopPropagation) {
				e.stopPropagation();
			}
		}
	});

	google.maps.event.addDomListener(this.div_, "mouseover", function () {
		var mc = cClusterIcon.cluster_.getMarkerClusterer();
		//This event is fired when the mouse moves over a cluster marker.
		google.maps.event.trigger(mc, "mouseover", cClusterIcon.cluster_);
	});

	google.maps.event.addDomListener(this.div_, "mouseout", function () {
		var mc = cClusterIcon.cluster_.getMarkerClusterer();
		//This event is fired when the mouse moves out of a cluster marker.
		google.maps.event.trigger(mc, "mouseout", cClusterIcon.cluster_);
	});
};

//Draws the icon.
ClusterIcon.prototype.draw = function () {
	if (this.visible_) {
		var pos = this.getPosFromLatLng_(this.center_);
		this.div_.style.top = pos.y + "px";
		this.div_.style.left = pos.x + "px";
	}
};

//Determines if a marker has already been added to the cluster.
Cluster.prototype.isMarkerAlreadyAdded_ = function (marker) {
	var i;
	if (this.markers_.indexOf) {
		return this.markers_.indexOf(marker) !== -1;
	} 
	else {
		for (i = 0; i < this.markers_.length; i++) {
			if (marker === this.markers_[i]) {
				return true;
			}
		}
	}
	return false;
};

//Calculates the extended bounds of the cluster with the grid.
Cluster.prototype.calculateBounds_ = function () {
	var bounds = new google.maps.LatLngBounds(this.center_, this.center_);
	this.bounds_ = this.markerClusterer_.getExtendedBounds(bounds);
};

//Returns the value of the <code>maxZoom</code> property.
MarkerClusterer.prototype.getMaxZoom = function () {
	return this.maxZoom_;
};

//Updates the cluster icon.
Cluster.prototype.updateIcon_ = function () {
	var mCount = this.markers_.length;
	var mz = this.markerClusterer_.getMaxZoom();

	if (mz !== null && this.map_.getZoom() > mz) {
		this.clusterIcon_.hide();
		return;
	}

	if (mCount < this.minClusterSize_) {
		// Min cluster size not yet reached.
		this.clusterIcon_.hide();
		return;
	}

	var numStyles = this.markerClusterer_.getStyles().length;
	var sums = this.markerClusterer_.getCalculator()(this.markers_, numStyles);
	this.clusterIcon_.setCenter(this.center_);
	this.clusterIcon_.useStyle(sums);
	this.clusterIcon_.show();
};

//Hides the icon.
ClusterIcon.prototype.hide = function () {
	if (this.div_) {
		this.div_.style.display = "none";
	}
	this.visible_ = false;
};

//Returns the center of the cluster.
Cluster.prototype.getCenter = function () {
	return this.center_;
};

//Calculates the distance between two latlng locations in km.
MarkerClusterer.prototype.distanceBetweenPoints_ = function (p1, p2) {
	var R = 6371; // Radius of the Earth in km
	var dLat = (p2.lat() - p1.lat()) * Math.PI / 180;
	var dLon = (p2.lng() - p1.lng()) * Math.PI / 180;
	var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
		Math.cos(p1.lat() * Math.PI / 180) * Math.cos(p2.lat() * Math.PI / 180) *
		Math.sin(dLon / 2) * Math.sin(dLon / 2);
	var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
	var d = R * c;
	return d;
};

//Determines if a marker lies within the cluster's bounds.
Cluster.prototype.isMarkerInClusterBounds = function (marker) {
	return this.bounds_.contains(marker.getPosition());
};

//Returns the value of the <code>calculator</code> property.
MarkerClusterer.prototype.getCalculator = function () {
	return this.calculator_;
};

//The default function for determining the label text and style for a cluster icon.
MarkerClusterer.CALCULATOR = function (markers, numStyles) {
	var index = 0;
	var count = markers.length.toString();

	var dv = count;
	while (dv !== 0) {
		dv = parseInt(dv / 10, 10);
		index++;
	}

	index = Math.min(index, numStyles);
	return {
		text: count,
		index: index
	};
};

//Sets the position at which to center the icon.
ClusterIcon.prototype.setCenter = function (center) {
	this.center_ = center;
};

//Sets the icon styles to the appropriate element in the styles array.
ClusterIcon.prototype.useStyle = function (sums) {
	this.sums_ = sums;
	var index = Math.max(0, sums.index - 1);
	index = Math.min(this.styles_.length - 1, index);
	var style = this.styles_[index];
	this.color_ = style.color;
	this.anchor_ = style.anchor;
	this.anchorIcon_ = [parseInt(28 / 2, 10), parseInt(28 / 2, 10)];
	this.textColor_ = "black";
	this.textSize_ = 11;
	this.textDecoration_ = "none";
	this.fontWeight_ = "bold";
	this.fontStyle_ = "normal";
	this.fontFamily_ = "Arial,sans-serif";
	this.backgroundPosition_ = "0 0";
};

//Positions and shows the icon.
ClusterIcon.prototype.show = function () {
	if (this.sums_.text < 10) {
		var circle_r = 9;
		var div_size = 18;
		var text_x = 35;
		var text_y = 70;
	}
	else if (this.sums_.text > 9 && this.sums_.text < 100) {
		var circle_r = 11.5;
		var div_size = 23;
		var text_x = 24;
		var text_y = 67;
	}
	else if (this.sums_.text > 99 && this.sums_.text < 1000) {
		var circle_r = 14;
		var div_size = 28;
		var text_x = 19;
		var text_y = 65;
	}
	else if (this.sums_.text > 999 && this.sums_.text < 10000) {
		var circle_r = 16.5;
		var div_size = 33;
		var text_x = 12;
		var text_y = 65;
	}
	else if (this.sums_.text > 9999) {
		this.sums_.text = '10000+';
		var circle_r = 21;
		var div_size = 42;
		var text_x = 5;
		var text_y = 62;
	}
	if (this.div_) {
		var pos = this.getPosFromLatLng_(this.center_);
		this.div_.style.cssText = this.createCss(pos);
		this.div_.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" style="height:'+div_size+'px;width:'+div_size+'px;" ><g><circle cx="'+circle_r+'" cy="'+circle_r+'" r="'+circle_r+'" fill-opacity="0.8" fill="#'+this.color_+'"></circle><text x="'+text_x+'%" y="'+text_y+'%">'+this.sums_.text+'</text></g></svg>';
		this.div_.style.display = "";
		//alert(this.div_.outerHTML);
	}
	this.visible_ = true;
};

//Returns the position at which to place the DIV depending on the latlng.
ClusterIcon.prototype.getPosFromLatLng_ = function (latlng) {
	var pos = this.getProjection().fromLatLngToDivPixel(latlng);
	pos.x -= this.anchorIcon_[1];
	pos.y -= this.anchorIcon_[0];
	return pos;
};

//Creates the cssText style parameter based on the position of the icon.
ClusterIcon.prototype.createCss = function (pos) {
	var style = [];
	style.push('cursor:pointer; top:' + pos.y + 'px; left:' +
		pos.x + 'px; color:' + this.textColor_ + '; position:absolute; font-size:' +
		this.textSize_ + 'px; font-family:' + this.fontFamily_ + '; font-weight:' +
		this.fontWeight_ + '; font-style:' + this.fontStyle_ + '; text-decoration:' +
		this.textDecoration_ + ';');

	return style.join("");
};

//Removes the cluster from the map.
Cluster.prototype.remove = function () {
	this.clusterIcon_.setMap(null);
	this.markers_ = [];
	delete this.markers_;
};

//Removes the icon from the DOM.
ClusterIcon.prototype.onRemove = function () {
	if (this.div_ && this.div_.parentNode) {
		this.hide();
		google.maps.event.clearInstanceListeners(this.div_);
		this.div_.parentNode.removeChild(this.div_);
		this.div_ = null;
	}
};

//Implementation of the draw interface method.
MarkerClusterer.prototype.draw = function () {};







//Returns the number of markers managed by the cluster.
Cluster.prototype.getSize = function () {
	return this.markers_.length;
};

//Returns the array of markers managed by the cluster.
Cluster.prototype.getMarkers = function () {
	return this.markers_;
};

//Returns the bounds of the cluster.
Cluster.prototype.getBounds = function () {
	var i;
	var bounds = new google.maps.LatLngBounds(this.center_, this.center_);
	var markers = this.getMarkers();
	for (i = 0; i < markers.length; i++) {
		bounds.extend(markers[i].getPosition());
	}
	return bounds;
};

//Implementation of the onRemove interface method. Removes map event listeners and all cluster icons from the DOM. All managed markers are also put back on the map.
MarkerClusterer.prototype.onRemove = function () {
	var i;

	// Put all the managed markers back on the map:
	for (i = 0; i < this.markers_.length; i++) {
		if (this.markers_[i].getMap() !== this.activeMap_) {
			this.markers_[i].setMap(this.activeMap_);
		}
	}

	// Remove all clusters:
	for (i = 0; i < this.clusters_.length; i++) {
		this.clusters_[i].remove();
	}
	this.clusters_ = [];

	// Remove map event listeners:
	for (i = 0; i < this.listeners_.length; i++) {
		google.maps.event.removeListener(this.listeners_[i]);
	}
	this.listeners_ = [];

	this.activeMap_ = null;
	this.ready_ = false;
};

//Fits the map to the bounds of the markers managed by the clusterer.
MarkerClusterer.prototype.fitMapToMarkers = function () {
	var i;
	var markers = this.getMarkers();
	var bounds = new google.maps.LatLngBounds();
	for (i = 0; i < markers.length; i++) {
		bounds.extend(markers[i].getPosition());
	}

	this.getMap().fitBounds(bounds);
};

//Sets the value of the <code>gridSize</code> property.
MarkerClusterer.prototype.setGridSize = function (gridSize) {
	this.gridSize_ = gridSize;
};

//Sets the value of the <code>minimumClusterSize</code> property.
MarkerClusterer.prototype.setMinimumClusterSize = function (minimumClusterSize) {
	this.minClusterSize_ = minimumClusterSize;
};

//Sets the value of the <code>maxZoom</code> property.
MarkerClusterer.prototype.setMaxZoom = function (maxZoom) {
	this.maxZoom_ = maxZoom;
};

//Sets the value of the <code>styles</code> property.
MarkerClusterer.prototype.setStyles = function (styles) {
	this.styles_ = styles;
};

//Returns the value of the <code>zoomOnClick</code> property.
MarkerClusterer.prototype.getZoomOnClick = function () {
	return this.zoomOnClick_;
};

//Sets the value of the <code>zoomOnClick</code> property.
MarkerClusterer.prototype.setZoomOnClick = function (zoomOnClick) {
	this.zoomOnClick_ = zoomOnClick;
};

//Sets the value of the <code>averageCenter</code> property.
MarkerClusterer.prototype.setAverageCenter = function (averageCenter) {
	this.averageCenter_ = averageCenter;
};

//Returns the value of the <code>ignoreHidden</code> property.
MarkerClusterer.prototype.getIgnoreHidden = function () {
	return this.ignoreHidden_;
};

//Sets the value of the <code>ignoreHidden</code> property.
MarkerClusterer.prototype.setIgnoreHidden = function (ignoreHidden) {
	this.ignoreHidden_ = ignoreHidden;
};

//Sets the value of the <code>calculator</code> property.
MarkerClusterer.prototype.setCalculator = function (calculator) {
	this.calculator_ = calculator;
};

//Returns the value of the <code>batchSizeIE</code> property.
MarkerClusterer.prototype.getBatchSizeIE = function () {
	return this.batchSizeIE_;
};

//Sets the value of the <code>batchSizeIE</code> property.
MarkerClusterer.prototype.setBatchSizeIE = function (batchSizeIE) {
	this.batchSizeIE_ = batchSizeIE;
};

//Sets the value of the <code>clusterClass</code> property.
MarkerClusterer.prototype.setClusterClass = function (clusterClass) {
	this.clusterClass_ = clusterClass;
};

//Returns the array of markers managed by the clusterer.
MarkerClusterer.prototype.getMarkers = function () {
	return this.markers_;
};

//Returns the number of markers managed by the clusterer.
MarkerClusterer.prototype.getTotalMarkers = function () {
	return this.markers_.length;
};

//Returns the current array of clusters formed by the clusterer.
MarkerClusterer.prototype.getClusters = function () {
	return this.clusters_;
};

//Returns the number of clusters formed by the clusterer.
MarkerClusterer.prototype.getTotalClusters = function () {
	return this.clusters_.length;
};

//Adds a marker to the clusterer. The clusters are redrawn unless <code>opt_nodraw</code> is set to <code>true</code>.
MarkerClusterer.prototype.addMarker = function (marker, opt_nodraw) {
	this.pushMarkerTo_(marker);
	if (!opt_nodraw) {
		this.redraw_();
	}
};

//Removes a marker from the cluster.  The clusters are redrawn unless <code>opt_nodraw</code> is set to <code>true</code>.
MarkerClusterer.prototype.removeMarker = function (marker, opt_nodraw) {
	var removed = this.removeMarker_(marker);

	if (!opt_nodraw && removed) {
		this.repaint();
	}

	return removed;
};

//Removes an array of markers from the cluster. The clusters are redrawn unless <code>opt_nodraw</code> is set to <code>true</code>.
MarkerClusterer.prototype.removeMarkers = function (markers, opt_nodraw) {
	var i, r;
	var removed = false;

	for (i = 0; i < markers.length; i++) {
		r = this.removeMarker_(markers[i]);
		removed = removed || r;
	}

	if (!opt_nodraw && removed) {
		this.repaint();
	}

	return removed;
};

//Removes a marker and returns true if removed, false if not.
MarkerClusterer.prototype.removeMarker_ = function (marker) {
	var i;
	var index = -1;
	if (this.markers_.indexOf) {
		index = this.markers_.indexOf(marker);
	} 
	else {
		for (i = 0; i < this.markers_.length; i++) {
			if (marker === this.markers_[i]) {
				index = i;
				break;
			}
		}
	}

	if (index === -1) {
		// Marker is not in our list of markers, so do nothing:
		return false;
	}

	marker.setMap(null);
	this.markers_.splice(index, 1); // Remove the marker from the list of managed markers
	return true;
};

//Removes all clusters and markers from the map and also removes all markers managed by the clusterer.
MarkerClusterer.prototype.clearMarkers = function () {
	this.resetViewport_(true);
	this.markers_ = [];
};