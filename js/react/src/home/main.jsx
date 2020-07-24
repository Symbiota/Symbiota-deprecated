import ReactDOM from "react-dom";
import React from "react";
import Slider from "react-slick";
import httpGet from "../common/httpGet.js";
import SearchWidget from "../common/search.jsx";

const RANK_FAMILY = 140;
const RANK_GENUS = 180;
/*
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { library } from "@fortawesome/fontawesome-svg-core";
import { faSearchPlus, faListUl, faChevronDown, faChevronUp } from '@fortawesome/free-solid-svg-icons'
library.add( faSearchPlus, faListUl, faChevronDown, faChevronDown)
*/



class Home extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      isLoading: false,
      searchText: '',
      news: [],
      events: [],
    };
		this.onSearchTextChanged = this.onSearchTextChanged.bind(this);
		this.onSearch = this.onSearch.bind(this);
  }

  onSearchTextChanged(e) {
    this.setState({ searchText: e.target.value });
  }

  // "searchObj" is the JSON object returned from ../webservices/autofillsearch.php
  onSearch(searchObj) {
    this.setState({ isLoading: true });
    let targetUrl = `${this.props.clientRoot}/taxa/`;
    if (searchObj.rankId && searchObj.rankId === RANK_FAMILY) {
      targetUrl += `search.php?family=${searchObj.taxonId}&familyName=${searchObj.text}`;

    } else if (searchObj.rankId && searchObj.rankId === RANK_GENUS) {
      targetUrl += `search.php?genus=${searchObj.taxonId}&genusName=${searchObj.text}`;

    } else {
      if (searchObj.taxonId) {
        targetUrl += `index.php?taxon=${searchObj.taxonId}`;
      } else {
        targetUrl += `search.php?search=${ encodeURIComponent(searchObj.text) }`;
      }
    }

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
		const slides = [2];//matches slide suffixes in /home/ dir
		
		const slickSettings = {
			autoplay: false,
			initialSlide: 0,
			autoplaySpeed: 10000,
			dots: true,
			infinite: true,
			slidesToShow: 1,
			slidesToScroll: 1
		};
			
		
    return (
    <div className="wrapper">
      <div className="container home">

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
													<a href={this.props.clientRoot + '/garden/index.php' }><strong>Choose</strong></a> the right plant for your project or garden.
												</p>
												<p className="link-desc">In our <a href={this.props.clientRoot + '/garden/index.php' }><strong>Plant Natives</strong></a> resource.</p>
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
												<p className="link-desc">With our powerful <a href={this.props.clientRoot + '/spatial/index.php' }><strong>Mapping</strong></a> resource that has two lines like this.</p>
								</div>
						
								<div className="row link-card">
												<p className="link-text">
												<a href="https://bpp.oregonstate.edu/herbarium"><img src={ this.props.clientRoot + "/images/slide-explore.png" }/></a>
												<a href="https://bpp.oregonstate.edu/herbarium"><strong>Explore</strong></a> the collections of the OSU Herbarium.
												</p>
												<p className="link-desc">With our <a href="https://bpp.oregonstate.edu/herbarium"><strong>Searchable Database</strong></a> and images.</p>
								</div>	
							</div>
						</div>
					</div>
     		{	slides.map((index) => {
     				var _html = require(`../../../../home/slide${index}.js`);
     				var _slide = { __html: _html};
						return (
       				<div key={index} dangerouslySetInnerHTML={_slide} />
     				)
     			})
     		}
     		
     		 <div key="3">
						<div className="row slide-wrapper slide-3">
							<h1>Oregon Flora News and Events</h1>
								<div className="row">
										<div className="col-sm-6 slide-col-1">
											{	this.state.news.map((item,index) => {
													
													return (					
														<div key={index} className="row">
															<h2 dangerouslySetInnerHTML={{__html: item.title}} ></h2>
															<p><span dangerouslySetInnerHTML={{__html: item.excerpt}} ></span>... <a href={this.props.clientRoot + '/pages/whats-new.php' } className="read-more">Read more</a></p>
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
