import React from "react";
import PropTypes from 'prop-types';

/*import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { library } from "@fortawesome/fontawesome-svg-core";
import { faTimesCircle } from '@fortawesome/free-solid-svg-icons'
library.add(faTimesCircle)*/

class FilterModal extends React.Component {//https://daveceddia.com/open-modal-in-react/

  render() {
    // Render nothing if the "show" prop is false
    if(!this.props.show) {
      return null;
    }
    
    return (
      <div className="modal-backdrop filter-modal">
        <div className="modal-content">
          {this.props.children}
        </div>
      </div>
    );
  }
}

FilterModal.propTypes = {
  show: PropTypes.bool,
  children: PropTypes.node
};

export default FilterModal;
