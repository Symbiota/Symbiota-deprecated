"use strict";

import React from "react";
import ReactDOM from "react-dom";

import SideBar from "./sidebar.jsx";
import ViewOpts from "./viewOpts.jsx";
import httpGet from "../common/httpGet.js";
import {ExploreSearchContainer, SearchResultContainer} from "../common/searchResults.jsx";
import {addUrlQueryParam, getUrlQueryParams} from "../common/queryParams.js";
import {getCommonNameStr, getTaxaPage} from "../common/taxaUtils";
import PageHeader from "../common/pageHeader.jsx";

class ExploreApp extends React.Component {
  constructor(props) {
    super(props);
    const queryParams = getUrlQueryParams(window.location.search);

    // TODO: searchText is both a core state value and a state.filters value; How can we make the filtering system more efficient?
    this.state = {
      clid: null,
      pid: null,
      title: '',
      authors: '',
      abstract: '',
      //taxa: [],
      isLoading: false,
      filters: {
        searchText: ("search" in queryParams ? queryParams["search"] : ViewOpts.DEFAULT_SEARCH_TEXT),
        //checklistId: ("clid" in queryParams ? parseInt(queryParams["clid"]) : ViewOpts.DEFAULT_CLID),
      },
      searchText: ("search" in queryParams ? queryParams["search"] : ViewOpts.DEFAULT_SEARCH_TEXT),
      searchResults: [],
      searchName: ("searchName" in queryParams ? queryParams["searchName"] : "sciname"),
      searchSynonyms: ("searchSynonyms" in queryParams ? queryParams["searchSynonyms"] : 'on'),
      sortBy: ("sortBy" in queryParams ? queryParams["sortBy"] : "family"),
      viewType: ("viewType" in queryParams ? queryParams["viewType"] : "list"),
      showTaxaDetail: ("showTaxaDetail" in queryParams ? queryParams["showTaxaDetail"] : 'off'),
      totals: {
      	families: 0,
      	genera: 0,
      	species: 0,
      	taxa: 0
      }
    };
    this.getPid = this.getPid.bind(this);
    this.getClid = this.getClid.bind(this);

    this.onSearchTextChanged = this.onSearchTextChanged.bind(this);
    this.onSearch = this.onSearch.bind(this);
    this.onSearchResults = this.onSearchResults.bind(this);
    this.onSearchNameChanged = this.onSearchNameChanged.bind(this);
    this.onSearchSynonymsChanged = this.onSearchSynonymsChanged.bind(this);
    this.onSortByChanged = this.onSortByChanged.bind(this);
    this.onViewTypeChanged = this.onViewTypeChanged.bind(this);
    this.onTaxaDetailChanged = this.onTaxaDetailChanged.bind(this);
    this.onFilterRemoved = this.onFilterRemoved.bind(this);
    this.sortResults = this.sortResults.bind(this);
  }

  getClid() {
    return parseInt(this.props.clid);
  }
  getPid() {
    return parseInt(this.props.pid);
  }

