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
		this.onSearchNameClicked = this.props.onSearchNameClicked.bind(this);
		this.onSearchSynonymsClicked = this.props.onSearchSynonymsClicked.bind(this);
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
					{/*<div className="stat export">
						<div className="stat-label">Export:</div><div className="stat-value">W CSV P</div>
					</div>*/}
				</div>

					{
					<SearchWidget
						placeholder="Search this checklist"
						clientRoot={ this.props.clientRoot }
						isLoading={ this.props.isLoading }
						textValue={ this.props.searchText }
						onTextValueChanged={ this.props.onSearchTextChanged }
						onSearch={ this.props.onSearch }
						suggestionUrl={ this.props.searchSuggestionUrl }
						clid={ this.props.clid }
						searchName={ this.props.searchName }
					/>
					}
	
					<div id="view-opts-search" className="container row">
						<div className="row">
							<div className="col text-right">
								<p>Search:</p>
							</div>
							<div className="col-auto">
								
								<div className="view-opt-wrapper">
									<input 
										type="radio"
										name={ "searchname" }
										value={ "sciname" }
										onChange={() => {
											this.onSearchNameClicked("sciname")
										}}
										checked={this.props.searchName === 'sciname'? true: false}
									
									/> Scientific Names
								</div>
								
								<div className="view-opt-wrapper">
									<input 
										type="radio"
										name={ "searchname" }
										value={ "commonname" }
										onChange={() => {
											this.onSearchNameClicked("commonname")
										}}
										checked={this.props.searchName === 'commonname'? true: false}
									/> Common Names
								</div>
							</div>
						</div>
						
						
						<div className="row">
							<div className="col text-right">
								<p>Include:</p>
							</div>
							<div className="col-auto">

										<input 
											type="checkbox" 
											name={ "searchSynonyms" } 
											value={ this.props.searchSynonyms == 'on' ? "on" : "off" } 
											onChange={() => {
												this.onSearchSynonymsClicked(this.props.searchSynonyms == 'on' ? "off" : "on" )
											}}
											checked={this.props.searchSynonyms === 'on'? true: false}
										/>
										<label className="ml-2 align-middle" htmlFor={ "searchSynonyms" }>{ "Synonyms" }</label>
							</div>
						</div>
					</div>
					
					
					

				<ViewOpts
					viewType={ this.props.viewType }
					sortBy={ this.props.sortBy }
					showTaxaDetail={ this.props.showTaxaDetail }
					onSortByClicked={ this.props.onSortByClicked }
					onSearchNameClicked={ this.props.onViewTypeClicked }
					onSearchSynonymsClicked={ this.props.onSearchSynonymsClicked }
					onViewTypeClicked={ this.props.onViewTypeClicked }
					onTaxaDetailClicked={ this.props.onTaxaDetailClicked }
					//onFilterClicked={ this.onFilterRemoved }
					filters={
						Object.keys(this.props.filters).map((filterKey) => {
							return { key: filterKey, val: this.props.filters[filterKey] }
						})
					}
				/>
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
