import React from "react";
import PropTypes from 'prop-types';
//import httpGet from "../common/httpGet.js";
const CLIENT_ROOT = "..";
import ImageCarousel from "../common/imageCarousel.jsx";

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { library } from "@fortawesome/fontawesome-svg-core";
import { faTimesCircle } from '@fortawesome/free-solid-svg-icons'
library.add(faTimesCircle)

class ImageModal extends React.Component {//https://daveceddia.com/open-modal-in-react/

  render() {
    // Render nothing if the "show" prop is false
    if(!this.props.show) {
      return null;
    }
    
    return (
      <div className="modal-backdrop">
        <div className="modal-content">
          {this.props.children}

					<ImageCarousel
						images={this.props.images}
						currImage={this.props.currImage}
					></ImageCarousel>



          <div className="footer">
						<FontAwesomeIcon className="close-modal" icon="times-circle" 
							onClick={this.props.onClose}/>
          </div>
        </div>
      </div>
    );
  }
}

ImageModal.propTypes = {
  onClose: PropTypes.func.isRequired,
  show: PropTypes.bool,
  children: PropTypes.node
};

export default ImageModal;
