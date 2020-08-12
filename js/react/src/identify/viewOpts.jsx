import React from "react";
import IconButton from "../common/iconButton.jsx";
import CheckboxItem from "../common/checkboxItem.jsx";

const CLIENT_ROOT = "..";

class ViewOpts extends React.Component {

  constructor(props) {
    super(props);
		//this.onViewTypeClicked = this.props.onViewTypeClicked.bind(this);
		this.onSortByClicked = this.props.onSortByClicked.bind(this);
  }

  render() {
    return (
      <div id="view-opts" className="row">

        <div className="col text-right">
          <p>Show results:</p>
        </div>
        <div className="col-auto ">

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
					<div className="view-type-wrapper">
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
    );
  }
}

ViewOpts.defaultProps = {
  sortBy: "vernacularName",
  //viewType: "grid",
  filters: [],
  //checklistNames: {},
  onSortByClicked: () => {},
  //onViewTypeClicked: () => {},
  //onTaxaDetailClicked: () => {},
  onFilterClicked: () => {}
};


export default ViewOpts;