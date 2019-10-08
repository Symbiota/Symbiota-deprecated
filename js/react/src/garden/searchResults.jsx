import React from "react";

function SearchResult(props) {
  let resStyle = Object.assign(
    { width: "100%", height: "100%", padding: "0.5em" },
    props.style
  );
  return (
    <div className="card" style={ resStyle }>
      <a href={ props.href }>
        <img
          className="card-img-top"
          style={{ height: "7em", width: "100%", objectFit: "cover", borderRadius: "0.25em" }}
          alt={ props.title }
          src={ props.src }
        />
        <div className="card-body px-0" style={{ overflow: "hidden" }}>
          <div className="card-text">{ props.commonName }</div>
          <div className="card-text">{ props.sciName }</div>
        </div>
      </a>
    </div>
  );
}

class SearchResultGrid extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    return (
      <div
        id="search-results"
        className="mt-4 w-100"
        style={{
          display: "grid",
          gridTemplateColumns: "repeat(5, 1fr)",
          gridAutoRows: "15em",
          gridGap: "0.5em",
          justifyContent: "center"
        }}
      >
        { this.props.children }
      </div>
    );
  }
}

export { SearchResultGrid, SearchResult };