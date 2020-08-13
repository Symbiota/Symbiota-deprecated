import React from "react";
import IconButton from "../common/iconButton.jsx";

const CLIENT_ROOT = "..";

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
			<IconButton
				key={ filterKey + ":" + itemText }
				title={ itemText }
				icon={ `${CLIENT_ROOT}/images/garden/x-out.png` }
				isSelected={ true }
				style={{ margin: "0.1em" }}
				onClick={ () => { this.props.onFilterClicked(filterKey,itemText,'off'); } }
			/>
		)
	}


  render() {
  	const buttons = [];
  	            	
		Object.keys(this.props.filters).map((filterKey) => {
			let filter = this.props.filters[filterKey];
			let showItem = true;
			let itemText = "";
			let itemKey = filter.key;//override below as needed
			switch (filter.key) {
				case "searchText":
					if (filter.val === ViewOpts.DEFAULT_SEARCH_TEXT) {
						showItem = false;
					} else {
						itemText = `Search: ${filter.val}`;
						buttons.push({"key":filter.key,"text":itemText});
					}
					break;

				case "attrs": {
					Object.entries(filter.val).map((feature) => {
						if (feature[1].length) {
							buttons.push({"key":feature[0],"text":feature[1]});
						}
					})
					break;
				}
				default:
					break;
			}
		});
		if (buttons.length > 0) {
			return (
				<div id="view-opts" className="row mx-2 mt-3 px-0 py-2">
					<div className="col">
						<h3 className="font-weight-bold">Filtered by:</h3>
						<div className="d-flex flex-row flex-wrap">
							{
								buttons.map((buttonItem) => {
									let button = this.buildButton(buttonItem.key,buttonItem.text);
									return (
										button
									)
								})
							}
						</div>
					</div>

					<div className="col-auto p-0 mx-1">

						<p>
					
							<IconButton
								key={ "reset" }
								title={ "Clear all" }
								isSelected={ true }
								style={{ margin: "0.1em" }}
								onClick={ () => { this.props.onReset(); } }
							/>
							
						</p>
					</div>
				</div>
			);
		}	
	  return <span style={{ display: "none" }}/>;
  }
}

ViewOpts.defaultProps = {
  filters: [],
  onFilterClicked: () => {},
  onReset: () => {}
};

ViewOpts.DEFAULT_SEARCH_TEXT = "";
ViewOpts.DEFAULT_CLID = -1;

export default ViewOpts;