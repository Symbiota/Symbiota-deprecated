import React from "react";

import HelpButton from "../common/helpButton.jsx";
import {SearchWidget} from "../common/search.jsx";
import FeatureSelector from "./featureSelector.jsx";
//import ViewOpts from "./viewOpts.jsx";

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { library } from "@fortawesome/fontawesome-svg-core";
import {faFileCsv, faFileWord, faPrint, faChevronDown, faChevronUp, faChevronCircleDown, faChevronCircleUp, faCircle } from '@fortawesome/free-solid-svg-icons'
library.add( faFileCsv, faFileWord, faPrint, faChevronDown, faChevronUp, faChevronCircleDown, faChevronCircleUp, faCircle );


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
          	clientRoot={ this.props.clientRoot }
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
        className={ "top-level" + (this.props.disabled === true ? " dropdown-disabled" : "") }
         >
        <div className="row">
          <h4 className="col" style={{ cursor: "default" }}>
            {this.props.title}
          </h4>
        </div>
        <div id={dropDownId} className="">
          <div className="">
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
    this.state = {
      displayFilters: null,
      isMobile: null,
    };

    this.onSortByClicked = this.props.onSortByClicked.bind(this);
		this.onFilterClicked = this.props.onFilterClicked.bind(this);
		this.getFilterCount = this.props.getFilterCount.bind(this);
  }
  componentDidMount() {
  	let displayFilters = true;
		let isMobile = false;

  	if (this.state.displayFilters == null && this.props.isMobile == true) {
  		displayFilters = false;
  		isMobile = true;
  	}
		this.setState({
			displayFilters: displayFilters,
			isMobile: isMobile
		});
	};
	
	componentWillReceiveProps(nextProps) {//necessary because React doesn't set isMobile in componentDidMount grr
  	let displayFilters = this.state.displayFilters;
		let isMobile = this.state.isMobile;
		
		if (isMobile != this.props.isMobile) {
			isMobile = this.props.isMobile;
			if (isMobile == true) {
				displayFilters = false;
			}
		}
		this.setState({
			displayFilters: displayFilters,
			isMobile: isMobile
		});
		
	}
  
  toggleFilters = () => {
		let newVal = true;
		if (this.state.displayFilters == true) {
			newVal = false;
		} 
		this.setState({
			displayFilters: newVal
		});

  }  
  render() {  
  	let showFixedTotals = false;
  	if (this.props.totals['taxa'] < this.props.fixedTotals['taxa']) {
  		showFixedTotals = true;
  	}
  	
  	let filterCount = this.getFilterCount();
    return (
      <div
        id="sidebar"
        className="m-1 rounded-border"
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
					{ this.props.clid > -1 &&
						<div className="stat export">
							<div className="stat-label">Export:</div>
							<div className="stat-value"> 
								<a className={ "export-word" + (this.props.totals['taxa'] === 0 ? " disabled" : '') } 
										href={ this.props.exportUrlWord} 
										title="Download Word .doc"
								>
									<FontAwesomeIcon icon="file-word" size="2x"/> 
								</a>
								<a className={ "export-csv" + (this.props.totals['taxa'] === 0 ? " disabled" : '') } 
										href={ this.props.exportUrlCsv + "&format=csv"} 
										title="Download CSV"
								>
									<FontAwesomeIcon icon="file-csv" size="2x"/>
								</a>
								{/*<a className="export-print">
									<FontAwesomeIcon icon="print" size="2x"/>
								</a>*/}
							</div>
						</div>
					}
				</div>

        {/* Search */}
        
				<div className="filter-header" id="filter-section">
					<h3 className="filter-title">Filter Tools</h3>
					{ filterCount > 0 &&
						<span className="filter-count">
						(<span className="filter-value">{filterCount.toString() }</span>  selected)
						</span>
					}
					{ this.props.isMobile == true && this.state.displayFilters == true &&
								<span className="filter-toggle">
									<span className="fa-layers fa-fw">
										<FontAwesomeIcon className="back" icon="circle" onClick={() => this.toggleFilters()} 
										/> 
										<FontAwesomeIcon className="front" icon="chevron-circle-up" onClick={() => this.toggleFilters()} 
										/>
									</span>
								</span>
					}
					
					{ this.props.isMobile == true && this.state.displayFilters == false &&
								<span className="filter-toggle">
									Open
									<span className="fa-layers fa-fw">
										<FontAwesomeIcon className="back" icon="circle" onClick={() => this.toggleFilters()} 
										/> 
										<FontAwesomeIcon className="front" icon="chevron-circle-down" onClick={() => this.toggleFilters()} 
										/>
									</span>
								</span>
					}
					
      	</div>


					{ this.state.displayFilters == true &&
					
						<div className="filter-tools" >
							<SearchWidget
								placeholder="Search plants by name"
								clientRoot={this.props.clientRoot}
								isLoading={ this.props.isLoading }
								textValue={ this.props.searchText }
								onTextValueChanged={ this.props.onSearchTextChanged }
								onSearch={ this.props.onSearch }
								suggestionUrl={ this.props.searchSuggestionUrl }
								clid={ this.props.clid }
								dynclid={ this.props.dynclid }
								onFilterClicked={ this.onFilterClicked }
								onClearSearch={ this.props.onClearSearch }
							/>
				
				
							<div className="view-opts container row">
								<div className="row">
									<div className="opt-labels">
										<p>Display as:</p>
									</div>
									<div className="opt-settings">

										<div className="view-opt-wrapper">
											<input 
												type="radio"
												name="sortBy"
												onChange={() => {
													this.onSortByClicked("vernacularName")
												}}
												checked={this.props.sortBy === "vernacularName"}
											/> <label className="" htmlFor={ "sortBy" }>Common name</label>
										</div>
										<div className="view-opt-wrapper">
											<input 
												type="radio"
												name="sortBy"
												onChange={() => {
													this.onSortByClicked("sciName")
												}}
												checked={this.props.sortBy === "sciName"}
											/> <label className="" htmlFor={ "sortBy" }>Scientific name</label>
										</div>

									</div>{ /* opt-settings */ }
								</div>{ /* row */ }
							</div>{ /*  view-opts */ }
					
					

							<h3>Filter by characteristic</h3>
									{	this.props.characteristics &&
										Object.keys(this.props.characteristics).map((idx) => {
										let firstLevel = this.props.characteristics[idx];
											return (					
												<SideBarDropdown key={ firstLevel.hid } title={ firstLevel.headingname }>
												{
													Object.keys(firstLevel.characters).map((idx2) => {
														let secondLevel = firstLevel.characters[idx2];
														/*if (secondLevel.display == 'slider') {
															console.log(secondLevel.states);
														}*/
														return (
															<FeatureSelector
																key={ secondLevel.cid }
																cid={ secondLevel.cid }
																title={ secondLevel.charname }
																display={ secondLevel.display }
																units={ secondLevel.units }
																states={ secondLevel.states }
																attrs={ this.props.filters.attrs }
																sliders={ this.props.filters.sliders }
																clientRoot={this.props.clientRoot}
																/*onChange={ (featureKey) => {
																	this.props.onWholePlantChanged(plantFeature, featureKey)
																}}*/
																onAttrClicked={ this.props.onAttrClicked } 
																onSliderChanged={ this.props.onSliderChanged } 
															/>
														)
													})
												}

												</SideBarDropdown>
											)
										})
									}
						</div>
					}
      </div>
    );
  }
}

SideBar.defaultProps = {
  searchText: '',
  searchSugestionUrl: '',
  characteristics: {"hid":'',"headingname":'',"characters":{}},
};

export default SideBar;
