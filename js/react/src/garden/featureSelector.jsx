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
                    //console.log(itemKey + ":" + itemVal);
                    return (
                      <li key={ itemKey }>
                        <CheckboxItem
                          name={ itemKey }
                          value={ itemVal ? "on" : "off" }
                          onChange={ () => this.props.onChange(itemKey) }
                        />
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
  onChanged: () => {}
};

export default FeatureSelector;