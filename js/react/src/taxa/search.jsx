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
  }

  render() {
    return (
      <div className="mx-auto my-5 py-2" style={{ maxWidth: "75%" }}>
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
    );
  }
}

TaxaSearchResults.defaultProps = {
  results: []
};

const domContainer = document.getElementById("react-taxa-search-app");
const queryParams = getUrlQueryParams(window.location.search);
if (queryParams.search) {
  httpGet(`./rpc/api.php?search=${queryParams.search}`).then((res) => {
    res = JSON.parse(res);
    if (res.length === 1) {
      window.location = `./index.php?taxon=${res[0].tid}`

    } else if (res.length > 1) {
      ReactDOM.render(<TaxaSearchResults results={ res } />, domContainer);

    } else {
      window.location = "/";

    }
  }).catch((err) => {
    console.error(err);
  })
} else {
  window.location = "/";
}