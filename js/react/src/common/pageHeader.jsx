import React from "react";
//import CrumbBuilder from "../common/crumbBuilder.jsx";

function PageHeader(props) {
  return (
		<section id="titlebackground" className={ props.bgClass }>
			<div className="container">
				{/*<div className="crumbs">
					{props.crumbs &&
						<CrumbBuilder crumbs={ props.crumbs }/>
					}
				</div>
				*/}
				<div>
					<h1>{ props.title }</h1>
				</div>
			</div>
		</section>
  );
}

export default PageHeader;