import ReactDOM from "react-dom";
import React from "react";
import { SearchResult, SearchResultContainer } from "../common/searchResults.jsx";
import httpGet from "../common/httpGet.js";
import { getUrlQueryParams } from "../common/queryParams.js";
import { getTaxaPage, getCommonNameStr } from "../common/taxaUtils";

const CLIENT_ROOT = "..";

class TaxaSearchResults extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      viewType: "grid"
    };

    this.getPageHeader = this.getPageHeader.bind(this);
  }

  getPageHeader() {
    if (this.props.family) {
      return <h1 style={{ display: this.props.family !== null ? "initial" : "none"  }}>Search results for the { this.props.family } family</h1>;

    } else if (this.props.genus) {
      return <h1 style={{ display: this.props.genus !== null ? "initial" : "none" }}>Search results for the { this.props.genus } genus</h1>;

    } else if (this.props.results.length >= 1) {
      return (
        <h1 style={{ display: this.props.results.length > 1 ? "intial" : "none" }}>
          Results for "{ this.props.searchText }"
        </h1>
      );
    } else {
      return (
        <div>
          <h1>
            Whoops, we didn't find any results for "{ this.props.searchText }"
          </h1>
          <button className="btn btn-primary my-4" onClick={ () => window.history.back() }>Go back</button>
        </div>
      );
    }
  }

  render() {
    return (
      <div className="mx-auto my-5 py-3" style={{ maxWidth: "75%" }}>
        { this.getPageHeader() }
        <div style={{ minHeight: "30em" }}>
          <SearchResultContainer viewType={ this.state.viewType }>
            {
              this.props.results.map((result) => {
                if (result.images.length > 0) {
                  return (
                    <SearchResult
                      key={result.tid}
                      viewType="grid"
                      display={true}
                      href={ getTaxaPage(CLIENT_ROOT, result.tid) }
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
  family: null,
  genus: null,
  searchText: ""
};

const domContainer = document.getElementById("react-taxa-search-app");
const queryParams = getUrlQueryParams(window.location.search);

if (queryParams.search) {
	let query = queryParams.search.trim();
  httpGet(`./rpc/api.php?search=${query}`).then((res) => {
    res = JSON.parse(res);
    if (res.length === 1) {
      window.location = `./index.php?taxon=${res[0].tid}`

    } else {
      ReactDOM.render(<TaxaSearchResults results={ res } searchText={ decodeURIComponent(query) } />, domContainer);
    }
  }).catch((err) => {
    console.error(err);
  })
} else if (queryParams.family) {
	//console.log(`./rpc/api.php?family=${queryParams.family}`);
  httpGet(`./rpc/api.php?family=${queryParams.family}`).then((res) => {
    res = JSON.parse(res);
    ReactDOM.render(<TaxaSearchResults results={ res } family={ queryParams.familyName } />, domContainer);

  }).catch((err) => {
    console.error(err);
  });

} else if (queryParams.genus) {
  httpGet(`./rpc/api.php?genus=${queryParams.genus}`).then((res) => {
    res = JSON.parse(res);
    ReactDOM.render(<TaxaSearchResults results={ res } genus={ queryParams.genusName } />, domContainer);

  }).catch((err) => {
    console.error(err);
  });

} else {
  window.location = "/";
}