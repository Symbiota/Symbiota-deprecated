import React from "react";

import HelpButton from "../common/helpButton.jsx";
import {SearchWidget} from "../common/search.jsx";
import FeatureSelector from "./featureSelector.jsx";


/**
 * @param valueArray {number[]} An array in the form [min, max]
 * @returns {string} An English description of the [min, max] values
 */
function getSliderDescription(valueArray) {
  let valueDesc;

  // Fix if the handles have switched
  if (valueArray[0] > valueArray[1]) {
    let tmp = valueArray[0];
    valueArray[0] = valueArray[1];
    valueArray[1] = tmp;
  }

  if (valueArray[0] === 0 && valueArray[1] === 50) {
    valueDesc = "(Any size)";
  } else if (valueArray[0] === 0) {
    valueDesc = `(At most ${valueArray[1]} ft)`;
  } else if (valueArray[1] === 50) {
    valueDesc = `(At least ${valueArray[0]} ft)`;
  } else {
    valueDesc = `(${valueArray[0]} ft - ${valueArray[1]} ft)`
  }

  return valueDesc;
}

/**
 * Sidebar header with title, subtitle, and help
 */
class SideBarHeading extends React.Component {
  render() {
    return (
      <div style={{color: "black"}}>
        <div className="mb-1 mt-1" style={{color: "inherit"}}>
          <h3 className="font-weight-bold d-inline">Search for plants</h3>
          {/*
          <HelpButton
          	clientRoot={ this.props.clientRoot }
            title="Search for plants"
            html={
                    `
              <ul>
                <li>As you make selections, the filtered results are immediately displayed in “Your search results”.</li>
                <li>Any number of search options may be selected, but too many filters may yield no results because no plant meets all the criteria you selected. If so, try removing filters.</li>
                <li>To remove a search filter, simply click its close (X) button</li>
                <li>Clicking on any image in the results will open that plants’ garden profile page; the page can be downloaded and printed.</li>
              </ul>
            `
            }
          />
          */
          }
        </div>
        <p className="container" style={{ fontSize: ".9em"}}>
          Start applying characteristics, and the matching plants will appear at
          right.
        </p>
      </div>
    );
  }
}

/**
 * 'Plant Need' dropdown with label
 */
function PlantNeed(props) {
  return (
    <div className = "input-group pt-1 mt-1 dashed-border">
      <label className="font-weight-bold" htmlFor={ props.label.toLowerCase() }>
        { props.label }
      </label>
      <select
        name={ props.label.toLowerCase().replace(/[^a-z]/g, '') }
        className="form-control ml-auto"
        style={{ maxWidth: "50%" }}
        value={ props.value }
        onChange={ props.onChange }>
        <option key="select" value="" disabled hidden>Select...</option>
        {
          props.choices.map((opt) =>
            <option
              key={ opt.toLowerCase().replace(/[^a-z-]/g, '') }
              value={ opt.toLowerCase().replace(/[^a-z-]/g, '') }
            >
              { opt }
            </option>
          )
        }
      </select>
    </div>
  );
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
        <br/>
        <label className="d-block text-center" htmlFor={ this.props.name }>
          { this.state.description }
        </label>
      </div>
    );
  }
}

PlantSlider.defaultProps = {
  value: [0, 50]
};

class SideBarDropdown extends React.Component {
  constructor(props) {
    super(props);
    this.state = { isExpanded: false };
    this.onButtonClicked = this.onButtonClicked.bind(this);
  }

  onButtonClicked() {
    if (this.props.disabled !== "true") {
      this.setState({isExpanded: !this.state.isExpanded});
    }
  }

  render() {
    let dropDownId = this.props.title;
    dropDownId = dropDownId.toLowerCase().replace(/[^a-z]/g, "").concat("-dropdown-body");

    return (
      <div
        className={ "top-level" + (this.props.disabled === true ? " dropdown-disabled" : "") }
         >
        <div className="row">
          <h4 className="col" style={{ cursor: "default" }}>
            {this.props.title}
          </h4>
        </div>
        <div id={dropDownId} className="">
          <div className="">
            { this.props.children }
          </div>
        </div>
      </div>
    );
  }
}

SideBarDropdown.defaultProps = {
  title: '',
  style: { padding: "1em", backgroundColor: "white", borderRadius: "0.5em", fontSize: "initial" },
};

/**
 * Full sidebar
 */
class SideBar extends React.Component {
  constructor(props) {
    super(props);
    this.sliderRefWidth = React.createRef();
    this.sliderRefHeight = React.createRef();

    this.resetWidth = this.resetWidth.bind(this);
    this.resetHeight = this.resetHeight.bind(this);
  }

