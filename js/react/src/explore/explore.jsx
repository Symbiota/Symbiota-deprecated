"use strict";
import React from "react";
import ReactDOM from "react-dom";

import SideBar from "./sidebar.jsx";
import ViewOpts from "./viewOpts.jsx";
import httpGet from "../common/httpGet.js";
import {ExploreSearchContainer, SearchResultContainer} from "../common/searchResults.jsx";
import {addUrlQueryParam, getUrlQueryParams} from "../common/queryParams.js";
import {getCommonNameStr, getTaxaPage, getIdentifyPage} from "../common/taxaUtils";
import PageHeader from "../common/pageHeader.jsx";

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { library } from "@fortawesome/fontawesome-svg-core";
import {faChevronDown, faChevronUp, faListUl, faSearchPlus } from '@fortawesome/free-solid-svg-icons'
library.add( faChevronDown, faChevronUp, faListUl, faSearchPlus );

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
      displayAbstract: 'default',
      googleMapUrl: '',
      exportUrl: '',
      //taxa: [],
      isLoading: true,
      filters: {
        searchText: ("search" in queryParams ? queryParams["search"] : ViewOpts.DEFAULT_SEARCH_TEXT),
        //checklistId: ("clid" in queryParams ? parseInt(queryParams["clid"]) : ViewOpts.DEFAULT_CLID),
      },
      searchText: ("search" in queryParams ? queryParams["search"] : ViewOpts.DEFAULT_SEARCH_TEXT),
      searchResults: {"familySort":{},"taxonSort":[]},
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
    //this.onSearch({ text: this.state.searchText });
    
    httpGet(`./rpc/api.php?clid=${this.props.clid}&pid=${this.props.pid}`)
			.then((res) => {
				// /checklists/rpc/api.php?clid=3
				res = JSON.parse(res);
				
				let googleMapUrl = '';				
				if (res.lat !== 0 && res.lng !== 0) {

					googleMapUrl += 'https://maps.google.com/maps/api/staticmap';
					let mapParams = new URLSearchParams();
					mapParams.append("key",this.props.googleMapKey);
					mapParams.append("maptype",'terrain');
					mapParams.append("size",'220x220');
					mapParams.append("zoom",6);
					mapParams.append("markers",'size:med|' + res.lat + ',' + res.lng + '');
		
					googleMapUrl += '?' + mapParams.toString();

				}
							
				this.setState({
					clid: this.getClid(),
					pid: this.getPid(),
					title: res.title,
					authors: res.authors,
					abstract: res.abstract,
					//taxa: res.taxa,
					searchResults: this.sortResults(res.taxa),
					totals: res.totals,
					fixedTotals: res.totals,
					googleMapUrl: googleMapUrl,
					exportUrl: `${this.props.clientRoot}/checklists/rpc/export.php?clid=` + this.getClid() + `&pid=` + this.getPid()
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
  updateExportUrl() {
  	let url = `${this.props.clientRoot}/checklists/rpc/api.php`;
  	let exportParams = new URLSearchParams();
  	
		exportParams.append("clid",this.getClid());
		exportParams.append("pid",this.getPid());
		if (this.state.searchName) {
			exportParams.append("name",this.state.searchName);
		}
		if (this.state.searchSynonyms) {
			exportParams.append("synonyms",this.state.searchSynonyms);
		}
		if (this.state.filters.searchText) {
			exportParams.append("search",this.state.filters.searchText);
		}
  	url += '?' + exportParams.toString();
  	
	  this.setState({
      exportUrl: url,
    });
  }

  onFilterRemoved(key) {
    // TODO: This is clunky
    console.log(key);
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
    //const newQueryStr = addUrlQueryParam("search", searchObj.text);
    
    /*window.history.replaceState(
      { query: newQueryStr },
      '',
      window.location.pathname + newQueryStr
    );*/
    this.setState({
      isLoading: true,
      searchText: searchObj.text,
      filters: Object.assign({}, this.state.filters, { searchText: searchObj.text })
    });
    let url = `${this.props.clientRoot}/checklists/rpc/api.php?search=${searchObj.text}`;
    url += '&name=' + this.state.searchName;
    url += '&clid=' + this.state.clid;
    url += '&pid=' + this.state.pid;
    url += '&synonyms=' + this.state.searchSynonyms;
    //console.log(url);
    httpGet(url)
      .then((res) => {
      	let jres = JSON.parse(res);
        this.onSearchResults(jres.taxa);
        this.updateTotals(jres.totals);
        this.updateExportUrl();
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
  
  sortResults(results) {//should receive taxa from API
  	let newResults = {};
  	//console.log(results);

		let familySort = {};
		let tmp = {};
		Object.entries(results).map(([key, result]) => {
			if (!tmp[result.family]) {
				tmp[result.family] = [];
			}
			tmp[result.family].push(result);
		})
		//sort family alpha
		Object.keys(tmp).sort().forEach(function(key) {
			familySort[key] = tmp[key];
		});

		let taxonSort = results;
    
    newResults = {"familySort": familySort, "taxonSort": taxonSort};
    
  	return newResults;
  }

  onSortByChanged(sortBy) {
    this.setState({ sortBy: sortBy });
  }
  onSearchNameChanged(name) {
    this.setState({ searchName: name });

    let newName;
    if (name === "commonname") {
      newName = name;
    } else {
      newName = 'sciname';
    }
    //let newQueryStr = addUrlQueryParam("searchName", newName);
    /*window.history.replaceState({ query: newQueryStr }, '', window.location.pathname + newQueryStr);*/
  }
  onSearchSynonymsChanged(synonyms) {
    this.setState({ searchSynonyms: synonyms });

    let newSynonyms;
    if (synonyms === 'off') {
      newSynonyms = synonyms;
    } else {
      newSynonyms = 'on';
    }
    //let newQueryStr = addUrlQueryParam("searchSynonyms", newSynonyms);
    /*window.history.replaceState({ query: newQueryStr }, '', window.location.pathname + newQueryStr);*/
  }
  onViewTypeChanged(type) {
    this.setState({ viewType: type });

    let newType;
    if (type) {
      newType = type;
    } else {
      newType = 'list';
    }
    
    if (newType === 'grid') {
  		this.setState({showTaxaDetail: "off"});
	  }
    
    //let newQueryStr = addUrlQueryParam("viewType", newType);
    /*window.history.replaceState({ query: newQueryStr }, '', window.location.pathname + newQueryStr);*/
  }
  onTaxaDetailChanged(taxaDetail) {
  	this.setState({showTaxaDetail: taxaDetail});
  	
  	let newVal;
  	if (taxaDetail === 'on') {
  		newVal = taxaDetail;
  	}else{
  		newVal = 'off';
  	}
  	
  	//let newQueryStr = addUrlQueryParam("taxaDetail",newVal);
    /*window.history.replaceState({ query: newQueryStr }, '', window.location.pathname + newQueryStr);*/
  }

  render() {
		let shortAbstract = '';
		if (this.state.abstract.length > 0) {
			shortAbstract = this.state.abstract.replace(/^(.{240}[^\s]*).*/, "$1") + "...";//wordsafe truncate
		}
    return (
    <div className="wrapper">
			<div className="page-header">
				<PageHeader bgClass="explore" title={ "Exploring Oregon's Botanical Diversity" } />
      </div>
      <div className="container explore" style={{ minHeight: "45em" }}>
 				<div className="row">
          <div className="col-9">
            <h2>{ this.state.title }</h2>
            {this.state.authors.length > 0 &&
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
              	<img  className="img-fluid" src={this.state.googleMapUrl} title="Project map" alt="Map representation of checklists" />
              }
          </div>
        </div>
				<div className="row explore-main inventory-main">
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
							exportUrl={ this.state.exportUrl }
						/>
						
					}
					</div>
					<div className="col results-wrapper">
						<div className="row">
							<div className="col">
								<div className="explore-header inventory-header">
									<div className="current-wrapper">
										<div className="btn btn-primary current-button" role="button"><FontAwesomeIcon icon="list-ul" /> Explore</div>
									</div>
									<div className="alt-wrapper">
										<div>Switch to</div>
										<a href={getIdentifyPage(this.props.clientRoot,this.getClid(),this.getPid())}><div className="btn btn-primary alt-button" role="button"><FontAwesomeIcon icon="search-plus" /> Identify</div></a>
									</div>
								</div>
									<ExploreSearchContainer
										searchResults={ this.state.searchResults }
										viewType={ this.state.viewType }
										sortBy={ this.state.sortBy }
										showTaxaDetail={ this.state.showTaxaDetail }
										clientRoot={this.props.clientRoot}
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
    <ExploreApp clid={queryParams.cl } pid={queryParams.pid } clientRoot={ dataProps["clientRoot"] } googleMapKey={ dataProps["googleMapKey"] }/>,
    domContainer
  );
} else {
  window.location = "/projects/";
}
