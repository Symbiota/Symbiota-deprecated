"use strict";

import React from "react";
import ReactDOM from "react-dom";

import InfographicDropdown from "./infographicDropdown.jsx";
import SideBar from "./sidebar.jsx";
import { SearchResultContainer, SearchResult } from "./searchResults.jsx";
import CannedSearchContainer from "./cannedSearches.jsx";
import ViewOpts from "./viewOpts.jsx";
import httpGet from "../common/httpGet.js";

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
  if (minMax[1] === 50) {
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

class GardenPageApp extends React.Component {
  constructor(props) {
    super(props);
    const queryParams = getUrlQueryParams(window.location.search);

    this.state = {
      isLoading: false,
      filters: {
        sunlight: ("sunlight" in queryParams ? queryParams["sunlight"] : ViewOpts.DEFAULT_SUNLIGHT),
        moisture: ("moisture" in queryParams ? queryParams["moisture"] : ViewOpts.DEFAULT_MOISTURE),
        height: ("height" in queryParams ? queryParams["height"].split(",").map((i) => parseInt(i)) : ViewOpts.DEFAULT_HEIGHT),
        width: ("width" in queryParams ? queryParams["width"].split(",").map((i) => parseInt(i)) : ViewOpts.DEFAULT_WIDTH),
        searchText: ''
      },
      searchText: ("search" in queryParams ? queryParams["search"] : ViewOpts.DEFAULT_SEARCH_TEXT),
      searchResults: [],
      cannedSearches: [],
      sortBy: ("sortBy" in queryParams ? queryParams["sortBy"] : "vernacularName"),
      viewType: ("viewType" in queryParams ? queryParams["viewType"] : "grid"),
    };

    // To Refresh sliders
    this.sideBarRef = React.createRef();

    this.onSearchTextChanged = this.onSearchTextChanged.bind(this);
    this.onSearch = this.onSearch.bind(this);
    this.onSearchResults = this.onSearchResults.bind(this);
    this.onSunlightChanged =  this.onSunlightChanged.bind(this);
    this.onMoistureChanged =  this.onMoistureChanged.bind(this);
    this.onHeightChanged =  this.onHeightChanged.bind(this);
    this.onWidthChanged =  this.onWidthChanged.bind(this);
    this.onSortByChanged = this.onSortByChanged.bind(this);
    this.onViewTypeChanged = this.onViewTypeChanged.bind(this);
    this.onFilterRemoved = this.onFilterRemoved.bind(this);
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

  onFilterRemoved(key) {
    // TODO: This is clunky
    switch (key) {
      case "sunlight":
        this.onSunlightChanged({ target: { value: ViewOpts.DEFAULT_SUNLIGHT } });
        break;
      case "moisture":
        this.onMoistureChanged({ target: { value: ViewOpts.DEFAULT_MOISTURE } });
        break;
      case "width":
        this.onWidthChanged({ target: { value: ViewOpts.DEFAULT_WIDTH } });
        this.sideBarRef.current.resetWidth();
        break;
      case "height":
        this.onHeightChanged({ target: { value: ViewOpts.DEFAULT_HEIGHT } });
        this.sideBarRef.current.resetHeight();
        break;
      case "searchText":
        this.setState({ searchText: ViewOpts.DEFAULT_SEARCH_TEXT }, () => this.onSearch());
        break;
      default:
        break;
    }
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

    this.setState({ isLoading: true, filters: Object.assign({}, this.state.filters, { searchText: this.state.searchText }) });
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
    this.setState({
      searchResults: results.sort((a, b) => { return a[this.state.sortBy] > b[this.state.sortBy] ? 1 : -1 })
    });
  }

  onSunlightChanged(event) {
    this.setState({ filters: Object.assign({}, this.state.filters, { sunlight: event.target.value }) });
    let newQueryStr = addUrlQueryParam("sunlight", event.target.value);
    window.history.replaceState({ query: newQueryStr }, '', window.location.pathname + newQueryStr);
  }

  onMoistureChanged(event) {
    this.setState({ filters: Object.assign({}, this.state.filters, { moisture: event.target.value }) });
    let newQueryStr = addUrlQueryParam("moisture", event.target.value);
    window.history.replaceState({ query: newQueryStr }, '', window.location.pathname + newQueryStr);
  }

  onHeightChanged(event) {
    this.setState({ filters: Object.assign({}, this.state.filters, { height: event.target.value }) });
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
    this.setState({ filters: Object.assign({}, this.state.filters, { width: event.target.value }) });
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

  onSortByChanged(type) {
    this.setState({
      sortBy: type,
      searchResults: this.state.searchResults.sort((a, b) => { return a[type] > b[type] ? 1 : -1 })
    });

    let newType;
    if (type === "sciName") {
      newType = type;
    } else {
      newType = '';
    }
    let newQueryStr = addUrlQueryParam("sortBy", newType);
    window.history.replaceState({query: newQueryStr}, '', window.location.pathname + newQueryStr);
  }

  onViewTypeChanged(type) {
    this.setState({ viewType: type });

    let newType;
    if (type === "list") {
      newType = type;
    } else {
      newType = '';
    }
    let newQueryStr = addUrlQueryParam("viewType", newType);
    window.history.replaceState({ query: newQueryStr }, '', window.location.pathname + newQueryStr);
  }

  render() {
    return (
      <div>
        <InfographicDropdown />
        <MainContentContainer>
          <div className="row">
            <div className="col-auto">
              <SideBar
                ref={ this.sideBarRef }
                style={{ background: "#DFEFD3" }}
                isLoading={ this.state.isLoading }
                sunlight={ this.state.filters.sunlight }
                moisture={ this.state.filters.moisture }
                height={ this.state.filters.height }
                width={ this.state.filters.width }
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
                    onSortByClicked={ this.onSortByChanged }
                    onViewTypeClicked={ this.onViewTypeChanged }
                    onFilterClicked={ this.onFilterRemoved }
                    filters={
                      Object.keys(this.state.filters).map((filterKey) => {
                        return { key: filterKey, val: this.state.filters[filterKey] }
                      })
                    }
                  />
                  <SearchResultContainer viewType={ this.state.viewType }>
                    {
                      this.state.searchResults.map((result) =>  {
                        let filterWidth = filterByWidth(result, this.state.filters.width);
                        let filterHeight = filterByHeight(result, this.state.filters.height);
                        let filterSunlight = filterBySunlight(result, this.state.filters.sunlight);
                        let filterMoisture = filterByMoisture(result, this.state.filters.moisture);
                        let showResult = filterWidth && filterHeight && filterSunlight && filterMoisture;
                        return (
                          <SearchResult
                            key={ result.tid }
                            viewType={ this.state.viewType }
                            display={ showResult }
                            href={ getTaxaPage(result.tid) }
                            src={ result.image }
                            commonName={ result.vernacularName ? result.vernacularName : '' }
                            sciName={ result.sciName ? result.sciName : '' }
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
