function FloraMap() {

  var self        = this;//so we always have access to the Object
  this.map        = null;
  this.defaultCenter    = new google.maps.LatLng(39, -95.5);//center of lower 48, can be overridden in init()
  this.defaultZoom    = 4;
  this.zoomBreakpoint = 725;
  this.currCenter     = this.defaultCenter;
  this.locations      = null;
  this.locationMarkers  = new Array;
  this.formOptions    = new Object();
  this.mapOptions     = new Object();
  this.mapPage      = null;

  this.init = function(options) {

    this.mapPage = options.mapPage;
    var center = this.defaultCenter;
    if (options.lat && options.lng) {
      center = new google.maps.LatLng(options.lat,options.lng);
    }
    var zoom = options.zoom? options.zoom : this.defaultZoom;
    if ($(window).width() < self.zoomBreakpoint) {//don't cut off map on mobile
    	zoom = zoom - 1;
    }
    self.mapOptions = {
      zoom: zoom,
      center: center
    };
    if (options.style) {
      self.mapOptions['styles'] = options.style
    }
    self.mapOptions['fullscreenControl'] = false;
    this.map = new google.maps.Map(document.getElementById('floramap'),self.mapOptions);


    $.when(this.setLocations()).then(function(location_json) {
      self.locations = $.parseJSON(location_json);
      self.setMarkers();
      if (!options.initMarkers) { //hide markers
        for (k = 0; k < self.locationMarkers.length; k++) {
          self.locationMarkers[k].setVisible(false);
        }
      }
    });

  }
  this.setLocations = function () {

    return $.ajax({
      url: floramap.ajax_url,
          async: true,
      type: 'get',
      data : {
        action : 'init_locations',
        mapPage : self.mapPage,
        security : floramap.ajax_nonce
      },
      success: function( data ) {
        return data;
      },
      error: function (request, status, error) {
        console.log(request.responseText);
      }
    });
  }
  this.setMarkers = function() {
    if (self.locations.length) {
      for (var m = 0; m < self.locations.length; m++) {
        var loc = this.locations[m];
        var markerWidth = 16;
        var markerHeight = 27;

				marker_url = '/wp-content/plugins/globalp-map/img/map-pin-retail-sm.png';
				markerWidth = 21;
				markerHeight = 32;
        
        var markerSize = new google.maps.Size(markerWidth, markerHeight);
        var image = {
          url: marker_url,
          size: markerSize,
          origin: new google.maps.Point(0,0),
          anchor: new google.maps.Point(markerWidth/2, markerHeight),//x is half the width
              labelOrigin: new google.maps.Point(markerWidth/2,markerHeight + 10)// y is height + 10
        };

        var point = new google.maps.LatLng(loc.lat, loc.lng);

        var marker = new google.maps.Marker({
          position: point,
          map: self.map,
          icon: image,
          defaultIcon: image,
          id: loc.id,
          //zIndex: markerZIndex
        });
        if (loc.site_type != undefined) {//terminals
          marker['terminal_type'] = loc.site_type;
        }
        google.maps.event.addListener(marker, 'click', function() {
          //self.showLocation(this);
          //send to url
        });
        this.locationMarkers.push(marker);
      }

    }
  }

  this.updateMarkers = function() {
    var bounds = new google.maps.LatLngBounds();
    var visibleMarkers = [];
    for (k = 0; k < self.locationMarkers.length; k++) {
      loc = self.getLocationByID(self.locationMarkers[k].id);
      if (loc.visible) {
        self.locationMarkers[k].setVisible(true);
        bounds.extend(self.locationMarkers[k].position);
        visibleMarkers.push(self.locationMarkers[k]);
      }else{
        self.locationMarkers[k].setVisible(false);
      }
    }

    if (visibleMarkers.length) {
      if (self.map && bounds){
        //https://stackoverflow.com/questions/3334729/google-maps-v3-fitbounds-zoom-too-close-for-single-marker
        // Don't zoom in too far on only one marker
        if (bounds.getNorthEast().equals(bounds.getSouthWest())) {
           var extendPoint1 = new google.maps.LatLng(bounds.getNorthEast().lat() + 0.01, bounds.getNorthEast().lng() + 0.01);
           var extendPoint2 = new google.maps.LatLng(bounds.getNorthEast().lat() - 0.01, bounds.getNorthEast().lng() - 0.01);
           bounds.extend(extendPoint1);
           bounds.extend(extendPoint2);
        }
        self.map.fitBounds(bounds);
      }
    }
  }

  this.getLocationByID = function(ID) {
    for (var p = 0; p < self.locations.length; p++) {
      if (self.locations[p].id == ID) {
        return self.locations[p];
      }
    }
    return false;
  }
  this.getMarkerByID = function(ID) {
    for (var p = 0; p < self.locationMarkers.length; p++) {
      if (self.locationMarkers[p].id == ID) {
        return self.locationMarkers[p];
      }
    }
    return false;
  }
  this.showMarkerByID = function(ID) {
    for (var p = 0; p < self.locationMarkers.length; p++) {
      if (self.locationMarkers[p].id == ID) {
        self.locationMarkers[p].visible = true;
        self.locationMarkers[p].setVisible(true);
      }
    }
    return false;
  }

  google.maps.event.addDomListener(window, "resize", function() {
    var thismap = self.map;
    var center = thismap.getCenter();
    google.maps.event.trigger(thismap, "resize");
    thismap.setCenter(center);
  });

}
