import React from "react";

function Searching(props) {
	let lClass = (props.isSearching == true? 'searching':'');
  return (
		<div className={"searching-overlay " + lClass}> 
			<img src={`${props.clientRoot}/images/icons/loading-state.png`} />
		</div>
  )
}
/*
Loading.defaultProps = {
  value: 'off',
};
*/
export default Searching;