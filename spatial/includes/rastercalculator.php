<!-- Raster Calculator Tool -->
<div id="rastercalctool" data-role="popup" class="well" style="width:600px;height:40%;">
    <a class="boxclose rastercalctool_close" id="boxclose"></a>
    <h2>Raster Overlay Calculator</h2>
    <div style="margin-top:10px;">
        <b>Enter name for output overlay:</b>
        <input data-role="none" type="text" id="rastercalcOutputName" style="width:150px;margin-left:10px;" value="" />
    </div>
    <div style="margin-top:10px;">
        <b>Set classification values:</b>
        <div id="rastercalcTableDiv">
            <table id="rastercalctable" class="styledtable" style="font-family:Arial;font-size:12px;margin-top:15px;margin-left:auto;margin-right:auto;width:500px;">
                <thead>
                    <tr>
                        <th style="text-align:center;">Overlay 1</th>
                        <th style="text-align:center;">Operator</th>
                        <th style="text-align:center;">Overlay 2</th>
                        <th style="text-align:center;">Output Color</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="width:200px;">
                            <select data-role="none" id="rastcalcoverlay1" style=""></select>
                        </td>
                        <td style="width:100px;">
                            <select data-role="none" id="rastcalcoperator" style="">
                                <option value="">Select Operator</option>
                                <option value="+">+</option>
                                <option value="-">-</option>
                                <option value="*">*</option>
                                <option value="/">/</option>
                            </select>
                        </td>
                        <td style="width:200px;">
                            <select data-role="none" id="rastcalcoverlay2" style=""></select>
                        </td>
                        <td style="width:30px;">
                            <input data-role="none" id="rastcalccolor" class="color" style="cursor:pointer;border:1px black solid;height:20px;width:20px;margin-left:5px;margin-bottom:-2px;font-size:0px;" value="FFFFFF"/>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div style="margin-right:50px;margin-top:20px;float:right;">
        <div id="rastercalcbutton" style="float:right;">
            <button data-role="none" type="button" onclick='checkRasterCalcForm();' >Calculate</button>
        </div>
        <div id="rastercalcclearbutton" style="float:right;margin-right:15px;">
            <button data-role="none" type="button" onclick='clearRasterCalcForm();' >Reset Tool</button>
        </div>
    </div>
</div>