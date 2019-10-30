import React from "react";

const gridImageStyle = {
  height: "7em",
  width: "100%",
  objectFit: "cover",
  borderRadius: "0.25em"
};

const listImageStyle = {
  height: "2em",
  width: "2em",
  objectFit: "cover",
  borderRadius: "0.25em"
};

const containerGridStyle = {
  display: "grid",
  gridTemplateColumns: "repeat(5, 1fr)",
  gridAutoRows: "15em",
  gridGap: "0.5em",
  justifyContent: "center"
};

const gridResultStyle = {
  width: "100%",
  height: "100%",
  padding: "0.5em"
};

const listResultStyle = {
  width: "100%",
  marginTop: "0.1em",
  marginBottom: "0.1em"
};

function SearchResult(props) {
  const useGrid = props.viewType === "grid";
  let style = useGrid ? gridResultStyle : listResultStyle;

  if (props.display) {
    return (
      <div className="card" style={style}>
        <a href={props.href}>
          <div className={useGrid ? "" : "card-body"}>
            <img
              className={useGrid ? "card-img-top" : "d-inline-block mr-1"}
              style={useGrid ? gridImageStyle : listImageStyle}
              alt={props.title}
              src={props.src}
            />
            <div className={(useGrid ? "card-body" : "d-inline py-1") + " px-0"} style={{overflow: "hidden"}}>
              <div className={"card-text" + (useGrid ? "" : " d-inline")}>
                <span className="text-capitalize">{props.commonName}</span>
                {useGrid ? <br/> : " - "}
                <span className="font-italic">{props.sciName}</span>
              </div>
            </div>
          </div>
        </a>
      </div>
    );
  }

  return <span style={{ display: "none" }}/>;
}

class SearchResultContainer extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    return (
      <div
        id="search-results"
        className="mt-4 w-100"
        style={ this.props.viewType === "grid" ? containerGridStyle : {} }
      >
        { this.props.children }
      </div>
    );
  }
}

export { SearchResultContainer, SearchResult };