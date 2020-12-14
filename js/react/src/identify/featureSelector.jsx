import React from "react";
import CheckboxItem from "../common/checkboxItem.jsx";


class CheckboxList extends React.Component {
  constructor(props) {
    super(props);
    //this.getDropdownId = this.getDropdownId.bind(this);
    this.onAttrClicked = this.props.onAttrClicked.bind(this);
  }

	render() {
		return (
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
	
		);
	}
}
/**
 * Slider from 0, 50+ with minimum and maximum value handles
 */
class PlantSlider extends React.Component {
  constructor(props) {
    super(props);
    this.state = { description: "(Any size)" };
    this.slider = null;
    this.registerSliderEvent = this.registerSliderEvent.bind(this);
    this.reset = this.reset.bind(this);
  }

  componentDidMount() {
    const sliderId = `slider-container-${this.props.name}`;
    this.slider = new Slider(`#${sliderId}`, {
      value: this.props.value.map((i) => parseInt(i)),
      ticks: [0, 10, 20, 30, 40, 50],
      ticks_labels: ["0", "", "", "", "", "50+"],
      ticks_snap_bounds: 1
    });

    this.registerSliderEvent();
  }

  componentWillUnmount() {
    this.slider.destroy();
    this.slider = null;
  }

  registerSliderEvent() {
    if (this.props.onChange) {
      const onChangeEvent = this.props.onChange;
      this.slider.off("slide");
      this.slider.on("slide", (sliderArray) => {
        this.setState({ description: getSliderDescription(sliderArray) });
        const fakeEvent = { target: { value: sliderArray } };
        onChangeEvent(fakeEvent);
      });
    }
  }

  reset() {
    if (this.slider !== null) {
      this.slider.refresh();
      this.setState({ description: getSliderDescription(PlantSlider.defaultProps.value) });
      this.registerSliderEvent();
    }
  }

  render() {
    return (
      <div>
        <label className="d-block text-center" htmlFor={ this.props.name }>{ this.props.label }</label>
        <input
          id={ "slider-container-" + this.props.name }
          type="text"
          className="bootstrap-slider"
          name={ this.props.name }
          onChange={ (e) => this.props.onChange(e) }
        />
        <label className="d-block text-center any-size" htmlFor={ this.props.name }>
          { this.state.description }
        </label>
      </div>
    );
  }
}

PlantSlider.defaultProps = {
  value: [0, 50]
};


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
      <div className="second-level rounded-border">
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
						{this.props.display == 'slider'?
							<PlantSlider
							
							
							/>
							
							:
					
							<CheckboxList 
								items={ this.props.items }
								attrs={ this.props.attrs }
								onAttrClicked={ this.onAttrClicked }
							/>
						}
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