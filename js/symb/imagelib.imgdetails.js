$(document).ready(function() {
	$( "#targettaxon" ).autocomplete({
		source: "rpc/gettaxasuggest.php",
		minLength: 3,
		//autoFocus: true,
		focus: function( event, ui ) {
			$( "#targettaxon" ).val(ui.item.label);
			return false;
		},
		select: function( event, ui ) {
			$( "#targettaxon" ).val(ui.item.id);
			//$( "#targettid" ).val(ui.item.value);
			return false;
		}
	});
});

function verifyEditForm(f){
    if(f.url.value.replace(/\s/g, "") == "" ){
        window.alert("ERROR: File path must be entered");
        return false;
    }
    return true;
}

function verifyChangeTaxonForm(f){
	var sciName = f.targettaxon.value.replace(/^\s+|\s+$/g, ""); 
    if(sciName == ""){
        window.alert("Enter a taxon name to which the image will be transferred");
    }
	else{
		checkScinameExistance(sciName);
	}
    return false;	//Submit takes place in the checkScinameExistance method
}

function checkScinameExistance(sciname){
	if(sciname.length > 0){
		$.ajax({
			type: "POST",
			url: "rpc/gettid.php",
			data: { term: sciname }
		}).done(function( msg ) {
			if(msg == ""){
				alert("ERROR: Scientific name does not exist in database. Did you spell it correctly? It may have to be added to database.");
			}
			else{
				$( "#targettid" ).val(msg);
				document.changetaxonform.submit();
			}
		});
	}
} 

function openOccurrenceSearch(target) {
	occWindow=open("../collections/misc/occurrencesearch.php?targetid="+target,"occsearch","resizable=1,scrollbars=0,width=750,height=500,left=20,top=20");
	if (occWindow.opener == null) occWindow.opener = self;
}