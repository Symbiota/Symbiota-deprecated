"use strict";

import React from "react";
import ReactDOM from "react-dom";
const CLIENT_ROOT = "..";

//import SideBar from "./sidebar.jsx";
import ViewOpts from "./viewOpts.jsx";
import httpGet from "../common/httpGet.js";
import {SearchResult, SearchResultContainer} from "../common/searchResults.jsx";
import {addUrlQueryParam, getUrlQueryParams} from "../common/queryParams.js";
import {getCommonNameStr, getTaxaPage} from "../common/taxaUtils";



function MainContentContainer(props) {
  return (
    <div className="container mx-auto p-4" style={{ maxWidth: "1400px" }}>
      {props.children}
    </div>
  );
}

class ExplorePageApp extends React.Component {
  constructor(props) {
    super(props);
    const queryParams = getUrlQueryParams(window.location.search);

    // TODO: searchText is both a core state value and a state.filters value; How can we make the filtering system more efficient?
    this.state = {
      clid: null,
      //pid: null,
      title: '',
      authors: '',
      abstract: '',
      taxa: [],
      isLoading: false,
      filters: {
        searchText: ("search" in queryParams ? queryParams["search"] : ViewOpts.DEFAULT_SEARCH_TEXT),
        //checklistId: ("clid" in queryParams ? parseInt(queryParams["clid"]) : ViewOpts.DEFAULT_CLID),
      },
      searchText: ("search" in queryParams ? queryParams["search"] : ViewOpts.DEFAULT_SEARCH_TEXT),
      searchResults: [],
      sortBy: ("sortBy" in queryParams ? queryParams["sortBy"] : "vernacularName"),
      viewType: ("viewType" in queryParams ? queryParams["viewType"] : "grid")
    };
    //this.getPid = this.getPid.bind(this);
    this.getClid = this.getClid.bind(this);

    // To Refresh sliders
    //this.sideBarRef = React.createRef();

    //this.onSearchTextChanged = this.onSearchTextChanged.bind(this);
    //this.onSearch = this.onSearch.bind(this);
    this.onSearchResults = this.onSearchResults.bind(this);
    this.onSortByChanged = this.onSortByChanged.bind(this);
    this.onViewTypeChanged = this.onViewTypeChanged.bind(this);
    this.onFilterRemoved = this.onFilterRemoved.bind(this);
  }

  getClid() {
    return parseInt(this.props.clid);
  }

  componentDidMount() {
    // Load search results
    //this.onSearch({ text: this.state.searchText });
    
    httpGet(`./rpc/api.php?clid=${this.props.clid}`)
			.then((res) => {
				// /checklists/rpc/api.php?clid=3
				res = JSON.parse(res);
			
				/*let googleMapUrl = '';				
				if (res.checklists.length > 0) {
					googleMapUrl = 'https://maps.google.com/maps/api/staticmap?maptype=terrain&key=AIzaSyBmcl6Y-gu3bGdmp7LIQaDCa43TKLrP7qY';
					googleMapUrl += '&size=640x400&zoom=6';
					let latLng = res.checklists.map((checklist) => checklist.latcentroid + ',' + checklist.longcentroid);
					googleMapUrl += '&markers=size:tiny%7C' + latLng.join("%7C");					
				}*/
		
				this.setState({
					title: res.title,
					authors: res.authors,
					abstract: res.abstract,
					taxa: res.taxa,
					//fullDescription: res.fullDescription,
					//isPublic: res.isPublic,
					//googleMapUrl: googleMapUrl
				});
				const pageTitle = document.getElementsByTagName("title")[0];
				pageTitle.innerHTML = `${pageTitle.innerHTML} ${res.projname}`;
			})
			.catch((err) => {
				window.location = "/";
				//console.error(err);
			});
  }


  onFilterRemoved(key) {
    // TODO: This is clunky
    switch (key) {
      case "searchText":
        this.setState({
          searchText: ViewOpts.DEFAULT_SEARCH_TEXT },
          () => this.onSearch({ text: ViewOpts.DEFAULT_SEARCH_TEXT, value: -1 })
        );
        break;
      default:
        break;
    }
  }
/*
  onSearchTextChanged(e) {
    this.setState({ searchText: e.target.value });
  }

  // On search start
  onSearch(searchObj) {
    const newQueryStr = addUrlQueryParam("search", searchObj.text);
    window.history.replaceState(
      { query: newQueryStr },
      '',
      window.location.pathname + newQueryStr
    );

    this.setState({
      isLoading: true,
      searchText: searchObj.text,
      filters: Object.assign({}, this.state.filters, { searchText: searchObj.text })
    });
    httpGet(`${CLIENT_ROOT}/explore/rpc/api.php?search=${searchObj.text}`)
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

*/
  // On search end
  onSearchResults(results) {
    let newResults;
    if (this.state.sortBy === "sciName") {
      newResults = results.sort((a, b) => { return a["sciName"] > b["sciName"] ? 1 : -1 });
    } else {
      newResults = results.sort((a, b) => {
        return (
          getCommonNameStr(a).toLowerCase() >
          getCommonNameStr(b).toLowerCase() ? 1 : -1
        );
      });
    }

    this.setState({ searchResults: newResults });
  }

  onSortByChanged(type) {
    let newResults;
    if (type === "sciName") {
      newResults = this.state.searchResults.sort((a, b) => { return a["sciName"] > b["sciName"] ? 1 : -1 });
    } else {
      newResults = this.state.searchResults.sort((a, b) => {
        return (
          getCommonNameStr(a).toLowerCase() >
          getCommonNameStr(b).toLowerCase() ? 1 : -1
        );
      });
    }

    this.setState({
      sortBy: type,
      searchResults: newResults
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
        <MainContentContainer>
          <div className="row">
            <div className="col-auto">
            {
            /*
              <SideBar
                //ref={ this.sideBarRef }
                style={{ background: "#DFEFD3" }}
                isLoading={ this.state.isLoading }
                //searchText={ this.state.searchText }
                //searchSuggestionUrl="./rpc/autofillsearch.php"
                //onSearch={ this.onSearch }
                //onSearchTextChanged={ this.onSearchTextChanged }
              />
              */
            }
            </div>
            <div className="col">
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
                        let showResult = true;
                        return (
                          <SearchResult
                            key={ result.tid }
                            viewType={ this.state.viewType }
                            display={ showResult }
                            href={ getTaxaPage(CLIENT_ROOT, result.tid) }
                            src={ result.image }
                            commonName={ getCommonNameStr(result) }
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

//const domContainer = document.getElementById("react-explore-app");
//ReactDOM.render(<ExplorePageApp />, domContainer);

const domContainer = document.getElementById("react-explore-app");
const queryParams = getUrlQueryParams(window.location.search);
if (queryParams.cl) {
  ReactDOM.render(
    <ExplorePageApp clid={queryParams.cl }/>,
    domContainer
  );
} else {
  window.location = "/projects/";
}
