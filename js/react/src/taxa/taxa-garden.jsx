import ReactDOM from "react-dom";
import React from "react";
import httpGet from "../common/httpGet.js";
import { getUrlQueryParams } from "../common/queryParams.js";
import ImageCarousel from "../common/imageCarousel.jsx";
import ImageModal from "../common/modal.jsx";
import ExplorePreviewModal from "../explore/previewModal.jsx";
import {getTaxaPage} from "../common/taxaUtils";
import Loading from "../common/loading.jsx";

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
      <div className="col">{ value }</div>
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
      <div className={ "mb-4 " + (itemKeys.length > 0 ? "" : "d-none") }>
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
      isLoading: true,
      sciName: '',
      basename: '',
      vernacularNames: [],
      images: [],
      description: "",
      highlights: {},
      plantFacts: {},
      growthMaintenance: {},
      isOpen: false,//imagemodal
      isPreviewOpen: false,//explorePreviewModal
      currClid: -1,//explorePreviewModal
      currPid: 3,//explorePreviewModal
      tid: null,
      currImage: 0,
      checklists: [],
      nativeGroups: []
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
	togglePreviewModal = (_currClid) => {
		this.setState({
			currClid: _currClid	
		});
    this.setState({
      isPreviewOpen: !this.state.isPreviewOpen
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
          
          let plantType = '';
          let foliageType = res.characteristics.features.foliage_type;
          plantType += foliageType.length > 0 ? `${foliageType[0]} `: '';

          if (res.characteristics.features.lifespan.length > 0) {
            plantType += `${res.characteristics.features.lifespan[0]}`.trim() + " ";
          }
          if (res.characteristics.features.plant_type.length > 0) {
            plantType += `${res.characteristics.features.plant_type[0]}`.trim() + " ";
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
          
          let moisture = [];
          if (res.characteristics.moisture.length > 0) {
            moisture.push(`${res.characteristics.moisture[0]}`.trim());
          }
          if (res.characteristics.summer_moisture.length > 0) {
            moisture.push(`${res.characteristics.summer_moisture[0]}`.trim() + " summer water");
          }

          this.setState({
            sciName: res.sciname,
            basename: res.vernacular.basename,
            vernacularNames: res.vernacular.names,
            images: res.imagesBasis.HumanObservation,
            description: res.gardenDescription,
            checklists: res.checklists,
            highlights: {
              "Plant type": plantType,
              "Size at maturity": sizeMaturity,
              "Light tolerance": res.characteristics.sunlight,
              "Ease of growth": ease_of_growth
            },
            plantFacts: {
              "Flower color": res.characteristics.features.flower_color,
              "Bloom time": res.characteristics.features.bloom_months,
              "Moisture": moisture,
              "Wildlife support": res.characteristics.features.wildlife_support
            },
            growthMaintenance: {
              "Spreads vigorously": spreads_vigorously === null ? "" : spreads_vigorously,
              "Cultivation preferences": res.characteristics.growth_maintenance.cultivation_preferences,
              "Plant behavior": res.characteristics.growth_maintenance.behavior,
              "Propagation": res.characteristics.growth_maintenance.propagation,
              "Landscape uses": res.characteristics.growth_maintenance.landscape_uses
            }
          });
          const pageTitle = document.getElementsByTagName("title")[0];
          pageTitle.innerHTML = `${pageTitle.innerHTML} ${res.sciname}`;
          
          const nativeGroups = [];
					httpGet(`${this.props.clientRoot}/garden/rpc/api.php?canned=true`)
					.then((res) => {
						let cannedSearches = JSON.parse(res);//14796, 14797, 14798, 14799, 14800
						Object.entries(cannedSearches).map(([key, checklist]) => {
							let match = this.state.checklists.indexOf(checklist.clid);
							if (match > -1) {
								nativeGroups.push(checklist);
							}
						})
						this.setState({nativeGroups: nativeGroups	});
					});
          
        })
        .catch((err) => {
          // TODO: Something's wrong
          console.error(err);
        })
				.finally(() => {
					this.setState({ isLoading: false });
				});
    }
  }//componentDidMount

  render() {
    return (
    
      <div className="container mx-auto pl-4 pr-4 pt-5" style={{ minHeight: "45em" }}>
				<Loading 
					clientRoot={ this.props.clientRoot }
					isLoading={ this.state.isLoading }
				/>      
        <div className="row">
          <div className="col">
            <h1 className="">{ this.state.vernacularNames[0] }</h1>
            <h2 className="font-italic">{ this.state.sciName }</h2>
          </div>
          <div className="col-auto">
            {/*<button className="d-block my-2 btn-primary">Printable page</button>*/}
            <button className="d-block my-2 btn-secondary" disabled={ true }>Add to basket</button>
          </div>
        </div>
        <div className="row mt-2">
          <div className="col-8 mr-2">
            
            { this.state.images.length > 0 && 
							<figure>
								<div className="img-main-wrapper">
									<img
										id="img-main"
										src={ this.state.images[0].url }
										alt={ this.state.sciName }
									/>
								</div>
							<figcaption>{ this.state.images[0].photographer}</figcaption>
							</figure>
						}
            
            
            <p className="mt-4">
              {/*
                Description includes HTML tags & URL-encoded characters in the db.
                It's dangerous to pull/render arbitrary HTML w/ react, so just render the
                plain text & remove any HTML in it.
              */}
              { this.state.description.replace(/(<\/?[^>]+>)|(&[^;]+;)/g, "") }
            </p>
            <div className="mt-4 dashed-border taxa-slideshows">
            
            	<h3 className="text-light-green font-weight-bold mt-2">{ this.state.vernacularNames[0] } images</h3>
							<div className="slider-wrapper">
  						<ImageCarousel
  							images={this.state.images}>
								{
									this.state.images.map((image,index) => {
										return (					
											<div key={image.url}>
												<div className="card" style={{padding: "0.6em"}}>
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
							</ImageCarousel>
							</div>

            </div>
          </div>
					<ImageModal 
						show={this.state.isOpen}
						currImage={this.state.currImage}
						images={this.state.images}
						onClose={this.toggleImageModal}
					>
						<h3>
							<span>{ this.state.vernacularNames[0] }</span> images
						</h3>
					</ImageModal>
          <div className="col-3 ml-2">
            <SideBarSection title="Highlights" items={ this.state.highlights } />
            { this.state.nativeGroups.length > 0 &&
            <div className={ "mb-4 " }>
								<h3 className="text-light-green font-weight-bold mb-1">Native plant groups</h3>
								<p>Containing <strong>{ this.state.vernacularNames[0] }:</strong></p>
									<div className="canned-results dashed-border">
									{
										this.state.nativeGroups.map((checklist) => {
																	
											return (
												<div key={ checklist.clid } className={"py-2 canned-search-result"}>
													<h4 className="canned-title" onClick={() => this.togglePreviewModal(checklist.clid)}>{checklist.name}</h4>
													<div className="card" style={{padding: "0.5em"}}>
														<div className="card-body" style={{padding: "0"}}>
															<div style={{ position: "relative", width: "100%", height: "7em", borderRadius: "0.25em"}}>
																<img
																	className="d-block"
																	style={{width: "100%", height: "100%", objectFit: "cover"}}
																	src={checklist.iconUrl}
																	alt={checklist.description}
																	onClick={() => this.togglePreviewModal(checklist.clid)}
																	//onMouseOver={ this.onMouseOver }
																/>
																{/*
																<div
																	className="text-center text-sentence w-100 h-100 px-2 py-1 align-items-center"
																	style={{
																		//display: this.state.hover ? "flex" : "none",
																		position: "absolute",
																		top: 0,
																		left: 0,
																		zIndex: 1000,
																		fontSize: "0.75em",
																		color: "white",
																		background: "rgba(100, 100, 100, 0.8)",
																		overflow: "hidden"
																	}}
																	onMouseOut={ this.onMouseOut }
																>
																</div>
																*/}
															</div>
														</div>
													</div>
												</div>
											)
										})
									}
									</div>
					        <span className="row mt-2 dashed-border"/>						
									<ExplorePreviewModal 
										key={this.state.currClid}
										show={this.state.isPreviewOpen}
										onTogglePreviewClick={this.togglePreviewModal}
										clid={this.state.currClid}
										pid={this.state.currPid}
										clientRoot={this.props.clientRoot}
									></ExplorePreviewModal>
								</div>

							}

            <SideBarSection title="Plant Facts" items={ this.state.plantFacts } />
            <SideBarSection title="Growth and Maintenance" items={ this.state.growthMaintenance } />
            <div className="taxa-link">
            	<a href={ getTaxaPage(this.props.clientRoot, this.getTid()) }><button className="d-block my-2 btn-primary">Core profile page</button></a>
            </div>
          </div>
        </div>
      </div>
    );
  }
}

TaxaApp.defaultProps = {
  tid: -1,
};



const headerContainer = document.getElementById("react-header");
const dataProps = JSON.parse(headerContainer.getAttribute("data-props"));
const domContainer = document.getElementById("react-taxa-garden-app");
const queryParams = getUrlQueryParams(window.location.search);
if (queryParams.search) {
  window.location = `./search.php?search=${encodeURIComponent(queryParams.search)}`;
} else if (queryParams.taxon) {
  ReactDOM.render(
    <TaxaApp tid={queryParams.taxon } clientRoot={ dataProps["clientRoot"] } />,
    domContainer
  );
} else {
  window.location = "/";
}