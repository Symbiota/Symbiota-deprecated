import React from "react";
import httpGet from "./httpGet";

/**
 * Sidebar 'plant search' button
 */
function SearchButton(props) {
  return (
    <button
      className="my-auto btn-search" style={ props.style }
      onClick={ props.isLoading ? () => {} : props.onClick}>
      <img
        style={{display: props.isLoading ? "none" : "block"}}
        src={`${props.clientRoot}/images/garden/search-green.png`}
        alt="search plants"/>
      <div
        className="mx-auto text-success spinner-border spinner-border-sm"
        style={{display: props.isLoading ? "block" : "none"}}
        role="status"
        aria-hidden="true"/>
    </button>
  );
}

/**
 * Sidebar 'plant search' text field & button
 */
export class SearchWidget extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      currentValue: "",
      suggestions: [],
    };
    this.onKeyUp = this.onKeyUp.bind(this);
    this.onSuggestionsRequested = this.onSuggestionsRequested.bind(this);
    this.onSuggestionsClear = this.onSuggestionsClear.bind(this);
    this.onSuggestionSelected = this.onSuggestionSelected.bind(this);
    this.onSearchTextChanged = this.onSearchTextChanged.bind(this);
  }

  onKeyUp(event) {
    const enterKey = 13;
    if (this.state.currentValue === '') {
      this.onSuggestionsClear();
    } else if ((event.which || event.keyCode) === enterKey && !this.props.isLoading) {
      event.preventDefault();
      const fakeEvent = { target: { value: this.state.currentValue }};
      this.props.onClick(fakeEvent);
    } else {
      this.onSuggestionsRequested();
    }
  }

  onSuggestionsRequested() {
    httpGet(`${this.props.clientRoot}/webservices/autofillsearch.php?q=${this.state.currentValue}`).then((res) => {
      return JSON.parse(res);
    }).catch((err) => {
      console.error(err);
    }).then((suggestionList) => {
      this.setState({ suggestions: suggestionList });
    });
  }

  onSuggestionSelected(suggestion) {
    this.onSearchTextChanged({ target: { value: suggestion } }, this.onSuggestionsClear);
  }

  onSuggestionsClear() {
    this.setState({ suggestions: [] });
  }

  onSearchTextChanged(event) {
    this.setState({ currentValue: event.target.value }, () => {
      if (this.state.currentValue === '') {
        this.onSuggestionsClear();
      }
    });
    this.props.onChange(event.target.value);
  }

  render() {
    return (
      <div className="search-widget dropdown input-group w-100 mb-4 p-2" style={ this.props.style }>
        <input
          name="search"
          type="text"
          className="form-control"
          autoComplete="off"
          data-toggle="dropdown"
          placeholder={ this.props.placeholder }
          onKeyUp={ this.onKeyUp }
          onChange={ this.onSearchTextChanged }
          value={ this.state.currentValue }/>
        <div className="dropdown-menu" style={{ display: (this.state.suggestions.length > 0 ? "" : "none") }}>
          {
            this.state.suggestions.map((s) => {
              return (
                <button
                  key={ s }
                  onClick={ () => { this.onSuggestionSelected(s); } }
                  className="dropdown-item"
                >
                  { s }
                </button>
              )
            })
          }
        </div>
        <SearchButton
          onClick={this.props.onClick}
          isLoading={this.props.isLoading}
          style={ this.props.buttonStyle }
          clientRoot={ this.props.clientRoot }
        />
      </div>
    );
  }
}

SearchWidget.defaultProps = {
  onChange: () => {},
  buttonStyle: {},
  clientRoot: ''
};

export default SearchWidget;
