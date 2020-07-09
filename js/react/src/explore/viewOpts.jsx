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



class ViewOpts extends React.Component {
  render() {
    return (
      <div id="view-opts" className="row mx-2 mt-3 px-0 py-2">
        <div className="col">
          <h3 className="font-weight-bold">Your search results:</h3>
          <div className="d-flex flex-row flex-wrap">
            {
              this.props.filters.map((filter) => {
                let showItem = true;
                let itemText = "";
                switch (filter.key) {
                  case "searchText":
                    if (filter.val === ViewOpts.DEFAULT_SEARCH_TEXT) {
                      showItem = false;
                    } else {
                      itemText = `Search: ${filter.val}`;
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
                    }
                    break;
                  default:
                    break;
                }

                if (showItem) {
                  return (
                    <IconButton
                      key={ filter.key }
                      title={ itemText }
                      icon={ `${CLIENT_ROOT}/images/garden/x-out.png` }
                      isSelected={ true }
                      style={{ margin: "0.1em" }}
                      onClick={ () => { this.props.onFilterClicked(filter.key); } }
                    />
                  );
                }
              })
            }
          </div>
        </div>
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
                this.props.onViewTypeClicked("grid")
              }}
              isSelected={this.props.viewType === "grid"}
            />
            <IconButton
              title="List"
              icon={`${CLIENT_ROOT}/images/garden/listViewIcon.png`}
              onClick={() => {
                this.props.onViewTypeClicked("list")
              }}
              isSelected={this.props.viewType === "list"}
            />
          </p>
          <p>
            <IconButton
              title="Common Name"
              onClick={() => {
                this.props.onSortByClicked("vernacularName")
              }}
              isSelected={this.props.sortBy === "vernacularName"}
            />
            <IconButton
              title="Scientific Name"
              onClick={() => {
                this.props.onSortByClicked("sciName")
              }}
              isSelected={this.props.sortBy === "sciName"}
            />
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
  onFilterClicked: () => {}
};

ViewOpts.DEFAULT_SEARCH_TEXT = "";
ViewOpts.DEFAULT_CLID = -1;

export default ViewOpts;