  componentDidMount() {
    // Load search results
    //this.onSearch({ text: this.state.searchText });
    
    httpGet(`./rpc/api.php?clid=${this.props.clid}&pid=${this.props.pid}`)
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
					clid: this.getClid(),
					pid: this.getPid(),
					title: res.title,
					authors: res.authors,
					abstract: res.abstract,
					//taxa: res.taxa,
					searchResults: this.sortResults(res.taxa),
					totals: res.totals,
					//googleMapUrl: googleMapUrl
				});
				const pageTitle = document.getElementsByTagName("title")[0];
				pageTitle.innerHTML = `${pageTitle.innerHTML} ${res.title}`;
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
    let url = `${this.props.clientRoot}/checklists/rpc/api.php?search=${searchObj.text}`;
    url += '&name=' + this.state.searchName;
    url += '&clid=' + this.state.clid;
    url += '&pid=' + this.state.pid;
    //console.log(url);
    httpGet(url)
      .then((res) => {
      	let jres = JSON.parse(res);
        this.onSearchResults(jres.taxa);
        this.updateTotals(jres.totals);
      })
      .catch((err) => {
        console.error(err);
      })
      .finally(() => {
        this.setState({ isLoading: false });
      });
      
  }
	updateTotals(totals) {
	  this.setState({
      totals: totals,
    });
	}

  // On search end
  onSearchResults(results) {
    let newResults;
    newResults = this.sortResults(results);
    /*
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
*/
    this.setState({ searchResults: newResults });
  }
  
  sortResults(results) {
  	let newResults = {};
    if (this.state.sortBy === 'family') {

			Object.entries(results).map(([key, result]) => {
				if (!newResults[result.family]) {
					newResults[result.family] = [];
				}
				newResults[result.family].push(result);
			})
    	//newResults = results;
    }else{
    	newResults = results;
    }
  	return newResults;
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
    if (type === "taxon") {
      newType = type;
    } else {
      newType = '';
    }
    let newQueryStr = addUrlQueryParam("sortBy", newType);
    window.history.replaceState({query: newQueryStr}, '', window.location.pathname + newQueryStr);
  }
  onSearchNameChanged(name) {
    this.setState({ searchName: name });

    let newName;
    if (name === "commonname") {
      newName = name;
    } else {
      newName = 'sciname';
    }
    let newQueryStr = addUrlQueryParam("searchName", newName);
    window.history.replaceState({ query: newQueryStr }, '', window.location.pathname + newQueryStr);
  }
  onSearchSynonymsChanged(synonyms) {
    this.setState({ searchSynonyms: synonyms });

    let newSynonyms;
    if (synonyms === 'off') {
      newSynonyms = synonyms;
    } else {
      newSynonyms = 'on';
    }
    let newQueryStr = addUrlQueryParam("searchSynonyms", newSynonyms);
    window.history.replaceState({ query: newQueryStr }, '', window.location.pathname + newQueryStr);
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
  onTaxaDetailChanged(taxaDetail) {
  	this.setState({showTaxaDetail: taxaDetail});
  	
  	let newVal;
  	if (taxaDetail === 'on') {
  		newVal = taxaDetail;
  	}else{
  		newVal = 'off';
  	}
  	
  	let newQueryStr = addUrlQueryParam("taxaDetail",newVal);
    window.history.replaceState({ query: newQueryStr }, '', window.location.pathname + newQueryStr);
  }

  render() {
//console.log(this.state);
    return (
    <div className="wrapper">
			<div className="page-header">
				<PageHeader bgClass="explore" title={ "Exploring Oregon's Botanical Diversity" } />
      </div>
      <div className="container explore" style={{ minHeight: "45em" }}>
 				<div className="row">
          <div className="col-9">
            <h2>{ this.state.title }</h2>
            <p className="authors"><strong>Authors:</strong> <span className="authors-content" dangerouslySetInnerHTML={{__html: this.state.authors}} /></p>
						<p className="abstract"><strong>Abstract:</strong> <span className="abstract-content" dangerouslySetInnerHTML={{__html: this.state.abstract}} /></p>
          </div>
          <div className="col-3">
          	map here
          </div>
        </div>
 					<div className="row">
            <div className="col-auto sidebar-wrapper">
            {
            
              <SideBar
                //ref={ this.sideBarRef }
                clid={ this.state.clid }
                style={{ background: "#DFEFD3" }}
                isLoading={ this.state.isLoading }
                clientRoot={this.props.clientRoot}
                totals={ this.state.totals }
                searchText={ this.state.searchText }
                searchSuggestionUrl="./rpc/autofillsearch.php"
                onSearch={ this.onSearch }
                onSearchTextChanged={ this.onSearchTextChanged }
                searchName={ this.state.searchName }
                searchSynonyms={ this.state.searchSynonyms }
                viewType={ this.state.viewType }
								sortBy={ this.state.sortBy }
								showTaxaDetail={ this.state.showTaxaDetail }
                onSearchSynonymsClicked={ this.onSearchSynonymsChanged }
                onSearchNameClicked={ this.onSearchNameChanged }
								onSortByClicked={ this.onSortByChanged }
								onViewTypeClicked={ this.onViewTypeChanged }
								onTaxaDetailClicked={ this.onTaxaDetailChanged }
								onFilterClicked={ this.onFilterRemoved }
								filters={
									Object.keys(this.state.filters).map((filterKey) => {
										return { key: filterKey, val: this.state.filters[filterKey] }
									})
								}
              />
              
            }
            </div>
            <div className="col results-wrapper">
              <div className="row">
                <div className="col">

          				<h3 className="font-weight-bold">Your search results:</h3>
                  <SearchResultContainer viewType={ this.state.viewType }>
                    
										<ExploreSearchContainer
											searchResults={ this.state.searchResults }
											viewType={ this.state.viewType }
											sortBy={ this.state.sortBy }
											showTaxaDetail={ this.state.showTaxaDetail }
											clientRoot={ this.props.clientRoot }
										/>
											
                  </SearchResultContainer>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}
ExploreApp.defaultProps = {
  clid: -1,
  pid: -1,
};

const headerContainer = document.getElementById("react-header");
const dataProps = JSON.parse(headerContainer.getAttribute("data-props"));
const domContainer = document.getElementById("react-explore-app");
const queryParams = getUrlQueryParams(window.location.search);
if (queryParams.cl) {
  ReactDOM.render(
    <ExploreApp clid={queryParams.cl } pid={queryParams.pid } clientRoot={ dataProps["clientRoot"] }/>,
    domContainer
  );
} else {
  window.location = "/projects/";
}
