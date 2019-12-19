import ReactDOM from "react-dom";
import React from "react";
import httpGet from "../common/httpGet.js";
import { getUrlQueryParams } from "../common/queryParams.js";

function BorderedItem(props) {
  let value = props.value;
  const isArray = Array.isArray(props.value);
  const showResult = (!isArray && value !== '') || props.value.length > 0;

  if (isArray) {
    value = (
      <ul className="list-unstyled p-0 m-0">
        { props.value.map((v) => <li key={ v }>{ v }</li>) }
      </ul>
    );
  }

  return (
    <div className={ "row dashed-border py-2 " + (showResult ? "" : "d-none") }>
      <div className="col font-weight-bold">{ props.keyName }</div>
      <div className="col text-capitalize">{ value }</div>
    </div>
  )
}

function SideBarSection(props) {
  const itemKeys = Object.keys(props.items);
  const showResult = itemKeys.length > 0;

  return (
      <div className={ "mb-5 " + (showResult ? "" : "") }>
        <h3 className="text-light-green font-weight-bold mb-3">{ props.title }</h3>
        {
          itemKeys.map((key) => {
            const val = props.items[key];
            return <BorderedItem key={ key } keyName={ key } value={ val } />
          })
        }
        <span className="row dashed-border"/>
    </div>
  );
}

class TaxaApp extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      sciName: '',
      basename: '',
      vernacularNames: [],
      images: [],
      description: "",
      isGardenTaxa: false,
      highlights: {},
      plantFacts: {},
      growthMaintenance: {}
    };

    this.getTid = this.getTid.bind(this);
  }

  getTid() {
    return parseInt(this.props.tid);
  }

  componentDidMount() {
    if (this.getTid() === -1) {
      window.location = "/";
    } else {
      httpGet(`./rpc/api.php?taxon=${this.props.tid}`)
        .then((res) => {
          res = JSON.parse(res);

          const foliageType = res.characteristics.features.foliage_type;
          const plantType = `${foliageType} ${res.characteristics.features.plant_type[0]}`.trim();

          const width = res.characteristics.width;
          const height = res.characteristics.height;
          let sizeMaturity = "";
          if (height.length > 0) {
            sizeMaturity += height.length > 1 ? `${height[0]}-${height[height.length - 1]}` : `${height[0]}`;
            sizeMaturity += "' high";
          }
          if (width.length > 0) {
            if (sizeMaturity !== '') {
              sizeMaturity += ", ";
            }
            sizeMaturity += (width.length > 1 ? `${width[0]}-${width[width.length - 1]}` : `${width[0]}`);
            sizeMaturity += "' wide";
          }

          let ease_growth = res.characteristics.growth_maintenance.ease_growth;
          ease_growth = ease_growth.length > 0 ? ease_growth[0] : "";

          this.setState({
            sciName: res.sciname,
            basename: res.vernacular.basename,
            vernacularNames: res.vernacular.names,
            images: res.images,
            isGardenTaxa: res.isGardenTaxa,
            description: res.description,
            highlights: {
              "Plant type": plantType,
              "Size at maturity": sizeMaturity,
              "Cultivation tolerances": res.characteristics.sunlight,
              "Wildlife support": res.characteristics.features.wildlife_support,
              "Ease of growth": ease_growth
            },
            plantFacts: {
              "Plant Type": plantType,
              "Size at maturity": sizeMaturity,
              "Flower color": res.characteristics.features.flower_color,
              "Bloom time": res.characteristics.features.bloom_months,
              "Light": res.characteristics.sunlight,
              "Moisture": res.characteristics.moisture,
              "Wildlife support": res.characteristics.features.wildlife_support
            },
            growthMaintenance: {
              "Ease of cultivation": res.characteristics.growth_maintenance.cultivation_prefs,
              "Spreads vigorously": res.characteristics.growth_maintenance.spreads_vigorously ? "yes" : "no",
              "Other cultivation factors": res.characteristics.growth_maintenance.other_cult_prefs,
              "Plant behavior": res.characteristics.growth_maintenance.behavior,
              "Propagation": res.characteristics.growth_maintenance.propagation
            }
          });
          const pageTitle = document.getElementsByTagName("title")[0];
          pageTitle.innerHTML = `${pageTitle.innerHTML} ${res.sciname}`;
        })
        .catch((err) => {
          console.error(err);
        });
    }
  }

  render() {
    return (
      <div className="container mt-5">
        <div className="row">
          <div className="col">
            <h1 className="text-capitalize">{ this.state.vernacularNames[0] }</h1>
            <h2 className="font-italic">{ this.state.sciName }</h2>
          </div>
          <div className="col-auto">
            <button className="d-block my-2 btn-primary">Printable page</button>
            <button className="d-block my-2 btn-secondary" disabled={ true }>Add to basket</button>
          </div>
        </div>
        <div className="row mt-2">
          <div className="col">
            <img
              id="img-main"
              src={ this.state.images.length > 0 ? this.state.images[0].url : '' }
              alt={ this.state.sciName }
            />
            <p className="mt-4">
              {/*
                Description includes HTML tags & URL-encoded characters in the db.
                It's dangerous to pull/render arbitrary HTML w/ react, so just render the
                plain text & remove any HTML in it.
              */}
              { this.state.description.replace(/(<\/?[^>]+>)|(&[^;]+;)/g, "") }
            </p>
          </div>
          <div className="col-auto mx-4">
            <SideBarSection title="Highlights" items={ this.state.highlights } />
            <SideBarSection title="Plant Facts" items={ this.state.plantFacts } />
            <SideBarSection title="Growth and Maintenance" items={ this.state.growthMaintenance } />
          </div>
        </div>
      </div>
    );
  }
}

TaxaApp.defaultProps = {
  tid: -1,
};

const domContainer = document.getElementById("react-taxa-app");
const queryParams = getUrlQueryParams(window.location.search);
if (queryParams.search) {
  httpGet(`./rpc/api.php?search=${queryParams.search}`).then((res) => {
    res = JSON.parse(res);
    if (res.length > 1) {
      console.log(JSON.parse(res));
    } else if (res.length > 0) {
      ReactDOM.render(
        <TaxaApp tid={res[0].tid }/>,
        domContainer
      );
    }
  }).catch((err) => {
    console.error(err);
  })
} else if (queryParams.taxon) {
  ReactDOM.render(
    <TaxaApp tid={queryParams.taxon }/>,
    domContainer
  );
} else {
  window.location = "/";
}