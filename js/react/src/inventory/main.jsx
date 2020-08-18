import ReactDOM from "react-dom";
import React, { useMemo, useState, useEffect } from "react";
import httpGet from "../common/httpGet.js";
import { getUrlQueryParams } from "../common/queryParams.js";
import Table from "./table.jsx";
import PageHeader from "../common/pageHeader.jsx";
import {getChecklistPage} from "../common/taxaUtils";
import { GoogleMap, LoadScript, Marker, MarkerClusterer } from '@react-google-maps/api';
//const ScriptLoaded = require("../../docs/ScriptLoaded").default;

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { library } from "@fortawesome/fontawesome-svg-core";
import { faSearchPlus, faListUl, faChevronDown, faChevronUp } from '@fortawesome/free-solid-svg-icons'
library.add( faSearchPlus, faListUl, faChevronDown, faChevronDown)

function ChecklistTable(props) {
  const columns = useMemo(
    () => [
      {
				Header: 'Checklist Name',
				accessor: 'name', // accessor is the "key" in the data
			},
			{
				Header: 'Actions',
				accessor: 'clid',
				disableSortBy: true
			},
			{
				Header: 'Longitude',
				accessor: 'longcentroid',
				//disableSortBy: true
			},
    ],
    []
  );
  return (
    <div className="App">
      <Table columns={columns} data={props.checklists} pid={ props.pid }/>
    </div>
  );
}
function ProjectMap(props) {
	return (
      <LoadScript
      	googleMapsApiKey={ props.googleMapKey } 
      >
        <GoogleMap
          mapContainerStyle={{width: '1100px', height: '555px'}}
          center={{"lat":44.156944, "lng":-120.490556}}
          zoom={7}
        >
        	{ props.children } 
        </GoogleMap>
      </LoadScript>
	)
}


class InventoryDetail extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      pid: null,
      projName: '',
      managers: '',
      briefDescription: "",
      fullDescription: "",
      isPublic: null,
      checklists: []
    };
    this.getPid = this.getPid.bind(this);
  }

  getPid() {
    return parseInt(this.props.pid);
  }

  componentDidMount() {

		httpGet(`./rpc/api.php?pid=${this.props.pid}`)
			.then((res) => {
				// /projects/rpc/api.php?pid=2454
				res = JSON.parse(res);
				this.setState({
					projname: res.projname,
					managers: res.managers,
					briefDescription: res.briefDescription,
					fullDescription: res.fullDescription,
					isPublic: res.isPublic,
					checklists: res.checklists,
					//googleMapUrl: googleMapUrl
				});
				const pageTitle = document.getElementsByTagName("title")[0];
				pageTitle.innerHTML = `${pageTitle.innerHTML} ${res.projname}`;
			})
			.catch((err) => {
      	//window.location = "/";
				console.error(err);
			});
    
  }//componentDidMount

  render() {
		let pid = this.getPid();
		
		const clusterOptions = {
			imagePath:
				'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m', // so you must have m1.png, m2.png, m3.png, m4.png, m5.png and m6.png in that folder
			minimumClusterSize:
				7
		}
    return (
    <div className="wrapper">
			<div className="page-header">
					<PageHeader bgClass="explore" title={ this.state.projname } />
			</div>
      <div className="container inventory-detail" style={{ minHeight: "45em" }}>
        <div className="row">
          <div className="col">
            <h2 dangerouslySetInnerHTML={{__html: this.state.fullDescription}} />   
          </div>
        </div>
        <div className="row">
          <div className="col">
            <p>That makes this inventory both a great companion to that printed resource, and a powerful online tool on its own. Research checklists are pre-compiled by biologists. This is a very controlled method for building a species list, which allows for specific specimens to be linked to the species names within the checklist and thus serve as vouchers. Specimen vouchers are proof that the species actually occurs in the given area. If there is any doubt, one can inspect these specimens for verification or annotate the identification when necessary. </p>
            <p>Use the map to navigate to a specific area (or explore it), or select an area from the lists below to really dive in.</p>  
          </div>
        </div>
        <div className="row mt-2 project-header">
          <div className="col">
          	<h3>Interactive map</h3><span className="explain">(or explore areas from list below)</span>
          </div>
        </div>
        <div className="row map">
          <div className="col">
              <ProjectMap
              	googleMapKey={this.props.googleMapKey}
              >
              {
              	
              	(pid == 1 || pid == 2) &&
								<MarkerClusterer options={clusterOptions}>
          				{(clusterer) =>
										this.state.checklists.map((checklist,index) => {
											let position = {
												lat: checklist.latcentroid,
												lng: checklist.longcentroid
											}
											let href = getChecklistPage(this.props.clientRoot, checklist.clid, pid)
											return (
												<Marker
													key={checklist.clid}
													title={ checklist.name }
													position={position}
													clusterer={clusterer}
													onClick={()=>
														location.href = href
													}
												/>
										
											)
										})
									}
								</MarkerClusterer>
              }
              </ProjectMap>
          </div>
        </div>
        <div className="row mt-4 project-header ">
          <div className="col research-checklists">
          	<h3>Research checklists</h3><span className="explain">(referenced in the map above)</span>
          </div>
        </div>
        <div className="row mt-2 project-key project-checklists">
            <div className="col">
                <div className="project-icons">
                    <FontAwesomeIcon icon="list-ul" />
                </div>
                <span className="verticalSeparator"></span>
              	<p><strong>EXPLORE</strong> plants that have been discovered at the listed location.</p>
            </div>
        </div>
        <div className="row mt-2 project-key project-identify">
            <div className="col">
                <div className="project-icons">
                    <FontAwesomeIcon icon="search-plus" />
                </div>
                <span className="verticalSeparator"></span>
                <p><strong>IDENTIFY</strong> a plant you've discovered at that location, using a host of characteristics.</p>
            </div>
        </div>
        <div className="row mt-4 mb-4 checklists-table">
          <div className="col">
              <ChecklistTable checklists={ this.state.checklists } pid={ pid }/>
          </div>
        </div>
      </div>
    </div>
    );
  }
}

