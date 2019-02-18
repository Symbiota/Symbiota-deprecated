<!-- Reclassify Tool -->
<div id="reclassifytool" data-role="popup" class="well" style="width:600px;height:40%;">
    <a class="boxclose reclassifytool_close" id="boxclose"></a>
    <h2>Reclassify Tool</h2>
    <div style="margin-top:5px;">
        <b>Select raster layer to reclassify:</b>
        <select data-role="none" id="reclassifysourcelayer" style="margin-left:10px;"></select>
    </div>
    <div style="margin-top:10px;">
        <b>Enter name for output overlay:</b>
        <input data-role="none" type="text" id="reclassifyOutputName" style="width:150px;margin-left:10px;" value="" />
    </div>
    <div style="margin-top:10px;">
        <b>Set classification values:</b>
        <div id="reclassifyTableDiv"></div>
    </div>
    <div style="margin-right:50px;margin-top:20px;float:right;">
        <div id="reclassifybutton" style="float:right;">
            <button data-role="none" type="button" onclick='checkReclassifyForm();' >Reclassify</button>
        </div>
        <div id="reclassifyclearbutton" style="float:right;margin-right:15px;">
            <button data-role="none" type="button" onclick='clearReclassifyForm();' >Reset Tool</button>
        </div>
    </div>
</div>