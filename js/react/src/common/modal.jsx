import React from "react";
import PropTypes from 'prop-types';
//import httpGet from "../common/httpGet.js";
const CLIENT_ROOT = "..";
import ImageCarousel from "../common/imageCarousel.jsx";
import IconButton from "../common/iconButton.jsx";

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
            <IconButton
							icon={ `${CLIENT_ROOT}/images/garden/x-out.png` }
							onClick={this.props.onClose}
						/>    
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
