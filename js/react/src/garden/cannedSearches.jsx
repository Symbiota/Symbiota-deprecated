const CLIENT_ROOT = "..";

function getChecklistPage(clid) {
  const gardenPid = 3;
  return `${CLIENT_ROOT}/checklists/checklist.php?cl=${clid}&pid=${gardenPid}`;
}

function CannedSearchResult(props) {
  return (
    <div
        className={ "mx-1 py-2 col canned-search-result" + (props.isSlidingLeft ? " slideLeft" : "") + (props.isSlidingRight ? " slideRight" : "") }
        style={ Object.assign({ background: "#EFFFE3", color: "#3B631D", textAlign: "center", borderRadius: "2%" }, props.style) }>
      <h4 className="canned-title">{ props.title }</h4>
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
      <div className="mt-2 px-2">
        <button className="w-100 px-3 my-1 btn-filter" onClick={ props.onFilter }>
          Filter for these
        </button>
        <button className="w-100 px-3 my-1 btn-learn" onClick={ props.onLearnMore }>
          Learn more
        </button>
      </div>
    </div>
  );
}

class CannedSearchContainer extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      offset: 0,
      isSlidingLeft: false,
      isSlidingRight: false
    };

    this.scrollLeft = this.scrollLeft.bind(this);
    this.scrollRight = this.scrollRight.bind(this);
  }

  scrollLeft() {
    this.setState({ isSlidingLeft: true }, () => {
      let newOffset = this.state.offset === 0 ? this.props.searches.length - 1 : this.state.offset - 1;
      this.setState({ offset: newOffset }, () => {
        window.setTimeout(() => this.setState({ isSlidingLeft: false }), 200);
      });
    });
  }

  scrollRight() {
    this.setState({ isSlidingRight: true }, () => {
      let newOffset = (this.state.offset + 1) % this.props.searches.length ;
      this.setState({ offset: newOffset }, () => {
        window.setTimeout(() => this.setState({ isSlidingRight: false }), 200);
      });
    });
  }

  render() {
    return (
      <div id="canned-searches" className="w-100 mt-1 p-3 rounded-border" style={{ background: "#DFEFD3" }}>
          <h1 style={{color: "black", fontWeight: "bold", fontSize: "1.75em"}}>
            Or start with these plant combinations:
          </h1>

        <div className="w-100 row mt-3 mx-auto p-0">
          <div className="d-flex align-items-center p-0 m-0 col-auto">
              <button className="mr-1 ml-0 p-0 scroll-btn" onClick={ this.scrollLeft }>
                <img
                  style={{transform: "rotate(-90deg)", width: "3em", height: "3em" }}
                  src={ `${CLIENT_ROOT}/images/garden/collapse-arrow.png` }
                  alt="scroll left"/>
              </button>
          </div>

          <div className="px-2 m-0 col">
            <div
              className="row"
              style={{ overflow: "hidden" }}
            >
              {
                [0, 1, 2, 3].map((i) => {
                  if (this.props.searches.length > 0) {
                    let searchResult = this.props.searches[(i + this.state.offset) % this.props.searches.length];
                    return (
                        <CannedSearchResult
                          key={searchResult.clid}
                          title={searchResult.name}
                          src={searchResult.iconurl}
                          href={getChecklistPage(searchResult.clid)}
                          isSlidingLeft={ this.state.isSlidingLeft }
                          isSlidingRight={ this.state.isSlidingRight }
                          onLearnMore={() => {
                            console.log(`Learn more about ${searchResult.name}!`)
                          }}
                          onFilter={() => {
                            console.log(`Filter for ${searchResult.name}!`)
                          }}
                        />
                    );
                  }
                })
              }
            </div>
          </div>

          <div className="d-flex align-items-center p-0 m-0 col-auto">
            <button className="mr-0 ml-1 p-0 scroll-btn" onClick={ this.scrollRight }>
              <img
                style={{ transform: "rotate(90deg)", width: "3em", height: "3em" }}
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