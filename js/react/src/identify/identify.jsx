"use strict";

import React from "react";
import ReactDOM from "react-dom";

import SideBar from "./sidebar.jsx";
import {IdentifySearchContainer, SearchResultContainer} from "../common/searchResults.jsx";
import ViewOpts from "./viewOpts.jsx";
import httpGet from "../common/httpGet.js";
import {addUrlQueryParam, getUrlQueryParams} from "../common/queryParams.js";
import {getCommonNameStr, getTaxaPage, getIdentifyPage} from "../common/taxaUtils";
import PageHeader from "../common/pageHeader.jsx";

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { library } from "@fortawesome/fontawesome-svg-core";
import {faChevronDown, faChevronUp, faListUl, faSearchPlus } from '@fortawesome/free-solid-svg-icons'
library.add( faChevronDown, faChevronUp, faListUl, faSearchPlus );


const CIDS_PLANT_FEATURE = {
  "flower_color": 612,
  "bloom_months": 165,
  "wildlife_support": 685,
  "lifespan": 136,
  "foliage_type": 100,
  "plant_type": 137
};

const CIDS_GROWTH_MAINTENANCE = {
  "landscape_uses": 679,
  "cultivation_preferences": 767,
  "behavior": 688,
  "propagation": 740,
  "ease_of_growth": 684
};

const CIDS_BEYOND_GARDEN = {
  "ecoregion": 19,
  "habitat": 163
};


const CIDS_WHOLE_PLANT = {
  "plant_type": 137,
  "ecoregion": 19,
  "habitat": 163,
  "groups_with_specialized_keys": 784
};
const CIDS_LEAF = {
  "leaf_type": 710,
  "leaf_arrangement": 640,
};
const CIDS_GARDENING = {
  "sunlight": 680
};





function getAttribMatrixFromArr(attribArray) {
  const attribMatrix = {};
  for (let i in attribArray) {
    let attrObj = attribArray[i];
    attribMatrix[attrObj.title] = {};
    for (let j in attrObj.values) {
      let attrVal = attrObj.values[j];
      attribMatrix[attrObj.title][attrVal] = false;
    }
  }
  return attribMatrix;
}

function filterByPlantAttribs(item, itemFilterName, filterMap) {
  let plantFeatureKeys = Object.keys(filterMap);
  let success = true;
  let iterSuccess;
  let itemFeatures = item[itemFilterName];
  // For each filter type
  for (let i in plantFeatureKeys) {
    iterSuccess = false;

    // flower_color, ecoregion, etc.
    let featureKey = plantFeatureKeys[i];

    // blue, green, Cascades, etc.
    let featureVals = filterMap[featureKey].map(item => item.toLowerCase());
    let itemVals = itemFeatures[featureKey].map(item => item.toLowerCase());

    // Is the intersection length greater than zero?
    iterSuccess = featureVals.length === 0 || featureVals.filter(item => itemVals.includes(item)).length > 0;
    success = success && iterSuccess;
  }
  return success;
}

