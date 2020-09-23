import React from "react";
import Slider from "react-slick";

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { library } from "@fortawesome/fontawesome-svg-core";
import { faChevronRight, faChevronLeft } from '@fortawesome/free-solid-svg-icons'
library.add( faChevronRight, faChevronLeft)


/*
	moving the slideshow loop into this page will mean making the toggle accessible to both this and taxa/main.jsx - I've tried twice
*/
function ImageCarousel(props) {
	/* https://github.com/akiran/react-slick/issues/1195 */
	const SlickButtonFix = ({currentSlide, slideCount, children, ...props}) => (
			<span {...props}>{children}</span>
	);

	const slickSettings = {
		autoplay: false,
		autoplaySpeed: 8000,
		dots: false,
		infinite: props.imageCount > 5,
		slidesToShow: props.slideshowCount,
		slidesToScroll: props.slideshowCount,
		nextArrow: <SlickButtonFix><FontAwesomeIcon icon="chevron-right"/></SlickButtonFix>,
		prevArrow: <SlickButtonFix><FontAwesomeIcon icon="chevron-left"/></SlickButtonFix>
	};
    return (
      <Slider { ...slickSettings } className="mx-auto"  style={{ maxWidth: "90%" }}>
        { props.children }
      </Slider>
    );
}

export default ImageCarousel;