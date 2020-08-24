import React from "react";

import HelpButton from "../common/helpButton.jsx";
import {SearchWidget} from "../common/search.jsx";
import FeatureSelector from "./featureSelector.jsx";
import ViewOpts from "./viewOpts.jsx";

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { library } from "@fortawesome/fontawesome-svg-core";
import {faFileCsv, faFileWord, faPrint } from '@fortawesome/free-solid-svg-icons'
library.add( faFileCsv, faFileWord, faPrint );


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

    this.onSortByClicked = this.props.onSortByClicked.bind(this);
  }
  render() {  
  	let showFixedTotals = false;
  	if (this.props.totals['taxa'] < this.props.fixedTotals['taxa']) {
  		showFixedTotals = true;
  	}

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
				</div>

        {/* Search */}
        
				<div className="filter-tools">
					<h3>Filter Tools</h3>
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
					/>
        
        
					<div className="view-opts" className="container row">
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

							</div>
						</div>
					</div>
      	</div>
      	
				<div className="filter-tools">
					<h3>Filter by characteristic</h3>
							{	this.props.characteristics &&
								Object.keys(this.props.characteristics).map((idx) => {
								let firstLevel = this.props.characteristics[idx];
									return (					
										<SideBarDropdown key={ firstLevel.hid } title={ firstLevel.headingname }>
										{
											Object.keys(firstLevel.characters).map((idx2) => {
												let secondLevel = firstLevel.characters[idx2];
												//console.log(secondLevel);
												return (
													<FeatureSelector
														key={ secondLevel.cid }
														cid={ secondLevel.cid }
														title={ secondLevel.charname }
														items={ secondLevel.states }
														attrs={ this.props.filters.attrs }
														clientRoot={this.props.clientRoot}
														/*onChange={ (featureKey) => {
															this.props.onWholePlantChanged(plantFeature, featureKey)
														}}*/
														onAttrClicked={ this.props.onAttrClicked } 
													/>
												)
											})
										}
						
										</SideBarDropdown>
									)
								})
							}
      	</div>
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
