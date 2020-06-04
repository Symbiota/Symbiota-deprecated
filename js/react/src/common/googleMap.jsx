import React, { Component }  from "react";
const CLIENT_ROOT = "..";

export default class GoogleMap extends Component {
  constructor(props) {
    super(props);
    this.state = {
      //nav1: null,
      //nav2: null
    };
  }

  componentDidMount() {
    this.setState({
      //nav1: this.slider1,
      //nav2: this.slider2,
    });
  }

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
				</div>
			);
	}
}

