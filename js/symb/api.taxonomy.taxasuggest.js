$(document).ready(function() {
	var acUrlBase = "/api/taxonomy/taxasuggest.php";
	var acUrl = acUrlBase;
	var dirArr = window.location.pathname.split('/');
	dirArr.shift(); dirArr.pop();
	var loopCnt = 0;
	while(!urlExists(acUrl) && dirArr.length > loopCnt){
		var newUrl = '';
		for(i = loopCnt; i >= 0; i--){
			newUrl = "/" + dirArr[i] + newUrl;
		}
		acUrl = newUrl + acUrlBase;
		loopCnt = loopCnt + 1;
	}
	
	function split( val ) {
		return val.split( /,\s*/ );
	}
	function extractLast( term ) {
		return split( term ).pop();
	}

	$( "#taxa" )
		// don't navigate away from the field on tab when selecting an item
		.bind( "keydown", function( event ) {
			// don't honor ENTER key if an autocomplete is not selected yet
			/*
			if (event.keyCode === $.ui.keyCode.ENTER) {
				if (this.autocomplete_stage != 0) {
				    event.preventDefault();
				}
		    } else
		    */
			// don't navigate away from the field on tab when selecting an item
			if (event.keyCode === $.ui.keyCode.TAB) {
				if ($(this).autocomplete('widget').is(':visible')) {
					$(this).trigger("select");
					event.preventDefault();
				}
			} else {
				this.autocomplete_stage = 1;
			}
		})
		.autocomplete({
			source: $.proxy(function( request, response ) {
				$.getJSON( acUrl, {
					term: extractLast( request.term ), t: function() { return $("#taxontype").val(); }
				}, response );
				this.autocomplete_stage = 0;
			},$('#taxa')[0]),
			autoFocus: true,
			search: function() {
				// custom minLength
				this.autocomplete_stage = 2;
				var term = extractLast( this.value );
				if ( term.length < 4 ) {
					return false;
				}
				this.autocomplete_stage = 3;
				return true;
			},
			focus: function() {
				// prevent value inserted on focus
				return false;
			},
			select: function( event, ui ) {
				var terms = split( this.value );
				// remove the current input
				terms.pop();
				// add the selected item
				terms.push( ui.item.value );
				this.value = terms.join( ", " );
				return false;
			}
		},{});
});

function urlExists(url){
    var http = new XMLHttpRequest();
    http.open('HEAD', url, false);
    http.send();
    return http.status!=404;
}

function verifyQuickSearch(f){
	if(f.quicksearchtaxon.value == ""){
		alert("Scientific name?");
		return false;
	}
	return true;
}