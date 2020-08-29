import React from "react";
import {IconButton, CancelButton} from "../common/iconButton.jsx";

function arrayCompare(a1, a2) {
  if (a1.length !== a2.length) {
    return false;
  }
  for (let i in a1) {
    if (a1[i] !== a2[i]) {
      return false;
    }
  }
  return true;
}

function getPlantAttrText(filter) {
  const attrKeys = Object.keys(filter.val);
  let itemText = [];
  for (let i in attrKeys) {
    let attrKey = attrKeys[i];
    if (filter.val[attrKey].length > 0) {
      itemText.push(`${attrKey.replace(/_/g, ' ')}: ${filter.val[attrKey].join(', ')}`);
    }
  }
	
  return itemText.join("<br />");
}

class ViewOpts extends React.Component {
	
	buildButton(filterKey,itemText) {
		return (
			<CancelButton
				key={ filterKey + ":" + itemText }
				title={ itemText }
				isSelected={ true }
				style={{ margin: "0.1em" }}
				onClick={ () => { this.props.onFilterClicked(filterKey,itemText); } }
			/>
		)
	}


  render() {
  	const buttons = [];
  	            	
		this.props.filters.map((filter) => {
			let showItem = true;
			let itemText = "";
			let itemKey = filter.key;//override below as needed
			switch (filter.key) {
				case "sunlight":
					if (filter.val === ViewOpts.DEFAULT_SUNLIGHT) {
						showItem = false;
					} else {
						itemText = `Sunlight: ${filter.val}`;
						buttons.push({"key":filter.key,"text":itemText});
					}
					break;
				case "moisture":
					if (filter.val === ViewOpts.DEFAULT_MOISTURE) {
						showItem = false;
					} else {
						itemText = `Moisture: ${filter.val}`;
						buttons.push({"key":filter.key,"text":itemText});
					}
					break;
				case "width":
					if (arrayCompare(filter.val, ViewOpts.DEFAULT_WIDTH)) {
						showItem = false;
					} else {
						itemText = `Plant Width: ${filter.val[0]}ft to ${filter.val[1]}ft`;
						buttons.push({"key":filter.key,"text":itemText});
					}
					break;
				case "height":
					if (arrayCompare(filter.val, ViewOpts.DEFAULT_HEIGHT)) {
						showItem = false;
					} else {
						itemText = `Plant Height: ${filter.val[0]}ft to ${filter.val[1]}ft`;
						buttons.push({"key":filter.key,"text":itemText});
					}
					break;
				case "searchText":
					if (filter.val === ViewOpts.DEFAULT_SEARCH_TEXT) {
						showItem = false;
					} else {
						itemText = `Search: ${filter.val}`;
						buttons.push({"key":filter.key,"text":itemText});
					}
					break;
				case "checklistId":
					if (filter.val === ViewOpts.DEFAULT_CLID) {
						showItem = false;
					} else {
						itemText = (
							filter.val in this.props.checklistNames ?
								`Checklist: ${this.props.checklistNames[filter.val]}` :
								''
						);
						buttons.push({"key":filter.key,"text":itemText});
					}
					break;
				case "plantFeatures": {
					Object.entries(filter.val).map((feature) => {
						if (feature[1].length) {
							feature[1].map((value) => {
								let featureKey = filter.key + ":" + feature[0];
								buttons.push({"key":featureKey,"text":value});
							})
						}
					})
					break;
				}
				case "growthMaintenance": {
					Object.entries(filter.val).map((feature) => {
						if (feature[1].length) {
							feature[1].map((value) => {
								let featureKey = filter.key + ":" + feature[0];
								buttons.push({"key":featureKey,"text":value});
							})
						}
					})
					break;
				}
				case "beyondGarden": {
					Object.entries(filter.val).map((feature) => {
						if (feature[1].length) {
							feature[1].map((value) => {
								let featureKey = filter.key + ":" + feature[0];
								buttons.push({"key":featureKey,"text":value});
							})
						}
					})
					break;
				}
				default:
					break;
			}
		});

    return (
      <div id="view-opts" className="row">
        <div className="col-8">
          <h3 className="font-weight-bold">Your search results:</h3>
          <div className="d-flex flex-row flex-wrap">
						{
							buttons.length == 0 &&
							<p className="no-results">No filters applied yet, so showing all native plants</p>
						}
						{
							buttons.length > 0 &&
								buttons.map((buttonItem) => {
									let button = this.buildButton(buttonItem.key,buttonItem.text);
									return (
										button
									)
								})
						}
						{
							buttons.length > 0 &&
							
								<CancelButton
									key={ "reset" }
									title={ "Clear all" }
									isSelected={ true }
									style={{ margin: "0.1em", textTransform: "uppercase", backgroundColor: "#5FB021", color: "white", border: "1px solid #999999" }}
									onClick={ () => { this.props.onReset(); } }
								/>
						}
			
          </div>
        </div>
        <div className="col-4 pt-2 container settings">
       		<div className="row mb-2">
       			<div className="col-5 text-right p-0 pr-2 pt-1">
          		View as:
          	</div>
       			<div className="col-7 p-0">
      
								<IconButton
									title="Grid"
									icon={`${this.props.clientRoot}/images/garden/gridViewIcon.png`}
									onClick={() => {
										this.props.onViewTypeClicked("grid")
									}}
									isSelected={this.props.viewType === "grid"}
								/>
								<IconButton
									title="List"
									icon={`${this.props.clientRoot}/images/garden/listViewIcon.png`}
									onClick={() => {
										this.props.onViewTypeClicked("list")
									}}
									isSelected={this.props.viewType === "list"}
								/>
	
						
          	</div>
          	
          </div>
          
       		<div className="row mb-2">
       			<div className="col-5 text-right p-0 pr-2 pt-1">
          		Sort by name:  
          	</div>
       			<div className="col-7 p-0">      	
		
								<IconButton
									title="Common"
									onClick={() => {
										this.props.onSortByClicked("vernacularName")
									}}
									isSelected={this.props.sortBy === "vernacularName"}
								/>
								<IconButton
									title="Scientific"
									onClick={() => {
										this.props.onSortByClicked("sciName")
									}}
									isSelected={this.props.sortBy === "sciName"}
								/>
						
						</div>
					</div>
        </div>
      </div>
    );
  }
}

ViewOpts.defaultProps = {
  sortBy: "vernacularName",
  viewType: "grid",
  filters: [],
  checklistNames: {},
  onSortByClicked: () => {},
  onViewTypeClicked: () => {},
  onFilterClicked: () => {},
  onReset: () => {}
};

ViewOpts.DEFAULT_SUNLIGHT = "";
ViewOpts.DEFAULT_MOISTURE = "";
ViewOpts.DEFAULT_WIDTH = [0, 50];
ViewOpts.DEFAULT_HEIGHT = [0, 50];
ViewOpts.DEFAULT_SEARCH_TEXT = "";
ViewOpts.DEFAULT_CLID = -1;

export default ViewOpts;