import ReactDOM from "react-dom";
import React from "react";
import Slider from "react-slick";
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
    
  render() {
		const slides = [2,3];//matches slide suffixes in /home/ dir
		
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
										<p><button className="btn btn-primary">Or take an introductory tour of our site</button></p>
							</div>
	
							<div className="col-sm-6 col-md-5 col-lg-4 slide-col-2">
	
								<div className="row link-card">
												<p className="link-text"><img src="/images/slide-choose.png"/><strong><a href="/garden/index.php">Choose</a></strong> the right plant for your project or garden.</p>
												<p className="link-desc">In our <strong>Plant Natives</strong> resource.</p>
								</div>
		
								<div className="row link-card">
												<p className="link-text"><img src="/images/slide-identify.png"/><strong><a href="/checklists/dynamicmap.php?interface=key">Identify</a></strong> a plant you’ve seen in Oregon.</p>
												<p className="link-desc">With our location-driven <strong>Interactive Key</strong> tool.</p>
								</div>
		
								<div className="row link-card">
												<p className="link-text"><img src="/images/slide-find.png"/><strong><a href="/spatial/index.php">Find</a></strong> where any vascular plant in Oregon calls home.</p>
												<p className="link-desc">With our powerful <strong>Mapping</strong> resource that has two lines like this.</p>
								</div>
						
								<div className="row link-card">
												<p className="link-text"><img src="/images/slide-explore.png"/><strong><a href="/projects/index.php">Explore</a></strong> the collections of the OSU Herbarium.</p>
												<p className="link-desc">With our <strong>Searchable Database</strong> and images.</p>
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
