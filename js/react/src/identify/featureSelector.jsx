import React from "react";
import { RangeSlider } from "@blueprintjs/core";//
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
					Object.keys(this.props.states).map((itemKey) => {
						let itemVal = this.props.states[itemKey];
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
    this.state = { 
    	description: "(Any size)",
    	slider: null,
    	//sliderId: '',
    	cid: -1,
    	minMax: [1,10],
    	step: 1,
    	labelPrecision: 1,
    	labelStepSize: 1,
    	label: '',
    	units: '',
    	states: [],
    	range: [0,0]
    };
    this.registerSliderRelease = this.registerSliderRelease.bind(this);
    this.registerSliderChange = this.registerSliderChange.bind(this);
    this.getSliderDescription = this.getSliderDescription.bind(this);
  }

  componentDidMount() {
		let minMax = [];
		minMax[0] = this.props.states[0].charstatename;
		minMax[1] = this.props.states[this.props.states.length - 1].charstatename;
		minMax[1] = minMax[1].toString().replace(/>+/g,'') - 0;
		let step = this.props.states[1].charstatename - this.props.states[0].charstatename;
		step = step.toFixed(1) - 0;
		let labelPrecision = (step < 1? 1 : 0);
		let labelStepSize = (this.props.states.length > 10? this.props.states.length : step);//no labels where > 10

		let range = this.props.range;
		if (this.props.range[0] == PlantSlider.defaultProps.range[0] && this.props.range[0] == PlantSlider.defaultProps.range[0]) {
			range = minMax;
		}

    this.setState({ 
    	description: this.getSliderDescription(this.props.units,minMax) ,
    	minMax: minMax,
    	step: step,
    	labelPrecision: labelPrecision,
    	labelStepSize: labelStepSize,
    	label: this.props.label,
    	states: this.props.states,
    	units: this.props.units,
    	cid: this.props.cid,
    	range: range
    });
    //this.registerSliderEvent();
  }

  registerSliderRelease(range) {//fires the search
    if (range) {
    	let cleanRange = range;
    	/* 	floats from slider sometimes have rounding errors (e.g. 5.70000000001)
    			so we correct the ones for our use and store in cleanRange, while leaving this.state.range alone for the slider to use
    			(the slider fixes those errors for its internal use)
    	 */
			if (this.state.step < 1) {
				cleanRange = range.map((value) => {
					return Number(value).toFixed(1);
				});		
			}
			const onChangeEvent = this.props.onSliderChanged;  
			onChangeEvent(this.state.cid, this.state.label, cleanRange, this.state.states, this.state.units);
    }
  }
  registerSliderChange(range) {//for display purposes only
    let desc = this.getSliderDescription(this.state.units,range) ;
    this.setState( { description: desc, range: range } );
  }
  
  /**
	* DUPLICATED IN garden/sidebar.jsx
	* @param valueArray {number[]} An array in the form [min, max]
	* @returns {string} An English description of the [min, max] values
	*/
	getSliderDescription(units,valueArray) {
		let valueDesc = '';

		if (Array.isArray(valueArray)) {
		
		/*	if ( valueArray[0] > valueArray[1] ) {// Fix if the handles have switched
				let tmp = valueArray[0];
				valueArray[0] = valueArray[1];
				valueArray[1] = tmp;
			}*/
			valueDesc = `(${valueArray[0]} ` + units + ` - ${valueArray[1]} ` + units + `)`;
		}
		/*if (valueArray[0] === this.minMax[0] && valueArray[1] === this.minMax[1]) {
			valueDesc = "(Any size)";
		} else if (valueArray[0] === this.minMax[0]) {
			valueDesc = `(At most ${valueArray[1]} ` + units + `)`;
		} else if (valueArray[1] === this.minMax[1]) {
			valueDesc = `(At least ${valueArray[0]} ` + units + `)`;
		} else {
		valueDesc = `(${valueArray[0]} ` + units + ` - ${valueArray[1]} ` + units + `)`
		}*/
		
		return valueDesc;
	}

  render() {
	  /* handles resets coming from ViewOpts */
  	let range = this.state.range;
  	if (!this.props.sliders[this.state.cid]) {
	  	range = this.state.minMax;
	  }
	  let desc = this.getSliderDescription(this.state.units,range)
    return (
      <div>
        <RangeSlider
					min={ this.state.minMax[0] }
					max={ this.state.minMax[1] }
					stepSize={ this.state.step }
					value={ range }
					onRelease={this.registerSliderRelease}
					onChange={ this.registerSliderChange }
					labelPrecision={ this.state.labelPrecision }
					labelStepSize={ this.state.labelStepSize }
				/>     
        <label className="d-block text-center any-size" id="slider-description" htmlFor={ this.props.name }>
          { desc }
        </label>
      </div>
    );
  }
}

PlantSlider.defaultProps = {
  attrs: {},
  cid: -1,
  label: '',
  onChange: () => {},
  onRelease: () => {},
  states: [{charstatename:'',}],
  units: '',
  range: [0, 0],
  onSliderChanged: () => {},
};


class FeatureSelector extends React.Component {
  constructor(props) {
    super(props);
    this.getDropdownId = this.getDropdownId.bind(this);
    this.onAttrClicked = this.props.onAttrClicked.bind(this);
    this.onSliderChanged = this.props.onSliderChanged.bind(this);
  }

  getDropdownId() {
    return `feature-selector-${this.props.cid}`;
  }

  render() {
		let classes = "collapse";
		if (this.props.display == 'slider') {
			classes = "slider ";//collapse
		}

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
          <div id={ this.getDropdownId() } className={ classes }>
						{this.props.display == 'slider'?
							<PlantSlider
								states={ this.props.states }
								attrs={ this.props.attrs }
								sliders={ this.props.sliders }
								label={ this.props.title }
								cid={ this.props.cid }
								units={ this.props.units }
								onSliderChanged={ this.props.onSliderChanged }
							/>
							
							:
					
							<CheckboxList 
								states={ this.props.states }
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
  states: [],
  onAttrClicked: () => {}
};

export default FeatureSelector;