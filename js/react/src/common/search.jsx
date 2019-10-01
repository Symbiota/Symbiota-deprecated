const CLIENT_ROOT = "..";

const searchButtonStyle = {
  width: "2em",
  height: "2em",
  padding: "0.3em",
  marginLeft: "0.5em",
  borderRadius: "50%",
  background: "rgba(255, 255, 255, 0.5)"
};

/**
 * Sidebar 'plant search' button
 */
function SearchButton(props) {
  return (
    <button className="my-auto" style={ Object.assign(searchButtonStyle, props.style) } onClick={props.onClick}>
      <img
        style={{display: props.isLoading ? "none" : "block"}}
        src={`${CLIENT_ROOT}/images/garden/search-green.png`}
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

  onKeyUp(event) {
    const enterKey = 13;
    if ((event.which || event.keyCode) === enterKey) {
      event.preventDefault();
      const fakeEvent = {target: {value: this.props.value}};
      this.props.onClick(fakeEvent);
    }
  }

  render() {
    return (
      <div className="input-group w-100 mb-4 p-2" style={ this.props.style }>
        <input
          name="search"
          type="text"
          placeholder={ this.props.placeholder }
          className="form-control"
          onKeyUp={this.onKeyUp}
          onChange={this.props.onChange}
          value={this.props.value}/>
        <SearchButton
          onClick={this.props.onClick}
          isLoading={this.props.isLoading}
          style={ this.props.buttonStyle }
        />
      </div>
    );
  }
}

export default SearchWidget;