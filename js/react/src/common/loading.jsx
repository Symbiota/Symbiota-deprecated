import React from "react";

function Loading(props) {
	/*let checked = (props.checked == true? 'checked':'');*/
	let lClass = (props.isLoading == true? 'loading':'');
  return (
		<div className={"loading-overlay " + lClass}> 
			<img src={`${props.clientRoot}/images/icons/loading.gif`} />
		</div>
  )
}
/*
Loading.defaultProps = {
  value: 'off',
};
*/
export default Loading;