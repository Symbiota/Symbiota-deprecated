import React from "react";
import Moment from 'react-moment';
import {getCommonNameStr, getTaxaPage} from "../common/taxaUtils";

function SearchResult(props) {
  const useGrid = props.viewType === "grid";

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
                  <span className="text-lowercase">{props.commonName}</span>
                  {useGrid ? <br/> : " - "}
                  <span className="font-italic">{props.sciName}</span>
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
						<img
							className={useGrid ? "card-img-top grid-image" : "d-inline-block mr-1 list-image"}
							alt={props.title}
							src={props.src}
						/>
						<div className={(useGrid ? "card-body" : "d-inline py-1") + " px-0"} style={{overflow: "hidden"}}>
							<div className={"card-text" + (useGrid ? "" : " d-inline")}>
								<span className="font-italic sci-name">{props.sciName}</span>
								<span className="author"> ({props.author})</span>
								{useGrid ? <br/> : <span dangerouslySetInnerHTML={{__html: ' &mdash; '}} /> }
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
				
      <SearchResultContainer viewType={ props.viewType }>
				{ props.searchResults.map((result) =>  {
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
				</SearchResultContainer>
			)
		}else{
			return (
				<SearchResultContainer viewType={ props.viewType }>
				{
						Object.entries(props.searchResults).map(([family, results]) => {
							return (
								<div key={ family }>
									<h4>{ family }</h4>					
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
							)
						})
				}
				</SearchResultContainer>
			)
		}
	}
  return <span style={{ display: "none" }}/>;
}
ExploreSearchContainer.defaultProps = {
  searchResults: [],
};

export { SearchResultContainer, SearchResult, ExploreSearchResult, ExploreSearchContainer };