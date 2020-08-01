"use strict";

import React from "react";
import ReactDOM from "react-dom";

import InfographicDropdown from "./infographicDropdown.jsx";
import SideBar from "./sidebar.jsx";
import {SearchResult, SearchResultContainer} from "../common/searchResults.jsx";
import CannedSearchContainer from "./cannedSearches.jsx";
import ViewOpts from "./viewOpts.jsx";
import httpGet from "../common/httpGet.js";
import {addUrlQueryParam, getUrlQueryParams} from "../common/queryParams.js";
import {getCommonNameStr, getGardenTaxaPage} from "../common/taxaUtils";


const CLIENT_ROOT = "..";

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

function getAttributeArr(keymap) {
  return new Promise((resolve, reject) => {
    let pArr = [];
    let keys = Object.keys(keymap);
    for (let i in keys) {
      let attrib_key = keys[i];
      pArr.push(
        httpGet(`./rpc/api.php?attr=${keymap[attrib_key]}`)
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

function filterByWidth(item, minMax) {
	let ret = false;

	if (	( 0 == item.width.length)
				|| ( minMax[0] <= item.width[0] && item.width[0] <= minMax[1] )//item min is between user min and max
				|| ( minMax[0] <= item.width[1] && item.width[1] <= minMax[1] )//item max is between user min and max
				|| minMax[1] === 50 && minMax[1] <= item.width[1]) {//user max == 50 and item max >= 50
		ret = true;	
	}
		
  return ret;
}

function filterByHeight(item, minMax) {
  let ret = false;
	
	if (	( 0 == item.height.length)
				|| 	( minMax[0] <= item.height[0] && item.height[0] <= minMax[1] )//item min is between user min and max
				|| ( minMax[0] <= item.height[1] && item.height[1] <= minMax[1] )//item max is between user min and max
				|| minMax[1] === 50 && minMax[1] <= item.height[1]) {//user max == 50 and item max >= 50
		ret = true;	
	}
				
  return ret;
}

function filterBySunlight(item, sunlight) {
  switch (sunlight) {
    case "sun":
      return item.sunlight.includes("sun");
    case "part-shade":
      return item.sunlight.includes("part shade");
    case "full-shade":
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

function filterByChecklist(item, clid) {
  return clid === -1 || item.checklists.includes(clid);
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

class GardenPageApp extends React.Component {
  constructor(props) {
    super(props);
    const queryParams = getUrlQueryParams(window.location.search);

    // TODO: searchText is both a core state value and a state.filters value; How can we make the filtering system more efficient?
    this.state = {
      isLoading: false,
      filters: {
        sunlight: ("sunlight" in queryParams ? queryParams["sunlight"] : ViewOpts.DEFAULT_SUNLIGHT),
        moisture: ("moisture" in queryParams ? queryParams["moisture"] : ViewOpts.DEFAULT_MOISTURE),
        height: ("height" in queryParams ? queryParams["height"].split(",").map((i) => parseInt(i)) : ViewOpts.DEFAULT_HEIGHT),
        width: ("width" in queryParams ? queryParams["width"].split(",").map((i) => parseInt(i)) : ViewOpts.DEFAULT_WIDTH),
        searchText: ("search" in queryParams ? queryParams["search"] : ViewOpts.DEFAULT_SEARCH_TEXT),
        checklistId: ("clid" in queryParams ? parseInt(queryParams["clid"]) : ViewOpts.DEFAULT_CLID),
        plantFeatures: {},
        growthMaintenance: {},
        beyondGarden: {}
      },
      searchText: ("search" in queryParams ? queryParams["search"] : ViewOpts.DEFAULT_SEARCH_TEXT),
      searchResults: [],
      cannedSearches: [],
      sortBy: ("sortBy" in queryParams ? queryParams["sortBy"] : "vernacularName"),
      viewType: ("viewType" in queryParams ? queryParams["viewType"] : "grid"),
      plantFeatureState: {},
      growthMaintenanceState: {},
      beyondGardenState: {}
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
    this.onCannedFilter = this.onCannedFilter.bind(this);
    this.clearFilters = this.clearFilters.bind(this);
    this.toggleFeatureCollectionVal = this.toggleFeatureCollectionVal.bind(this);
    this.onPlantFeaturesChanged = this.onPlantFeaturesChanged.bind(this);
    this.onGrowthMaintenanceChanged = this.onGrowthMaintenanceChanged.bind(this);
    this.onBeyondGardenChanged = this.onBeyondGardenChanged.bind(this);
    this.updateFeatureCollectionFilters = this.updateFeatureCollectionFilters.bind(this);
  }

  componentDidMount() {
    // Load canned searches
    httpGet(`${CLIENT_ROOT}/garden/rpc/api.php?canned=true`)
      .then((res) => {
        this.setState({ cannedSearches: JSON.parse(res) });
      });

    // Load search results
    this.onSearch({ text: this.state.searchText });

    // Load sidebar options
    Promise.all([
      getAttributeArr(CIDS_PLANT_FEATURE),
      getAttributeArr(CIDS_GROWTH_MAINTENANCE),
      getAttributeArr(CIDS_BEYOND_GARDEN)
    ]).then((res) => {
        const allPlantFeatures = res[0];
        const allGrowthMaintainence = res[1];
        const allBeyondGarden = res[2];
        const newFilters = Object.assign({}, this.state.filters);
        for (let i in allPlantFeatures) {
          let featureKey = allPlantFeatures[i];
          newFilters.plantFeatures[featureKey.title] = [];
        }

        for (let i in allGrowthMaintainence) {
          let featureKey = allGrowthMaintainence[i];
          newFilters.growthMaintenance[featureKey.title] = [];
        }

        for (let i in allBeyondGarden) {
          let featureKey = allBeyondGarden[i];
          newFilters.beyondGarden[featureKey.title] = [];
        }

        const newFeatures = Object.assign({}, this.state.plantFeatureState, getAttribMatrixFromArr(allPlantFeatures));
        const newGrowth = Object.assign({}, this.state.growthMaintenanceState, getAttribMatrixFromArr(allGrowthMaintainence));
        const newBeyond = Object.assign({}, this.state.beyondGardenState, getAttribMatrixFromArr(allBeyondGarden));
        //console.log(newFeatures);
        this.setState({
          plantFeatureState: newFeatures,
          growthMaintenanceState: newGrowth,
          beyondGardenState: newBeyond,
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
  	const characteristics = ["plantFeatures","growthMaintenance","beyondGarden"];
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
        this.setState({
          searchText: ViewOpts.DEFAULT_SEARCH_TEXT },
          () => this.onSearch({ text: ViewOpts.DEFAULT_SEARCH_TEXT, value: -1 })
        );
        break;
      case "checklistId":
        this.onCannedFilter(ViewOpts.DEFAULT_CLID);
        break;
      default://characteristics: plant features, etc.
      	let keyArr = key.split(":");
				switch(keyArr[0]) {
					case "plantFeatures":
					  this.onPlantFeaturesChanged(keyArr[1], text);
					  break;
					case "growthMaintenance":
					  this.onGrowthMaintenanceChanged(keyArr[1], text);
					  break;
					case "beyondGarden":
					  this.onBeyondGardenChanged(keyArr[1], text);
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
    httpGet(`${CLIENT_ROOT}/garden/rpc/api.php?search=${searchObj.text}`)
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

  onSunlightChanged(event) {
    this.setState({ filters: Object.assign({}, this.state.filters, { sunlight: event.target.value }) });
    let newQueryStr = addUrlQueryParam("sunlight", event.target.value);
    /*window.history.replaceState({ query: newQueryStr }, '', window.location.pathname + newQueryStr);*/
  }

  onMoistureChanged(event) {
    this.setState({ filters: Object.assign({}, this.state.filters, { moisture: event.target.value }) });
    let newQueryStr = addUrlQueryParam("moisture", event.target.value);
    /*window.history.replaceState({ query: newQueryStr }, '', window.location.pathname + newQueryStr);*/
  }

  onHeightChanged(event) {
    this.setState({ filters: Object.assign({}, this.state.filters, { height: event.target.value }) });
    let newQueryStr = '';

    if (event.target.value[0] === 0 && event.target.value[1] === 50) {
      newQueryStr = addUrlQueryParam("height", '');
    } else {
      newQueryStr = addUrlQueryParam("height", event.target.value);
    }

    /*window.history.replaceState(
      {query: newQueryStr},
      '',
      window.location.pathname + newQueryStr
    );*/
  }

  onWidthChanged(event) {
    this.setState({ filters: Object.assign({}, this.state.filters, { width: event.target.value }) });
    let newQueryStr = '';

    if (event.target.value[0] === 0 && event.target.value[1] === 50) {
      newQueryStr = addUrlQueryParam("width", '');
    } else {
      newQueryStr = addUrlQueryParam("width", event.target.value);
    }
    /*window.history.replaceState(
      {query: newQueryStr},
      '',
      window.location.pathname + newQueryStr
    );*/
  }

  onPlantFeaturesChanged(featureKey, featureVal) {
    this.toggleFeatureCollectionVal("plantFeatureState", "plantFeatures", featureKey, featureVal);
  }

  onGrowthMaintenanceChanged(featureKey, featureVal) {
    this.toggleFeatureCollectionVal("growthMaintenanceState", "growthMaintenance", featureKey, featureVal);
  }

  onBeyondGardenChanged(featureKey, featureVal) {
    this.toggleFeatureCollectionVal("beyondGardenState", "beyondGarden", featureKey, featureVal);
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
    /*window.history.replaceState({query: newQueryStr}, '', window.location.pathname + newQueryStr);*/
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
    /*window.history.replaceState({ query: newQueryStr }, '', window.location.pathname + newQueryStr);*/
  }

  onCannedFilter(clid) {
    this.setState({ filters: Object.assign({}, this.state.filters, { checklistId: clid }) });
    if (clid === -1) {
      clid = '';
    }
    let newQueryStr = addUrlQueryParam("clid", clid);
    /*window.history.replaceState({ query: newQueryStr }, '', window.location.pathname + newQueryStr);*/
  }
	clearFilters() {
		let filters = {
			sunlight: ViewOpts.DEFAULT_SUNLIGHT,
			moisture: ViewOpts.DEFAULT_MOISTURE,
			height: ViewOpts.DEFAULT_HEIGHT,
			width: ViewOpts.DEFAULT_WIDTH,
			searchText: ViewOpts.DEFAULT_SEARCH_TEXT,
			checklistId: ViewOpts.DEFAULT_CLID,
			plantFeatures: {},
			growthMaintenance: {},
			beyondGarden: {}
		};
    this.setState({ filters: filters });
	}
  render() {
    const checkListMap = {};
    for (let i in this.state.cannedSearches) {
      let search = this.state.cannedSearches[i];
      checkListMap[search.clid] = search.name;
    }
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
                plantFeatures={ this.state.plantFeatureState }
                growthMaintenance={ this.state.growthMaintenanceState }
                beyondGarden={ this.state.beyondGardenState }
                searchText={ this.state.searchText }
                searchSuggestionUrl="./rpc/autofillsearch.php"
                onSearch={ this.onSearch }
                onSearchTextChanged={ this.onSearchTextChanged }
                onSunlightChanged={ this.onSunlightChanged }
                onMoistureChanged={ this.onMoistureChanged }
                onHeightChanged={ this.onHeightChanged }
                onWidthChanged={ this.onWidthChanged }
                onPlantFeaturesChanged={ this.onPlantFeaturesChanged }
                onGrowthMaintenanceChanged={ this.onGrowthMaintenanceChanged }
                onBeyondGardenChanged={ this.onBeyondGardenChanged }
              />
            </div>
            <div className="col">
              <div className="row">
                <div className="col">
                  <CannedSearchContainer
                    searches={ this.state.cannedSearches }
                    onFilter={ this.onCannedFilter }
                  />
                </div>
              </div>
              <div className="row">
                <div className="col">
                  <ViewOpts
                    viewType={ this.state.viewType }
                    sortBy={ this.state.sortBy }
                    onSortByClicked={ this.onSortByChanged }
                    onViewTypeClicked={ this.onViewTypeChanged }
                    onReset={ this.clearFilters }
                    onFilterClicked={ this.onFilterRemoved }
                    checklistNames={ checkListMap }
                    filters={
                      Object.keys(this.state.filters).map((filterKey) => {
                        return { key: filterKey, val: this.state.filters[filterKey] }
                      })
                    }
                  />
                  <SearchResultContainer viewType={ this.state.viewType }>
                    {
                      this.state.searchResults.map((result) =>  {
                        let filterChecklist = filterByChecklist(result, this.state.filters.checklistId);
                        let filterWidth = filterByWidth(result, this.state.filters.width);
                        let filterHeight = filterByHeight(result, this.state.filters.height);
                        let filterSunlight = filterBySunlight(result, this.state.filters.sunlight);
                        let filterMoisture = filterByMoisture(result, this.state.filters.moisture);
                        let filterFeatures = filterByPlantAttribs(result, "features", this.state.filters.plantFeatures);
                        let filterGrowthMaint = filterByPlantAttribs(result, "growth_maintenance", this.state.filters.growthMaintenance);
                        let filterBeyondGarden = filterByPlantAttribs(result, "beyond_garden", this.state.filters.beyondGarden);
                        let showResult = (
                          filterChecklist &&
                          filterWidth &&
                          filterHeight &&
                          filterSunlight &&
                          filterMoisture &&
                          filterFeatures &&
                          filterBeyondGarden &&
                          filterGrowthMaint
                        );
                        return (
                          <SearchResult
                            key={ result.tid }
                            viewType={ this.state.viewType }
                            display={ showResult }
                            href={ getGardenTaxaPage(CLIENT_ROOT, result.tid) }
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

const domContainer = document.getElementById("react-garden");
ReactDOM.render(<GardenPageApp />, domContainer);
