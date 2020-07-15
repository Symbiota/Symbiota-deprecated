import React from "react";

import HelpButton from "../common/helpButton.jsx";
import {SearchWidget} from "../common/search.jsx";

const CLIENT_ROOT = "..";


/**
 * Sidebar header with title, subtitle, and help
 */
class SideBarHeading extends React.Component {
  render() {
    return (
      <div style={{color: "black"}}>
        <div className="mb-1" style={{color: "inherit"}}>
          <h3 className="font-weight-bold d-inline">Search for plants</h3>
          <HelpButton
            title="Search for plants"
            html={
                    `
              <ul>
                <li>As you make selections, the filtered results are immediately displayed in “Your search results”.</li>
                <li>Any number of search options may be selected, but too many filters may yield no results because no plant meets all the criteria you selected. If so, try removing filters.</li>
                <li>To remove a search filter, simply click its close (X) button</li>
                <li>Clicking on any image in the results will open that plants’ garden profile page; the page can be downloaded and printed.</li>
              </ul>
            `
            }
          />
        </div>
        <p>
          Start applying characteristics, and the matching plants will appear at
          right.
        </p>
      </div>
    );
  }
}



/**
 * Full sidebar
 */
class SideBar extends React.Component {
  constructor(props) {
    super(props);


    //this.resetWidth = this.resetWidth.bind(this);
    //this.resetHeight = this.resetHeight.bind(this);
  }
/*
  resetWidth() {
    this.sliderRefWidth.current.reset();
  }

  resetHeight() {
    this.sliderRefHeight.current.reset();
  }
*/
  render() {
    return (
      <div
        id="sidebar"
        className="m-1 p-3 rounded-border"
        style={ this.props.style }>

        {/* Title & Subtitle */}
        <SideBarHeading />

        {/* Search */}
        <SearchWidget
          placeholder="Search plants by name"
          clientRoot={ this.props.clientRoot }
          isLoading={ this.props.isLoading }
          textValue={ this.props.searchText }
          onTextValueChanged={ this.props.onSearchTextChanged }
          onSearch={ this.props.onSearch }
          suggestionUrl={ this.props.searchSuggestionUrl }
        />

        {/* Sunlight & Moisture 
        <div style={{ background: "white" }} className="rounded-border p-4">
          <h4>Plant needs</h4>
          <PlantNeed
            label="Sunlight"
            choices={ ["Sun", "Part-Shade", "Full-Shade"] }
            value={ this.props.sunlight }
            onChange={ this.props.onSunlightChanged } />
          <PlantNeed
            label="Moisture"
            choices={ ["Dry", "Moderate", "Wet"] }
            value={ this.props.moisture }
            onChange={ this.props.onMoistureChanged } />
        </div>
				*/}

      </div>
    );
  }
}

SideBar.defaultProps = {
  searchText: '',
  searchSugestionUrl: '',
  //onPlantFeaturesChanged: () => {},
  //onGrowthMaintenanceChanged: () => {},
  //onBeyondGardenChanged: () => {}
};

export default SideBar;
