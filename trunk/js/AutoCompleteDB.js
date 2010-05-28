var db;
function AutoCompleteDB()
{
	// set the initial values.
	this.bEnd = false;
	this.nCount = 0;
	this.aStr = new Object;

	this.add = function(str){
		// increment the count value.
		this.nCount++;

		// if at the end of the string, flag this node as an end point.
		if ( str == "" ){
			this.bEnd = true;
		}
		else{
			// otherwise, pull the first letter off the string
			var letter = str.substring(0,1);
			var rest = str.substring(1,str.length);
			
			// and either create a child node for it or reuse an old one.
			if ( !this.aStr[letter] ) this.aStr[letter] = new AutoCompleteDB();
			this.aStr[letter].add(rest);
		}
	}

	this.getCount = function(str, bExact){
		// if end of search string, return number
		if ( str == "" )
			if ( this.bEnd && bExact && (this.nCount == 1) ) return 0;
			else return this.nCount;
		
		// otherwise, pull the first letter off the string
		var letter = str.substring(0,1);
		var rest = str.substring(1,str.length);
		
		// and look for case-insensitive matches
		var nCount = 0;
		var lLetter = letter.toLowerCase();
		if ( this.aStr[lLetter] )
			nCount += this.aStr[lLetter].getCount(rest, bExact && (letter == lLetter));
		
		var uLetter = letter.toUpperCase();
		if ( this.aStr[uLetter] )
			nCount += this.aStr[uLetter].getCount(rest, bExact && (letter == uLetter));
		
		return nCount;	
	}

	this.getStrings = function(str1, str2, outStr){
		if ( str1 == "" ){
			// add matching strings to the array
			if ( this.bEnd ){
				outStr.push(str2);
			}
			// get strings for each child node
			for ( var i in this.aStr ){
				this.aStr[i].getStrings(str1, str2 + i, outStr);
			}
		}
		else{
			// pull the first letter off the string
			var letter = str1.substring(0,1);
			var rest = str1.substring(1,str1.length);
			
			// and get the case-insensitive matches.
			var lLetter = letter.toLowerCase();
			if ( this.aStr[lLetter] ){
				this.aStr[lLetter].getStrings(rest, str2 + lLetter, outStr);
			}
			var uLetter = letter.toUpperCase();
			if ( this.aStr[uLetter] )
				this.aStr[uLetter].getStrings(rest, str2 + uLetter, outStr);
		}
	}
}
