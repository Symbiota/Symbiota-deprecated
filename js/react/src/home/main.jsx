import ReactDOM from "react-dom";
import React from "react";
import Slider from "react-slick";
import httpGet from "../common/httpGet.js";
import SearchWidget from "../common/search.jsx";
import ModalVideo from 'react-modal-video';

const RANK_FAMILY = 140;
const RANK_GENUS = 180;

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { library } from "@fortawesome/fontawesome-svg-core";
import { faChevronRight, faChevronLeft } from '@fortawesome/free-solid-svg-icons'
library.add( faChevronRight, faChevronLeft)

/* https://github.com/akiran/react-slick/issues/1195 */
const SlickButtonFix = ({currentSlide, slideCount, children, ...props}) => (
    <span {...props}>{children}</span>
);

class Home extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      isLoading: false,
      isVideoOpen: false,
      videoId: '',
      searchText: '',
      news: [],
      events: [],
    };
		this.onSearchTextChanged = this.onSearchTextChanged.bind(this);
		this.onSearch = this.onSearch.bind(this);
		this.openVideoModal = this.openVideoModal.bind(this);
  }
  openVideoModal(_videoId) {
  	this.setState({isVideoOpen: true});
  	this.setState({videoId: _videoId});
  }

  onSearchTextChanged(e) {
    this.setState({ searchText: e.target.value });
  }

  // "searchObj" is the JSON object returned from ../webservices/autofillsearch.php
  // WARNING - this code is copied exactly on header/main.jsx
  onSearch(searchObj) {
  	//console.log(searchObj);
    this.setState({ isLoading: true });
    let targetUrl = `${this.props.clientRoot}/taxa/`;
    /*
    if (searchObj.rankId && searchObj.rankId === RANK_FAMILY) {
      targetUrl += `search.php?family=${searchObj.taxonId}&familyName=${searchObj.text}`;

    } else if (searchObj.rankId && searchObj.rankId === RANK_GENUS) {
      targetUrl += `search.php?genus=${searchObj.taxonId}&genusName=${searchObj.text}`;

    } else {*/
      if (searchObj.taxonId) {
        if (searchObj.taxonId === searchObj.tidaccepted) {
	        targetUrl += `index.php?taxon=${searchObj.taxonId}`;
	      }else{
	        targetUrl += `index.php?taxon=${searchObj.tidaccepted}&synonym=${searchObj.taxonId}`;
	      }
      } else {
        targetUrl += `search.php?search=${ encodeURIComponent(searchObj.text) }`;
      }
    /*}*/

    window.location = targetUrl;
  }
  
  componentDidMount() {

		httpGet(`./home/rpc/api.php`)
			.then((res) => {
				res = JSON.parse(res);
				
				this.setState({
					news: res.news.splice(0,3),
					events: res.events.splice(0,3),
				});
				const pageTitle = document.getElementsByTagName("title")[0];
				pageTitle.innerHTML = `${pageTitle.innerHTML} Home`;
			})
			.catch((err) => {
				console.error(err);
			});
  }//componentDidMount
  
    
  render() {
		
		const slickSettings = {
			autoplay: true,
			initialSlide: 0,
			autoplaySpeed: 10000,
			dots: true,
			infinite: true,
			slidesToShow: 1,
			slidesToScroll: 1,
			nextArrow: <SlickButtonFix><FontAwesomeIcon icon="chevron-right"/></SlickButtonFix>,
			prevArrow: <SlickButtonFix><FontAwesomeIcon icon="chevron-left"/></SlickButtonFix>
		};
			
		
    return (
    <div className="wrapper">
      <div className="container home">
      	<ModalVideo channel='youtube' isOpen={this.state.isVideoOpen} videoId={ this.state.videoId } onClose={() => this.setState({isVideoOpen: false})} />
      	
     		<Slider { ...slickSettings } className="mx-auto">
     			<div key="1">
						<div className="row slide-wrapper slide-1">
							<div className="col-sm slide-col-1">
										<h1>Welcome to Oregon Flora, the world's most comprehensive guide to the vascular plants of Oregon</h1>
										<h3>Get started right now:</h3>
										<SearchWidget
											placeholder="Type a plant name here"
											clientRoot={ this.props.clientRoot }
											isLoading={ this.state.isLoading }
											textValue={ this.state.searchText }
											onTextValueChanged={ this.onSearchTextChanged }
											onSearch={ this.onSearch }
											suggestionUrl={ `${this.props.clientRoot}/webservices/autofillsearch.php` }
											location={"home-main"}
										/>
										<p className="search-explain">to access all its information, including <br />distribution maps, images and more...</p>
										<p><a href={this.props.clientRoot + '/pages/tutorials.php' }><button className="btn btn-primary">Or take an introductory tour of our site</button></a></p>
							</div>
	
							<div className="col-sm-6 col-md-5 col-lg-4 slide-col-2">
	
								<div className="row link-card">
												<p className="link-text">
													<a href={this.props.clientRoot + '/garden/index.php' }><img src={ this.props.clientRoot + '/images/slide-choose.png' }/></a>
													<a href={this.props.clientRoot + '/garden/index.php' }><strong>Choose</strong></a> the right plant for your garden or landscape.
												</p>
												<p className="link-desc">In our <a href={this.props.clientRoot + '/garden/index.php' }><strong>Grow Natives</strong></a> resource.</p>
								</div>
		
								<div className="row link-card">
												<p className="link-text">
													<a href={this.props.clientRoot + '/checklists/dynamicmap.php?interface=key' }><img src={ this.props.clientRoot + "/images/slide-identify.png" }/></a>
													<a href={this.props.clientRoot + '/checklists/dynamicmap.php?interface=key' }><strong>Identify</strong></a> a plant you’ve seen in Oregon.
													</p>
												<p className="link-desc">With our location-driven <a href={this.props.clientRoot + '/checklists/dynamicmap.php?interface=key' }><strong>Interactive Key</strong></a> tool.</p>
								</div>
		
								<div className="row link-card">
												<p className="link-text">
												<a href={this.props.clientRoot + '/spatial/index.php' }><img src={ this.props.clientRoot + "/images/slide-find.png" }/></a>
												<a href={this.props.clientRoot + '/spatial/index.php' }><strong>Find</strong></a> where any vascular plant in Oregon calls home.
												</p>
												<p className="link-desc">With our powerful <a href={this.props.clientRoot + '/spatial/index.php' }><strong>Mapping</strong></a> resource.</p>
								</div>
						
								<div className="row link-card">
												<p className="link-text">
												<a href={this.props.clientRoot + '/collections/harvestparams.php?db[]=5,8,10,7,238,239,240,241' }><img src={ this.props.clientRoot + "/images/slide-explore.png" }/></a>
												<a href={this.props.clientRoot + '/collections/harvestparams.php?db[]=5,8,10,7,238,239,240,241' }><strong>Explore</strong></a> the collections of the OSU Herbarium.
												</p>
												<p className="link-desc">Explore <a href="https://bpp.oregonstate.edu/herbarium"><strong>OSU Herbarium</strong></a> plants, mosses, lichens, algae, and fungi.</p>
								</div>	
							</div>
						</div>
					</div>




     		 <div key="2">
     		 <div className="row slide-wrapper slide-2">
						<div className="col-sm-6 slide-col-1">
								<h1>How to get the most out of our site</h1>
									<p>OregonFlora is made for land managers, gardeners, scientists, restorationists, and plant lovers of all ages. You’ll find information about all the native and exotic plants of the state—ferns, conifers, grasses, herbs, and trees—that grow in the wild.</p>
									<p>We have joined forces with Symbiota to present our website as a Symbiota portal! Learn what OregonFlora can do for you in the overview below, explore our featured tools at right, or browse our full set of tutorials—as text or videos—<a href={ this.props.clientRoot + "/pages/tutorials.php" }>here</a>.</p>
									<div className="row video-card">
											<div className="col-auto video-img">
													<a onClick={() => this.openVideoModal('9ystxXKEOp4')}><img src={ this.props.clientRoot + "/images/YouTube-tutorial-Intro.png" }/></a>
											</div>
											<div className="col video-text">
													<h3><a onClick={() => this.openVideoModal('9ystxXKEOp4')}>An Introduction to Oregon Flora</a></h3>
													<p>Get an overview of the powerful tools available on the website.</p>
											</div>
									</div>
							</div>
						<div className="col-sm-6 slide-col-2">
									<div className="row video-card">
											<div className="col-auto video-img">
													<a onClick={() => this.openVideoModal('HwtEXcTO9jA')}><img src={ this.props.clientRoot + "/images/YouTube-tutorial-Taxon.png" }/></a>
											</div>
											<div className="col video-text">
													<h3><a onClick={() => this.openVideoModal('HwtEXcTO9jA')}>Taxon profile pages</a></h3>
													<p>Comprehensive information—gathered in one location—for each of the ~4,700 vascular plant in the state! </p>
											</div>
									</div>
									<div className="row video-card">
											<div className="col-auto video-img">
													<a onClick={() => this.openVideoModal('Y2sdnibf1O8')}><img src={ this.props.clientRoot + "/images/YouTube-tutorial-Map.png" }/></a>
											</div>
											<div className="col video-text">
													<h3><a onClick={() => this.openVideoModal('Y2sdnibf1O8')}>Mapping</a></h3>
													<p>Draw a shape on the interactive map to learn what plants occur there or enter plant names to see their distribution.</p>
											</div>
									</div>
									<div className="row video-card">
											<div className="col-auto video-img">
													<a onClick={() => this.openVideoModal('DKxoEEwL3V4')}><img src={ this.props.clientRoot + "/images/YouTube-tutorial-InterKey.png" }/></a>
											</div>
											<div className="col video-text">
													<h3><a onClick={() => this.openVideoModal('DKxoEEwL3V4')}>Interactive Key</a></h3>
													<p>An identification tool based on the plant features you recognize! Mark your location on a map to get a list of species found there, then narrow the possibilities.</p>
											</div>
									</div>        
									<div className="row video-card">
											<div className="col-auto video-img">
													<a onClick={() => this.openVideoModal('9ystxXKEOp4')}><img src={ this.props.clientRoot + "/images/YouTube-tutorial-Inventory.png" }/></a>
											</div>
											<div className="col video-text">
													<h3><a onClick={() => this.openVideoModal('9ystxXKEOp4')}>Plant Inventories</a></h3>
													<p>In-depth information about the plants of a defined place. Choose from thousands of lists.</p>
											</div>
									</div>          
									<div className="row video-card">
											<div className="col-auto video-img">
													<a onClick={() => this.openVideoModal('9ystxXKEOp4')}><img src={ this.props.clientRoot + "/images/YouTube-tutorial-Inventory.png" }/></a>
											</div>
											<div className="col video-text">
													<h3><a onClick={() => this.openVideoModal('9ystxXKEOp4')}>OSU Herbarium</a></h3>
													<p>All databased specimen records of OSU Herbarium’s vascular plants, mosses, lichens, fungi, and algae in a searchable, downloadable format.</p>
											</div>
									</div>             
									<p><a href={ this.props.clientRoot + "/pages/tutorials.php" }><button className="btn btn-primary">See the rest of our tutorials here</button></a></p>
						</div>
					</div>
     		 </div>
     		
     		 <div key="3">
						<div className="row slide-wrapper slide-3">
							<h1>Oregon Flora News and Events</h1>
								<div className="row">
										<div className="col-sm-6 slide-col-1">
											{	this.state.news.map((item,index) => {
													
													return (					
														<div key={index} className="row">
															<h2 dangerouslySetInnerHTML={{__html: item.title}} ></h2>
															<p><span dangerouslySetInnerHTML={{__html: item.excerpt}} ></span>... <a href={this.props.clientRoot + '/pages/news-events.php#' + item.ID } className="read-more">Read more</a></p>
														</div>
													)
												})
											}
										</div>
										<div className="col-sm-6 slide-col-2">
										
											{	this.state.events.map((item,index) => {
													
													return (					
														<div key={index} className="row">
														     
															<div className="col col-3 event-date">
																	<p>{ item.date } { item.time }</p>
															</div>
															<div className="col event-desc">
																	<p>
																		<span className="event-title" dangerouslySetInnerHTML={{__html: item.title}} ></span>
																		&nbsp;<span className="event-content" dangerouslySetInnerHTML={{__html: item.content}} ></span>
																	</p>
																	<p className="event-location" dangerouslySetInnerHTML={{__html: item.location}} ></p>
															</div></div>
													)
												})
											}
											<p><button className="btn btn-primary"><a href={this.props.clientRoot + '/pages/whats-new.php' }>See all news and events</a></button></p>
										</div>    
								</div>
						</div>
					</div>

      	</Slider>
        
      </div>
    </div>
    );
  }
}


const headerContainer = document.getElementById("react-header");
const dataProps = JSON.parse(headerContainer.getAttribute("data-props"));
const domContainer = document.getElementById("react-home-app");
ReactDOM.render(
	<Home clientRoot={ dataProps["clientRoot"] } />,
	domContainer
);
