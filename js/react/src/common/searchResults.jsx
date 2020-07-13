import React from "react";
import Moment from 'react-moment';

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

  if (props.display) {
  //console.log(props);
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
           			props.vouchers.length && 
           					
										<div className="vouchers">           		
										{
											props.vouchers.map((voucher) =>  {
												console.log(voucher);
												let eventDate = '';
												if (voucher.eventdate) {
													eventDate = voucher.eventdate.date;
													//console.log(dateObj.getMonth());
													//eventDate = ' ' + dateObj;
												}
												return (
													<span key={ voucher.occid } className={ "voucher" } >
														<span className={ "recorded-by" }>{voucher.recordedby} </span>
														<span className={ "event-date" }><Moment format="YYYY-MM-DD">{eventDate}</Moment></span>
														<span className={ "institution-code" }> [{ voucher.institutioncode }]</span>
													</span>
												)
											})
											.reduce((prev, curr) => [prev, ', ', curr])
										}
										</div>
           /*       
                  Object.entries(this.getRenderedItems())
					.map(([key, obj]) => {
						return (
							<span key={ key} className={ "synonym-item" } >
								<span className={ "synonym-sciname" }>{obj.sciname}</span>
								<span className={ "synonym-author" }> { obj.author }</span>
							</span>
						)
					})
					.reduce((prev, curr) => [prev, ', ', curr])
				 }
           */}       
                  
                  
                </div>
              </div>
            </div>
        </div>
      </a>
    );
  }

  return <span style={{ display: "none" }}/>;
}


export { SearchResultContainer, SearchResult, ExploreSearchResult };