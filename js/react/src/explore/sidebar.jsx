import React from "react";

import HelpButton from "../common/helpButton.jsx";
import {SearchWidget} from "../common/search.jsx";
import ViewOpts from "./viewOpts.jsx";

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
        className="m-1 rounded-border"
        style={ this.props.style }>

				<div className="currently-displayed">
					<h3>Currently displayed:</h3>
					<div className="stat">
						<div className="stat-label">Families:</div><div className="stat-value">50</div>
					</div>
					<div className="stat">
						<div className="stat-label">Genera:</div><div className="stat-value">106</div>
					</div>
					<div className="stat">
						<div className="stat-label">Species:</div><div className="stat-value">121 (species rank)</div>
					</div>
					<div className="stat">
						<div className="stat-label">Total Taxa:</div><div className="stat-value">130 (including subsp. and var.)</div>
					</div>
					<div className="stat export">
						<div className="stat-label">Export:</div><div className="stat-value">W CSV P</div>
					</div>
				</div>

					{/* Search 
					<SearchWidget
						placeholder="Search plants by name"
						clientRoot={ this.props.clientRoot }
						isLoading={ this.props.isLoading }
						textValue={ this.props.searchText }
						onTextValueChanged={ this.props.onSearchTextChanged }
						onSearch={ this.props.onSearch }
						suggestionUrl={ this.props.searchSuggestionUrl }
					/>*/}

				<ViewOpts
					viewType={ this.props.viewType }
					sortBy={ this.props.sortBy }
					showTaxaDetail={ this.props.showTaxaDetail }
					onSortByClicked={ this.onSortByChanged }
					onViewTypeClicked={ this.props.onViewTypeClicked }
					onTaxaDetailClicked={ this.props.onTaxaDetailClicked }
					//onFilterClicked={ this.onFilterRemoved }
					filters={
						Object.keys(this.props.filters).map((filterKey) => {
							return { key: filterKey, val: this.props.filters[filterKey] }
						})
					}
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
