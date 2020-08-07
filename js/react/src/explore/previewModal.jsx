"use strict";
import React from "react";
import ReactDOM from "react-dom";

import httpGet from "../common/httpGet.js";
import {addUrlQueryParam, getUrlQueryParams} from "../common/queryParams.js";
import {getChecklistPage} from "../common/taxaUtils";
//import PageHeader from "../common/pageHeader.jsx";
/*
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { library } from "@fortawesome/fontawesome-svg-core";
import {faChevronDown, faChevronUp, faListUl, faSearchPlus } from '@fortawesome/free-solid-svg-icons'
library.add( faChevronDown, faChevronUp, faListUl, faSearchPlus );
*/
class ExplorePreviewModal extends React.Component {
  constructor(props) {
    super(props);
    const queryParams = getUrlQueryParams(window.location.search);

    // TODO: searchText is both a core state value and a state.filters value; How can we make the filtering system more efficient?
    this.state = {
      clid: null,
      pid: null,
      title: '',
      authors: '',
      abstract: '',
      displayAbstract: 'default',
      //taxa: [],
      isLoading: false,
      totals: {
      	families: 0,
      	genera: 0,
      	species: 0,
      	taxa: 0
      },

    };
    this.getPid = this.getPid.bind(this);
    this.getClid = this.getClid.bind(this);
  }

  getClid() {
    return parseInt(this.props.clid);
  }
  getPid() {
    return parseInt(this.props.pid);
  }

  componentDidMount() {
    // Load search results
   
    httpGet(`./rpc/api.php?clid=${this.props.clid}&pid=${this.props.pid}`)
			.then((res) => {
				// /checklists/rpc/api.php?clid=3
				res = JSON.parse(res);

				this.setState({
					clid: this.getClid(),
					pid: this.getPid(),
					title: res.title,
					authors: res.authors,
					abstract: res.abstract,

					totals: res.totals,

				});
				const pageTitle = document.getElementsByTagName("title")[0];
				pageTitle.innerHTML = `${pageTitle.innerHTML} ${res.title}`;
			})
			.catch((err) => {
				window.location = "/";
				//console.error(err);
			});
  }


  render() {
		let shortAbstract = '';
		if (this.state.abstract.length > 0) {
			shortAbstract = this.state.abstract.replace(/^(.{330}[^\s]*).*/, "$1") + "...";//wordsafe truncate
		}
    return (
    <div className="wrapper">
			<div className="page-header">
				<PageHeader bgClass="explore" title={ "Exploring Oregon's Botanical Diversity" } />
      </div>
      <div className="container explore" style={{ minHeight: "45em" }}>
 				<div className="row">
          <div className="col-9">
            <h2>{ this.state.title }</h2>
            <p className="authors"><strong>Authors:</strong> <span className="authors-content" dangerouslySetInnerHTML={{__html: this.state.authors}} /></p>
						
						{this.state.abstract.length > 0 && this.state.displayAbstract == 'default' &&
							<div>
							<p className="abstract"><strong>Abstract:</strong> <span className="abstract-content" dangerouslySetInnerHTML={{__html: shortAbstract}} /></p>
							<div className="more more-less" onClick={() => this.toggleDisplay()}>
									<FontAwesomeIcon icon="chevron-down" />Show Abstract
							</div>
							</div>
						}
						{this.state.abstract.length > 0 && this.state.displayAbstract == 'expanded' &&
							<div>
							<p className="abstract"><strong>Abstract:</strong> <span className="abstract-content" dangerouslySetInnerHTML={{__html: this.state.abstract}} /></p>
							<div className="less more-less" onClick={() => this.toggleDisplay()}>
									<FontAwesomeIcon icon="chevron-up" />Hide Abstract
							</div>
							</div>
						
						}				
												

						
          </div>
          <div className="col-3">
          	map here
          </div>
        </div>
				<div className="row explore-main">
					<hr/>
					<div className="col-auto sidebar-wrapper">
					
					

										{/* left sidebar */ }
						
					
					</div>
					<div className="col results-wrapper">
						<div className="row">
							<div className="col">
								<div className="explore-header inventory-header">
									<div className="current-wrapper">
										<div className="btn btn-primary current-button" role="button"><FontAwesomeIcon icon="list-ul" /> Explore</div>
									</div>
									<div className="alt-wrapper">
										<div>Switch to</div>
										<a href={getIdentifyPage(this.props.clientRoot,this.getClid(),this.getPid())}><div className="btn btn-primary alt-button" role="button"><FontAwesomeIcon icon="search-plus" /> Identify</div></a>
									</div>
								</div>
										{/* MAIN */ }
										
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
  clientRoot: ''
};


//export default ExplorePreviewModal;

/*
const headerContainer = document.getElementById("react-header");
const dataProps = JSON.parse(headerContainer.getAttribute("data-props"));
const domContainer = document.getElementById("react-explore-app");
const queryParams = getUrlQueryParams(window.location.search);
if (queryParams.cl) {
  ReactDOM.render(
    <ExplorePreviewModal clid={queryParams.cl } pid={queryParams.pid } clientRoot={ dataProps["clientRoot"] }/>,
    domContainer
  );
} else {
  window.location = "/projects/";
}
*/


