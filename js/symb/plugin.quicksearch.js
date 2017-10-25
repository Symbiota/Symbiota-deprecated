$(document).ready(function() {
	function split( val ) {	return val.split( /,\s*/ ); }
	function extractLast( term ) { return split( term ).pop(); }
	$( "#quicksearchtaxon" )
		.bind( "keydown", function( event ) {
			if ( event.keyCode === $.ui.keyCode.TAB && $( this ).data( "autocomplete" ).menu.active ) { event.preventDefault(); }
		})
		.autocomplete({
			source: function( request, response ) { 
				$.getJSON( "collections/rpc/taxalist.php", { 
					term: extractLast( request.term )
				}, response ); 
			},
			appendTo: "#quicksearchdiv",
			search: function() { 
				var term = extractLast( this.value ); 
				if ( term.length < 4 ) { return false; }
			},
			focus: function() { return false; },
			select: function( event, ui ) { 
				var terms = split( this.value );
				terms.pop();
				terms.push( ui.item.value );
				this.value = terms.join( ", " );
				return false;
			}
		},{});
});

function verifyQuickSearch(f){
	if(f.quicksearchtaxon.value == ""){
		alert("Scientific name?");
		return false;
	}
	return true;
}