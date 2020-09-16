import ReactDOM from "react-dom";
import React from "react";
import httpGet from "../common/httpGet.js";
import { getUrlQueryParams } from "../common/queryParams.js";
import {getGardenTaxaPage} from "../common/taxaUtils";
import ImageCarousel from "../common/imageCarousel.jsx";
import ImageModal from "../common/modal.jsx";
import Loading from "../common/loading.jsx";
import { Tab, Tabs, TabList, TabPanel } from 'react-tabs';
//import 'react-tabs/style/react-tabs.css';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { library } from "@fortawesome/fontawesome-svg-core";
import { Link, DirectLink, Element, Events, animateScroll as scroll, scrollSpy, scroller } from 'react-scroll'
import { faArrowCircleUp, faArrowCircleDown, faEdit, faChevronDown, faChevronUp } from '@fortawesome/free-solid-svg-icons'
library.add(faArrowCircleUp, faArrowCircleDown, faEdit, faChevronDown, faChevronUp)

const RANK_FAMILY = 140;
const RANK_GENUS = 180;

function stripHtml(str) {
	/*
  Description includes HTML tags & URL-encoded characters in the db.
  It's dangerous to pull/render arbitrary HTML w/ react, so just render the
  plain text & remove any HTML in it.
  */
  return str.replace(/(<\/?[^>]+>)|(&[^;]+;)/g, "");
}