function MainContentContainer(props) {
  return (
    <div className="container mx-auto p-4" style={{ maxWidth: "1400px" }}>
      {props.children}
    </div>
  );
}

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
        wholePlant: {},
        leaf: {},
        gardening: {}
      },
      searchText: ("search" in queryParams ? queryParams["search"] : ViewOpts.DEFAULT_SEARCH_TEXT),
      searchResults: {"familySort":{},"taxonSort":[]},
      characteristics: {},
      sortBy: ("sortBy" in queryParams ? queryParams["sortBy"] : "sciName"),
      //viewType: ("viewType" in queryParams ? queryParams["viewType"] : "list"),
      wholePlantState: {},
      leafState: {},
      gardeningState: {},
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
    this.toggleFeatureCollectionVal = this.toggleFeatureCollectionVal.bind(this);
    this.onWholePlantChanged = this.onWholePlantChanged.bind(this);
    this.onLeafChanged = this.onLeafChanged.bind(this);
    this.onGardeningChanged = this.onGardeningChanged.bind(this);
    this.updateFeatureCollectionFilters = this.updateFeatureCollectionFilters.bind(this);
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
	getAttributeArr(keymap) {
		return new Promise((resolve, reject) => {
			let pArr = [];
			let keys = Object.keys(keymap);
			for (let i in keys) {
				let attrib_key = keys[i];
				pArr.push(
					httpGet(`${this.props.clientRoot}/ident/rpc/api.php?attr=${keymap[attrib_key]}`)
						.then((res) => {
							return {
								"title": attrib_key,
								"values": JSON.parse(res)
							};
						})
				);
			}
			Promise.all(pArr).then((vals) => { resolve(vals); }).catch((err) => { reject(err); });
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
    // Load sidebar options
    Promise.all([
      this.getAttributeArr(CIDS_WHOLE_PLANT),
      this.getAttributeArr(CIDS_LEAF),
      this.getAttributeArr(CIDS_GARDENING)
    ]).then((res) => {
        const allWholePlant = res[0];
        const allLeaf = res[1];
        const allGardening = res[2];
        const newFilters = Object.assign({}, this.state.filters);
        for (let i in allWholePlant) {
          let featureKey = allWholePlant[i];
          newFilters.wholePlant[featureKey.title] = [];
        }

        for (let i in allLeaf) {
          let featureKey = allLeaf[i];
          newFilters.leaf[featureKey.title] = [];
        }

        for (let i in allGardening) {
          let featureKey = allGardening[i];
          newFilters.gardening[featureKey.title] = [];
        }

        const newWholePlant = Object.assign({}, this.state.wholePlantState, getAttribMatrixFromArr(allWholePlant));
        const newLeaf = Object.assign({}, this.state.leafState, getAttribMatrixFromArr(allLeaf));
        const newGardening = Object.assign({}, this.state.gardeningState, getAttribMatrixFromArr(allGardening));
        //console.log(newFeatures);
        this.setState({
          wholePlantState: newWholePlant,
          leafState: newLeaf,
          gardeningState: newGardening,
          filters: newFilters
        });
      }
    )
    .catch((err) => {
      console.error(err);
    });
  }

  toggleFeatureCollectionVal(featureCollection, featureCollectionFilterName, featureKey, featureVal) {
    const changeObj = {};
    const newCollection = Object.assign({}, this.state[featureCollection]);
    changeObj[featureVal] = !this.state[featureCollection][featureKey][featureVal];
    newCollection[featureKey] = Object.assign({}, newCollection[featureKey], changeObj);
    const stateObj = {};
    stateObj[featureCollection] = newCollection;
    this.setState(stateObj, () => {
      this.updateFeatureCollectionFilters(featureCollectionFilterName, featureCollection, featureKey, featureVal);
    });
  }

  updateFeatureCollectionFilters(featureCollectionFilter, featureCollectionStateName, featureKey, featureVal) {
    const isInFilters = this.state.filters[featureCollectionFilter][featureKey].includes(featureVal);
    const stateVal = this.state[featureCollectionStateName][featureKey][featureVal];
    let changed = false;
    let newFilters;
    if (stateVal && !isInFilters) {
      newFilters = Object.assign({}, this.state.filters);
      newFilters[featureCollectionFilter][featureKey].push(featureVal);
      changed = true;
    } else if (isInFilters) {
      newFilters = Object.assign({}, this.state.filters);
      newFilters[featureCollectionFilter][featureKey] = newFilters[featureCollectionFilter][featureKey].filter(
        (item) => item !== featureVal
      );
      changed = true;
    }
    if (changed) {
      this.setState({ filters: newFilters });
    }
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
      default://characteristics: plant features, etc.
      	let keyArr = key.split(":");
				switch(keyArr[0]) {
					case "wholePlant":
					  this.onWholePlantChanged(keyArr[1], text);
					  break;
					case "leaf":
					  this.onLeafChanged(keyArr[1], text);
					  break;
					case "gardening":
					  this.onGardeningChanged(keyArr[1], text);
					  break;
					default: 
						break;
				}
        break;
    }
  }

  onSearchTextChanged(e) {
    this.setState({ searchText: e.target.value });
  }

  // On search start
  onSearch(searchObj) {
    const newQueryStr = addUrlQueryParam("search", searchObj.text);
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
    url += '&name=sciname';
    url += '&clid=' + this.state.clid;
    url += '&pid=' + this.state.pid;
    url += '&synonyms=off';
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
    this.setState({ searchResults: newResults });
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

  onWholePlantChanged(featureKey, featureVal) {
    this.toggleFeatureCollectionVal("wholePlantState", "wholePlant", featureKey, featureVal);
  }

  onLeafChanged(featureKey, featureVal) {
    this.toggleFeatureCollectionVal("leafState", "leaf", featureKey, featureVal);
  }

  onGardeningChanged(featureKey, featureVal) {
    this.toggleFeatureCollectionVal("gardeningState", "gardening", featureKey, featureVal);
  }

  onSortByChanged(type) {
    this.setState({ sortBy: type },function() {
    	this.setState({ searchResults: this.sortByName(this.state.searchResults) });
    });
  }

	clearFilters() {
		let filters = {
			searchText: ViewOpts.DEFAULT_SEARCH_TEXT,
			wholePlant: {},
			leaf: {},
			gardening: {}
		};
    this.setState({ filters: filters });
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
							onSearchNameClicked={ this.onSearchNameChanged }
							onSortByClicked={ this.onSortByChanged }
							onViewTypeClicked={ this.onViewTypeChanged }
							onFilterClicked={ this.onFilterRemoved }
							
							wholePlant={ this.state.wholePlantState }
							leaf={ this.state.leafState }
							gardening={ this.state.gardeningState }
							onWholePlantChanged={ this.onWholePlantChanged }
							onLeafChanged={ this.onLeafChanged }
							onGardeningChanged={ this.onGardeningChanged }
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
								<div className="explore-header inventory-header">
									<div className="current-wrapper">
										<div className="btn btn-primary current-button" role="button"><FontAwesomeIcon icon="list-ul" /> Identify</div>
									</div>
									<div className="alt-wrapper">
										<div>Switch to</div>
										<a href={getIdentifyPage(this.props.clientRoot,this.getClid(),this.getPid())}><div className="btn btn-primary alt-button" role="button"><FontAwesomeIcon icon="search-plus" /> Identify</div></a>
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






