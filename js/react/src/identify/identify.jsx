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
import Loading from "../common/loading.jsx";

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
      isLoading: true,
      isSearching: false,
      clid: -1,
      pid: -1,
      projName: '',
      dynclid: -1,
      title: '',
      authors: '',
      abstract: '',
      displayAbstract: 'default',
      filters: {
        searchText: ("search" in queryParams ? queryParams["search"] : ViewOpts.DEFAULT_SEARCH_TEXT),
        attrs: {}
      },
      searchText: ("search" in queryParams ? queryParams["search"] : ViewOpts.DEFAULT_SEARCH_TEXT),
      searchResults: {"familySort":{},"taxonSort":[]},
      characteristics: {},
      sortBy: ("sortBy" in queryParams ? queryParams["sortBy"] : "sciName"),
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
      },
      exportUrlCsv: '',
      exportUrlWord: '',
      googleMapUrl: '',
    };

    this.getPid = this.getPid.bind(this);
    this.getClid = this.getClid.bind(this);
    this.getDynclid = this.getDynclid.bind(this);

    this.onSearchTextChanged = this.onSearchTextChanged.bind(this);
    this.onSearch = this.onSearch.bind(this);
    this.onSearchResults = this.onSearchResults.bind(this);
    this.onSortByChanged = this.onSortByChanged.bind(this);
    this.onFilterRemoved = this.onFilterRemoved.bind(this);
    this.clearFilters = this.clearFilters.bind(this);
    this.onAttrChanged = this.onAttrChanged.bind(this);
    this.sortResults = this.sortResults.bind(this);
    this.clearTextSearch = this.clearTextSearch.bind(this);
  }

  getClid() {
    return parseInt(this.props.clid);
  }
  getPid() {
    return parseInt(this.props.pid);
  }
  getDynclid() {
    return parseInt(this.props.dynclid);
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
    let url = `${this.props.clientRoot}/ident/rpc/api.php`;
    let identParams = new URLSearchParams();
    if (this.getClid() > -1) {
	    identParams.append("clid",this.getClid());
	  }
	  if (this.getPid() > -1) {
	    identParams.append("pid",this.getPid());
	  }
    if (this.getDynclid() > -1) {
	    identParams.append("dynclid",this.getDynclid());
	  }
  	url = url + '?' + identParams.toString();
		//console.log(url);
		
    httpGet(url)
			.then((res) => {
				res = JSON.parse(res);
				
				let taxa = '';
				if (res && res.taxa) {
					taxa = this.sortResults(res.taxa);
				}
				
				let googleMapUrl = '';				
				if (res.lat !== '' && res.lng !== '' && res.lat > 0 && res.lng != 0) {
					googleMapUrl += 'https://maps.google.com/maps/api/staticmap';
					let mapParams = new URLSearchParams();
					let markerUrl = 'http://symbiota.oregonflora.org' + this.props.clientRoot + '/images/icons/map_markers/single.png'; 
					mapParams.append("key",this.props.googleMapKey);
					mapParams.append("maptype",'terrain');
					mapParams.append("size",'220x220');
					mapParams.append("zoom",6);
					mapParams.append("markers",'icon:' + markerUrl + '|anchor:center|' + res.lat + ',' + res.lng + '');

					googleMapUrl += '?' + mapParams.toString();
				}
				
				this.setState({
					clid: this.getClid(),
					pid: this.getPid(),
					dynclid: this.getDynclid(),
					projName: res.projName,
					title: res.title,
					authors: res.authors,
					abstract: res.abstract,
					characteristics: res.characteristics,
					searchResults: taxa,
					totals: res.totals,
					fixedTotals: res.totals,
					googleMapUrl: googleMapUrl,
					exportUrlCsv: `${this.props.clientRoot}/checklists/rpc/export.php?clid=` + this.getClid() + `&pid=` + this.getPid() + `&dynclid=` + this.getDynclid(),
					exportUrlWord: `${this.props.clientRoot}/checklists/defaultchecklistexport.php?cl=` + this.getClid() + `&pid=` + this.getPid() + `&dynclid=` + this.getDynclid()
				});
				const pageTitle = document.getElementsByTagName("title")[0];
				pageTitle.innerHTML = `${pageTitle.innerHTML} ${res.title}`;
			})
			.catch((err) => {
				//window.location = "/";
				console.error(err);
			})
      .finally(() => {
        this.setState({ isLoading: false });
      });
 
  }
	updateExportUrls() {
  	this.updateExportUrlCsv();
    this.updateExportUrlWord();
  }
  updateExportUrlCsv() {

  	let url = `${this.props.clientRoot}/checklists/rpc/export.php`;
  	let exportParams = new URLSearchParams();
  	
		exportParams.append("clid",this.getClid());
		exportParams.append("pid",this.getPid());
		exportParams.append("dynclid",this.getDynclid());

		if (this.state.filters.searchText) {
			exportParams.append("search",this.state.filters.searchText);
		}
  	url += '?' + exportParams.toString();

	  this.setState({
      exportUrlCsv: url,
    });
  }
  updateExportUrlWord() {
  	let url = `${this.props.clientRoot}/checklists/defaultchecklistexport.php`;
  	let exportParams = new URLSearchParams();
  	//params here match /checklists/defaultchecklistexport.php
		exportParams.append("cl",this.getClid());
		exportParams.append("pid",this.getPid());
		exportParams.append("dynclid",this.getDynclid());
		exportParams.append("showcommon",1);
		if (this.state.filters.searchText) {
			exportParams.append("taxonfilter",this.state.filters.searchText);
		}
  	url += '?' + exportParams.toString();
	  this.setState({
      exportUrlWord: url,
    });
  }

	clearTextSearch() {
		this.onFilterRemoved("searchText",'');
	}  
  onFilterRemoved(key,text) {

    // TODO: This is clunky
    switch (key) {
      case "searchText":
        this.setState({
          searchText: ViewOpts.DEFAULT_SEARCH_TEXT },
          () => this.onSearch({ text: ViewOpts.DEFAULT_SEARCH_TEXT, value: -1 })
        );
        this.updateExportUrls();
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
      //isLoading: true,
      isSearching: true,
    });
    let url = `${this.props.clientRoot}/ident/rpc/api.php`;
    let identParams = new URLSearchParams();
    if (this.getClid() > -1) {
	    identParams.append("clid",this.getClid());
	  }
	  if (this.getPid() > -1) {
	    identParams.append("pid",this.getPid());
	  }
    if (this.getDynclid() > -1) {
	    identParams.append("dynclid",this.getDynclid());
	  }
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
        this.updateExportUrls();
      })
      .catch((err) => {
        console.error(err);
      })
      .finally(() => {
        //this.setState({ isLoading: false });
        this.setState({ isSearching: false });
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
    this.setState({ searchResults: newResults },function() {
			this.updateExportUrls();
		});
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
    	this.setState({ searchResults: this.sortByName(this.state.searchResults) },function() {
    		this.updateExportUrls();
    	});
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
			shortAbstract = this.state.abstract.replace(/^(.{240}[^\s]*).*/, "$1") + "...";//wordsafe truncate
		}
		let suggestionUrl = `${this.props.clientRoot}/checklists/rpc/autofillsearch.php`;

    return (
    <div className="wrapper">
			<Loading 
				clientRoot={ this.props.clientRoot }
				isLoading={ this.state.isLoading }
			/>
			<div className="page-header">
				<PageHeader bgClass="explore" title={ this.state.projName } />
      </div>
      <div className="container identify" style={{ minHeight: "45em" }}>
 				<div className="row pb-2">
          <div className="col-9">
            <h2>{ this.state.title }</h2>

            { this.state.authors.length > 0 &&
            <p className="authors"><strong>Authors:</strong> <span className="authors-content" dangerouslySetInnerHTML={{__html: this.state.authors}} /></p>
						}
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
          <div className="col-3 text-right mt-3">
          		{ this.state.googleMapUrl.length > 0 &&
          			<a href={ this.props.clientRoot + "/checklists/checklistmap.php?clid=" + this.getClid() } target="_blank">
              		<img className="img-fluid" src={this.state.googleMapUrl} title="Project map" alt="Map representation of checklists" />
              	</a>
              }
          </div>
        </div>
				<div className="row identify-main inventory-main">
					<hr/>
					<div className="col-12 col-xl-4 col-md-5 col-sm-6 sidebar-wrapper">
					{	(this.getDynclid() > 0 || this.getClid() > 0) &&
						<SideBar
							//ref={ this.sideBarRef }
							clid={ this.state.clid }
							dynclid={ this.state.dynclid }
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
							onSortByClicked={ this.onSortByChanged }
							onAttrClicked={ this.onAttrChanged }
							onFilterClicked={ this.onFilterRemoved }
							onClearSearch={ this.clearTextSearch }
							filters={ this.state.filters }
							exportUrlCsv={ this.state.exportUrlCsv }
							exportUrlWord={ this.state.exportUrlWord }
						/>
						
					}
					</div>
					<div className="col-12 col-xl-8 col-md-7 col-sm-6 results-wrapper">
						<div className="row">
							<div className="col">
								<div className="identify-header inventory-header">
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
									{ this.getClid() > -1 &&
									<div className="alt-wrapper">
										<div>Switch to</div>
										<a href={getChecklistPage(this.props.clientRoot,this.getClid(),this.getPid())}>
											<div className="btn btn-primary alt-button" role="button">
												<FontAwesomeIcon icon="list-ul" /> Explore
											</div>
										</a>
									</div>
									}
								</div>
								  { this.getDynclid() > 0 && this.state.searchResults.taxonSort.length == 0 && this.state.isLoading == false &&
										<p><strong>No results found:</strong> Your dynamic checklist may have expired.  <a href={ this.props.clientRoot + "/checklists/dynamicmap.php?interface=key"}>Try again?</a></p>
									}
            
									<IdentifySearchContainer
										searchResults={ this.state.searchResults }
										viewType={ this.state.viewType }
										sortBy={ this.state.sortBy }
										clientRoot={ this.props.clientRoot }
										isSearching={this.state.isSearching}
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

if (queryParams.cl || queryParams.dynclid) {
  ReactDOM.render(
    <IdentifyApp 
    	clid={queryParams.cl ? queryParams.cl : -1 } 
    	pid={queryParams.proj ? queryParams.proj : -1 } 
    	dynclid={queryParams.dynclid ? queryParams.dynclid : -1 } 
    	clientRoot={ dataProps["clientRoot"] }
    	googleMapKey={ dataProps["googleMapKey"] }
    />,
    domContainer
  );
} else {
  window.location = "/projects/";
}






