import React, { Component } from "react";
import ReactDOM from "react-dom";

import httpGet from "../common/httpGet.js";
//import {addUrlQueryParam, getUrlQueryParams} from "../common/queryParams.js";
import {getChecklistPage} from "../common/taxaUtils";
//import PageHeader from "../common/pageHeader.jsx";

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { library } from "@fortawesome/fontawesome-svg-core";
import {faTimesCircle } from '@fortawesome/free-solid-svg-icons'
library.add(  faTimesCircle );

export default class ExplorePreviewModal extends Component {
  constructor(props) {
    super(props);

    this.state = {
      title: '',
      intro: '',
      iconUrl: '',
      authors: '',
      abstract: '',
      //taxa: [],
      isLoading: false,
      totals: {
      	families: 0,
      	genera: 0,
      	species: 0,
      	taxa: 0
      },
    };
    
		this.onTogglePreviewClick = this.props.onTogglePreviewClick.bind(this);
  }
  componentDidMount() {
		if (this.props.clid > -1) {
			httpGet(`${this.props.clientRoot}/checklists/rpc/api.php?clid=${this.props.clid}&pid=${this.props.pid}`)
				.then((res) => {
					// /checklists/rpc/api.php?clid=3&pid=1
					res = JSON.parse(res);

					this.setState({
						title: res.title,
      			intro: res.intro,
			      iconUrl: res.iconUrl,
						authors: res.authors,
						abstract: res.abstract,
						totals: res.totals,
					});
				})
				.catch((err) => {
					//window.location = "/";
					console.error(err);
				});
			}
  }


  render() {
    if(!this.props.show) {
      return null;
    }
 //   console.log(this.state);
    return (
    
    <div className="modal-backdrop">
      <div className="modal-content">
			<div className="wrapper explore-preview-modal">
							
				<div className="container" style={{ minHeight: "45em" }}>
				
					<div className="row">
						<div className="col-12 container" style={{ minHeight: "15em", backgroundSize: "cover", backgroundImage:`url('`+ this.state.iconUrl + `')` }}>
							
							<div className="row explore-preview-header">
								<h1 className="col-10">{ this.state.title }</h1>
								<div className="col-2 text-md-right close-modal" onClick={() => { this.onTogglePreviewClick() }}>
									<FontAwesomeIcon icon="times-circle" />
								</div>
							</div>
				
							
							<div className="row">
								<h2 dangerouslySetInnerHTML={{__html: this.state.intro}}/>
							</div>
							
						</div>
					</div>
					
					<div className="row explore-preview-main">
	

						<div className="col main-wrapper">
							<div className="row">
								<div className="col">
										<p dangerouslySetInnerHTML={{__html: this.state.abstract}} />
								</div>
							</div>
						</div>
						
						
						<div className="col-auto sidebar-wrapper">
							<h3>{ this.state.title } Data</h3>
								<div className={ "row dashed-border py-2" }>
						      <div className="col font-weight-bold">Families:</div>
						      <div className="col text-capitalize">{ this.state.totals.families }</div>
						    </div>
								<div className={ "row dashed-border py-2" }>
						      <div className="col font-weight-bold">Genera:</div>
						      <div className="col text-capitalize">{ this.state.totals.genera }</div>
						    </div>
								<div className={ "row dashed-border py-2" }>
						      <div className="col font-weight-bold">Species:</div>
						      <div className="col text-capitalize">{ this.state.totals.species } (species rank)</div>
						    </div>
								<div className={ "row dashed-border py-2" }>
						      <div className="col font-weight-bold">Total Taxa:</div>
						      <div className="col text-capitalize">{ this.state.totals.taxa } (including subsp. and var.)</div>
						    </div>
	           	 	<div className="taxa-link">
            			<a href={ getChecklistPage(this.props.clientRoot, this.props.clid, this.props.pid) }><button className="d-block my-2 btn-primary">See the plants</button></a>
            		</div>
						</div>
						
						
						
					</div>
				</div>
			</div>
		</div>
	</div>
    );
  }
}
ExplorePreviewModal.defaultProps = {
  clid: -1,
  pid: -1,
  clientRoot: '',
  onClose: () => {},
  show: false
};

