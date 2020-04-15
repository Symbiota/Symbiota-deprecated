import React, { Component }  from "react";
import Slider from "react-slick";
import {getImageDetailPage} from "../common/taxaUtils";
const CLIENT_ROOT = "..";

export default class ImageCarousel extends Component {
  constructor(props) {
    super(props);
    this.state = {
      nav1: null,
      nav2: null
    };
    //this.onImgLoad = this.onImgLoad.bind(this);
  }

  componentDidMount() {
    this.setState({
      nav1: this.slider1,
      nav2: this.slider2,
    });
  }
  /*
	onImgLoad({target:img}) {
		let key = img.getAttribute("data-key");
		this.props.images[key].width = img.offsetWidth;
    this.updateDetails(this.props.currImage);
	}
	updateDetails(index) {
		if (this.props.images[index].width) {
			
		}
	}*/
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
					slidesToShow={1}
					adaptiveHeight={true}
					initialSlide={this.props.currImage}
					/*beforeChange= { (current,next) => this.updateDetails(next) }*/
					className="images-main" id="main-lightbox" style={{ maxWidth: "100%" }}>
					
					{	this.props.images.map((image,index) => {
						return (
							<div key={image.url} data-id={image.imgid}>
								<h4>From the {image.collectionname}</h4>
								<div className="slide-wrapper">
									<div className="image-wrapper">
									<img
										className=""
										src={image.url}
										alt={image.thumbnailurl}
										style={{height: "100%"}}
										/*onLoad={this.onImgLoad}
										data-key={index}*/
									/>
									</div>
									
									<div className="container image-details">
										<div className="row line-item">
											<div className="col label">
												Collector: 
											</div>
											<div className="col value">
												 {image.photographer}
											</div>
										</div>
										<div className="row line-item">
											<div className="col label">
												Locality: 
											</div>
											<div className="col value">
												 {image.country}, {image.stateprovince}, {image.county}
											</div>
										</div>
										<div className="row">
											<div className="col label">
												Date: 
											</div>
											<div className="col value">
												 {image.fulldate}
											</div>
										</div>
										<div className="row image-link">
											<div className="col">
												<a 
													className="btn" 
													style={{color: "white"}}
													href={ getImageDetailPage(CLIENT_ROOT, image.imgid) }
												>See the full record for this image</a>
											</div>
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
          slidesToShow={6}
          swipeToSlide={true}
          focusOnSelect={true}
				
					className="images-nav"  style={{ maxWidth: "100%" }}>
					{	this.props.images.map((image) => {
							return (
								<div key={image.url} className={""}>
									<div className="card" style={{padding: "0.3em"}}>
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

