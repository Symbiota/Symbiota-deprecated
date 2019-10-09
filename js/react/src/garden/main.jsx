"use strict";

import React from "react";
import ReactDOM from "react-dom";

import IconButton from "../common/iconButton.jsx";
import InfographicDropdown from "./infographicDropdown.jsx";
import SideBar from "./sidebar.jsx";
import { SearchResultContainer, SearchResult } from "./searchResults.jsx";
import CannedSearchContainer from "./cannedSearches.jsx";
import httpGet from "./httpGet.js";

const CLIENT_ROOT = "..";

function getUrlQueryParams(url) {
  let params = {};
  if (url.includes("?")) {
    let queryParams = url.split("?")[1].trim("&").split("&");
    for (let i = 0; i < queryParams.length; i++) {
      let [key, val] = queryParams[i].split("=");
      params[key] = val;
    }
  }
  return params;
}

function addUrlQueryParam(key, val) {
  const params = getUrlQueryParams(window.location.search);
  params[key] = val;

  const paramKeys = Object.keys(params);
  let queryParams = [];

  for (let i = 0; i < paramKeys.length; i++) {
    let k = paramKeys[i];
    let v = params[k];
    if (v.toString() !== '') {
      queryParams.push(`${k}=${v}`);
    }
  }

  return queryParams.length > 0 ? `?${queryParams.join("&")}` : "";
}

function getTaxaPage(tid) {
  return `${CLIENT_ROOT}/taxa/garden.php?taxon=${tid}`;
}

function filterByWidth(item, minMax) {
  const withinMin = item.width[0] >= minMax[0];
  if (minMax[0] === 50) {
    return withinMin;
  }
  return withinMin && item.width[1] <= minMax[1];
}

function filterByHeight(item, minMax) {
  const withinMin = item.height[0] >= minMax[0];
  if (minMax[1] === 50) {
    return withinMin;
  }
  return withinMin && item.height[1] <= minMax[1];
}

function filterBySunlight(item, sunlight) {
  switch (sunlight) {
    case "sun":
      return item.sunlight.includes("sun");
    case "partshade":
      return item.sunlight.includes("part shade");
    case "fullshade":
      return item.sunlight.includes("shade");
    default:
      return true;
  }
}

function filterByMoisture(item, moisture) {
  switch (moisture) {
    case ("dry"):
      return item.moisture.includes("dry");
    case ("wet"):
      return item.moisture.includes("wet");
    case ("moderate"):
      return item.moisture.includes("moist");
    default:
      return true;
  }
}

function MainContentContainer(props) {
  return (
    <div className="container mx-auto p-4" style={{ maxWidth: "1400px" }}>
      {props.children}
    </div>
  );
}

function ViewOpts(props) {
  const selectedStyle = {
    background: "#DFEFD3",
    color: "#3B631D"
  };

  const unselectedStyle = {
    color: "#9FD07A"
  };

  return (
    <div id="view-opts" className="row mx-2 mt-3 px-0 py-2">
      <div className="col">
        <h3 className="font-weight-bold">Your search results:</h3>
        <div>{/* TODO: Tag container */}</div>
      </div>
      <div className="col text-right p-0 m-0">
        <p>View as:</p>
        <p>Sort by name:</p>
      </div>
      <div className="col-auto">
        <p>
          <IconButton
            title="Grid"
            icon={ `${CLIENT_ROOT}/images/garden/gridViewIcon.png` }
            onClick={ () => { props.onViewTypeClicked("grid") } }
            isSelected={ props.viewType === "grid" }
          />
          <IconButton
            title="List"
            icon={ `${CLIENT_ROOT}/images/garden/listViewIcon.png` }
            onClick={ () => { props.onViewTypeClicked("list") } }
            isSelected={ props.viewType === "list" }
          />
        </p>
        <p>
          <IconButton
            title="Common Name"
            onClick={ () => { props.onSortByClicked("vernacularname") } }
            isSelected={ props.sortBy === "vernacularname" }
          />
          <IconButton
            title="Scientific Name"
            onClick={ () => { props.onSortByClicked("sciname") } }
            isSelected={ props.sortBy === "sciname" }
          />
        </p>
      </div>
    </div>
  );
}

ViewOpts.defaultProps = {
  sortBy: "vernacularname",
  viewType: "grid",
  onSortByClicked: () => {},
  onViewTypeClicked: () => {}
};

class GardenPageApp extends React.Component {
  constructor(props) {
    super(props);
    const queryParams = getUrlQueryParams(window.location.search);

    this.state = {
      isLoading: false,
      sunlight: ("sunlight" in queryParams ? queryParams["sunlight"] : ""),
      moisture: ("moisture" in queryParams ? queryParams["moisture"] : ""),
      height: ("height" in queryParams ? queryParams["height"].split(",").map((i) => parseInt(i)) : [0, 50]),
      width: ("width" in queryParams ? queryParams["width"].split(",").map((i) => parseInt(i)) : [0, 50]),
      searchText: ("search" in queryParams ? queryParams["search"] : ""),
      searchResults: [],
      cannedSearches: [],
      sortBy: "vernacularname",
      viewType: "grid"
    };

    this.onSearchTextChanged = this.onSearchTextChanged.bind(this);
    this.onSearch = this.onSearch.bind(this);
    this.onSearchResults = this.onSearchResults.bind(this);
    this.onSunlightChanged =  this.onSunlightChanged.bind(this);
    this.onMoistureChanged =  this.onMoistureChanged.bind(this);
    this.onHeightChanged =  this.onHeightChanged.bind(this);
    this.onWidthChanged =  this.onWidthChanged.bind(this);
    this.sortBy = this.sortBy.bind(this);
    this.viewType = this.viewType.bind(this);
  }

