import React, { Component } from "react";
import ReactDOM from "react-dom";

import httpGet from "../common/httpGet.js";
import {getGardenPage} from "../common/taxaUtils";
import Loading from "../common/loading.jsx";

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
      isLoading: true,
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
				})
				.finally(() => {
					this.setState({ isLoading: false });
				});
			}
  }


  render() {
    if(!this.props.show) {
      return null;
    }
 //   console.log(this.state);
    return (
    
    <div className="modal-backdrop explore-preview-modal">
      <div className="modal-content container mx-auto">
			<div className="wrapper explore-preview-wrapper">
				<div className="" style={{ position: "relative", minHeight: "45em", maxWidth: "100%", overflowX: "hidden" }}>
							
				<Loading 
					clientRoot={ this.props.clientRoot }
					isLoading={ this.state.isLoading }
				/>
				
					<div className="row" style={{ maxWidth: "100%", overflowX: "hidden"}}>
						<div className="col-12" style={{  minHeight: "15em", backgroundSize: "cover", backgroundImage:`url('`+ this.state.iconUrl + `')` }}>
							<div className="mask">&nbsp;</div>
							<div className="explore-preview-header">
								<h1 className="col-10">{ this.state.title }</h1>
								<div className="col-2 text-md-right close-modal" onClick={() => { this.onTogglePreviewClick() }}>
									<FontAwesomeIcon icon="times-circle" size="2x"/>
								</div>
							</div>
						
							<div className="col-12">
								<h2 dangerouslySetInnerHTML={{__html: this.state.intro}}/>
							</div>
							
						</div>
					</div>
					
					<div className="row explore-preview-main" style={{ maxWidth: "100%", overflowX: "hidden"}}>

						<div className="container main-wrapper">
							<div className="row">
								<div className="col-12">
										<p dangerouslySetInnerHTML={{__html: this.state.abstract}} />
								</div>
							</div>
						</div>
						
						<div className="container sidebar-wrapper">
							<div>
								<h3>{ this.state.title } Data</h3>
								<div className={ "dashed-border data-item" }>
						      <div className="data-label font-weight-bold">Families:</div>
						      <div>{ this.state.totals.families }</div>
						    </div>
								<div className={ "dashed-border data-item" }>
						      <div className="data-label font-weight-bold">Genera:</div>
						      <div>{ this.state.totals.genera }</div>
						    </div>
								<div className={ "dashed-border data-item" }>
						      <div className="data-label font-weight-bold">Species:</div>
						      <div>{ this.state.totals.species } (species rank)</div>
						    </div>
								<div className={ "dashed-border data-item" }>
						      <div className="data-label font-weight-bold">Total Taxa:</div>
						      <div>{ this.state.totals.taxa } (including subsp. and var.)</div>
						    </div>
	           	 	<div className="taxa-link">
	           	 		{ this.props.referrer == 'garden' ?
	           	 			(
	           	 				<button className="d-block my-2 btn-primary"
	           	 					onClick={() => this.props.newSearch(this.props.clid)}
	           	 				>{ 'Filter for these' }</button>
	           	 			)
	           	 		:
	           	 			(
            					<a href={ getGardenPage(this.props.clientRoot, this.props.clid) }>
            						<button className="d-block my-2 btn-primary">{ 'See the plants' }</button>
            					</a>
            				)
            			}
            		</div>
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

