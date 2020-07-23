import ReactDOM from "react-dom";
import React from "react";
import httpGet from "../common/httpGet.js";
import { getUrlQueryParams } from "../common/queryParams.js";
import {getGardenTaxaPage} from "../common/taxaUtils";
import GardenCarousel from "../common/gardenCarousel.jsx";
import ImageModal from "../common/modal.jsx";
import { Tab, Tabs, TabList, TabPanel } from 'react-tabs';
//import 'react-tabs/style/react-tabs.css';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { library } from "@fortawesome/fontawesome-svg-core";
import { Link, DirectLink, Element, Events, animateScroll as scroll, scrollSpy, scroller } from 'react-scroll'
import { faArrowCircleUp, faArrowCircleDown, faEdit, faChevronDown, faChevronUp } from '@fortawesome/free-solid-svg-icons'
library.add(faArrowCircleUp, faArrowCircleDown, faEdit, faChevronDown, faChevronUp)

const CLIENT_ROOT = "..";


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
      <div className="col text-capitalize">{ value }</div>
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
				<div className="col text-capitalize">{ 
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
									<a href={v.url}><button className="d-block my-2 btn-primary"><img src={ `${CLIENT_ROOT}/images/pdf24.png` } />{v.title}</button></a>
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
      <div className="col text-capitalize">{ value }</div>
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
	value = (
		<div className="col-sm-12 related py-2 row">
			<div className="col-sm-8 related-sciname">{ props.value[0] }</div>
			<div className="col-sm-4 related-nav pr-0">
				<span className="related-label">Related</span>
				<span className="related-links"> 
					{ props.isGenus != true &&
					<a href={ props.value[1] }>
						<FontAwesomeIcon icon="arrow-circle-up" />
					</a>
					}
					{ props.isGenus != true && props.value[2].length > 0 && 
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
	            return <RelatedBorderedItem key={ key } keyName={ key } value={ val } isGenus={ props.isGenus }/>
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
	const image = item.images[0];
	let sppQueryParams = queryParams;
	sppQueryParams['taxon'] = item.tid;
	let sppUrl = window.location.pathname + '?taxon=' + encodeURIComponent(sppQueryParams['taxon']);
	return (
		<div key={image.imgid} className="card">
			<a href={sppUrl}>
				<h4>{item.sciname}</h4>
				<div className="img-thumbnail" style={{ position: "relative", width: "100%", height: "7em", borderRadius: "0.25em"}}>														
					<img
						className="d-block"
						style={{width: "100%", height: "100%", objectFit: "cover"}}
						src={image.thumbnailurl}
						alt={image.thumbnailurl}
					/>
				</div>
				<div className="map-preview">
					<img src={ `${CLIENT_ROOT}/images/map-temp.png` }/>
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
		console.log(res);
    const pageTitle = document.getElementsByTagName("title")[0];
    pageTitle.innerHTML = `${pageTitle.innerHTML} ${res.sciName}`;
  	return (
			<div className="container my-5 py-2 taxa-detail" style={{ minHeight: "45em" }}>
				<div className="row">
					<div className="col">

						<h1 className="text-capitalize">{ res.sciName } { res.author }</h1>
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
								<h3 className="text-capitalize text-light-green font-weight-bold mt-2">Subspecies and varieties</h3>   
								<div className="spp-wrapper">
									{
										res.spp.map((spp,index) => {
											return (
												<SppItem item={spp} key={spp.tid} />
											)
										})
									}
								</div> 			
							</div>
						}
										
					</div>
					<div className="col-sm-4 sidebar">
						<SideBarSection title="Context" items={ res.highlights } classes="highlights" isGenus={ res.isGenus }/>
						<SideBarSection title="Web links" items={ res.taxalinks} classes="weblinks"  isGenus={ res.isGenus }/>
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
			currImage: 0
		};
  }

	toggleImageModal = (_currImage) => {
		this.setState({
			currImage: _currImage	
		});
    this.setState({
      isOpen: !this.state.isOpen
    });
  }
	render() {
	
		const res = this.props.res;
    const pageTitle = document.getElementsByTagName("title")[0];
    pageTitle.innerHTML = `${pageTitle.innerHTML} ${res.sciName} ${res.author}`;
		const allImages = res.images.HumanObservation.concat(res.images.PreservedSpecimen);
		const showDescriptions = res.descriptions? true: false;
		return (
	
			<div className="container my-5 py-2 taxa-detail" style={{ minHeight: "45em" }}>
				<div className="row">
					<div className="col">
						<h1 className="text-capitalize">{ res.sciName } { res.author }</h1>
						<h2 className="text-capitalize font-italic">{ res.vernacularNames[0] }</h2>
					</div>
					<div className="col-auto">
						{/*<button className="d-block my-2 btn-primary">Printable page</button>*/}
						<button className="d-block my-2 btn-secondary" disabled={ true }>Add to basket</button>
					</div>
				</div>
				<div className="row mt-2 row-cols-sm-2">
					<div className="col-sm-8 px-4">
						<div className="img-main-wrapper">
							{ res.images.HumanObservation.length > 0 && 
							<img
								id="img-main"
								src={ res.images.HumanObservation[0].url }
								alt={ res.sciName }
							/>
							}
						</div>
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
								<h3 className="text-capitalize text-light-green font-weight-bold mt-2">Subspecies and varieties</h3>   
								<div className="spp-wrapper">
									{
										res.spp.map((spp,index) => {
											return (
												<SppItem item={spp} key={spp.tid} />
											)
										})
									}
								</div> 			
							</div>
						}
					
						{  res.images.HumanObservation.length > 0 && 
						<div className="mt-4 dashed-border" id="photos">     
							<h3 className="text-capitalize text-light-green font-weight-bold mt-2">Photo images</h3>
							<div className="slider-wrapper">
						
							<GardenCarousel
								images={res.images.HumanObservation}>
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
						</div>
					}
				 
						{  res.images.PreservedSpecimen.length > 0 && 
						<div className="mt-4 dashed-border" id="herbarium">     
							<h3 className="text-capitalize text-light-green font-weight-bold mt-2">Herbarium specimens</h3>
							<div className="slider-wrapper">
							<GardenCarousel
								images={res.images.PreservedSpecimen}>
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
															onClick={() => this.toggleImageModal(index + res.images.HumanObservation.length)}
														/>
													</div>
												</div>
											</div>
										);
									})
								}
							</GardenCarousel>
							</div>
						</div>    
						}
							<ImageModal 
								show={this.state.isOpen}
								currImage={this.state.currImage}
								images={allImages}
								onClose={this.toggleImageModal}
							>
								<h3>
									<span className="text-capitalize">{ res.vernacularNames[0] }</span> images
								</h3>
							</ImageModal>        
					
					
					</div>
					<div className="col-sm-4 sidebar">
						<SideBarSection title="Context" items={ res.highlights } classes="highlights" />
						<SideBarSection title="Web links" items={ res.taxalinks} classes="weblinks" />
					</div>
				</div>
			</div>
		);
	}
}


class TaxaApp extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
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
      related: [],
      isGenus: false
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
						let gardenUrl = getGardenTaxaPage(CLIENT_ROOT, res.gardenId);
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
					let isGenus = (res.rankId <= 180 && res.rankId > 140);

          this.setState({
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
            isGenus: isGenus
          });
        })
        .catch((err) => {
          // TODO: Something's wrong
          console.error(err);
        });
    }
  }//componentDidMount

	render() {
		//choose page
		if (this.state.isGenus) {
			return <TaxaChooser res = { this.state } />;
		}else{
			return <TaxaDetail res = { this.state } />;
		}
  }
}

TaxaApp.defaultProps = {
  tid: -1,
};

const domContainer = document.getElementById("react-taxa-app");
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