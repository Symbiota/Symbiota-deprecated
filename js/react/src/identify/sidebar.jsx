import React from "react";

import HelpButton from "../common/helpButton.jsx";
import {SearchWidget} from "../common/search.jsx";
import FeatureSelector from "./featureSelector.jsx";
import ViewOpts from "./viewOpts.jsx";

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
          {/*
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
          */
          }
        </div>
        <p>
          Start applying characteristics, and the matching plants will appear at
          right.
        </p>
      </div>
    );
  }
}


class SideBarDropdown extends React.Component {
  constructor(props) {
    super(props);
    this.state = { isExpanded: false };
    this.onButtonClicked = this.onButtonClicked.bind(this);
  }

  onButtonClicked() {
    if (this.props.disabled !== "true") {
      this.setState({isExpanded: !this.state.isExpanded});
    }
  }

  render() {
    let dropDownId = this.props.title;
    dropDownId = dropDownId.toLowerCase().replace(/[^a-z]/g, "").concat("-dropdown-body");

    return (
      <div
        className={ "my-3 py-auto" + (this.props.disabled === true ? " dropdown-disabled" : "") }
        style={ this.props.style } >
        <div className="row">
          <h4 className="mx-0 my-auto col" style={{ cursor: "default", fontSize: this.props.style.fontSize }}>
            {this.props.title}
          </h4>
          <button
            className="d-block col-sm-auto"
            data-toggle="collapse"
            data-target={ "#" + dropDownId }
            type="button"
            aria-expanded={ this.state.isExpanded.toString() }
            aria-controls={ dropDownId }
            onClick={ this.onButtonClicked }
            disabled={ this.props.disabled }
          >
            <img
              className={ "ml-auto will-v-flip" + (this.state.isExpanded ? " v-flip" : "") }
              style={{ background: "black", borderRadius: "50%", height: "2em", width: "2em" }}
              src={ `${CLIENT_ROOT}/images/garden/expand-arrow.png` }
              alt="collapse"
            />
          </button>
        </div>
        <div id={dropDownId} className="collapse">
          <div className="card card-body mt-2">
            { this.props.children }
          </div>
        </div>
      </div>
    );
  }
}

SideBarDropdown.defaultProps = {
  title: '',
  style: { padding: "1em", backgroundColor: "white", borderRadius: "0.5em", fontSize: "initial" },
};

/**
 * Full sidebar
 */
