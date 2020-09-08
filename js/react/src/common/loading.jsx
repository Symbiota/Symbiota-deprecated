import React from "react";

function Loading(props) {
	/*let checked = (props.checked == true? 'checked':'');*/
  return (
		<div className="loading" style={{"--n: 5"}}>
			<div className="dot" style={{"--i: 0"}}></div>
			<div className="dot" style={{"--i: 1"}}></div>
			<div className="dot" style={{"--i: 2"}}></div>
			<div className="dot" style={{"--i: 3"}}></div>
			<div className="dot" style={{"--i: 4"}}></div>
		</div>
  )
}

Loading.defaultProps = {
  value: 'off',
};

export default Loading;