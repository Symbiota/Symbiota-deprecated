import React, { Component }  from "react";
import Slider from "react-slick";
import {getImageDetailPage} from "../common/taxaUtils";

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { library } from "@fortawesome/fontawesome-svg-core";
import { faChevronRight, faChevronLeft } from '@fortawesome/free-solid-svg-icons'
library.add( faChevronRight, faChevronLeft)


/* https://github.com/akiran/react-slick/issues/1195 */
const SlickButtonFix = ({currentSlide, slideCount, children, ...props}) => (
    <span {...props}>{children}</span>
);


export default class ImageModalCarousel extends Component {
  constructor(props) {
    super(props);
    this.state = {
      nav1: null,
      nav2: null,
      slideshowCount: 0
    };
    this.updateViewport = this.updateViewport.bind(this);
  }

  componentDidMount() {
    this.setState({
      nav1: this.slider1,
      nav2: this.slider2,
    });
    this.updateViewport();
  }
	updateViewport() {
		let newSlideshowCount = 5;
		if (window.innerWidth < 1200) {
			newSlideshowCount = 4;
		}
		if (window.innerWidth < 992) {
			newSlideshowCount = 3;
		}
		if (window.innerWidth < 600) {
			newSlideshowCount = 2;
		}
		this.setState({ slideshowCount: newSlideshowCount });
	}
/*
	const mainSettings = {
		autoplay: true,
		autoplaySpeed: 5000,
		dots: false,
		arrows: false,
		infinite: true,
		slidesToShow: 1,
		slidesToScroll: 1
	};
	const navSettings = {
		slidesToShow: 4,
		slidesToScroll: 1,
		asNavFor: '.images-main',
		dots: false,
		arrow: true,
		centerMode: true,
		focusOnSelect: true
	};
	
	https://react-slick.neostack.com/docs/example/as-nav-for
*/
	render() {
			return (
				<div className="lightbox-wrapper">
				<Slider 
					asNavFor={this.state.nav2}
					ref={slider => (this.slider1 = slider)}
					infinite={true}
					lazyLoad={true}
					slidesToShow={1}
					adaptiveHeight={true}
					initialSlide={this.props.currImage}
					/*beforeChange= { (current,next) => this.updateDetails(next) }*/
					className="images-main" id="main-lightbox" style={{ maxWidth: "100%" }}
					nextArrow={<SlickButtonFix><FontAwesomeIcon icon="chevron-right"/></SlickButtonFix>}
					prevArrow={<SlickButtonFix><FontAwesomeIcon icon="chevron-left"/></SlickButtonFix>}
					>
				
					{	this.props.images.map((image,index) => {
						return (
							<div key={image.url} data-id={image.imgid}>
								<div className="slide-wrapper">
								{/*<h4>From the {image.collectionname}</h4>*/}
									<div className="image-wrapper">
									<img
										className=""
										src={image.url}
										alt={image.collectionname}
										/*onLoad={this.onImgLoad}
										data-key={index}*/
									/>
									</div>
									
									<div className="image-details">
										{ image.basisofrecord != 'PreservedSpecimen' &&
										<div>
											<div className="line-item">
													 {image.fulldate} &copy; {image.photographer}, Courtesy of OregonFlora
											</div>
											<div className="line-item">
														{image.county} County, {image.stateprovince}, {image.country} 
											</div>
										</div>
										}
										<div className="image-link">
												<a 
													className="btn" 
													style={{color: "white"}}
													href={ getImageDetailPage(this.props.clientRoot, image.occid) }
													target="_blank"
												>See the full record for this image</a>

										</div>
									</div>									
									
								</div>
								
								
							</div>
						);
					})}
					
				</Slider>

				<Slider
          asNavFor={this.state.nav1}
          ref={slider => (this.slider2 = slider)}
          slidesToShow={ this.state.slideshowCount}
          swipeToSlide={true}
          focusOnSelect={true}
          infinite={true}
					initialSlide={this.props.currImage}
					nextArrow={<SlickButtonFix><FontAwesomeIcon icon="chevron-right"/></SlickButtonFix>}
					prevArrow={<SlickButtonFix><FontAwesomeIcon icon="chevron-left"/></SlickButtonFix>}
				
					className="images-nav"  style={{ maxWidth: "100%" }}>
					{	this.props.images.map((image) => {
							return (
								<div key={image.url} className={""}>
									<div className="card">
										<div style={{ position: "relative", width: "100%", height: "7em"}}>
										
											<img
												className="d-block"
												style={{width: "100%", height: "100%", objectFit: "cover"}}
												src={image.thumbnailurl}
												alt={image.thumbnailurl}
											/>
										</div>
									</div>
								</div>
							);
						})
					}
				</Slider>
				</div>
			);
	}
}

