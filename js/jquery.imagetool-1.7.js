//Code: http://code.google.com/p/jquery-imagetool/
//Sample page: http://homepage.mac.com/bendik/imagetool/demo/index.html
(function($) {
    $.widget("ui.imagetool", {
        /**
        * Public methods
        */
                
        options: {
            src: null /* The image src is used */
            ,allowZoom: true
            ,allowPan: true
            ,allowResizeX: true
            ,allowResizeY: true
            ,zoomFactor: 5
            ,defaultCursor: "url(../../images/openhand.cur), move"
            ,zoomCursor: "crosshair"
            ,panCursor: "url(../../images/closedhand.cur), move"
            ,disabledCursor: "not-allowed"
            ,viewportWidth: 400
            ,viewportHeight: 300
            ,viewportMinWidth: 100
            ,viewportMinHeight: 80
            ,viewportMaxWidth: 800
            ,viewportMaxHeight: 800
            ,"cursor-se":"se-resize"
            ,"cursor-s":"s-resize"
            ,"cursor-e":"e-resize"
            ,edgeSensitivity: 15
            ,imageWidth: 200 /* The width of the work image */
            ,imageHeight: 200 /* The height of the work image */
            ,imageMaxWidth: 2500
            ,x: 0
            ,y: 0
            ,w: 1 
            ,h: 1
            ,ready: function() {}
            ,change: function() {}
        }
                
        ,reset: function(options) {
            $.extend(this.options, options);
            this._setup();
        }
        
        /**
        * Returns all options
        */
        ,properties: function() {
            return this.options;
        }
                

        ,_create: function() {
                var self = this;
                var o = this.options;
                o._cursor = o.defaultCursor;

                var image = this.element;
                image.css("display", "none");
                if(!o.src) {
                    o.src = image.attr("src");
                }
                // Set up the viewport
                image.wrap("<div/>");

                self._setup();
        }
        

        /**
         * Loads the image to get width/height
         * Called when 
         */
        ,_setup: function() {
                var self = this;
                var o = this.options;
                var image = this.element;
                var viewport = image.closest("div");

                viewport.css({
                        overflow: "hidden"
                        ,position: "relative" /* Needed by IE for some reason */
                        ,width: o.viewportWidth + "px"
                        ,height: o.viewportHeight + "px"
                        ,border: "solid 1px #084B8A"
                });
                if(o.allowPan || o.allowZoom) {
                        viewport.mousedown(function(e) {self._handleMouseDown(e);});
                        viewport.mouseover(function(e) {self._handleMouseOver(e);});
                        viewport.mouseout(function(e) {self._handleMouseOut(e);});
                }
                else {
                        image.css("cursor", o.disabledCursor);
                        viewport.mousedown(function(e) {
                                e.preventDefault();
                        });
                }
                
                var i = new Image();
                i.onload = function() {
                    o.imageWidth = i.width;
                    o.imageHeight = i.height;
                    self._configure();
                }
                i.src = o.src;

                if(o.src != image.attr("src")) {
                    image.attr("src", o.src);
                }
        }

        ,_configure: function() {
                var self = this;
                var o = this.options;
                var image = this.element;

                // Set the initial size of the image to the original size.
                o._width = o.imageWidth;
                o._height = o.imageHeight;

                // There is only one scale value. We don't stretch images.
                //var scale = Math.max(o.viewportWidth/(o.w * o.imageWidth), o.viewportHeight/(o.h * o.imageHeight));
                //Modified to allow minimum zoom below portal size (egbot: Jan 2014)
                var scale = Math.min(o.viewportWidth/(o.w * o.imageWidth), o.viewportHeight/(o.h * o.imageHeight));

                o._width = o._width * scale;
                o._height = o._height * scale;

                o._oldWidth = o._width;
                o._oldHeight = o._height;

                /**
                 * Calculate absolute pixel values for the position of the image relative to the viewport.
                 */
                o._absx = -(o.x * o._width);
                o._absy = -(o.y * o._height);

                self._zoom();

                image.css({position: "relative", display: "block"});
                self._trigger("ready", null, o);
        }


        ,_handleMouseOver: function(event) {
                var self = this;
                var o = this.options;
                var image = this.element;
                var viewport = image.parent();
                viewport.css("cursor", o._cursor);              
                viewport.mousemove(function(mme) {self._handleMouseMove(mme);});
                if(typeof $.fn.mousewheel == "function") {
                        viewport.mousewheel(function(mwe,delta) {self._handleMouseWheel(mwe,delta);return false;});
                }
                

        }
        /**
         * Sets the right cursor when the mouse moves off the image. 
         */
        ,_handleMouseOut: function(e) {
                var o = this.options;
                var image = this.element;
                var viewport = image.parent();
                image.css("cursor", o._cursor);
                viewport.unbind("mousewheel").unbind("mousemove");
        }

        ,_handleMouseMove: function(mmevt) {
                var self = this;
                var image = this.element;
                var viewport = image.parent();
                var o = this.options;

                
                var mouseX = (mmevt.pageX - viewport.offset().left);
                var mouseY = (mmevt.pageY - viewport.offset().top);
                
                var edge = self._getEdge(mouseX, mouseY);
                
                if(edge) {
                        o._cursor = o["cursor-" + edge];
                        edge = null;
                }
                else {
                        o._cursor = o.defaultCursor;
                }
                

                image.css("cursor", o._cursor);
        }
        
        /**
         * Find the edge n, e, s, w, 
         */
        ,_getEdge: function(x, y) {
                var self = this;
                var image = this.element;
                var o = this.options;
                
                var scale = o._width / o.imageWidth;


                var fromEdgdeE = o.viewportWidth - x;
                var fromEdgdeS = o.viewportHeight - y;


                if(fromEdgdeE < o.edgeSensitivity && fromEdgdeS < o.edgeSensitivity && (o.allowResizeX || o.allowResizeY)) {
                        return "se";
                }
                else if(fromEdgdeE < o.edgeSensitivity && o.allowResizeX) {
                        return "e";
                }
                else if(fromEdgdeS < o.edgeSensitivity && o.allowResizeY) {
                        return "s";
                }
                else {
                        return null;
                }
        }

        ,_handleMouseDown: function(mousedownEvent) {
                mousedownEvent.preventDefault();

                var self = this;
                var o = this.options;
                var image = this.element;
                var viewport = image.parent();

                o.origoX = mousedownEvent.clientX;
                o.origoY = mousedownEvent.clientY;

                var mouseX = (mousedownEvent.pageX - viewport.offset().left);
                var mouseY = (mousedownEvent.pageY - viewport.offset().top);

                var edge = self._getEdge(mouseX, mouseY);

                if(edge) {
                        $(document).mousemove(function(e) {
                                self._handleViewPortResize(e, edge);
                        });
                }
                else if(o.allowZoom && (mousedownEvent.shiftKey) ) {
                        o._cursor = o.zoomCursor;
                        image.css("cursor", o.zoomCursor);
                        $("body").css("cursor", o.zoomCursor);
                        $(document).mousemove(function(e) {
                                self._handleZoom(e);
                        });
                }
                else if(o.allowZoom && (mousedownEvent.ctrlKey) ) {
                    var o = this.options;
                    var self = this;

                    var factor = 50;

                    o._oldWidth = o._width;
                    o._oldHeight = o._height;

                    o._width = ((factor/100) * o._width) + o._width;
                    o._height = ((factor/100) * o._height) + o._height;

                    if(self._zoom(mousedownEvent)) {
                            this._trigger("change", mousedownEvent, o);
                            o.origoY = mousedownEvent.clientY;
                    }
                }
                else if(o.allowPan) {
                        
                        o._cursor = o.panCursor;
                        image.css("cursor", o._cursor);
                        $("body").css("cursor", o._cursor);
                        $(document).mousemove(function(e) {
                                self._handlePan(e);
                        });
                }

                $(document).mouseup(function() {
                        o._cursor = o.defaultCursor;
                        $("body").css("cursor", "default");
                        image.css("cursor", o._cursor);
                        viewport.unbind("mousemove").unbind("mouseup").unbind("mouseout");
                        $(document).unbind("mousemove");
                });
                return false;
        }
        
        ,_handleMouseWheel: function(e, delta) {                
                e.preventDefault();
                var self = this;
                var o = this.options;
                var image = this.element;
                
                var factor = o.zoomFactor * (delta < 0 ? -1 : 1);

                if(o.allowZoom) {
                        o._oldWidth = o._width;
                        o._oldHeight = o._height;
                        o._width = ((factor/100) * o._width) + o._oldWidth;
                        o._height = ((factor/100) * o._height) + o._oldHeight;

                        if(self._zoom(e)) {
                                this._trigger("change", e, o);
                                o.origoY = e.clientY;
                        }
                }
                return false;
        }

        ,_handleZoom: function(e) {
                e.preventDefault();
                var o = this.options;
                var self = this;

                var factor = (o.origoY - e.clientY);

                o._oldWidth = o._width;
                o._oldHeight = o._height;

                o._width = ((factor/100) * o._width) + o._width;
                o._height = ((factor/100) * o._height) + o._height;

                if(self._zoom(e)) {
                        this._trigger("change", e, o);
                        o.origoY = e.clientY;
                }
        }
        
        /**
         * Handles resize of the viewport
         */
        ,_handleViewPortResize: function(e, edge) {
                e.preventDefault();
                var self = this;
                var image = this.element;
                var o = this.options;

                var deltaX = o.origoX - e.clientX;
                var deltaY = o.origoY - e.clientY;

                o.origoX = e.clientX;
                o.origoY = e.clientY;

                var targetWidth = o.viewportWidth;
                var targetHeight = o.viewportHeight;

                if(edge == "e" || edge == "se") {
                        targetWidth = o.viewportWidth - deltaX;
                }
                if(edge == "s" || edge == "se") {
                        targetHeight = o.viewportHeight - deltaY;
                }

                if(targetWidth > o.viewportMaxWidth) {
                        o.viewportWidth = o.viewportMaxWidth;
                }
                else if(targetWidth < o.viewportMinWidth) {
                        o.viewportWidth = o.viewportMinWidth;
                }
                else if(o.allowResizeX) {
                        o.viewportWidth = targetWidth;
                }

                if(targetHeight > o.viewportMaxHeight) {
                        o.viewportHeight = o.viewportMaxHeight;
                }
                else if(targetHeight < o.viewportMinHeight) {
                        o.viewportHeight = o.viewportMinHeight;
                }
                else if(o.allowResizeY) {
                        o.viewportHeight = targetHeight;
                }
                self._resize();

                o.w = o.viewportWidth/o._width;
                o.h = o.viewportHeight/o._height;
                //Set cookie to remember width and height of view port
                //document.cookie = "symbimgport=" + escape(o.viewportWidth) + ":" + escape(o.viewportHeight);
                setPortXY(escape(o.viewportWidth),escape(o.viewportHeight));
        }

        ,_resize: function() {
                var self = this;
                var image = this.element;
                var o = this.options;
                
                image.parent().css({
                        width: o.viewportWidth + "px"
                        ,height: o.viewportHeight + "px"
                });

                self._fit();
                this._trigger("change", null, o);
        }


        ,_handlePan: function(e) {
                e.preventDefault();
                var self = this;
                var o = this.options;

                var deltaX = o.origoX - e.clientX;
                var deltaY = o.origoY - e.clientY;

                o.origoX = e.clientX;
                o.origoY = e.clientY;

                var targetX = o._absx - deltaX;
                var targetY = o._absy - deltaY;

                var minX = -o._width + o.viewportWidth;
                var minY = -o._height + o.viewportHeight;

                o._absx = targetX;
                o._absy = targetY;
                self._move();  

                
        } // end pan



        ,_move: function() {
                var o = this.options;
                var image = this.element;

                var minX = -o._width + o.viewportWidth;
                var minY = -o._height + o.viewportHeight;
                if(o._absx >= 0) {
                	//Keeps image from going too far to rigth
                	if(o._width > o.viewportWidth){
                		o._absx = 0;
                	}
                	else{
                		o._absx = minX/2;
                	}
                }
                else if(o._absx < minX) {
                	//Keeps image from being moved too far to left
                    o._absx = minX;
                }

                if(o._absy >= 0) {
                	if(o._height > o.viewportHeight){
                		o._absy = 0;
                	}
                	else{
                		o._absy = minY/2;
                	}
                }    
                else if(o._absy < minY) {
                    o._absy = minY;
                }
                
                o.x = (-o._absx/o._width);
                o.y = (-o._absy/o._height);

                image.css({
                    left: o._absx + "px"
                    ,top: o._absy + "px"
                });
                this._trigger("change", null, o);
        }


        /**
         * Zooms the image by setting its width/height
         * Makes sure the desired size greater or equal to the viewport size. 
         */
        ,_zoom: function(e) {
                var self = this;
                var image = this.element;
                var o = this.options;
                var viewport = image.parent();
                
                var wasZoomed = true;

				//Modified to allow zooming in beyond image size (egbot: Jan 2014)
				if((o._width < o.viewportWidth) && (o._height < o.viewportHeight)){
					if((o.viewportWidth - o._width) > (o.viewportHeight - o._height)) {
						o._width = parseInt(o.imageWidth * (o.viewportHeight/o.imageHeight));
						o._height = o.viewportHeight;
					}
    				else{
						o._height = parseInt(o.imageHeight * (o.viewportWidth/o.imageWidth));
						o._width = o.viewportWidth;
					}
					wasZoomed = false;
                }

                /*
                if(o._width < o.viewportWidth) {
                        o._height = parseInt(o.imageHeight * (o.viewportWidth/o.imageWidth));
                        o._width = o.viewportWidth;
                        wasZoomed = false;
                }

                if(o._height < o.viewportHeight) {
                        o._width = parseInt(o.imageWidth * (o.viewportHeight/o.imageHeight));
                        o._height = o.viewportHeight;
                        wasZoomed = false;
                }
                */

                if(o._width > o.imageMaxWidth) {
                        o._height = parseInt(o._height * (o.imageMaxWidth/o._width));
                        o._width = o.imageMaxWidth;
                        wasZoomed = false;
                }

                var originOffetX = 0;
                var originOffetY = 0;
                /**
                 * If an event is sent, we can use this as the origin of the zoom
                 */
                if (e && typeof(e) != 'undefined') {
                	// Zoom at the cursor position (like in Google Maps)
               	    if(e.pageX || e.pageY) {
               	    	originOffetX = e.pageX;
               	    	originOffetY = e.pageY;
                	}
               	    else {
               	    	originOffetX = e.clientX + document.body.scrollLeft + document.documentElement.scrollLeft;
               	    	originOffetY = e.clientY + document.body.scrollTop + document.documentElement.scrollTop;
               	    }
               	    originOffetX -= viewport.offset().left;
               	    originOffetY -= viewport.offset().top;
               	    originOffetX = 2*originOffetX;
               	    originOffetY = 2*originOffetY;
                	//originOffetX = e.clientX - viewport.offset().left; //Cursor position relative to viewport
                	//originOffetY = e.clientY - viewport.offset().top;
                }
                else{
                    // Scale at center of viewport
                    originOffetX = o.viewportWidth/2;
                    originOffetY = o.viewportHeight/2;
                }
                
                var cx = o._width /(-o._absx + originOffetX);
                var cy = o._height /(-o._absy + originOffetY);

                o._absx = o._absx - ((o._width - o._oldWidth) / cx);
                o._absy = o._absy - ((o._height - o._oldHeight) / cy);
                
                image.css({
                        width: o._width + "px"
                        ,height: o._height + "px"
                });
                
                self._move();
                
                o.w = o.viewportWidth/o._width;
                o.h = o.viewportHeight/o._height;
                
                return wasZoomed;
        }


        /**
         * Makes sure the image is not smaller than the viewport.
         */
        ,_fit: function() {
                var self = this;
                var image = this.element;
                var o = this.options;

                if(o.viewportWidth > o._width && o.viewportHeight > o._height) {
                        var factor = o.viewportWidth / o._width;
                        o._width = o.viewportWidth;
                        o._height = o._height * factor;
                        self._zoom();
                }
                self._move();
        }
        
    });

})(jQuery);