
function toggleImageTd(){
	toggle("imgprocondiv");
	toggle("imgprocoffdiv");
	if(document.getElementById("imgtd").style.display == "none"){
		document.getElementById("imgtd").style.display = "block";
		initImageTool(document.getElementById("activeimage"));
	}
	else{
		document.getElementById("imgtd").style.display = "none";
	}
}

function initImageTool(img){
	if(!img.complete){
		imgWait=setTimeout('initImageTool(document.getElementById("activeimage"))', 500);
	}
	else{
		$(function() {
			$("#labelimagediv img").imagetool({
				maxWidth: 6000
				,viewportWidth: 400
		        ,viewportHeight: 400
		        ,imageWidth: 3500
		        ,imageHeight: 5200
			});
		});
	}
}

function ocrImage(){
	activeimage
}