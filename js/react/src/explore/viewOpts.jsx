import React from "react";
import IconButton from "../common/iconButton.jsx";
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
      <div id="view-opts" className="row mx-2 mt-3 px-0 py-2">

        <div className="col text-right p-0 mx-1 mt-auto">
          <p>View as:</p>
          <p>Sort by name:</p>
        </div>
        <div className="col-auto p-0 mx-1 mt-auto">
          <p>
            <IconButton
              title="Grid"
              icon={`${CLIENT_ROOT}/images/garden/gridViewIcon.png`}
              onClick={() => {
                this.onViewTypeClicked("grid")
              }}
              isSelected={this.props.viewType === "grid"}
            />
            <IconButton
              title="List"
              icon={`${CLIENT_ROOT}/images/garden/listViewIcon.png`}
              onClick={() => {
                this.onViewTypeClicked("list")
              }}
              isSelected={this.props.viewType === "list"}
            />
          </p>
          <p>
						<span>
							<input 
								type="checkbox" 
								name={ "showTaxaDetail" } 
								value={ this.props.showTaxaDetail == 'on' ? "on" : "off" } 
								onChange={() => {
									this.onTaxaDetailClicked(this.props.showTaxaDetail == 'on' ? "off" : "on" )
								}}
							/>
							<label className="ml-2 align-middle" htmlFor={ "showTaxaDetail" }>{ "Vouchers & taxon authors" }</label>
						</span>
          </p>
          <p>
          	<span>
							<input 
								type="checkbox" 
								name={ "sortBy" } 
								value={ this.props.sortBy == 'taxon' ? "taxon" : "family" } 
								onChange={() => {
									this.onSortByClicked(this.props.sortBy == 'taxon' ? "family" : "taxon" )
								}}
							/>
							<label className="ml-2 align-middle" htmlFor={ "sortBy" }>{ "Alphabetical by taxon" }</label>
						</span>
          </p>
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