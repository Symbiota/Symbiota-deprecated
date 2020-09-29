import ReactDOM from "react-dom";
import React from "react";
import { SearchResult, SearchResultContainer } from "../common/searchResults.jsx";
import httpGet from "../common/httpGet.js";
import { getUrlQueryParams } from "../common/queryParams.js";
import { getTaxaPage, getCommonNameStr } from "../common/taxaUtils";
import Loading from "../common/loading.jsx";
/*
this page formerly handled genus and family searches, so I've left the structure for that.
however, taxa/rpc/api.php doesn't currently support it
*/

function SearchPageHeader(props) {
	/*if (props.family) {
		return <h1 style={{ display: props.family !== null ? "initial" : "none"  }}>Search results for the { props.family } family</h1>;

	} else if (props.genus) {
		return <h1 style={{ display: props.genus !== null ? "initial" : "none" }}>Search results for the { props.genus } genus</h1>;

	} else*/ 
	if (props.results.length >= 1) {
		return (
			<h1 style={{ display: props.results.length > 1 ? "intial" : "none" }}>
				Results for "{ props.searchText }"
			</h1>
		);
	} else if (!props.isLoading){
		return (
			<div>
				<h1>
					Whoops, we didn't find any results for "{ props.searchText }"
				</h1>
				<button className="btn btn-primary my-4" onClick={ () => window.history.back() }>Go back</button>
			</div>
		);
	} else {
		return ("");
	}
}
class TaxaSearchResults extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      viewType: "grid",
      isLoading: true,
      results: []
    };

  }

  componentDidMount() {
  	if (this.props.searchText.length > 0) {
  		let url = `./rpc/api.php?search=${this.props.searchText}`;
  	  httpGet(url)
  	  	.then((res) => {
					res = JSON.parse(res);
					//console.log(res);
					if (res.length === 1) {
						window.location = `./index.php?taxon=${res[0].tid}`;
					} 
					this.setState({ results: res });
				}).catch((err) => {
					console.error(err);
				}).finally(() => {
					this.setState({ isLoading: false });
				});
  	}/*else if (this.props.genus.length > 0) {
  		let url = `./rpc/api.php?genus=${this.props.genus}`;
  	  httpGet(url)
  	  	.then((res) => {
					res = JSON.parse(res);
					this.setState({ results: res });

				}).catch((err) => {
					console.error(err);
				}).finally(() => {
					this.setState({ isLoading: false });
				});
  	}else if (this.props.family.length > 0) {
  		let url = `./rpc/api.php?family=${this.props.family}`;
  		console.log(url);
			httpGet(url)
				.then((res) => {
					res = JSON.parse(res);
					console.log(res);
					this.setState({ results: res });

				}).catch((err) => {
					console.error(err);
				}).finally(() => {
					this.setState({ isLoading: false });
				});

  	}*/
  
  }

  render() {
    return (
      <div className="mx-auto my-5 py-3" style={{ maxWidth: "75%" }}>
				<Loading 
					clientRoot={ this.props.clientRoot }
					isLoading={ this.state.isLoading }
				/>
        <SearchPageHeader 
        	results={ this.state.results }
        	genus={ this.props.genus }
        	family={ this.props.family }
        	searchText={ this.props.searchText }
        	isLoading={ this.state.isLoading }
        />
        <div style={{ minHeight: "30em" }}>
          <SearchResultContainer viewType={ this.state.viewType }>
            {
              this.state.results.map((result) => {
                if (result.images.length > 0) {
                  return (
                    <SearchResult
                      key={result.tid}
                      viewType="grid"
                      display={true}
                      href={ getTaxaPage(this.props.clientRoot, result.tid) }
                      src={ result.images[0].thumbnailurl }
                      commonName={ getCommonNameStr(result) }
                      sciName={ result.sciname ? result.sciname : '' }
                    />
                  );
                }
              })
            }
          </SearchResultContainer>
        </div>
      </div>
    );
  }
}

TaxaSearchResults.defaultProps = {
  results: [],
  //family: '',
  //genus: '',
  searchText: ""
};

const headerContainer = document.getElementById("react-header");
const dataProps = JSON.parse(headerContainer.getAttribute("data-props"));

const domContainer = document.getElementById("react-taxa-search-app");
const queryParams = getUrlQueryParams(window.location.search);

//let family = TaxaSearchResults.defaultProps.family;
//let genus = TaxaSearchResults.defaultProps.genus;
let searchText = TaxaSearchResults.defaultProps.searchText;

if (queryParams.search) {
	searchText = queryParams.search.trim();
}/*else if (queryParams.family) {
	family = queryParams.family.trim();
}else if (queryParams.genus) {
	genus = queryParams.genus.trim();
}*/else {
  window.location = "/";
}

ReactDOM.render(<TaxaSearchResults 
									clientRoot={ dataProps["clientRoot"] } 
									searchText={ searchText } 
								/>, domContainer);
/*
genus={ genus }
family={ family } 
*/								
								
								
