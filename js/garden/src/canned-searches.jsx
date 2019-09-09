const CLIENT_ROOT = "..";

function CannedSearchResult(props) {
  return (
    <div className="mx-2" style={ props.style }>
      <h5 className="canned-title">{ props.title }</h5>
      <div className="card" style={{ padding: "0.5em" }} >
        <a href={ props.href }>
          <div className="card-body" style={{ padding: "0" }}>
            <img
              className="d-block"
              style={{ width: "100%", height: "7em", borderRadius: "0.25em", objectFit: "cover" }}
              src={ props.src }
              alt={ props.src }
            />
          </div>
        </a>
      </div>
    </div>
  );
}

class CannedSearchContainer extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    return (
      <div id="canned-searches" className="w-100 mt-1">
        <h1 style={{color: "black", fontWeight: "bold", fontSize: "1.75em"}}>
          Kickstart your search with one of our native plant collections:
        </h1>
        <div className="w-100 rounded-border p-3 row" style={{background: "#DFEFD3"}}>
          <div className="col-auto p-0 m-0">
            <button>
              <img
                style={{transform: "rotate(-90deg)", width: "2em", height: "2em"}}
                src={ `${CLIENT_ROOT}/images/garden/collapse-arrow.png` }
                alt="scroll left"/>
            </button>
          </div>

          <div className="col p-0 m-1">
            <div
              style={{
                display: "grid",
                gridTemplateColumns: "repeat(4, 1fr)",
              }}
            >
              { this.props.children }
            </div>
          </div>

          <div className="col-auto p-0 m-0">
            <button>
              <img
                style={{transform: "rotate(90deg)", width: "2em", height: "2em"}}
                src={ `${CLIENT_ROOT}/images/garden/collapse-arrow.png` }
                alt="scroll right"/>
            </button>
          </div>
        </div>
      </div>
    );
  }
}

export { CannedSearchContainer, CannedSearchResult };