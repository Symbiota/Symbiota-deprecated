import ReactDOM from "react-dom";
import React from "react";
import httpGet from "../common/httpGet.js";
import { getUrlQueryParams } from "../common/queryParams.js";
import GardenCarousel from "../common/gardenCarousel.jsx";
import ImageModal from "../common/modal.jsx";

function showItem(item) {
  const isArray = Array.isArray(item);
  return (!isArray && item !== '') || item.length > 0;
}

function BorderedItem(props) {
  let value = props.value;
  const isArray = Array.isArray(value);

  if (isArray) {
    value = (
      <ul className="list-unstyled p-0 m-0">
        { props.value.map((v) => <li key={ v }>{ v }</li>) }
      </ul>
    );
  }

  return (
    <div className={ "row dashed-border py-2" }>
      <div className="col font-weight-bold">{ props.keyName }</div>
      <div className="col text-capitalize">{ value }</div>
    </div>
  );
}

function SideBarSection(props) {
  let itemKeys = Object.keys(props.items);
  itemKeys = itemKeys.filter((k) => {
    const v = props.items[k];
    return showItem(v);
  });

  return (
      <div className={ "mb-5 " + (itemKeys.length > 0 ? "" : "d-none") }>
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
      highlights: {},
      plantFacts: {},
      growthMaintenance: {},
      isOpen: false,
      tid: null,
      currImage: 0
    };
    this.getTid = this.getTid.bind(this);
  }

  getTid() {
    return parseInt(this.props.tid);
  }
	toggleImageModal = (_currImage) => {
		this.setState({
			currImage: _currImage	
		});
    this.setState({
      isOpen: !this.state.isOpen
    });
  }
  componentDidMount() {
    if (this.getTid() === -1) {
      window.location = "/";
    } else {
      httpGet(`./rpc/api.php?taxon=${this.props.tid}`)
        .then((res) => {
       		// /taxa/rpc/api.php?taxon=2454
          res = JSON.parse(res);
          let foliageType = res.characteristics.features.foliage_type;
          foliageType = foliageType.length > 0 ? foliageType[0] : null;

          let plantType = foliageType !== null ? `${foliageType} ` : "";
          if (res.characteristics.features.plant_type.length > 0) {
            plantType += `${res.characteristics.features.plant_type[0]}`.trim();
          }

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

          let ease_of_growth = res.characteristics.growth_maintenance.ease_of_growth;
          ease_of_growth = ease_of_growth.length > 0 ? ease_of_growth[0] : "";

          const spreads_vigorously = res.characteristics.growth_maintenance.spreads_vigorously;

          this.setState({
            sciName: res.sciname,
            basename: res.vernacular.basename,
            vernacularNames: res.vernacular.names,
            images: res.images,
            description: res.gardenDescription,
            highlights: {
              "Plant type": plantType,
              "Size at maturity": sizeMaturity,
              "Cultivation tolerances": res.characteristics.sunlight,
              "Wildlife support": res.characteristics.features.wildlife_support,
              "Ease of growth": ease_of_growth
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
              "Ease of cultivation": res.characteristics.growth_maintenance.cultivation_preferences,
              "Spreads vigorously": spreads_vigorously === null ? "" : spreads_vigorously,
              "Other cultivation factors": res.characteristics.growth_maintenance.other_cult_prefs,
              "Plant behavior": res.characteristics.growth_maintenance.behavior,
              "Propagation": res.characteristics.growth_maintenance.propagation
            }
          });
          const pageTitle = document.getElementsByTagName("title")[0];
          pageTitle.innerHTML = `${pageTitle.innerHTML} ${res.sciname}`;
        })
        .catch((err) => {
          // TODO: Something's wrong
          console.error(err);
        });
    }
  }//componentDidMount
  


  render() {
    return (
    
      <div className="container my-5 py-2" style={{ minHeight: "45em" }}>
        <div className="row">
          <div className="col">
            <h1 className="text-capitalize">{ this.state.vernacularNames[0] }</h1>
            <h2 className="font-italic">{ this.state.sciName }</h2>
          </div>
          <div className="col-auto">
            {/*<button className="d-block my-2 btn-primary">Printable page</button>*/
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
            <div className="mt-4 dashed-border">
            
            	<h3 className="text-capitalize text-light-green font-weight-bold mt-2">{ this.state.vernacularNames[0] } images</h3>
							<div className="slider-wrapper">
  						<GardenCarousel
  							images={this.state.images}>
								{
									this.state.images.map((image,index) => {
										return (					
											<div key={image.url}>
												<div className="card" style={{padding: "0.5em"}}>
													<div style={{ position: "relative", width: "100%", height: "7em", borderRadius: "0.25em"}}>
														
														<img
															className="d-block"
															style={{width: "100%", height: "100%", objectFit: "cover"}}
															src={image.thumbnailurl}
															alt={image.thumbnailurl}
															onClick={() => this.toggleImageModal(index)}
														/>
													</div>
												</div>
											</div>
										);
									})
								}
							</GardenCarousel>
							</div>
							<ImageModal 
								show={this.state.isOpen}
								currImage={this.state.currImage}
								images={this.state.images}
								onClose={this.toggleImageModal}
							>
								<h3>
									<span className="text-capitalize">{ this.state.vernacularNames[0] }</span> images
								</h3>
							</ImageModal>
            </div>
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

const domContainer = document.getElementById("react-taxa-garden-app");
const queryParams = getUrlQueryParams(window.location.search);
if (queryParams.search) {
  window.location = `./search.php?search=${encodeURIComponent(queryParams.search)}`;
} else if (queryParams.taxon) {
  ReactDOM.render(
    <TaxaApp tid={queryParams.taxon }/>,
    domContainer
  );
} else {
  window.location = "/";
}