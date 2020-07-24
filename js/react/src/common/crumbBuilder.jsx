import React from "react";

function CrumbBuilder(props) {
  return (
    <span>
      {
        props.crumbs.map((crumb, idx) => {
          return (
            <span key={ crumb.title }>
              <a href={ crumb.url }>{ crumb.title }</a>
              { idx !== props.crumbs.length - 1 ? <span className="d-inline-block mx-1">></span> : '' }
            </span>
          )
        })
      }
    </span>
  );
}

export default CrumbBuilder;