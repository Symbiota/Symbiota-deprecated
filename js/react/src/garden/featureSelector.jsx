import React from "react";
import CheckboxItem from "../common/checkboxItem.jsx";

class FeatureSelector extends React.Component {
  constructor(props) {
    super(props);
    this.getDropdownId = this.getDropdownId.bind(this);
  }

  getDropdownId() {
    return `feature-selector-${this.props.title.replace(' ', '_')}`;
  }

  render() {
    return (
      <div className="second-level rounded-borders">
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
                    return (
                      <li key={ itemKey }>
                        <CheckboxItem
                          name={ itemKey }
                          value={ itemVal ? "on" : "off" }
                          onChange={ () => this.props.onChange(itemKey) }
													checked={ itemVal }
                        />
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
  onChanged: () => {}
};

export default FeatureSelector;