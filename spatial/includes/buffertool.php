<!-- Buffer Tool -->
<div id="buffertool" data-role="popup" class="well" style="width:600px;height:25%;">
    <a class="boxclose buffertool_close" id="boxclose"></a>
    <h2>Buffer Tool</h2>
    <div style="margin-top:10px;">
        <b>Enter size of buffer in kilometers:</b>
        <input data-role="none" type="text" id="bufferSize" style="width:150px;margin-left:10px;" value="" />
    </div>
    <div style="margin-right:50px;margin-top:20px;float:right;">
        <div id="bufferbutton" style="float:right;">
            <button data-role="none" type="button" onclick='checkBufferForm();' >Create Buffer</button>
        </div>
    </div>
</div>