  resetWidth() {
    this.sliderRefWidth.current.reset();
  }

  resetHeight() {
    this.sliderRefHeight.current.reset();
  }

  render() {
    return (
      <div
        id="sidebar"
        className="m-1 rounded-border "
        style={ this.props.style }>

				<div className="filter-tools">
        {/* Title & Subtitle */}
        <SideBarHeading />

					{/* Search */}
					<SearchWidget
						placeholder="Search plants by name"
						clientRoot={ this.props.clientRoot }
						isLoading={ this.props.isLoading }
						textValue={ this.props.searchText }
						onTextValueChanged={ this.props.onSearchTextChanged }
						onSearch={ this.props.onSearch }
						suggestionUrl={ this.props.searchSuggestionUrl }
						onClearSearch={ this.props.onClearSearch }
					/>

					{/* Sunlight & Moisture */}
					<div style={{ background: "white" }} className="rounded-border p-3 top-level plant-needs">
						<h4>Plant needs</h4>
						<PlantNeed
							label="Sunlight"
							choices={ ["Sun", "Part-Shade", "Full-Shade"] }
							value={ this.props.sunlight }
							onChange={ this.props.onSunlightChanged } />
						<PlantNeed
							label="Moisture"
							choices={ ["Dry", "Moderate", "Wet"] }
							value={ this.props.moisture }
							onChange={ this.props.onMoistureChanged } />
					</div>

					{/* Sliders */}
					<div className="my-2 plant-needs p-3 sliders">
						<h4 className="mr-2 mb-2 d-inline">Mature Size</h4>
						<span>(Just grab the slider dots)</span><br />
						<div className="mt-2 row d-flex justify-content-center">
							<div className="col-sm-6" style={{ borderRight: "1px dashed grey" }}>
								<PlantSlider
									ref={ this.sliderRefHeight }
									label="Height (ft)"
									name="height"
									value={ this.props.height }
									onChange={ this.props.onHeightChanged } />
							</div>
							{/*<div
								style={{ width: "1px", borderRight: "1px dashed grey", marginLeft: "-0.5px" }}
							/>*/}
							<div className="col-sm-6">
								<PlantSlider
									ref={ this.sliderRefWidth }
									label="Width (ft)"
									name="width"
									value={ this.props.width }
									onChange={ this.props.onWidthChanged } />
							</div>
						</div>
					</div>

					{/* Dropdowns */}
					<div>
						<SideBarDropdown title="Plant features">
							{
								Object.keys(this.props.plantFeatures).map((plantFeature) => {
									return (
										<FeatureSelector
											key={ plantFeature }
											title={ plantFeature }
											items={ this.props.plantFeatures[plantFeature] }
											clientRoot={ this.props.clientRoot }
											onChange={ (featureKey) => {
												this.props.onPlantFeaturesChanged(plantFeature, featureKey)
											}}
										/>
									)
								})
							}
						</SideBarDropdown>

						<SideBarDropdown title="Growth & maintenance">
							{
								Object.keys(this.props.growthMaintenance).map((plantFeature) => {
									return (
										<FeatureSelector
											key={ plantFeature }
											title={ plantFeature }
											items={ this.props.growthMaintenance[plantFeature] }
											clientRoot={ this.props.clientRoot }
											onChange={ (featureKey) => {
												this.props.onGrowthMaintenanceChanged(plantFeature, featureKey)
											}}
										/>
									)
								})
							}
						</SideBarDropdown>

						<SideBarDropdown title="Beyond the garden">
							{
								Object.keys(this.props.beyondGarden).map((plantFeature) => {
									return (
										<FeatureSelector
											key={ plantFeature }
											title={ plantFeature }
											items={ this.props.beyondGarden[plantFeature] }
											clientRoot={ this.props.clientRoot }
											onChange={ (featureKey) => {
												this.props.onBeyondGardenChanged(plantFeature, featureKey)
											}}
										/>
									)
								})
							}
						</SideBarDropdown>

						<SideBarDropdown title="Commercial Availability (Coming soon)" disabled={ true } />
      		</div>
        </div>
      </div>
    );
  }
}

SideBar.defaultProps = {
  sunlight: '',
  moisture: '',
  width: [0, 50],
  height: [0, 50],
  plantFeatures: {},
  growthMaintenance: {},
  beyondGarden: {},
  searchText: '',
  searchSugestionUrl: '',
  onPlantFeaturesChanged: () => {},
  onGrowthMaintenanceChanged: () => {},
  onBeyondGardenChanged: () => {}
};

export default SideBar;
