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
import FilterModal from "../common/filterModal.jsx";

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { library } from "@fortawesome/fontawesome-svg-core";
import {faChevronDown, faChevronUp, faListUl, faSearchPlus } from '@fortawesome/free-solid-svg-icons'
library.add( faChevronDown, faChevronUp, faListUl, faSearchPlus );

const MOBILE_BREAKPOINT = 576;

class IdentifyApp extends React.Component {
  constructor(props) {
    super(props);
    const queryParams = getUrlQueryParams(window.location.search);

    // TODO: searchText is both a core state value and a state.filters value; How can we make the filtering system more efficient?
    this.state = {
      isLoading: true,
      isSearching: false,
      isMobile: false,
      showFilterModal: false,
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
        attrs: {},
        sliders: {}
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
      apiUrl: '',
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
    this.resetSlider = this.resetSlider.bind(this);
    this.onSliderChanged = this.onSliderChanged.bind(this);
    this.sortResults = this.sortResults.bind(this);
    this.clearTextSearch = this.clearTextSearch.bind(this);
    this.getStatesByCid = this.getStatesByCid.bind(this);
    this.mobileScrollToResults = this.mobileScrollToResults.bind(this);
    this.mobileScrollToFilters = this.mobileScrollToFilters.bind(this);
    this.getFilterCount = this.getFilterCount.bind(this);
    this.setFilterModal = this.setFilterModal.bind(this);
    this.doConfirm = this.doConfirm.bind(this);
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
    let apiUrl = `${this.props.clientRoot}/ident/rpc/api.php`;
    let url = apiUrl;

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
				let host = window.location.host;

				let googleMapUrl = '';				
				if (res.lat !== '' && res.lng !== '' && res.lat > 0 && res.lng != 0) {
					googleMapUrl += 'https://maps.google.com/maps/api/staticmap';
					let mapParams = new URLSearchParams();
					let markerUrl = 'http://' + host + this.props.clientRoot + '/images/icons/map_markers/single.png'; 
					mapParams.append("key",this.props.googleMapKey);
					mapParams.append("maptype",'terrain');
					mapParams.append("size",'220x220');
					mapParams.append("zoom",6);
					mapParams.append("markers",'icon:' + markerUrl + '|anchor:center|' + res.lat + ',' + res.lng + '');

					googleMapUrl += '?' + mapParams.toString();
				}
				let isMobile = false;
				if (window.innerWidth < MOBILE_BREAKPOINT) {
					isMobile = true;
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
					isMobile: isMobile,
					apiUrl: apiUrl,
					exportUrlCsv: apiUrl + `?export=csv&clid=` + this.getClid() + `&pid=` + this.getPid() + `&dynclid=` + this.getDynclid(),
					exportUrlWord: apiUrl + `?export=word&clid=` + this.getClid() + `&pid=` + this.getPid() + `&dynclid=` + this.getDynclid()
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

  	let url = this.state.apiUrl;
  	let exportParams = new URLSearchParams();
  	
		exportParams.append("export",'csv');
		exportParams.append("clid",this.getClid());
		exportParams.append("pid",this.getPid());
		exportParams.append("dynclid",this.getDynclid());

		if (this.state.filters.searchText) {
			exportParams.append("search",this.state.filters.searchText);
		}
  	url += '?' + exportParams.toString();
  	//console.log(url);
	  this.setState({
      exportUrlCsv: url,
    });
  }
  updateExportUrlWord() {
  	let url = this.state.apiUrl;
  	let exportParams = new URLSearchParams();

		exportParams.append("export",'word');
		exportParams.append("clid",this.getClid());
		exportParams.append("pid",this.getPid());
		exportParams.append("dynclid",this.getDynclid());
		exportParams.append("showcommon",1);
		if (this.state.filters.searchText) {
			exportParams.append("taxonfilter",this.state.filters.searchText);
		}
  	url += '?' + exportParams.toString();
  	//console.log(url);
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
				if (this.state.filters.attrs[key]) {
	      	this.onAttrChanged(key,text,'off');
	      }
				if (this.state.filters.sliders[key]) {
	      	this.resetSlider(key);
	      }
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
  catchQuery() {

  	let doConfirm = false;
  	if (this.state.isMobile) {
  		doConfirm = true;
  	}
  	if (doConfirm) {
	    this.setFilterModal(true);
	  }else{
	  	this.doQuery();
	  }
  }
  doQuery() {
    this.setState({
      //isLoading: true,
      isSearching: true,
    });
    let url = this.state.apiUrl;
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
		/* compare slider values vs characteristics and add to attr list;
				adding each state as its own attr[] value makes the URL unacceptably long,
				so we create a new range[] param for purposes of building the URL;
				the API will convert this back into attrs for the DB calls
		 */

		Object.entries(this.state.filters.sliders).map((item) => {
			let cid = item[0];
			let slider = item[1];
			let states = this.getStatesByCid(cid);
			let min = states[0].cs;
			let max = (states.length > 1? states[1].cs : states[0].cs);
			Object.keys(states).map((key) => {
				let stateNum = Number(states[key].numval);
				let stateCs = Number(states[key].cs);
				if (stateNum == slider.range[0]) {
					min = stateCs;			
				}
				if (stateNum == slider.range[1]) {
					max = stateCs;
				}	
			})
			identParams.append("range[]",cid + '-n-' + min);
			identParams.append("range[]",cid + '-x-' + max);
		});	
		
  	url = url + '?' + identParams.toString();
    //console.log(decodeURIComponent(url));
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
        this.mobileScrollToResults();
      });
  }
  mobileScrollToResults() {
    if (this.state.isMobile && this.getFilterCount() > 0) {
      let section = document.getElementById("results-section");      
			let yOffset = 60;
			document.getElementById("results-section").scrollIntoView();
			const newY = section.getBoundingClientRect().top + window.pageYOffset - yOffset;
			window.scrollTo({top: newY, behavior: 'smooth'});
		}
  }
  mobileScrollToFilters() {
		let section = document.getElementById("filter-section");      
		let yOffset = 60;
		document.getElementById("filter-section").scrollIntoView();
		const newY = section.getBoundingClientRect().top + window.pageYOffset - yOffset;
		window.scrollTo({top: newY, behavior: 'smooth'});
  }
  getFilterCount() {
  	let filterCount = 0;
  	filterCount += Object.keys(this.state.filters.attrs).length;
  	filterCount += Object.keys(this.state.filters.sliders).length;
  	return filterCount;
  }
  setFilterModal(val) {
  	let newVal = (this.getFilterCount() > 0 ? true : false);
    this.setState({ showFilterModal: newVal });
  }
  doConfirm() {
  	this.setFilterModal(false);
  	this.doQuery();
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

  	let newAttrs = {};
  	let newSliders = {};

  	let newCids = [];
  	Object.entries(chars).map(([key, group]) => {
  		Object.entries(group.characters).map(([ckey,gchar]) => {
  			newCids.push(gchar.cid);
  		});
  	});
  	Object.entries(this.state.filters.attrs).map(([cid,attr]) => {
  		if (newCids.indexOf(Number(cid)) != -1) {
  			newAttrs[cid] = attr;
  		}
  	});
  	Object.entries(this.state.filters.sliders).map(([cid,slider]) => {
  		if (newCids.indexOf(Number(cid)) != -1) {
  			newSliders[cid] = slider;
  		}
  	});
  	this.setState({
      filters: Object.assign({}, this.state.filters, { attrs: newAttrs }),
      filters: Object.assign({}, this.state.filters, { sliders: newSliders }),
      characteristics: chars
    });
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
  getStatesByCid(cid) {
  	let results = {};
  	Object.entries(this.state.characteristics).map(([key, group]) => {
			Object.entries(group.characters).map(([ckey, character]) => {
				if (character.cid == cid) {
					results = character.states;
				}
			});  	
  	});
  	return results;
  }

  onAttrChanged(featureKey, featureName, featureVal) {
  /* 710-1, simple, on */
  	let filters = this.state.filters;

  	if (featureVal == 'off') {
  		delete filters.attrs[featureKey];
  	}else{
  		filters.attrs[featureKey] = featureName;
  	}

    this.setState({
      filters: Object.assign({}, this.state.filters, { attrs: filters.attrs })
    },function() {
    	this.catchQuery();
    });
    
  }
  resetSlider(cid) {
  	let filters = this.state.filters;
  	delete filters.sliders[cid];
    this.setState({
      filters: Object.assign({}, this.state.filters, { sliders: filters.sliders })
    },function() {
    	this.catchQuery();
    });
  }
  
  onSliderChanged(sliderState, range) {

		let min = sliderState.states[0].numval;
		let max = sliderState.states[sliderState.states.length - 1].numval;
  	let filters = this.state.filters;
  	
  	if (range[0] == min && range[1] == max) {
  		delete filters.sliders[sliderState.cid];
  	}else{
  		filters.sliders[sliderState.cid] = { range: sliderState.range, label: sliderState.label, units: sliderState.units, step: sliderState.step, originalStates: sliderState.states };
  	}
  
    this.setState({
      filters: Object.assign({}, this.state.filters, { sliders: filters.sliders })
    },function() {
    	this.catchQuery();
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
			sliders: {},
		};
    this.setState({ filters: filters },function() {
    	this.catchQuery();
    });
	}
  render() {
		let shortAbstract = '';
		if (this.state.abstract.length > 0) {
			shortAbstract = this.state.abstract.replace(/^(.{240}[^\s]*).*/, "$1") + "...";//wordsafe truncate
		}
		let suggestionUrl = `${this.props.clientRoot}/checklists/rpc/autofillsearch.php`;

  	let filterCount = this.getFilterCount();
  	
    return (
    <div className={ "wrapper" + (this.state.isMobile? ' is-mobile': '')}>
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
							onSliderChanged={ this.onSliderChanged }
							onFilterClicked={ this.onFilterRemoved }
							onClearSearch={ this.clearTextSearch }
							filters={ this.state.filters }
							exportUrlCsv={ this.state.exportUrlCsv }
							exportUrlWord={ this.state.exportUrlWord }
							getFilterCount={ this.getFilterCount } 
							isMobile={ this.state.isMobile }
						/>
					}

					</div>
					<div className="col-12 col-xl-8 col-md-7 col-sm-6 results-wrapper" id="results-section">
						<div className="row">
							<div className="col">
								{ this.state.isMobile == true && this.getFilterCount() > 0 &&
									<div className="mobile-to-filters" onClick={() => this.mobileScrollToFilters()}>
										<span>Apply More Filters</span>
										<FontAwesomeIcon icon="chevron-up" />
									</div>
								}
							</div>
						</div>
						<div className="row">
							<div className="col">
								<div className="identify-header inventory-header">
									<div className="current-wrapper">
										<div className="btn btn-primary current-button" role="button"><FontAwesomeIcon icon="search-plus" /> Identify</div>
										{
										<ViewOpts
											onReset={ this.clearFilters }
											onFilterClicked={ this.onFilterRemoved }
											filters={
												Object.keys(this.state.filters).map((filterKey) => {
													return { key: filterKey, val: this.state.filters[filterKey] }
												})
											}
											getStatesByCid={ this.getStatesByCid } 
										/>
										}
															
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
			{ (this.getDynclid() > 0 || this.getClid() > 0) && this.state.isMobile == true &&
				<FilterModal 
					show={ this.state.showFilterModal }
				>
					<div className="modal-filter-content">
						<div className="filter-count">{ filterCount} filter{ filterCount > 1? 's':'' } chosen</div>
						<div 
							className="btn btn-primary current-button" 
							role="button"
							onClick={() => this.doConfirm()}
						>Filter and see results</div>
					</div>
				</FilterModal>
			}
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






