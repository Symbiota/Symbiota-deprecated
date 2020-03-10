import React from "react";
import Slider from "../common/slider.jsx";

import HelpButton from "../common/helpButton.jsx";

const CLIENT_ROOT = "..";

function getChecklistPage(clid) {
  const gardenPid = 3;
  return `${CLIENT_ROOT}/checklists/checklist.php?cl=${clid}&pid=${gardenPid}`;
}

const helpHtml = `

`;

class CannedSearchResult extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      hover: false
    };

    this.onMouseOver = this.onMouseOver.bind(this);
    this.onMouseOut = this.onMouseOut.bind(this);
  }

  onMouseOver() {
    this.setState({ hover: true });
  }

  onMouseOut() {
    this.setState({ hover: false });
  }

  render() {
    return (
      <div className={"py-2 canned-search-result"}>
        <h4 className="canned-title">{this.props.title}</h4>
        <div className="card" style={{padding: "0.5em"}}>
          <div className="card-body" style={{padding: "0"}}>
            <div style={{ position: "relative", width: "100%", height: "7em", borderRadius: "0.25em"}}>
              <img
                className="d-block"
                style={{width: "100%", height: "100%", objectFit: "cover"}}
                src={this.props.src}
                alt={this.props.src}
                onMouseOver={ this.onMouseOver }
              />
              <div
                className="text-center text-sentence w-100 h-100 px-2 py-1 align-items-center"
                style={{
                  display: this.state.hover ? "flex" : "none",
                  position: "absolute",
                  top: 0,
                  left: 0,
                  zIndex: 1000,
                  fontSize: "0.75em",
                  color: "white",
                  background: "rgba(100, 100, 100, 0.8)",
                  overflow: "hidden"
                }}
                onMouseOut={ this.onMouseOut }
              >
                {this.props.description}
              </div>
            </div>
          </div>
        </div>
        <div className="mt-2 px-2">
          <button className="w-100 px-3 my-1 btn btn-primary" onClick={this.props.onFilter}>
            Filter for these
          </button>
          <button className="w-100 px-3 my-1 btn btn-secondary" onClick={ () => { window.open(this.props.href) } }>
            Learn more
          </button>
        </div>
      </div>
    );
  }
}

class CannedSearchContainer extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    return (
      <div id="canned-searches" className="row mt-1 p-3 mx-0 rounded-border" style={{ background: "#DFEFD3" }}>
        <div className="col">
          <div className="row">
            <h1 className="col" style={{ fontWeight: "bold", fontSize: "1.75em"}}>
              Or start with these plant combinations:
            </h1>
            {/* TODO: Re-enable once we have help verbiage */}
            <div className="col-auto d-none">
              <HelpButton title="Garden collections" html={ helpHtml } />
            </div>
          </div>

          <div className="row">
            <div className="col">
              <div>
                <Slider>
                  {
                    this.props.searches.map((searchResult) => {
                      return (
                        <div key={searchResult.clid} className="p-1">
                          <CannedSearchResult
                            title={searchResult.name}
                            description={ searchResult.description }
                            src={ `${searchResult.iconUrl}` }
                            href={getChecklistPage(searchResult.clid)}
                            onFilter={() => { this.props.onFilter(searchResult.clid); }}
                          />
                        </div>
                      );
                    })
                  }
                </Slider>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}

CannedSearchContainer.defaultProps = {
  onFilter: () => {},
};

export default CannedSearchContainer;