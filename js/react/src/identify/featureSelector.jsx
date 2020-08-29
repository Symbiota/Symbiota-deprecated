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
      <div className="second-level">
        <div className="feature-selectors">
          <a
            data-toggle="collapse"
            aria-expanded="false"
            aria-controls={ this.getDropdownId() }
            href={ `#${this.getDropdownId()}` }
          >
            <span>{ this.props.title.replace(/_/g, ' ') }</span>
            
            <img
              className={ "will-v-flip" }
              src={ `${this.props.clientRoot}/images/garden/expand-arrow.png` }
              alt="collapse"
            />
          </a>
          <div id={ this.getDropdownId() } className="collapse">

              <ul
                className="list-unstyled"
                style={{ overflow: "hidden", whiteSpace: "nowrap" }}>
                {
                  Object.keys(this.props.items).map((itemKey) => {
                    let itemVal = this.props.items[itemKey];
                    let attr = itemVal.cid + '-' + itemVal.cs;
                    let checked = (this.props.attrs[attr] ? true: false );
                    return (
                      <li key={ attr }>
                        
													<input 
														type="checkbox" 
														name={ attr } 
														value={ 'on' } 
														checked={ checked? 'checked' : '' }
														onChange={() => {
															this.onAttrClicked(attr,itemVal.charstatename,(checked? 'off':'on'))
														}}
													/>
													<label htmlFor={ attr }>{ itemVal.charstatename }</label>
									
                        
                      </li>
                    )
                  })
                }
              </ul>
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