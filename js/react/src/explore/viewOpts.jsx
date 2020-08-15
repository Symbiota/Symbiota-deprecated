import React from "react";
import CheckboxItem from "../common/checkboxItem.jsx";

const CLIENT_ROOT = "..";

class ViewOpts extends React.Component {

  constructor(props) {
    super(props);
		this.onViewTypeClicked = this.props.onViewTypeClicked.bind(this);
		this.onTaxaDetailClicked = this.props.onTaxaDetailClicked.bind(this);
		this.onSortByClicked = this.props.onSortByClicked.bind(this);
  }

  render() {
    return (
      <div className="container row">
				<div className="row">
					<div className="opt-labels">
						<p>Show results:</p>
					</div>
        <div className="opt-settings ">

					<div className="view-opt-wrapper">
					<input 
						type="radio"
						name="viewType"
						onChange={() => {
							this.onViewTypeClicked("grid")
						}}
						checked={this.props.viewType === "grid"}
					/> <label className="" htmlFor={ "viewType" }>As images</label>
					</div>
					<div className="view-opt-wrapper">
					<input 
						type="radio"
						name="viewType"
						onChange={() => {
							this.onViewTypeClicked("list")
						}}
						checked={this.props.viewType === "list"}
					/> <label className="" htmlFor={ "viewType" }>As list</label>
					</div>

					<div className="view-opt-wrapper">
							<input 
								type="checkbox" 
								name={ "sortBy" } 
								value={ this.props.sortBy == 'taxon' ? "taxon" : "family" } 
								onChange={() => {
									this.onSortByClicked(this.props.sortBy == 'taxon' ? "family" : "taxon" )
								}}
							/>
							<label className="" htmlFor={ "sortBy" }>{ "Alphabetical by taxon" }</label>
          </div>
					<div className="view-opt-wrapper">
							<input 
								type="checkbox" 
								name={ "showTaxaDetail" } 
								value={ this.props.showTaxaDetail == 'on' ? "on" : "off" } 
								onChange={() => {
									this.onTaxaDetailClicked(this.props.showTaxaDetail == 'on' ? "off" : "on" )
								}}
								disabled={ this.props.viewType === 'grid'? true : false }
								checked={ this.props.showTaxaDetail == 'on' }
							/>
							<label className="" htmlFor={ "showTaxaDetail" }>{ "Vouchers & taxon authors" }</label>
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
  onTaxaDetailClicked: () => {},
  onFilterClicked: () => {}
};

ViewOpts.DEFAULT_SEARCH_TEXT = "";
ViewOpts.DEFAULT_CLID = -1;

export default ViewOpts;