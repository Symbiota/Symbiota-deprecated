import React from "react";

function Searching(props) {
	let lClass = (props.isSearching == true? 'searching':'');
	console.log(props.isSearching);
  return (
		<div className={"searching-overlay " + lClass}> 
			<img src={`${props.clientRoot}/images/icons/loading-sun.gif`} />
		</div>
  )
}
/*
Loading.defaultProps = {
  value: 'off',
};
*/
export default Searching;