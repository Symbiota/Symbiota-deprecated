<!-- Map Tools -->
<div id="maptools" data-role="popup" class="well" style="width:600px;height:90%;">
    <a class="boxclose maptools_close" id="boxclose"></a>
    <h2>Tools</h2>
    <div style="margin-top:5px;">
        <b>Display Heat Map</b> <input data-role="none" type='checkbox' name='heatmapswitch' id='heatmapswitch' onchange="toggleHeatMap();" value='1' >
    </div>
    <div style="margin-top:5px;">
        <b>Display Date Slider</b> <input data-role="none" type='checkbox' name='datesliderswitch' id='datesliderswitch' onchange="toggleDateSlider();" value='1' >
        <input data-role="none" type="radio" name="dateslidertype" id="dssingletype" value="single" onchange="checkDateSliderType();" checked /> Single
        <input data-role="none" type="radio" name="dateslidertype" id="dsdualtype" value="dual" onchange="checkDateSliderType();" /> Dual
    </div>
    <div id="reclassifytoollink" style="margin-top:5px;display:none;">
        <a class="maptools_close reclassifytool_open" href="#"><b>Reclassify Tool</b></a>
    </div>
    <!-- <div id="rastercalctoollink" style="margin-top:5px;display:none;">
        <a class="maptools_close rastercalctool_open" href="#"><b>Raster Overlay Calculator</b></a>
    </div> -->
    <div id="vectorizeoverlaytoollink" style="margin-top:5px;display:none;">
        <a class="" onclick="checkVectorizeOverlayToolOpen();" href="#"><b>Vectorize Tool</b></a>
    </div>
</div>