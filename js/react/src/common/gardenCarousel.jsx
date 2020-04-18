import React from "react";
import Slider from "react-slick";

const slickSettings = {
  autoplay: true,
  autoplaySpeed: 5000,
  dots: false,
  infinite: true,
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