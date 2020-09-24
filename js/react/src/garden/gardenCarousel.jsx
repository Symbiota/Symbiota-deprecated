import React from "react";
import Slider from "react-slick";

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { library } from "@fortawesome/fontawesome-svg-core";
import { faChevronRight, faChevronLeft } from '@fortawesome/free-solid-svg-icons'
library.add( faChevronRight, faChevronLeft)

/* https://github.com/akiran/react-slick/issues/1195 */
const SlickButtonFix = ({currentSlide, slideCount, children, ...props}) => (
    <span {...props}>{children}</span>
);

/*
	moving the slideshow loop into this page will mean making the toggle accessible to both this and taxa/main.jsx - I've tried twice 
	- but now taxa uses /common/imageCarousel, so..?
*/

class GardenCarousel extends React.Component {
  constructor(props) {
    super(props);
	}    

  render() {
		const slickSettings = {
			autoplay: this.props.carouselPlay,
			autoplaySpeed: 8000,
			dots: false,
			infinite: true,
			slidesToShow: this.props.slideshowCount,
			slidesToScroll: 1,
			pauseOnFocus: true,
			initialSlide: this.props.currSlideIndex,
			nextArrow: <SlickButtonFix><FontAwesomeIcon icon="chevron-right"/></SlickButtonFix>,
			prevArrow: <SlickButtonFix><FontAwesomeIcon icon="chevron-left"/></SlickButtonFix>
		};
  
		return (
			<Slider  ref={slider => (this.slider = slider)} { ...slickSettings } className="mx-auto"  style={{ maxWidth: "100%" }}>
				{ this.props.children }
			</Slider>
		);

	}
}

export default GardenCarousel;

