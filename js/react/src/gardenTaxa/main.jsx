
class GardenTaxaApp extends React.Component {
  render() {
      return (
        <div>
          <h1>{ this.props.commonName }</h1>
          <h2>{ this.props.sciName }</h2>
          <img src=""/>
          <p></p>
        </div>
      );
  }
}

const domContainer = document.getElementById("react-garden-taxa");
const props = JSON.parse(domContainer.getAttribute("data-props"));
ReactDOM.render(
  <GardenTaxaApp commonName={ props.commonName } sciname={ props.sciName }>
  </GardenTaxaApp>,
  domContainer
);