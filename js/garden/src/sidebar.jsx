"use strict";

import HelpButton from "./help-button.jsx";

const CLIENT_ROOT = "../../..";

const searchButtonStyle = {
  width: "2em",
  height: "2em",
  padding: "0.3em",
  marginLeft: "0.5em",
  borderRadius: "50%",
  background: "rgba(255, 255, 255, 0.5)"
};

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
        <div className="mb-1" style={{color: "inherit"}}>
          <h3 className="font-weight-bold d-inline">Search for plants</h3>
          <HelpButton
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
        </div>
        <p>
          Start applying characteristics, and the matching plants will appear at
          right.
        </p>
      </div>
    );
  }
}

/**
 * Sidebar 'plant search' button
 */
function SearchButton(props) {
  return (
    <button className="my-auto" style={ searchButtonStyle } onClick={ props.onClick }>
      <img
        style={{ display: props.isLoading ? "none" : "block" }}
        src={ `${CLIENT_ROOT}/images/garden/search-green.png` }
        alt="search plants"/>
      <div
        className="mx-auto text-success spinner-border spinner-border-sm"
        style={{ display: props.isLoading ? "block" : "none" }}
        role="status"
        aria-hidden="true"/>
    </button>
  );
}

/**
 * Sidebar 'plant search' text field & button
 */
class SideBarSearch extends React.Component {
  constructor(props) {
    super(props);
    this.onKeyUp = this.onKeyUp.bind(this);
  }

  onKeyUp(event) {
    const enterKey = 13;
    if ((event.which || event.keyCode) === enterKey) {
      event.preventDefault();
      const fakeEvent = { target: { value: this.props.value } };
      this.props.onClick(fakeEvent);
    }
  }

  render() {
    return (
      <div className="input-group w-100 mb-4 p-2">
        <input
          name="search"
          type="text"
          placeholder="Search plants by name"
          className="form-control"
          onKeyUp={ this.onKeyUp }
          onChange={ this.props.onChange }
          value={ this.props.value }/>
        <SearchButton onClick={ this.props.onClick } isLoading={ this.props.isLoading }/>
      </div>
    );
  }
}

/**
 * 'Plant Need' dropdown with label
 */
function PlantNeed(props) {
  return (
    <div className = "input-group pt-3 mt-3" style={{ borderTop: "1px dashed black" }}>
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
              key={ opt.toLowerCase().replace(/[^a-z]/g, '') }
              value={ opt.toLowerCase().replace(/[^a-z]/g, '') }
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
    this.state = { description: "(Any size)" }
  }

  componentDidMount() {
    const sliderId = `slider-container-${this.props.name}`;
    this.slider = new Slider(`#${sliderId}`);

    if (this.props.onChange) {
      const onChangeEvent = this.props.onChange;
      this.slider.on("slide", (sliderArray) => {
        this.setState({ description: getSliderDescription(sliderArray) });
        const fakeEvent = { target: { value: sliderArray } };
        onChangeEvent(fakeEvent);
      });
    }
  }

  componentWillUnmount() {
    this.slider.destroy();
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
          data-slider-value="[0, 50]"
          data-slider-ticks="[0, 10, 20, 30, 40, 50]"
          data-slider-ticks-labels='["0", "", "", "", "", "50+"]'
          data-slider-ticks-snap-bounds="1"
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
        className={ "my-3 py-auto" + (this.props.disabled === true ? " dropdown-disabled" : "") }
        style={ this.props.style } >
        <div className="row">
          <h4 className="mx-0 my-auto col" style={{ cursor: "default", fontSize: this.props.style.fontSize }}>
            {this.props.title}
          </h4>
          <button
            className="d-block col-sm-auto"
            data-toggle="collapse"
            data-target={ "#" + dropDownId }
            type="button"
            aria-expanded={ this.state.isExpanded.toString() }
            aria-controls={ dropDownId }
            onClick={ this.onButtonClicked }
            disabled={ this.props.disabled }
          >
            <img
              className={ "ml-auto will-v-flip" + (this.state.isExpanded ? " v-flip" : "") }
              style={{ background: "black", borderRadius: "50%", height: "2em", width: "2em" }}
              src={ `${CLIENT_ROOT}/images/garden/expand-arrow.png` }
              alt="collapse"
            />
          </button>
        </div>
        <div id={dropDownId} className="collapse">
          <div className="card card-body mt-2">
            Blah blah blah blah
          </div>
        </div>
      </div>
    );
  }
}

SideBarDropdown.defaultProps = {
  title: '',
  style: { padding: "1em", backgroundColor: "white", borderRadius: "0.5em", fontSize: "initial" }
};

/**
 * Full sidebar
 */
class SideBar extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      search: '',
    };

    this.onSearchTextChanged = this.onSearchTextChanged.bind(this);
    this.onSearch = this.onSearch.bind(this);
  }

  onSearchTextChanged(event) {
    this.setState({ search: event.target.value });
  }

  onSearch() {
    this.props.onSearch(this.state.search);
  }

  render() {
    return (
      <div
        id="sidebar"
        className="m-2 rounded-border"
        style={ this.props.style }>

        {/* Title & Subtitle */}
        <SideBarHeading />

        {/* Search */}
        <SideBarSearch
          onChange={ this.onSearchTextChanged }
          onClick={ this.onSearch }
          value={ this.state.search }
          isLoading={ this.props.isLoading }
        />

        {/* Sunlight & Moisture */}
        <div style={{ background: "white" }} className="rounded-border p-4">
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
        <div className="my-5">
          <h4 className="mr-2 mb-2 d-inline">Mature Size</h4>
          <span>(Just grab the slider dots)</span><br />
          <div className="mt-2 row d-flex justify-content-center">
            <div className="col-sm-5 mr-2">
              <PlantSlider
                label="Height (ft)"
                name="height"
                onChange={ this.props.onHeightChanged } />
            </div>
            <div
              style={{ width: "1px", borderRight: "1px dashed grey", marginLeft: "-0.5px" }}
            />
            <div className="col-sm-5 ml-2">
              <PlantSlider
                label="Width (ft)"
                name="width"
                onChange={ this.props.onWidthChanged } />
            </div>
          </div>
        </div>

        {/* Dropdowns */}
        <div>
          <SideBarDropdown title="Plant features" />
          <SideBarDropdown title="Growth & maintenance" />
          <SideBarDropdown title="Beyond the garden" />
          <SideBarDropdown title="Availability (Coming soon)" disabled={ true } />
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
};

export default SideBar;
