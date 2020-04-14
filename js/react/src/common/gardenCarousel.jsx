import React from "react";
import Carousel from "react-slick";

const slickSettings = {
  autoplay: true,
  autoplaySpeed: 5000,
  dots: false,
  infinite: true,
  slidesToShow: 4,
  slidesToScroll: 1
};

function GardenCarousel(props) {
    return (
      <Carousel { ...slickSettings } className="mx-auto"  style={{ maxWidth: "90%" }}>
        { props.children }
      </Carousel>
    );
}

export default GardenCarousel;