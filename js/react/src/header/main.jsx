import React from "react";
import ReactDOM from "react-dom";
import SearchWidget from "../common/search.jsx";

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { library } from "@fortawesome/fontawesome-svg-core";
import { faBars } from '@fortawesome/free-solid-svg-icons'
library.add(faBars)


const RANK_FAMILY = 140;
const RANK_GENUS = 180;

const dropDowns = [
  { title: "Tools" },
  { title: "Resources" },
  { title: "About" },
  { title: "Contribute" },
];

const dropDownChildren = {
  "Tools": [
    { title: "Mapping", href: "/spatial/index.php" },
    { title: "Identify Plants", href: "/checklists/dynamicmap.php?interface=key" },
    { title: "Inventories", href: "/projects/index.php" },
    { title: "OSU Herbarium", href: "/collections/harvestparams.php?db[]=5,8,10,7,238,239,240,241" },
    { title: "Grow Natives", href: "/garden/index.php" },
    //{ title: "Image Search", href: "/imagelib/search.php" },
    { title: "Taxonomic Tree", href: "/taxa/admin/taxonomydisplay.php" },
  ],
  "Resources": [
    { title: "Tutorials and Tips", href: "/pages/tutorials.php" },
    { title: "News and Events", href: "/pages/news-events.php" },
    { title: "Archived Newsletters", href: "/newsletters/index.php" },
    { title: "Links", href: "/pages/links.php" }
  ],
  "About": [
    { title: "Mission and History", href: "/pages/mission.php" },
    { title: "Contact Info", href: "/pages/contact.php" },
    { title: "Partners", href: "/pages/project-participants.php" },
  ],
  "Contribute": [
    { title: "Donate", href: "/pages/donate.php" },
    { title: "Volunteer", href: "/pages/volunteer.php" },
    { title: "OregonFlora Store", href: "/pages/store.php" },
  ]
};

function HeaderButton(props) {
  return (
    <a href={ props.href }>
      <button className={ "col header-button" }>
        { props.title }
      </button>
    </a>
  );
}

function HeaderButtonBar(props) {
  return (
    <div className="row header-button-bar" style={ props.style }>
      { props.children }
    </div>
  );
}

function getScrollPos() {
  return document.body.scrollTop || document.documentElement.scrollTop;
}

function HeaderDropdownItem(props) {
  return (
    <a className="dropdown-item" href={ props.href }>{ props.title }</a>
  );
}

function HeaderDropdown(props) {
  let id = props.title.replace(/[^a-zA-Z_]/g, '').toLowerCase();
  id = `header-dropdown-${id}`;
  return (
    <li className="nav-item dropdown">
      <a
        id={ id }
        className="nav-link dropdown-toggle"
        href="#"
        role="button"
        data-toggle="dropdown"
        aria-haspopup="true"
        aria-expanded="false">
        { props.title }
      </a>
      <div className="dropdown-menu" aria-labelledby={ id }>
        { props.children }
      </div>
    </li>
  );
}