  componentDidMount() {
    // Load canned searches
    httpGet(`${CLIENT_ROOT}/garden/rpc/api.php?canned=true`)
      .then((res) => {
        this.setState({ cannedSearches: JSON.parse(res) });
      });

    // Load search results
    this.onSearch();
  }


  onSearchTextChanged(event) {
    this.setState({ searchText: event.target.value });
  }

  // On search start
  onSearch() {
    const newQueryStr = addUrlQueryParam("search", this.state.searchText);
    window.history.replaceState(
      { query: newQueryStr },
      '',
      window.location.pathname + newQueryStr
    );

    this.setState({ isLoading: true });
    httpGet(`${CLIENT_ROOT}/garden/rpc/api.php?search=${this.state.searchText}`)
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
    this.setState({ sunlight: event.target.value });
    let newQueryStr = addUrlQueryParam("sunlight", event.target.value);
    window.history.replaceState({ query: newQueryStr }, '', window.location.pathname + newQueryStr);
  }

  onMoistureChanged(event) {
    this.setState({ moisture: event.target.value });
    let newQueryStr = addUrlQueryParam("moisture", event.target.value);
    window.history.replaceState({ query: newQueryStr }, '', window.location.pathname + newQueryStr);
  }

  onHeightChanged(event) {
    this.setState({ height: event.target.value });
    let newQueryStr = '';

    if (event.target.value[0] === 0 && event.target.value[1] === 50) {
      newQueryStr = addUrlQueryParam("height", '');
    } else {
      newQueryStr = addUrlQueryParam("height", event.target.value);
    }

    window.history.replaceState(
      {query: newQueryStr},
      '',
      window.location.pathname + newQueryStr
    );
  }

  onWidthChanged(event) {
    this.setState({ width: event.target.value });
    let newQueryStr = '';

    if (event.target.value[0] === 0 && event.target.value[1] === 50) {
      newQueryStr = addUrlQueryParam("width", '');
    } else {
      newQueryStr = addUrlQueryParam("width", event.target.value);
    }

    window.history.replaceState(
      {query: newQueryStr},
      '',
      window.location.pathname + newQueryStr
    );
  }

  sortBy(type) {
    this.setState({
      sortBy: type,
      searchResults: this.state.searchResults.sort((a, b) => { return a[type] > b[type] ? 1 : -1 })
    });
  }

  viewType(type) {
    this.setState({ viewType: type });
  }

  render() {
    return (
      <div>
        <InfographicDropdown />
        <MainContentContainer>
          <div className="row">
            <div className="col-auto">
              <SideBar
                style={{ background: "#DFEFD3" }}
                isLoading={ this.state.isLoading }
                sunlight={ this.state.sunlight }
                moisture={ this.state.moisture }
                height={ this.state.height }
                width={ this.state.width }
                searchText={ this.state.searchText }
                onSearch={ this.onSearch }
                onSearchTextChanged={ this.onSearchTextChanged }
                onSunlightChanged={ this.onSunlightChanged }
                onMoistureChanged={ this.onMoistureChanged }
                onHeightChanged={ this.onHeightChanged }
                onWidthChanged={ this.onWidthChanged }
              />
            </div>
            <div className="col">
              <div className="row">
                <div className="col">
                  <CannedSearchContainer searches={ this.state.cannedSearches }/>
                </div>
              </div>
              <div className="row">
                <div className="col">
                  <ViewOpts
                    viewType={ this.state.viewType }
                    sortBy={ this.state.sortBy }
                    onSortByClicked={ this.sortBy }
                    onViewTypeClicked={ this.viewType }
                  />
                  <SearchResultContainer viewType={ this.state.viewType }>
                    {
                      this.state.searchResults.filter((item) => { return filterByHeight(item, this.state.height) }).map((result) => {
                        let filterWidth = filterByWidth(result, this.state.width);
                        let filterHeight = filterByWidth(result, this.state.height);
                        let filterSunlight = filterBySunlight(result, this.state.sunlight);
                        let filterMoisture = filterByMoisture(result, this.state.moisture);
                        let display = filterWidth && filterHeight && filterSunlight && filterMoisture;
                        return (
                          <SearchResult
                            viewType={ this.state.viewType }
                            style={{display: display ? "initial" : "none" }}
                            key={result.tid}
                            href={getTaxaPage(result.tid)}
                            src={result.image}
                            commonName={result.vernacularname ? result.vernacularname : ''}
                            sciName={result.sciname}
                          />
                        )
                      })
                    }
                  </SearchResultContainer>
                </div>
              </div>
            </div>
          </div>
        </MainContentContainer>
      </div>
    );
  }
}

const domContainer = document.getElementById("react-garden");
ReactDOM.render(<GardenPageApp />, domContainer);