InventoryDetail.defaultProps = {
  pid: -1,
};

class InventoryChooser extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
    	projects: [],

    };
  }
	toggleProjectDisplay = (index) => {
		let newArr = this.state.projects;
		let newVal = 'default';
		if (this.state.projects[index].display == 'default') {
			newVal = 'expanded';
		} 
		newArr[index].display = newVal;
		this.setState({
			projects: newArr
		});

  }
  componentDidMount() {

		httpGet(`./rpc/api.php`)
			.then((res) => {
				// /projects/rpc/api.php
				res = JSON.parse(res);
			
				res.map((project,index) => {
					res[index].display = "default";
				})
				this.setState({
					projects: res
				});
				const pageTitle = document.getElementsByTagName("title")[0];
				pageTitle.innerHTML = `${pageTitle.innerHTML} Inventory Projects`;
			})
			.catch((err) => {
				// TODO: Something's wrong
				console.error(err);
			});
    
  }//componentDidMount

  render() {
    return (
    <div className="wrapper">
			<div className="page-header">
				<PageHeader bgClass="explore" title={ 'Inventory Projects' } />
      </div>
      <div className="container inventory-chooser" style={{ minHeight: "45em" }}>

        <div className="row">
          <div className="col">
            <h2>Inventories are curated species lists from a defined area. In short, it’s a great way to explore Oregon flora through the lens of where a plant actually calls home. </h2>
          </div>
        </div>
        <div className="row">
          <div className="col">
            <p>Inventories are species lists from a defined area. Frequently, a species checklist has been compiled by one or more researchers visiting a study area over a period of several years. In all instances, inventories represent a curated list of the flora of the area. Records associated with an inventory can include herbarium specimens, unvouchered observations and field photographs. OregonFloraalso has compiled over 7,980 species lists derived from observations; these are referenced by List #.</p>
						<p>Explore the flora of an inventoried area by selecting a site name to open its checklist. The list can be sorted by family, genus, species, or common name; it can also be displayed as a list of thumbnail images. Clicking on the key symbol opens the checklist as an interactive key.</p>
          </div>
        </div>
        <div className="row">
          <div className="col">
 								{
									this.state.projects.map((project,index) => {
										return (					
											<div key={index} className="project-item">
												{project.display == 'default' && 
										
														<div className="project-default">
															<div className="project-header">
                                                                <div className="more more-less" onClick={() => this.toggleProjectDisplay(index)}>
                                                                    More
                                                                    <FontAwesomeIcon icon="chevron-down" />
                                                                </div>
                                                                <div className="">
                                                                    <a className="btn btn-primary" role="button" href={ this.props.clientRoot + '/projects/index.php?pid=' + project.pid } >Explore</a>
																	<h3>{project.projname}</h3>
                                                                </div>
															</div>
															<div className="project-content" dangerouslySetInnerHTML={{__html: project.fulldescription}} />
														</div>
													
												}
												{project.display == 'expanded' && 
												
														<div className="project-expanded">
                                                            <div className="row">
                                                                <div className="project-image col-sm-8 pr-0">
                                                                    <img className="img-fluid" src={ this.props.clientRoot + '/images/inventory/flora_oregon_lg.png' } />
                                                                </div>
                                                                <div className="col-sm pl-0">
                                                                    <div className="project-map-image">
                                                                    <img className="img-fluid" src={ this.props.clientRoot + '/images/inventory/flora_or_map_lg.png' } />
                                                                    </div>
                                                                    <div className="project-description" dangerouslySetInnerHTML={{__html: project.fulldescription}} />
                                                                    <a className="btn btn-primary project-explore" role="button" href={ this.props.clientRoot + '/projects/index.php?pid=' + project.pid } >Explore</a>
                                                                </div>
                                                            </div>
                                                            <div className="less more-less" onClick={() => this.toggleProjectDisplay(index)}>
                                                                Less
                                                                <FontAwesomeIcon icon="chevron-up" />
                                                            </div>
														</div>
													
												}
											</div>
										);
									})
								}
          </div>
        </div>
      </div>
    </div>
    );
  }
}


const headerContainer = document.getElementById("react-header");
const dataProps = JSON.parse(headerContainer.getAttribute("data-props"));
const domContainer = document.getElementById("react-inventory-app");
const queryParams = getUrlQueryParams(window.location.search);
if (queryParams.search) {
  window.location = `./search.php?search=${encodeURIComponent(queryParams.search)}`;
} else if (queryParams.pid) {
  ReactDOM.render(
    <InventoryDetail pid={queryParams.pid } googleMapKey={ dataProps["googleMapKey"] } clientRoot={ dataProps["clientRoot"] }/>,
    domContainer
  );
} else {
  ReactDOM.render(
    <InventoryChooser/>,
    domContainer
  );
}