class HeaderApp extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      isCollapsed: getScrollPos() > 0,
      scrollLock: false,
      isLoading: false,
      searchText: '',
    };

    this.onSearchTextChanged = this.onSearchTextChanged.bind(this);
    this.onSearch = this.onSearch.bind(this);
  }

  onSearchTextChanged(e) {
    this.setState({ searchText: e.target.value });
  }

  // "searchObj" is the JSON object returned from ../webservices/autofillsearch.php
  // WARNING - this code is copied exactly on home/main.jsx
  onSearch(searchObj) {
  	//console.log(searchObj);
    this.setState({ isLoading: true });
    let targetUrl = `${this.props.clientRoot}/taxa/`;
    /*
    if (searchObj.rankId && searchObj.rankId === RANK_FAMILY) {
      targetUrl += `search.php?family=${searchObj.taxonId}&familyName=${searchObj.text}`;

    } else if (searchObj.rankId && searchObj.rankId === RANK_GENUS) {
      targetUrl += `search.php?genus=${searchObj.taxonId}&genusName=${searchObj.text}`;

    } else {*/
      if (searchObj.taxonId) {
        if (searchObj.taxonId === searchObj.tidaccepted) {
	        targetUrl += `index.php?taxon=${searchObj.taxonId}`;
	      }else{
	        targetUrl += `index.php?taxon=${searchObj.tidaccepted}&synonym=${searchObj.taxonId}`;
	      }
      } else {
        targetUrl += `search.php?search=${ encodeURIComponent(searchObj.text) }`;
      }
    /*}*/

    window.location = targetUrl;
  }

  componentDidMount() {
    const siteHeader = document.getElementById("site-header");
    siteHeader.addEventListener("transitionstart", () => {
      this.setState({ scrollLock: true });
    });
    siteHeader.addEventListener("transitionend", () => {
      this.setState({ scrollLock: false });
    });
    siteHeader.addEventListener("transitioncancel", () => {
      this.setState({ scrollLock: false });
    });
    window.addEventListener("scroll", () => {
      if (!this.state.scrollLock) {
        this.setState({isCollapsed: getScrollPos() > (siteHeader.offsetHeight)});
      }
    });
  }

  getLoginButtons() {
    if (this.props.userName !== "") {
      return (
        <HeaderButtonBar style={{ display: this.state.isCollapsed ? 'none' : 'flex' }}>
        	
    			<a href="" className={ "disabled" }>
						<button className={ "col header-button" }>
							{ "Hello, " +  this.props.userName  + "!"}
						</button>
					</a>
          <HeaderButton title="My Profile" href={ `${this.props.clientRoot}/profile/viewprofile.php` } />
          <HeaderButton title="Logout" href={ `${this.props.clientRoot}/profile/index.php?submit=logout` } />
        </HeaderButtonBar>
      );
    }

    return (
      <HeaderButtonBar style={{ display: this.state.isCollapsed ? 'none' : 'flex' }}>
        <HeaderButton title="Contact" href={ `${this.props.clientRoot}/pages/contact.php` } />
        <HeaderButton title="Donate" href={ `${this.props.clientRoot}/pages/donate.php` } />
        <HeaderButton title="Login" href={ `${this.props.clientRoot}/profile/index.php?refurl=${ location.pathname }` } />
      </HeaderButtonBar>
    );
  }

  render() {
  	let lgLogo = `${this.props.clientRoot}/images/header/oregonflora-logo.png`;
  	let smLogo = `${this.props.clientRoot}/images/header/oregonflora-logo-sm.png`;
    return (
    <div className="header-wrapper" style={{ backgroundImage: `url(${this.props.clientRoot}/images/header/OF-Header_May8.png)` }}>
      <nav
        id="site-header"
        className={ `container navbar navbar-expand-lg navbar-dark site-header ${this.state.isCollapsed ? "site-header-scroll" : ''}` }>



        <div id="site-header-dropdowns-wrapper" className="">
        
					<button
						id="site-header-navbar-toggler"
						className="navbar-toggler ml-auto"
						type="button"
						data-toggle="collapse"
						data-target="#site-header-dropdowns"
						aria-controls="navbarSupportedContent"
						aria-expanded="false"
						aria-label="Toggle navigation">

						<span className="menu-toggle">
							<FontAwesomeIcon 
								icon="bars"	
								size="2x"
						/></span>
					</button>
        
          <ul id="site-header-dropdowns" className="collapse navbar-collapse navbar-nav">
            {
              dropDowns.map((dropdownData) => {
                return (
                  <HeaderDropdown key={ dropdownData.title } title={ dropdownData.title }>
                    {
                      dropDownChildren[dropdownData.title].map((dropDownChildData) => {
                        return (
                          <HeaderDropdownItem
                            key={ dropDownChildData.title }
                            title={ dropDownChildData.title }
                            href={ `${ this.props.clientRoot }${ dropDownChildData.href }` }
                          />
                        )
                      })
                    }
                  </HeaderDropdown>
                )
              })
            }
          </ul>
        </div>

        <a className="navbar-brand" href={ `${this.props.clientRoot}/` }>
        	{ this.state.isCollapsed ? (
        	<picture>
          	<img id="site-header-logo"
               src={smLogo}
               alt="OregonFlora"
          	/>
          </picture>
					) : (
						<picture>
	        		<source media="(max-width: 992px)" srcSet={ smLogo } />
  	      		<source media="(min-width: 992px)" srcSet={ lgLogo } />
							<img id="site-header-logo"
								 src={lgLogo}
								 alt="OregonFlora"
							/>
						</picture>
						)
					}        
        </a>

      
        <div className={ "search-wrapper ml-auto"}>
          { this.getLoginButtons() }
					<button
						id="site-search-toggler"
						type="button" 
						data-toggle="collapse" 
						data-target="#search-widget-wrapper" 
						aria-expanded="false" 
						aria-controls="search-widget-wrapper"
					>
						Plant search
					</button>
          <div className="row widget-wrapper collapse" id="search-widget-wrapper">
            <SearchWidget
              placeholder="Search all plants"
              clientRoot={ this.props.clientRoot }
              isLoading={ this.state.isLoading }
              textValue={ this.state.searchText }
              onTextValueChanged={ this.onSearchTextChanged }
              onSearch={ this.onSearch }
              suggestionUrl={ `${this.props.clientRoot}/webservices/autofillsearch.php` }
            />
          </div>
        </div>
      </nav>
    </div>
    );
  }
}

const domContainer = document.getElementById("react-header");
const dataProps = JSON.parse(domContainer.getAttribute("data-props"));
ReactDOM.render(<HeaderApp clientRoot={ dataProps["clientRoot"] } userName={ dataProps["userName"] } />, domContainer);

      {/*
      <!-- Holds dropdowns on mobile -->

      
      <!-- Search -->
      <form
        className="form-inline ml-auto"
        name="quick-search"
        id="quick-search"
        autoComplete="off"
        action="<?php echo $clientRoot . '/taxa/index.php'?>">
        <div className="input-group">
          <div className="dropdown">
            <input id="search-term" name="taxon" type="text" className="form-control dropdown-toggle"
                   data-toggle="dropdown" placeholder="Search all plants">
              <div id="autocomplete-results" className="dropdown-menu" aria-labelledby="search-term">
                <a className="dropdown-item" onClick="document.getElementById('search-term').value = this.innerHTML;"
                   href="#" />
                <a className="dropdown-item" onClick="document.getElementById('search-term').value = this.innerHTML;"
                   href="#" />
                <a className="dropdown-item" onClick="document.getElementById('search-term').value = this.innerHTML;"
                   href="#" />
                <a className="dropdown-item" onClick="document.getElementById('search-term').value = this.innerHTML;"
                   href="#" />
                <a className="dropdown-item" onClick="document.getElementById('search-term').value = this.innerHTML;"
                   href="#" />
              </div>
          </div>
          <input
            id="search-btn"
            src="<?php echo $clientRoot; ?>/images/header/search-white.png"
            className="mt-auto mb-auto"
            type="image" />
        </div>
      </form>
      <!-- Search end -->

    </nav>
  <!-- Header end -->
  */}