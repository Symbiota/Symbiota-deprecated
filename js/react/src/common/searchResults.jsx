import React from "react";
import {getCommonNameStr, getTaxaPage} from "../common/taxaUtils";

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { library } from "@fortawesome/fontawesome-svg-core";
import { faSquare } from '@fortawesome/free-solid-svg-icons'
library.add( faSquare );

function SearchResult(props) {
  const useGrid = props.viewType === "grid";
  let nameFirst = '';
  let nameSecond = '';
	if (props.sortBy == 'vernacularName') {
		nameFirst = props.commonName;
		nameSecond = props.sciName;
	}else {
		nameFirst = props.sciName;
		nameSecond = props.commonName;
	}
  if (props.display) {
    return (
      <a href={props.href} className="text-decoration-none" style={{ maxWidth: "185px" }} target="_blank">
        <div className={ "card search-result " + (useGrid ? "grid-result" : "list-result") }>
            <div className={useGrid ? "" : "card-body"}>
              <img
                className={useGrid ? "card-img-top grid-image" : "d-inline-block mr-1 list-image"}
                alt={props.title}
                src={props.src}
              />
              <div className={(useGrid ? "card-body" : "d-inline py-1") + " px-0"} style={{overflow: "hidden"}}>
                <div className={"card-text" + (useGrid ? "" : " d-inline")}>
                  <span className="">{nameFirst}</span>
                  {useGrid ? <br/> : " - "}
                  <span className="font-italic">{nameSecond}</span>
                </div>
              </div>
            </div>
        </div>
      </a>
    );
  }

  return <span style={{ display: "none" }}/>;
}

class SearchResultContainer extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    return (
      <div
        id="search-results"
        className={ "mt-4 w-100" + (this.props.viewType === "grid" ? " search-result-grid" : "") }
      >
        { this.props.children }
      </div>
    );
  }
}

function ExploreSearchResult(props) {
  const useGrid = props.viewType === "grid";

	return (
		<a href={props.href} className="text-decoration-none" style={{ maxWidth: "185px" }} target="_blank">
			<div className={ "card search-result " + (useGrid ? "grid-result" : "list-result") }>
					<div className={useGrid ? "" : "card-body"}>
						{useGrid &&
							<img
								className={useGrid ? "card-img-top grid-image" : "d-inline-block mr-1 list-image"}
								alt={props.title}
								src={props.src}
							/>
						}
						{!useGrid && 
							<FontAwesomeIcon icon="square" />
						}
						<div className={(useGrid ? "card-body" : "d-inline py-1") + " px-0"} style={{overflow: "hidden"}}>
							<div className={"card-text" + (useGrid ? "" : " d-inline")}>
								<span className="font-italic sci-name">{props.sciName}</span>
								{
									props.showTaxaDetail === 'on' &&
									<span className="author"> ({props.author})</span>
								}
								{ !useGrid && props.commonName.length > 0? <span dangerouslySetInnerHTML={{__html: ' &mdash; '}} /> :''}
								<span className="text-lowercase common-name">{props.commonName}</span>
								{
									props.showTaxaDetail === 'on' && props.vouchers.length && 
								
										<div className="vouchers">Vouchers:&nbsp;          		
										{
											props.vouchers.map((voucher) =>  {
												return (
													<span key={ voucher.occid } className={ "voucher" } >
														<span className={ "recorded-by" }>{voucher.recordedby} </span>
														<span className={ "event-date" }>{voucher.eventdate}</span>
														<span className={ "institution-code" }> [{ voucher.institutioncode }]</span>
													</span>
												)
											})
											.reduce((prev, curr) => [prev, ', ', curr])
										}
										</div>
								}                         
							</div>
						</div>
					</div>
			</div>
		</a>
	)
}
function ExploreSearchContainer(props) {
  const useGrid = props.viewType === "grid";
	if (props.searchResults) {
		if (props.sortBy === 'taxon') {		
			return (
				
      
				<div
					id="search-results"
					className={ "mt-4 w-100" + (props.viewType === "grid" ? " search-result-grid" : "") }
				>
				{ props.searchResults.taxonSort.map((result) =>  {
						//console.log(result);
						return (
							<ExploreSearchResult
								key={ result.tid }
								viewType={ props.viewType }
								showTaxaDetail={ props.showTaxaDetail }
								href={ getTaxaPage(props.clientRoot, result.tid) }
								src={ result.thumbnail }
								commonName={ getCommonNameStr(result) }
								sciName={ result.sciname ? result.sciname : '' }
								author={ result.author ? result.author : '' }
								vouchers={  result.vouchers ? result.vouchers : '' }
							/>
						)
					})
				}
				</div>
			)
		}else{
			return (
				
				<div
					id="search-results"
					className={ "mt-2 w-100" }
				>
				{
						Object.entries(props.searchResults.familySort).map(([family, results]) => {
							return (
								<div key={ family } className="family-group">
									<h4>{ family }</h4>	
									<div className={ (props.viewType === "grid" ? " search-result-grid" : "") } >
									{ results.map((result) =>  {
											return (
												<ExploreSearchResult
													key={ result.tid }
													viewType={ props.viewType }
													showTaxaDetail={ props.showTaxaDetail }
													href={ getTaxaPage(props.clientRoot, result.tid) }
													src={ result.thumbnail }
													commonName={ getCommonNameStr(result) }
													sciName={ result.sciname ? result.sciname : '' }
													author={ result.author ? result.author : '' }
													vouchers={  result.vouchers ? result.vouchers : '' }
												/>
											)
										})
									}
									</div>
								</div>
							)
						})
				}
				</div>
			)
		}
	}
  return <span style={{ display: "none" }}/>;
}
ExploreSearchContainer.defaultProps = {
  searchResults: [],
};

export { SearchResultContainer, SearchResult, ExploreSearchResult, ExploreSearchContainer };