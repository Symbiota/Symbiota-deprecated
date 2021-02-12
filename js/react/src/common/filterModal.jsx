import React from "react";
import PropTypes from 'prop-types';

/*import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { library } from "@fortawesome/fontawesome-svg-core";
import { faTimesCircle } from '@fortawesome/free-solid-svg-icons'
library.add(faTimesCircle)*/

class FilterModal extends React.Component {//https://daveceddia.com/open-modal-in-react/

  render() {
    let thisClass = (this.props.show? 'active' : '');
    
    return (
      <div className={ "modal-backdrop filter-modal " + thisClass }>
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
