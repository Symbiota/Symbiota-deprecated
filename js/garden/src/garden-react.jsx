"use strict";

import InfographicDropdown from "./infographic-dropdown.jsx";
import SideBar from "./sidebar.jsx";
import { SearchResultGrid, SearchResult } from "./search-results.jsx";
import { CannedSearchContainer, CannedSearchResult } from "./canned-searches.jsx";
import httpGet from "./http-get.js";

function getUrlQueryParams(url) {
  let params = {};
  try {
    let queryParams = url.split("?")[1].split("&");
    for (let i = 0; i < queryParams.length; i++) {
      console.log(queryParams[i]);
      let [key, val] = queryParams[i].split("=");
      params[key] = val;
    }
  } catch (e) {
    // console.error(`error parsing query params: ${e}`);
  }

  return params;
}

function getChecklistPage(clid) {
  const gardenPid = 3;
  return `/checklists/checklist.php?cl=${clid}&pid=${gardenPid}`;
}

function getTaxaPage(tid) {
  return `../taxa/garden.php?taxon=${tid}`;
}

function MainContentContainer(props) {
  return (
    <div className="container mx-auto p-4" style={{ maxWidth: "1400px" }}>
      <div className="row">
        {props.children}
      </div>
    </div>
  );
}

class GardenPageApp extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      isLoading: false,
      sunlight: "",
      moisture: "",
      height: [0, 50],
      width: [0, 50],
      searchResults: [],
      cannedSearches: []
    };

    this.onSearch = this.onSearch.bind(this);
    this.onSearchResults = this.onSearchResults.bind(this);
    this.onSunlightChanged =  this.onSunlightChanged.bind(this);
    this.onMoistureChanged =  this.onMoistureChanged.bind(this);
    this.onHeightChanged =  this.onHeightChanged.bind(this);
    this.onWidthChanged =  this.onWidthChanged.bind(this);
  }

  componentDidMount() {
    // Load canned searches
    httpGet("/garden/rpc/api.php?canned=true")
      .then((res) => {
        this.setState({ cannedSearches: JSON.parse(res) });
      });

    // Load initial results
    let queryParams = getUrlQueryParams(window.location.search);
    let search = '';
    if ("search" in queryParams) {
      search = queryParams["search"];
    }
    this.onSearch(search);
  }

  // On search start
  onSearch(searchText) {
    this.setState({ isLoading: true });
    httpGet(`/garden/rpc/api.php?search=${searchText}`)
      .then((res) => {
        this.onSearchResults(JSON.parse(res));
      })
      .catch((err) => {
        console.error(err);
      })
      .finally(() => {
        this.setState({ isLoading: false });
      });
  }

  // On search end
  onSearchResults(results) {
    this.setState({ searchResults: results });
  }

  onSunlightChanged(event) {
    this.setState({ sunlight: event.target.value }, () => {
      console.log(`sunlight: ${this.state.sunlight}`);
    });
  }

  onMoistureChanged(event) {
    this.setState({ moisture: event.target.value }, () => {
      console.log(`moisture: ${this.state.moisture}`);
    });
  }

  onHeightChanged(event) {
    this.setState({ height: event.target.value }, () => {
      console.log(`height: ${this.state.height}`);
    });
  }

  onWidthChanged(event) {
    this.setState({ width: event.target.value }, () => {
      console.log(`width: ${this.state.width}`);
    });
  }

  render() {
    return (
      <div>
        <InfographicDropdown />
        <MainContentContainer>
          <div className="col-auto">
            <SideBar
              style={{ background: "#DFEFD3" }}
              isLoading={ this.state.isLoading }
              sunlight={ this.state.sunlight }
              moisture={ this.state.moisture }
              height={ this.state.height }
              width={ this.state.width }
              onSearch={ this.onSearch }
              onSunlightChanged={ this.onSunlightChanged }
              onMoistureChanged={ this.onMoistureChanged }
              onHeightChanged={ this.onHeightChanged }
              onWidthChanged={ this.onWidthChanged }
            />
          </div>
          <div className="col mx-2">
            <div className="row">
              <CannedSearchContainer>
                {
                  this.state.cannedSearches.map((result, idx) =>
                    <CannedSearchResult
                      style={{ display: (idx < 4 ? "initial" : "none") }}
                      key={ result.clid }
                      title={ result.name }
                      src={ result.iconurl }
                      href={ getChecklistPage(result.clid) }
                    />
                  )
                }
              </CannedSearchContainer>
            </div>
            <div className="row">
              <SearchResultGrid>
                {
                  this.state.searchResults.map((result, idx) =>
                    <SearchResult
                      style={{ display: (idx < 20 ? "initial" : "none") }}
                      key={ result.tid }
                      href={ getTaxaPage(result.tid) }
                      src={ result.image }
                      commonName={ result.vernacularname ? result.vernacularname : '' }
                      sciName={ result.sciname }
                    />
                  )
                }
              </SearchResultGrid>
            </div>
          </div>

        </MainContentContainer>
      </div>
    );
  }
}

const domContainer = document.getElementById("react-app");
ReactDOM.render(<GardenPageApp />, domContainer);
