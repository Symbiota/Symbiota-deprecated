<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/MapInterfaceManager.php');
header("Content-Type: text/html; charset=".$charset);
header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
header('Pragma: no-cache'); // HTTP 1.0.
header('Expires: 0'); // Proxies.

$selections = array_key_exists('selections',$_REQUEST)?$_REQUEST['selections']:0;

$mapManager = new MapInterfaceManager();

$gpxText = $mapManager->getGpxText($selections);
$fileName = time();
?>

<html>
	<head>
		<title><?php echo $defaultTitle; ?> - Garmin Downloader</title>
		<link type="text/css" href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" rel="stylesheet" />
		<link type="text/css" href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" rel="stylesheet" />
		<link type="text/css" href="../../css/jquery.mobile-1.4.0.min.css" rel="stylesheet" />
		<link type="text/css" href="../../css/jquery.symbiota.css" rel="stylesheet" />
		<link type="text/css" href="../../css/jquery-ui_accordian.css" rel="stylesheet" />
		<link type="text/css" href="../../css/jquery-ui.css" rel="Stylesheet" />
		<link type="text/css" href="../../css/communicator.css" rel="Stylesheet" />
		<script type="text/javascript" src="../../js/jquery.js"></script>
		<script type="text/javascript" src="../../js/jquery-ui.js"></script>
		<script type="text/javascript" src="../../js/garmin/prototype/prototype.js"></script>
		<script type="text/javascript" src="../../js/garmin/garmin/device/GarminDeviceDisplay.js"></script>
		<script src="//maps.googleapis.com/maps/api/js?v=3.exp&libraries=drawing<?php echo (isset($GOOGLE_MAP_KEY) && $GOOGLE_MAP_KEY?'&key='.$GOOGLE_MAP_KEY:''); ?>"></script>
		<script type="text/javascript">
			var GarminDeviceControlDemo = Class.create();
			GarminDeviceControlDemo.prototype = {

				////////////////////////////////////////////////////////////////////////
				//prototype constructor method:
				
				initialize: function(statusDiv, mapId) {        
					this.status = $(statusDiv);
					this.findDevicesButton = $("findDevicesButton");
					this.cancelFindDevicesButton = $("cancelFindDevicesButton");
					this.deviceSelect = $("deviceSelect");
					this.deviceInfo = $("deviceInfoText");
					this.writeDataButton = $("writeDataButton");
					this.cancelWriteDataButton = $("cancelWriteDataButton");
					this.writeDataText = $("writeDataText");
					this.writeDataFilename = $("writeDataFilename");
					this.progressBar = $("progressBar");
					this.progressBarDisplay = $("progressBarDisplay");
					this.garminController = null;
					this.tracks = null;
					this._intializeController();
					//this._setStatus("Plug-in initialized.  Find some devices to get started.");
					this.findDevicesButton.disabled = false;
					this.findDevicesButton.onclick = function() {
						this.findDevicesButton.disabled = true;
						this.cancelFindDevicesButton.disabled = false;
						this.garminController.findDevices();
					}.bind(this)
				},
				
				////////////////////////////////////////////////////////////////////////
				//Garmin.DeviceControl call-back methods:

				onFinishFindDevices: function(json) {
					this.findDevicesButton.disabled = false;
					this.cancelFindDevicesButton.disabled = true;

					if(json.controller.numDevices > 0) {
						var devices = json.controller.getDevices();
						this._setStatus("Found " + devices.length + " devices.");
						this._listDevices(devices);
						
						this.cancelWriteDataButton.onclick = function() {
							this.writeDataButton.disabled = false;
							this.cancelWriteDataButton.disabled = true;
							this._hideProgressBar();
							this.garminController.cancelWriteToDevice();
						}.bind(this)
			
						this.writeDataButton.disabled = false;            
						this.writeDataButton.onclick = function() {
							this.writeDataButton.disabled = true;
							this.cancelWriteDataButton.disabled = false;
							this._showProgressBar();
							this.garminController.writeToDevice(this.writeDataText.value, this.writeDataFilename.value);
						}.bind(this);
					} else {
						this._setStatus("No devices found.");
					}
				},

				onStartFindDevices: function(json) {
					this._setStatus("Looking for connected Garmin devices");
				},

				onCancelFindDevices: function(json) {
					this._setStatus("Find cancelled");
				},

				//The device already has a file with this name on it.  Do we want to override?  1 is yes, 2 is no 
				onWaitingWriteToDevice: function(json) {    
					if(confirm(json.message.getText())) {
						this._setStatus('Overwriting file');
						json.controller.respondToMessageBox(true);
					} else {
						this._setStatus('Will not be overwriting file');
						json.controller.respondToMessageBox(false);
					}
				},
			
				onProgressWriteToDevice: function(json) {
					this._updateProgressBar(json.progress.getPercentage());
					this._setStatus(json.progress);
				},
			
				onFinishWriteToDevice: function(json) {
					this._hideProgressBar();
					this._setStatus("Data written to the device.");
					this._hideProgressBar();
					this.writeDataButton.disabled = false;
					this.cancelWriteDataButton.disabled = true;
				},

			
				////////////////////////////////////////////////////////////////////////
				//internal utility methods:

				_intializeController: function() {
					try {
						this.garminController = new Garmin.DeviceControl();
						this.garminController.unlock( ["http://swbiodiversity.org","e2d4e034ebdad5cf81a705e68714c122"] );
						this.garminController.register(this);
					} catch (e) {
						setRealStatus(e);
						if(e == "OutOfDatePluginException") {
							//alert("Plug-in out of date");
						} else if(e == "PluginNotInstalledException: Garmin Communicator Plugin NOT detected.") { 
							//alert("Plug-in not installed");
						} else if(e == "BrowserNotSupportedException") { 
							//alert("Browser not supported");
						} else {
							//alert("Error initializing - " + e);
						}
					}
				},

				_showProgressBar: function() {
					Element.show(this.progressBar);
				},

				_hideProgressBar: function() {
					Element.hide(this.progressBar);
				},

				_updateProgressBar: function(value) {
					var percent = (value <= 100) ? value : 100;
					this.progressBarDisplay.style.width = percent + "%";
				},

				_listDevices: function(devices) {
					for( var i=0; i < devices.length; i++ ) {
						this.deviceSelect.options[i] = new Option(devices[i].getDisplayName(),devices[i].getNumber());
						if(devices[i].getNumber() == this.garminController.deviceNumber) {
							this.deviceSelect.selectedIndex = i;
							this._showDeviceInfo(devices[i]);
						}
					}
					this.deviceSelect.onchange = function() {
						var device = this.garminController.getDevices()[this.deviceSelect.value];
						this._showDeviceInfo(device);
						this.garminController.setDeviceNumber(this.deviceSelect.value);
					}.bind(this)
					this.deviceSelect.disabled = false;
				},

				_showDeviceInfo: function(device) {
					this.deviceInfo.innerHTML = "Part Number:\t\t" + device.getPartNumber() + "\n";
					this.deviceInfo.innerHTML += "Software Version:\t" + device.getSoftwareVersion() + "\n";
					this.deviceInfo.innerHTML += "Description:\t\t" + device.getDescription() + "\n";
					this.deviceInfo.innerHTML += "Id:\t\t\t" + device.getId();
				},
			
				_setStatus: function(statusText) {
					this.status.innerHTML = statusText;
				}
			};

			//control is created when HTML page is loaded
			var control;
			
			function setRealStatus(e){
				var statusDiv = document.getElementById("statusText");
				if(e){
					if(e == "PluginNotInstalledException: Garmin Communicator Plugin NOT detected.") { 
						statusDiv.innerHTML = '<span style="color:red;">Plug-in not installed.</span> Please download and install the free <a href="http://software.garmin.com/en-US/gcp.html" target="_blank" >Garmin Communicator Plugin</a> to get started';
					}
					else{
						statusDiv.innerHTML = e;
					}
				}
			}
				
			function load() {
				control = new GarminDeviceControlDemo("statusText", "readMap");
			}
		</script>
	</head> 
	<body style="width:400px;height:330px;margin-left:0px;margin-right:0px;background-color:white;overflow-y:hidden;overflow-x:hidden;" onload="load()">
		<div id="innertext">
			<fieldset style="padding:10px;height:280px;width:350px;">
				<legend><b>Garmin GPS Downloader</b></legend>
				<div id="actionStatus">
					<div id="statusText"><b>Plug-in initialized.  Find some devices to get started.</b></div>
					<div id="progressBar" style="display: none;" align="left">
						<div id="progressWrapper"><div id="progressBarDisplay"></div></div>
					</div><br />
				</div>
				<div id="deviceBox">
					<input type="button" value="Find Devices" id="findDevicesButton" disabled="true" />
					<input type="button" value="Cancel Find Devices" id="cancelFindDevicesButton" disabled="true" />

					<br />
					<select name="deviceSelect" id="deviceSelect" disabled="true">
						<option value="-1">No Devices Found</option>
					</select>
					<br />
					<textarea id="deviceInfoText" rows="4" cols="60" style="resize:none;"></textarea>
				</div><br />
				<div id="writeBox">
					<input type="button" value="Write Occurrences To Device" id="writeDataButton" disabled="true" />
					<input type="button" value="Cancel Write To Device" id="cancelWriteDataButton" disabled="true" />
					<br />
					<b>File to be saved on device:</b> <input type="text" id="writeDataFilename" value="<?php echo $fileName; ?>.gpx"><br />
					<textarea id="writeDataText" name="writeDataText" style="display:none;" rows="21" cols="75"><?php echo $gpxText; ?></textarea>
				</div>
				<div style="margin-top:20px;height:20px;width:100%">
					<div style="float:right;">
						<img src="../../images/icons/garmin.png" title="From the good people at Garmin." />
					</div>
				</div>
			</fieldset>
		</div>
	</body>
</html>
