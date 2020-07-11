import ReactDOM from "react-dom";
import React from "react";
import Slider from "react-slick";
const CLIENT_ROOT = "..";

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
    };
  }

  render() {
		const slides = [1,2,3];//matches slide suffixes in /home/ dir
		
		const slickSettings = {
			autoplay: true,
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


const domContainer = document.getElementById("react-home-app");
ReactDOM.render(
	<Home/>,
	domContainer
);
