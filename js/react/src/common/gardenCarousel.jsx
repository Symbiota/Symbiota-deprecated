import React from "react";
import Slider from "react-slick";
/*
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { library } from "@fortawesome/fontawesome-svg-core";
import { faCoffee } from '@fortawesome/free-solid-svg-icons'
library.add(faCoffee)

  nextArrow: <FontAwesomeIcon icon="faCoffee" />,
  prevArrow: <FontAwesomeIcon icon="faCoffee" />
*/
const slickSettings = {
  autoplay: true,
  autoplaySpeed: 5000,
  dots: false,
  infinite: false,
  slidesToShow: 5,
  slidesToScroll: 1
};
/*
	moving the slideshow loop into this page will mean making the toggle accessible to both this and taxa/main.jsx - I've tried twice
*/
function GardenCarousel(props) {
    return (
      <Slider { ...slickSettings } className="mx-auto"  style={{ maxWidth: "90%" }}>
        { props.children }
      </Slider>
    );
}

export default GardenCarousel;