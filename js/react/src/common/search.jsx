import React from "react";

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
    this.onKeyUp = this.onKeyUp.bind(this);
  }

  componentDidMount() {
    const autoComplete = $(`#autocomplete-${this.props.name}`).autoComplete({
      minLength: 2,
      formatResult: (item) => {
        return item.text;
      }
    });

    autoComplete.on("autocomplete.select", (item) => {
      this.props.onChange(item);
      this.props.onClick();
    })
  }

  onKeyUp(event) {
    const enterKey = 13;
    if ((event.which || event.keyCode) === enterKey && !this.props.isLoading) {
      event.preventDefault();
      const fakeEvent = {target: {value: this.props.value}};
      this.props.onClick(fakeEvent);
    }
  }

  render() {
    return (
      <div className="search-widget input-group w-100 mb-4 p-2" style={ this.props.style }>
        <input
          id={ `autocomplete-${ this.props.name }` }
          name="search"
          type="text"
          name={ this.props.name }
          placeholder={ this.props.placeholder }
          className="form-control"
          onKeyUp={this.onKeyUp}
          onChange={this.props.onChange}
          value={this.props.value}
          autoComplete={ this.props.autoComplete ? 'on' : 'off' }
          data-url={ this.props.autoCompleteUrl }/>
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
  name: '',
  autoComplete: false,
  autoCompleteUrl: '',
  buttonStyle: {},
  clientRoot: ''
};

export default SearchWidget;