class SideBar extends React.Component {
  constructor(props) {
    super(props);

  }
  render() {  
  	let showFixedTotals = false;
  	if (this.props.totals['taxa'] < this.props.fixedTotals['taxa']) {
  		showFixedTotals = true;
  	}

    return (
      <div
        id="sidebar"
        className="m-1 p-3 rounded-border"
        style={ this.props.style }>

				<div className="currently-displayed">
					<h3>Currently displayed:</h3>
					<div className="stat">
						<div className="stat-label">Families:</div>
						<div className="stat-value">{ this.props.totals['families'] }{ showFixedTotals && <span className="fixed-totals"> (of { this.props.fixedTotals['families']})</span> }</div>
					</div>
					<div className="stat">
						<div className="stat-label">Genera:</div>
						<div className="stat-value">{ this.props.totals['genera'] }{ showFixedTotals && <span className="fixed-totals"> (of { this.props.fixedTotals['genera']})</span> }</div>
					</div>
					<div className="stat">
						<div className="stat-label">Species:</div>
						<div className="stat-value">{ this.props.totals['species'] }{ showFixedTotals && <span className="fixed-totals"> (of { this.props.fixedTotals['species']})</span> } (species rank)</div>
					</div>
					<div className="stat">
						<div className="stat-label">Total Taxa:</div>
						<div className="stat-value">{ this.props.totals['taxa'] }{ showFixedTotals && <span className="fixed-totals"> (of { this.props.fixedTotals['taxa']})</span> } (including subsp. and var.)</div>
					</div>
					{/*<div className="stat export">
						<div className="stat-label">Export:</div><div className="stat-value">W CSV P</div>
					</div>*/}
				</div>

        {/* Search */}
        <SearchWidget
          placeholder="Search plants by name"
          clientRoot={ CLIENT_ROOT }
          isLoading={ this.props.isLoading }
          textValue={ this.props.searchText }
          onTextValueChanged={ this.props.onSearchTextChanged }
          onSearch={ this.props.onSearch }
          suggestionUrl={ this.props.searchSuggestionUrl }
					clid={ this.props.clid }
        />
        
        
				<ViewOpts
					viewType={ this.props.viewType }
					sortBy={ this.props.sortBy }
					onSortByClicked={ this.props.onSortByClicked }
					onSearchNameClicked={ this.props.onViewTypeClicked }
					onViewTypeClicked={ this.props.onViewTypeClicked }
					//onFilterClicked={ this.onFilterRemoved }
					filters={
						Object.keys(this.props.filters).map((filterKey) => {
							return { key: filterKey, val: this.props.filters[filterKey] }
						})
					}
				/>
				
				{
					Object.keys(this.props.characteristics).map((idx) => {
					let firstLevel = this.props.characteristics[idx];
						return (					
		          <SideBarDropdown key={ firstLevel.hid } title={ firstLevel.headingname }>
							{
								Object.keys(firstLevel.characters).map((idx2) => {
									let secondLevel = firstLevel.characters[idx2];
									console.log(secondLevel);
									return (
										<FeatureSelector
											key={ secondLevel.cid }
											title={ secondLevel.charname }
											items={ secondLevel.states }
											/*onChange={ (featureKey) => {
												this.props.onWholePlantChanged(plantFeature, featureKey)
											}}*/
										/>
									)
								})
							}
						
          		</SideBarDropdown>
						)
					})
				}

{/*
        <div>
          <SideBarDropdown title="Whole Plant">
            {
              Object.keys(this.props.wholePlant).map((plantFeature) => {
                return (
                  <FeatureSelector
                    key={ plantFeature }
                    title={ plantFeature }
                    items={ this.props.wholePlant[plantFeature] }
                    onChange={ (featureKey) => {
                      this.props.onWholePlantChanged(plantFeature, featureKey)
                    }}
                  />
                )
              })
            }
          </SideBarDropdown>

          <SideBarDropdown title="Leaf">
            {
              Object.keys(this.props.leaf).map((plantFeature) => {
                return (
                  <FeatureSelector
                    key={ plantFeature }
                    title={ plantFeature }
                    items={ this.props.leaf[plantFeature] }
                    onChange={ (featureKey) => {
                      this.props.onLeafChanged(plantFeature, featureKey)
                    }}
                  />
                )
              })
            }
          </SideBarDropdown>

          <SideBarDropdown title="Gardening">
            {
              Object.keys(this.props.gardening).map((plantFeature) => {
                return (
                  <FeatureSelector
                    key={ plantFeature }
                    title={ plantFeature }
                    items={ this.props.gardening[plantFeature] }
                    onChange={ (featureKey) => {
                      this.props.onGardeningChanged(plantFeature, featureKey)
                    }}
                  />
                )
              })
            }
          </SideBarDropdown>

          <SideBarDropdown title="Commercial Availability (Coming soon)" disabled={ true } />

        </div>          */}
      </div>
    );
  }
}

SideBar.defaultProps = {
  sunlight: '',
  moisture: '',
  width: [0, 50],
  height: [0, 50],
  plantFeatures: {},
  growthMaintenance: {},
  beyondGarden: {},
  searchText: '',
  searchSugestionUrl: '',
  characteristics: {"hid":'',"headingname":'',"characters":{}},
  onPlantFeaturesChanged: () => {},
  onGrowthMaintenanceChanged: () => {},
  onBeyondGardenChanged: () => {}
};

export default SideBar;