function BorderedItem(props) {
  let value = props.value;
  const isArray = Array.isArray(value);

  if (isArray) {
    value = (
      <ul className="border-item list-unstyled p-0 m-0">
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
class SynonymItem extends React.Component {
  constructor(props) {
    super(props);
		this.state = {
			showSynonyms: false,
			//hiddenSynonyms: false,
			maxSynonyms: 3
		};
  }
  toggleSynonyms = () => {
  	this.setState({ showSynonyms: !this.state.showSynonyms });
  }
	getRenderedItems() {
		if (this.state.showSynonyms || this.props.value.length <= this.state.maxSynonyms) {
			return this.props.value;
		}
		return this.props.value.slice(0,this.state.maxSynonyms);
	}

  render() {
		return (
			<div className={ "synonym-item row dashed-border py-1" }>
				<div className="col font-weight-bold">Synonyms</div>
				<div className="col">{ 
					Object.entries(this.getRenderedItems())
					.map(([key, obj]) => {
						return (
							<span key={ key} className={ "synonym-item" } >
								<span className={ "synonym-sciname" }>{obj.sciname}</span>
								<span className={ "synonym-author" }> { obj.author }</span>
							</span>
						)
					})
					.reduce((prev, curr) => [prev, ', ', curr])
				 }
				{this.props.value.length > this.state.maxSynonyms && 	
					<span>...
					<div className="up-down-toggle">
						<FontAwesomeIcon icon={this.state.showSynonyms? "chevron-up" : "chevron-down"}
							onClick={this.toggleSynonyms}		
						/>
					</div>
					</span>
				}
				</div>
			</div>
		);
	}
}
function MoreInfoItem(props) {
  let value = props.value;
  const isArray = Array.isArray(value);

  if (isArray) {
    value = (
      <ul className="list-unstyled p-0 m-0">
        { props.value.map((v) => {
						if (v.url.indexOf('pdf') > 0) {
							return (
								<li key={ v.url }>
									<a href={v.url}><button className="d-block my-2 btn-primary"><img src={ `${this.props.clientRoot}/images/pdf24.png` } />{v.title}</button></a>
								</li>
							)
						}else{
							return (
								<li key={ v.url }>
									<a href={v.url}><button className="d-block my-2 btn-primary">{v.title}</button></a>
								</li>
							)
						}
        	})
        }
      </ul>
    );
  }

  return (
    <div className={ "more-info row dashed-border py-2" }>
      <div className="col font-weight-bold">{ props.keyName }</div>
      <div className="col">{ value }</div>
    </div>
  );
}
function SingleBorderedItem(props) {
  let value = props.value;
  const isArray = Array.isArray(value);

  if (isArray) {
    value = (
      <ul className="p-0 m-0 single-border-item">
        { props.value.map((v) => {
        		return (
	        		<li className="col dashed-border py-2" key={ v['key'] }>{ v }</li>
  	      	)
        	})
        }
      </ul>
    );
  }

  return (
    <div className={ "row" }>
      { value }
    </div>
  );
}
function RelatedBorderedItem(props) {

  let value = '';
  //console.log(props);
	value = (
		<div className="col-sm-12 related py-2 row">
			<div className="col-sm-8 related-sciname">{ props.value[0] }</div>
			<div className="col-sm-4 related-nav pr-0">
				<span className="related-label">Related</span>
				<span className="related-links"> 
					{ props.rankId > RANK_FAMILY &&
							<a href={ props.value[1] }>
								<FontAwesomeIcon icon="arrow-circle-up" />
							</a>
					}
					{ props.rankId > RANK_FAMILY && props.value[2].length > 0 && 
						/* two statements here because I don't want to wrap them in one div */
						<span className="separator">/</span>
					}
					{ props.value[2].length > 0 && 
					
						<Link 
								to="spp-wrapper"
								spy={true}
								smooth={true}
								duration={400}
								offset={-180}
							>	
							<FontAwesomeIcon icon="arrow-circle-down"	/>
						</Link>	
						
					}
				</span>
			</div>		
		</div>
	);
  return (
    <div className={ "row" }>
      { value }
    </div>
  );
}

function MapItem(props) {

	let mapImage = null;
	mapImage = `${props.clientRoot}/images/maps/${props.tid}.jpg`;
	// /map/googlemap.php?maptype=taxa&taxon=6076&clid=0
	let mapLink = `${props.clientRoot}/map/googlemap.php?maptype=taxa&clid=0&taxon=${props.tid}`;
	
  return (
  	<div className={ "sidebar-section mb-5" }>
    	<h3 className="text-light-green font-weight-bold mb-3">Distribution</h3>
    	<div className={ "dashed-border pt-0" }>
    		<a 
    			className="map-link"
    			onClick={ () => window.open(mapLink,'gmap','toolbar=0,scrollbars=1,width=950,height=700,left=20,top=20') }
    		>
      		<img
						src={mapImage}
						alt={props.title}
					/>
				</a>
			</div>
    	<div className={ "map-label text-right" }>
    		<a 
    			className="map-link"
    			onClick={ () => window.open(mapLink,'gmap','toolbar=0,scrollbars=1,width=950,height=700,left=20,top=20') }
    		>				
      		Click/tap to launch
				</a>
			</div>
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
      <div className={ "sidebar-section mb-5 " + props.classes + ' ' + (itemKeys.length > 0 ? "" : "d-none") }>
        <h3 className="text-light-green font-weight-bold mb-3">{ props.title }</h3>
        {
          itemKeys.map((key) => {
            const val = props.items[key];
            if (key == 'webLinks') {
	            return <SingleBorderedItem key={ val } keyName={ val } value={ val } />
	          }else if (key == 'Related') {
	            return <RelatedBorderedItem key={ key } keyName={ key } value={ val } rankId={ props.rankId }/>
	          }else if (key == "More info") {
	            return <MoreInfoItem key={ key } keyName={ key } value={ val } />
	          }else if (key == "Synonyms") {
	            return <SynonymItem key={ val } keyName={ val } value={ val }  />
	          }else if(val){
	            return <BorderedItem key={ key } keyName={ key } value={ val } />
	          }
          })
        }
        <span className="row dashed-border"/>
    </div>
  );
}

function SppItem(props) {
	const item = props.item;
	let image = null;
	if (item.images.length > 0) {
		image = item.images[0];
	}
	let mapImage = null;
	mapImage = `${props.clientRoot}/images/maps/${item.tid}_sm.jpg`;
	let sppQueryParams = queryParams;
	sppQueryParams['taxon'] = item.tid;
	let sppUrl = window.location.pathname + '?taxon=' + encodeURIComponent(sppQueryParams['taxon']);
	return (
		<div key={item.tid} className="card search-result">
			<a href={sppUrl}>
				<h4>{item.sciname}</h4>
				{ image &&
				<div className="img-thumbnail" style={{ position: "relative", width: "100%", height: "5.7em", borderRadius: "0.25em"}}>														
					<img
						className="d-block"
						style={{width: "100%", height: "100%", objectFit: "cover"}}
						src={image.thumbnailurl}
						alt={image.thumbnailurl}
					/>
				</div>
				}
				<div className="map-preview">
					<img src={ mapImage }/>
				</div>
			</a>
		</div>						
	)

}
function showItem(item) {
  const isArray = Array.isArray(item);
  return (!isArray && item !== '') || item.length > 0;
}

class TaxaTabs extends React.Component {
  constructor() {
    super();
    this.state = { tabIndex: 0 };
  }
	render() {
		return (
			<Tabs className="description-tabs" selectedIndex={this.state.tabIndex} onSelect={tabIndex => this.setState({ tabIndex })}>
				<TabList>
					{
						Object.entries(this.props.descriptions).map(([key, value]) => {
							return (
								<Tab key={key}>{value['caption']}</Tab>
							)
						})
					}	
				</TabList>	
				{
					Object.entries(this.props.descriptions).map(([key, value]) => {
						let descriptions = value['desc'];
						let source = '';
						if (value['url'] != null) {
							source = "<a href=" + value['url'] + " target='_blank' >" + value['source'] + "</a>";
						}else{
							source = value['source'];
						}
						source = '[ ' + source + ' ]';
						let description = '';
						Object.entries(descriptions).map(([dkey, dvalue]) => {
							description += dvalue;
						})
						var display = source + ' ' + description;
						return (
							<TabPanel key={key}>
								<div className="reference" dangerouslySetInnerHTML={{__html: source}} />	
								<div className="description" dangerouslySetInnerHTML={{__html: description}} />
							</TabPanel>
						)
					})
				}	

			</Tabs>
		)
	}
}

class TaxaChooser extends React.Component {

  constructor(props) {
    super(props);	
		this.state = {
		};
  }
  
  render() {
		const res = this.props.res;
    const pageTitle = document.getElementsByTagName("title")[0];
    let titleVal = (res.sciName? res.sciName : res.family);
    pageTitle.innerHTML = `${pageTitle.innerHTML} ${titleVal}`;
  	return (
			<div className="container mx-auto pl-4 pr-4 py-5 taxa-detail" style={{ minHeight: "45em" }}>
				<Loading 
					clientRoot={ this.props.clientRoot }
					isLoading={ res.isLoading }
				/>
				<div className="row">
					<div className="col">

						<h1>{ res.sciName } { res.author }</h1>
					</div>
					<div className="col-auto">
						<FontAwesomeIcon icon="edit" />
					</div>
				</div>
				<div className="row mt-2 row-cols-sm-2">
					<div className="col-sm-8 px-4">
						<p className="mt-4">
							{/*
								Description includes HTML tags & URL-encoded characters in the db.
								It's dangerous to pull/render arbitrary HTML w/ react, so just render the
								plain text & remove any HTML in it.
							*/}
							{ /*this.state.descriptions.replace(/(<\/?[^>]+>)|(&[^;]+;)/g, "") */}
						</p>
						{ 
							res.descriptions.length > 0 &&
							<TaxaTabs descriptions={ res.descriptions } />
						}
					
					
						{res.spp.length > 0 &&
							<div className="mt-4 dashed-border" id="subspecies">     
								<h3 className="text-light-green font-weight-bold mt-2">Species, subspecies and varieties</h3>   
								<div className="spp-wrapper">
									{
										res.spp.map((spp,index) => {
											return (
												<SppItem item={spp} key={spp.tid} clientRoot={ this.props.clientRoot } />
											)
										})
									}
								</div> 			
							</div>
						}
										
					</div>
					<div className="col-sm-4 sidebar">
						<SideBarSection title="Context" items={ res.highlights } classes="highlights" rankId={ res.rankId }/>
						<SideBarSection title="Web links" items={ res.taxalinks} classes="weblinks" rankId={ res.rankId }/>
					</div>
				</div>
			</div>
		)
  }
}


class TaxaDetail extends React.Component {

  constructor(props) {
    super(props);
		
		this.state = {
			isOpen: false,
			currImage: 0,
			currImageBasis: this.props.res.images.HumanObservation
		};
  }

	toggleImageModal = (_currImage, _imageBasis) => {
		let basis = (_imageBasis === "PreservedSpecimen"? this.props.res.images.PreservedSpecimen: this.props.res.images.HumanObservation )	
	
		this.setState({
			currImage: _currImage,	
      isOpen: !this.state.isOpen,
      currImageBasis: basis,
    });
  }
	render() {
	
		const res = this.props.res;
    const pageTitle = document.getElementsByTagName("title")[0];
    pageTitle.innerHTML = `${pageTitle.innerHTML} ${res.sciName} ${res.author}`;
		const allImages = res.images.HumanObservation.concat(res.images.PreservedSpecimen);
		const showDescriptions = res.descriptions? true: false;

		return (
	
			<div className="container mx-auto pl-4 pr-4 py-5 taxa-detail" style={{ minHeight: "45em" }}>
				<Loading 
					clientRoot={ this.props.clientRoot }
					isLoading={ res.isLoading }
				/>
				<div className="row">
					<div className="col">
						<h1><span className="font-italic">{ res.sciName }</span> { res.author }</h1>

						<h2 className=""><span className="font-italic">{ res.vernacularNames[0] }</span>					
						{ res.synonym &&
							<span className="synonym"> (synonym: <span className="font-italic">{ res.synonym }</span>)</span>
						}
						</h2>
					</div>
					<div className="col-auto">
						{/*<button className="d-block my-2 btn-primary">Printable page</button>*/}
						<button className="d-block my-2 btn-secondary" disabled={ true }>Add to basket</button>
					</div>
				</div>
				<div className="row mt-2 row-cols-sm-2">
					<div className="col-sm-8 px-4">

							{ allImages.length > 0 && 
							<figure>
								<div className="img-main-wrapper">
									<img
										id="img-main"
										src={ allImages[0].url }
										alt={ res.sciName }
									/>
								</div>
							<figcaption>{ allImages[0].photographer}</figcaption>
							</figure>
							}
							{/*
				
								Description includes HTML tags & URL-encoded characters in the db.
								It's dangerous to pull/render arbitrary HTML w/ react, so just render the
								plain text & remove any HTML in it.		
								<p className="mt-4">
								</p>
							*/}
							{ /*this.state.descriptions.replace(/(<\/?[^>]+>)|(&[^;]+;)/g, "") */}
						{ showDescriptions &&
							<TaxaTabs descriptions={ res.descriptions } />
						}
					
					
						{res.spp.length > 0 &&
							<div className="mt-4 dashed-border" id="subspecies">     
								<h3 className="text-light-green font-weight-bold mt-2">Subspecies and varieties</h3>   
								<div className="spp-wrapper">
									{
										res.spp.map((spp,index) => {
											return (
												<SppItem item={spp} key={spp.tid}  clientRoot={ this.props.clientRoot } />
											)
										})
									}
								</div> 			
							</div>
						}
					
						{  res.images.HumanObservation.length > 0 && 
						<div className="mt-4 dashed-border taxa-slideshows" id="photos">     
							<h3 className="text-light-green font-weight-bold mt-2">Photo images</h3>
							<div className="slider-wrapper">
						
							<ImageCarousel
								images={res.images.HumanObservation}
								imageCount={ res.images.HumanObservation.length } 
							>
								{
									res.images.HumanObservation.map((image,index) => {
										return (					
											<div key={image.url}>
												<div className="card" style={{padding: "0.5em"}}>
													<div style={{ position: "relative", width: "100%", height: "7em", borderRadius: "0.25em"}}>
													
														<img
															className="d-block"
															style={{width: "100%", height: "100%", objectFit: "cover"}}
															src={image.thumbnailurl}
															alt={image.thumbnailurl}
															onClick={() => this.toggleImageModal(index,"HumanObservation")}
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
					}
				 
						{  res.images.PreservedSpecimen.length > 0 && 
						<div className="mt-4 dashed-border taxa-slideshows" id="herbarium">     
							<h3 className="text-light-green font-weight-bold mt-2">Herbarium specimens</h3>
							<div className="slider-wrapper">
							<ImageCarousel
								images={res.images.PreservedSpecimen}
								imageCount={ res.images.PreservedSpecimen.length } 
							>
								{
									res.images.PreservedSpecimen.map((image,index) => {
										return (					
											<div key={image.url}>
												<div className="card" style={{padding: "0.5em"}}>
													<div style={{ position: "relative", width: "100%", height: "7em", borderRadius: "0.25em"}}>
													
														<img
															className="d-block"
															style={{width: "100%", height: "100%", objectFit: "cover"}}
															src={image.thumbnailurl}
															alt={image.thumbnailurl}
															onClick={() => this.toggleImageModal(index,"PreservedSpecimen")}
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
						}       
					
					
					</div>
					<div className="col-sm-4 sidebar">
						<SideBarSection title="Context" items={ res.highlights } classes="highlights" rankId={ res.rankId } />
						<MapItem title={ res.sciName } tid={ res.tid } clientRoot={ this.props.clientRoot } />
						<SideBarSection title="Web links" items={ res.taxalinks} classes="weblinks"  rankId={ res.rankId }/>
					</div>
				</div>
				<ImageModal 
					show={this.state.isOpen}
					currImage={this.state.currImage}
					images={this.state.currImageBasis}
					onClose={this.toggleImageModal}
					clientRoot={ this.props.clientRoot }
				>
					<h3>
						<span>{ res.vernacularNames[0] }</span> images
					</h3>
				</ImageModal> 
			</div>
		);
	}
}


class TaxaApp extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      isLoading: true,
      tid: null,
      sciName: '',
      author: '',
      basename: '',
      family: '',
      vernacularNames: [],
      images: {
      	'HumanObservation': [],
      	'PreservedSpecimen': [],
      	'LivingSpecimen': []
      },
      descriptions:[],
      synonym: '',
      synonyms: [],
      origin: '',
      taxalinks: [],
      gardenId: null,
      rarePlantFactSheet: '',
      highlights: {},
      spp: [],
      tid: null,
      rankId: null,
      currImage: 0,
      related: []
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
    	let api = `./rpc/api.php?taxon=${this.props.tid}`;
    	//console.log(api);
      httpGet(api)
        .then((res) => {
       		// /taxa/rpc/api.php?taxon=2454
          res = JSON.parse(res); 

					let url = new URL(window.location);
					let parentQueryParams = new URLSearchParams(url.search);
					parentQueryParams.set('taxon',res.parentTid);
					let parentUrl = window.location.pathname + '?' + parentQueryParams.toString();
					
					let childUrl = '';
					if (res.spp.length) {
						childUrl = "#subspecies";
					}
					
					const relatedArr = [res.sciname,parentUrl,childUrl];
					
					let moreInfo = [];
					if (res.rarePlantFactSheet.length) {
						moreInfo.push({title: "Rare Plant Fact Sheet", url: res.rarePlantFactSheet});
					}
					if (res.gardenId > 0) {
						let gardenUrl = getGardenTaxaPage(this.props.clientRoot, res.gardenId);
						moreInfo.push({title: "Garden Fact Sheet", url: gardenUrl});
					}
					
       		let web_links = res.taxalinks.map((link,index) => {
						return (					
							<div key={link.url}>
								<a 
									href={link.url}
									target="_blank"
								>{link.title}
								</a>
							</div>
						)
					});
					
					let synonym = '';
					if (this.props.synonym) {
						Object.keys(res.synonyms).map((key) => {
							if (this.props.synonym === res.synonyms[key].tid) {
								synonym = res.synonyms[key].sciname;
							}
						});
					}
          this.setState({
      			tid: this.getTid(),
            sciName: res.sciname,
            author: res.author,
            basename: res.vernacular.basename,
            vernacularNames: res.vernacular.names,
            images: res.imagesBasis,
            gardenId: res.gardenId,
            rankId: res.rankId,
            descriptions: res.descriptions,
            highlights: {
            	"Related": relatedArr,
              "Family": res.family,
              "Common Names": res.vernacular.names,
              "Synonyms": res.synonyms,
              "Origin": res.origin,
              "More info": moreInfo
            },
            taxalinks: {
            	"webLinks": web_links
            },
            spp: res.spp,
            related: relatedArr,
            family: res.family,
            synonym: synonym
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
		//choose page
		if (this.state.rankId <= RANK_GENUS) {
			return <TaxaChooser res = { this.state } clientRoot={ this.props.clientRoot } />;//Genus or Family
		}else{
			return <TaxaDetail res = { this.state } clientRoot={ this.props.clientRoot } />;//Species
		}
  }
}

TaxaApp.defaultProps = {
  tid: -1,
};


const headerContainer = document.getElementById("react-header");
const dataProps = JSON.parse(headerContainer.getAttribute("data-props"));
const domContainer = document.getElementById("react-taxa-app");
const queryParams = getUrlQueryParams(window.location.search);
if (queryParams.search) {
  window.location = `./search.php?search=${encodeURIComponent(queryParams.search)}`;
} else if (queryParams.taxon) {
  ReactDOM.render(
    <TaxaApp tid={queryParams.taxon } clientRoot={ dataProps["clientRoot"] } synonym={ queryParams.synonym - 0 } />,
    domContainer
  );
} else {
  window.location = "/";
}