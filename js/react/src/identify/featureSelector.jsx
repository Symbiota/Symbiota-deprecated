import React from "react";
import CheckboxItem from "../common/checkboxItem.jsx";

class FeatureSelector extends React.Component {
  constructor(props) {
    super(props);
    this.getDropdownId = this.getDropdownId.bind(this);
    this.onAttrClicked = this.props.onAttrClicked.bind(this);
  }

  getDropdownId() {
    return `feature-selector-${this.props.cid}`;
  }

  render() {
    return (
      <div>
        <div className="feature-selectors">
          <a
            data-toggle="collapse"
            aria-expanded="false"
            aria-controls={ this.getDropdownId() }
            href={ `#${this.getDropdownId()}` }
          >
            <p style={{ fontSize: "1.1em" }}>{ this.props.title.replace(/_/g, ' ') }</p>
          </a>
          <div id={ this.getDropdownId() } className="collapse">
            <div className="card card-body">
              <ul
                className="list-unstyled"
                style={{ overflow: "hidden", whiteSpace: "nowrap" }}>
                {
                  Object.keys(this.props.items).map((itemKey) => {
                    let itemVal = this.props.items[itemKey];
                    //console.log(itemVal);
                    let attr = itemVal.cid + '-' + itemVal.cs;
                    //console.log(this.props.attrs);
                    let checked = (this.props.attrs[attr] ? true: false );
                    return (
                      <li key={ attr }>
                        <span>
													<input 
														type="checkbox" 
														name={ attr } 
														value={ 'on' } 
														checked={ checked? 'checked' : '' }
														onChange={() => {
															this.onAttrClicked(attr,itemVal.charstatename,(checked? 'off':'on'))
														}}
													/>
													<label className="ml-2 align-middle" htmlFor={ attr }>{ itemVal.charstatename }</label>
												</span>
                        
                      </li>
                    )
                  })
                }
              </ul>
            </div>
          </div>
        </div>
      </div>
    );
  }
}

FeatureSelector.defaultProps = {
  items: [],
  onAttrClicked: () => {}
};

export default FeatureSelector;