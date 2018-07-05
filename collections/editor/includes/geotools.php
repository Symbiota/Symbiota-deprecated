<div id="coordAidDiv">
	<div id="dmsAidDiv">
		<div>
			Lat: 
			<input id="latdeg" style="width:35px;" title="Latitude Degree" />&deg; 
			<input id="latmin" style="width:50px;" title="Latitude Minutes" />' 
			<input id="latsec" style="width:50px;" title="Latitude Seconds" />&quot; 
			<select id="latns">
				<option>N</option>
				<option>S</option>
			</select>
		</div>
		<div>
			Long: 
			<input id="lngdeg" style="width:35px;" title="Longitude Degree" />&deg; 
			<input id="lngmin" style="width:50px;" title="Longitude Minutes" />' 
			<input id="lngsec" style="width:50px;" title="Longitude Seconds" />&quot; 
			<select id="lngew">
				<option>E</option>
				<option SELECTED>W</option>
			</select>
		</div>
		<div style="margin:5px;">
			<input type="button" value="Insert Lat/Long Values" onclick="insertLatLng(this.form)" />
		</div>
	</div>
	<div id="utmAidDiv">
		Zone: <input id="utmzone" style="width:40px;" /><br/>
		East: <input id="utmeast" type="text" style="width:100px;" /><br/>
		North: <input id="utmnorth" type="text" style="width:100px;" /><br/>
		Hemisphere: <select id="hemisphere" title="Use hemisphere designator (e.g. 12N) rather than grid zone ">
			<option value="Northern">North</option>
			<option value="Southern">South</option>
		</select><br/>
		<div style="margin-top:5px;">
			<input type="button" value="Insert UTM Values" onclick="insertUtm(this.form)" />
		</div>
	</div>
	<div id="trsAidDiv">
		T<input id="township" style="width:30px;" title="Township" />
		<select id="townshipNS">
			<option>N</option>
			<option>S</option>
		</select>&nbsp;&nbsp;&nbsp;&nbsp;
		R<input id="range" style="width:30px;" title="Range" />
		<select id="rangeEW">
			<option>E</option>
			<option>W</option>
		</select><br/>
		Sec: 
		<input id="section" style="width:30px;" title="Section" />&nbsp;&nbsp;&nbsp; 
		Details: 
		<input id="secdetails" style="width:90px;" title="Section Details" /><br/>
		<select id="meridian" title="Meridian">
			<option value="">Meridian Selection</option>
			<option value="">----------------------------------</option>
			<option value="G-AZ">Arizona, Gila &amp; Salt River</option>
			<option value="NAAZ">Arizona, Navajo</option>
			<option value="F-AR">Arkansas, Fifth Principal</option> 
			<option value="H-CA">California, Humboldt</option>
			<option value="M-CA">California, Mt. Diablo</option>
			<option value="S-CA">California, San Bernardino</option>
			<option value="NMCO">Colorado, New Mexico</option>
			<option value="SPCO">Colorado, Sixth Principal</option>
			<option value="UTCO">Colorado, Ute</option>
			<option value="B-ID">Idaho, Boise</option>
			<option value="SPKS">Kansas, Sixth Principal</option>
			<option value="F-MO">Missouri, Fifth Principal</option>
			<option value="P-MT">Montana, Principal</option>
			<option value="SPNE">Nebraska, Sixth Principal</option>
			<option value="M-NV">Nevada, Mt. Diablo</option>
			<option value="NMNM">New Mexico, New Mexico</option>
			<option value="F-ND">North Dakota, Fifth Principal</option>
			<option value="C-OK">Oklahoma, Cimarron</option>
			<option value="I-OK">Oklahoma, Indian</option>
			<option value="W-OR">Oregon, Willamette</option>
			<option value="BHSD">South Dakota, Black Hills</option>
			<option value="F-SD">South Dakota, Fifth Principal</option>
			<option value="SPSD">South Dakota, Sixth Principal</option>
			<option value="SLUT">Utah, Salt Lake</option>
			<option value="U-UT">Utah, Uinta</option>
			<option value="W-WA">Washington, Willamette</option>
			<option value="SPWY">Wyoming, Sixth Principal</option>
			<option value="WRWY">Wyoming, Wind River</option>
		</select>
		<div style="margin:5px;">
			<input type="button" value="Insert TRS Values" onclick="insertTRS(this.form)" />
		</div>
	</div>
</div>