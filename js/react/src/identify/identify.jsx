"use strict";

import React from "react";
import ReactDOM from "react-dom";

import SideBar from "./sidebar.jsx";
import {IdentifySearchContainer, SearchResultContainer} from "../common/searchResults.jsx";
import ViewOpts from "./viewOpts.jsx";
import httpGet from "../common/httpGet.js";
import {addUrlQueryParam, getUrlQueryParams} from "../common/queryParams.js";
import {getCommonNameStr, getTaxaPage, getIdentifyPage, getChecklistPage} from "../common/taxaUtils";
import PageHeader from "../common/pageHeader.jsx";

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { library } from "@fortawesome/fontawesome-svg-core";
import {faChevronDown, faChevronUp, faListUl, faSearchPlus } from '@fortawesome/free-solid-svg-icons'
library.add( faChevronDown, faChevronUp, faListUl, faSearchPlus );


class IdentifyApp extends React.Component {
  constructor(props) {
    super(props);
    const queryParams = getUrlQueryParams(window.location.search);

    // TODO: searchText is both a core state value and a state.filters value; How can we make the filtering system more efficient?
    this.state = {
      isLoading: false,
      clid: null,
      pid: null,
      title: '',
      authors: '',
      abstract: '',
      displayAbstract: 'default',
      filters: {
        searchText: ("search" in queryParams ? queryParams["search"] : ViewOpts.DEFAULT_SEARCH_TEXT),
        //wholePlant: {},
        //leaf: {},
        //gardening: {},
        attrs: {}
      },
      searchText: ("search" in queryParams ? queryParams["search"] : ViewOpts.DEFAULT_SEARCH_TEXT),
      searchResults: {"familySort":{},"taxonSort":[]},
      characteristics: {},
      sortBy: ("sortBy" in queryParams ? queryParams["sortBy"] : "sciName"),
      //viewType: ("viewType" in queryParams ? queryParams["viewType"] : "list"),
      //wholePlantState: {},
      //leafState: {},
      //gardeningState: {},
      totals: {
      	families: 0,
      	genera: 0,
      	species: 0,
      	taxa: 0
      },
      fixedTotals: {//unchanged by filtering
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
    this.onSortByChanged = this.onSortByChanged.bind(this);
    //this.onViewTypeChanged = this.onViewTypeChanged.bind(this);
    this.onFilterRemoved = this.onFilterRemoved.bind(this);
    this.clearFilters = this.clearFilters.bind(this);
    //this.toggleFeatureCollectionVal = this.toggleFeatureCollectionVal.bind(this);
    //this.onWholePlantChanged = this.onWholePlantChanged.bind(this);
    //this.onLeafChanged = this.onLeafChanged.bind(this);
    //this.onGardeningChanged = this.onGardeningChanged.bind(this);
    this.onAttrChanged = this.onAttrChanged.bind(this);
    //this.updateFeatureCollectionFilters = this.updateFeatureCollectionFilters.bind(this);
    this.sortResults = this.sortResults.bind(this);
  }

  getClid() {
    return parseInt(this.props.clid);
  }
  getPid() {
    return parseInt(this.props.pid);
  }
  toggleDisplay = () => {
		let newVal = 'default';
		if (this.state.displayAbstract == 'default') {
			newVal = 'expanded';
		} 
		this.setState({
			displayAbstract: newVal
		});

  }  

  componentDidMount() {
    // Load search results
    let url = `${this.props.clientRoot}/ident/rpc/api.php?clid=${this.props.clid}&pid=${this.props.pid}`;
    httpGet(url)
			.then((res) => {
				// /ident/rpc/api.php?clid=3&pid=1
				res = JSON.parse(res);
			
				this.setState({
					clid: this.getClid(),
					pid: this.getPid(),
					title: res.title,
					authors: res.authors,
					abstract: res.abstract,
					characteristics: res.characteristics,
					searchResults: this.sortResults(res.taxa),
					totals: res.totals,
					fixedTotals: res.totals,
					//googleMapUrl: googleMapUrl
				});
				const pageTitle = document.getElementsByTagName("title")[0];
				pageTitle.innerHTML = `${pageTitle.innerHTML} ${res.title}`;
			})
			.catch((err) => {
				//window.location = "/";
				console.error(err);
			});
 
  }

  onFilterRemoved(key,text) {

  	const characteristics = ["wholePlant","leaf","gardening"];
    // TODO: This is clunky
    switch (key) {
      case "searchText":
        this.setState({
          searchText: ViewOpts.DEFAULT_SEARCH_TEXT },
          () => this.onSearch({ text: ViewOpts.DEFAULT_SEARCH_TEXT, value: -1 })
        );
        break;

      default://characteristics/attr numbers
      	this.onAttrChanged(key,text,'off');
        break;
    }
  }

  onSearchTextChanged(e) {
    this.setState({ searchText: e.target.value });
  }

  // On search start
  onSearch(searchObj) {
    this.setState({
      searchText: searchObj.text,
      filters: Object.assign({}, this.state.filters, { searchText: searchObj.text })
    },function() {
			this.doQuery();
    });
  }
  doQuery() {
    this.setState({
      isLoading: true
    });
    let url = `${this.props.clientRoot}/ident/rpc/api.php`;
    let identParams = new URLSearchParams();
    identParams.append("clid",this.getClid());
    identParams.append("pid",this.getPid());
    if (this.state.searchText) {
    	identParams.append("search",this.state.searchText);
	    identParams.append("name",'sciname');
    	//url += '&synonyms=off';
  	}
  	Object.keys(this.state.filters.attrs).map((idx) => {
	    identParams.append("attr[]",idx);
		});
  	url = url + '?' + identParams.toString();
    //console.log(url);
    httpGet(url)
      .then((res) => {
      	let jres = JSON.parse(res);
        this.onSearchResults(jres.taxa);
        this.onAttrResults(jres.characteristics);
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
    this.setState({ searchResults: newResults });
  }
  onAttrResults(chars) {
    this.setState({ characteristics: chars });
  }
  sortResults(results) {//should receive taxa from API
  	let newResults = {};
		let taxonSort = results;
		let familySort = {};
		
		Object.entries(results).map(([key, result]) => {
			if (!familySort[result.family]) {
				familySort[result.family] = [];
			}
			familySort[result.family].push(result);
		})
		

    newResults = {"familySort": familySort, "taxonSort": taxonSort};
    newResults = this.sortByName(newResults);
  	return newResults;
  }
  sortByName(searchResults) {
  	//uses state.searchResults, which are already duplicated into taxonSort and familySort
  	//taxonSort not implemented on this page, but left intact just in case
  	let familySort = searchResults.familySort;
  	Object.entries(familySort).map(([key, result]) => {
			if (this.state.sortBy === "sciName") {
				familySort[key] = result.sort((a, b) => { return a["sciname"] > b["sciname"] ? 1 : -1 });
			} else {
				familySort[key] = result.sort((a, b) => {
					return (
						getCommonNameStr(a).toLowerCase() >
						getCommonNameStr(b).toLowerCase() ? 1 : -1
						
					);
				});
			}
		})
    let newResults = {"familySort": familySort, "taxonSort": searchResults.taxonSort};
    return newResults;
  }

  onAttrChanged(featureKey, featureName, featureVal) {

  	let filters = this.state.filters;

  	if (featureVal == 'off') {
  		delete filters.attrs[featureKey];
  	}else{
  		filters.attrs[featureKey] = featureName;
  	}

    this.setState({
      filters: Object.assign({}, this.state.filters, { attrs: filters.attrs })
    },function() {
    	this.doQuery();
    });
    
  }
  onSortByChanged(type) {
    this.setState({ sortBy: type },function() {
    	this.setState({ searchResults: this.sortByName(this.state.searchResults) });
    });
  }

	clearFilters() {
		let filters = {
			searchText: ViewOpts.DEFAULT_SEARCH_TEXT,
			attrs: {},
		};
    this.setState({ filters: filters },function() {
    	this.doQuery();
    });
	}
    render() {
		let shortAbstract = '';
		if (this.state.abstract.length > 0) {
			shortAbstract = this.state.abstract.replace(/^(.{330}[^\s]*).*/, "$1") + "...";//wordsafe truncate
		}
		let suggestionUrl = `${this.props.clientRoot}/checklists/rpc/autofillsearch.php`;
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
						
						{this.state.abstract.length > 0 && this.state.displayAbstract == 'default' &&
							<div>
							<p className="abstract"><strong>Abstract:</strong> <span className="abstract-content" dangerouslySetInnerHTML={{__html: shortAbstract}} /></p>
							<div className="more more-less" onClick={() => this.toggleDisplay()}>
									<FontAwesomeIcon icon="chevron-down" />Show Abstract
							</div>
							</div>
						}
						{this.state.abstract.length > 0 && this.state.displayAbstract == 'expanded' &&
							<div>
							<p className="abstract"><strong>Abstract:</strong> <span className="abstract-content" dangerouslySetInnerHTML={{__html: this.state.abstract}} /></p>
							<div className="less more-less" onClick={() => this.toggleDisplay()}>
									<FontAwesomeIcon icon="chevron-up" />Hide Abstract
							</div>
							</div>					
						}				

          </div>
          <div className="col-3">
          	map here
          </div>
        </div>
				<div className="row explore-main">
					<hr/>
					<div className="col-auto sidebar-wrapper">
					{
					
						<SideBar
							//ref={ this.sideBarRef }
							clid={ this.state.clid }
							style={{ background: "#DFEFD3" }}
							isLoading={ this.state.isLoading }
							clientRoot={this.props.clientRoot}
							totals={ this.state.totals }
							fixedTotals={ this.state.fixedTotals }
							characteristics={ this.state.characteristics }
							searchText={ this.state.searchText }
							searchSuggestionUrl={ suggestionUrl }
							onSearch={ this.onSearch }
							onSearchTextChanged={ this.onSearchTextChanged }
							searchName={ this.state.searchName }
							viewType={ this.state.viewType }
							sortBy={ this.state.sortBy }
							//onSearchNameClicked={ this.onSearchNameChanged }
							onSortByClicked={ this.onSortByChanged }
							onAttrClicked={ this.onAttrChanged }
							//onViewTypeClicked={ this.onViewTypeChanged }
							onFilterClicked={ this.onFilterRemoved }
							
							//wholePlant={ this.state.wholePlantState }
							//leaf={ this.state.leafState }
							//gardening={ this.state.gardeningState }
							//onWholePlantChanged={ this.onWholePlantChanged }
							//onLeafChanged={ this.onLeafChanged }
							//onGardeningChanged={ this.onGardeningChanged }
							filters={ this.state.filters }
						/>
						
					}
					</div>
					<div className="col results-wrapper">
						<div className="row">
							<div className="col">
								<div className="explore-header inventory-header">
									<div className="current-wrapper">
										<div className="btn btn-primary current-button" role="button"><FontAwesomeIcon icon="search-plus" /> Identify</div>
										
										<ViewOpts
											onReset={ this.clearFilters }
											onFilterClicked={ this.onFilterRemoved }
											filters={
												Object.keys(this.state.filters).map((filterKey) => {
													return { key: filterKey, val: this.state.filters[filterKey] }
												})
											}
										/>
															
									</div>
									<div className="alt-wrapper">
										<div>Switch to</div>
										<a href={getChecklistPage(this.props.clientRoot,this.getClid(),this.getPid())}><div className="btn btn-primary alt-button" role="button">
										<FontAwesomeIcon icon="list-ul" /> Explore</div>
										</a>
									</div>
									
								</div>
									<IdentifySearchContainer
										searchResults={ this.state.searchResults }
										viewType={ this.state.viewType }
										sortBy={ this.state.sortBy }
										clientRoot={ this.props.clientRoot }
									/>
										
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
    );
  }
}

const headerContainer = document.getElementById("react-header");
const dataProps = JSON.parse(headerContainer.getAttribute("data-props"));
const domContainer = document.getElementById("react-identify-app");
const queryParams = getUrlQueryParams(window.location.search);

if (queryParams.cl) {
  ReactDOM.render(
    <IdentifyApp clid={queryParams.cl } pid={queryParams.proj } clientRoot={ dataProps["clientRoot"] }/>,
    domContainer
  );
} else {
  window.location = "/projects/";
}






