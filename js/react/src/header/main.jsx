import React from "react";
import ReactDOM from "react-dom";
import SearchWidget from "../common/search.jsx";

const dropDowns = [
  { title: "Explore Our Site" },
  { title: "Resources" },
  { title: "About" },
  { title: "Contribute" },
];

const dropDownChildren = {
  "Explore Our Site": [
    { title: "Mapping", href: "/spatial/index.php" },
    { title: "Interactive Key", href: "/checklists/dynamicmap.php?interface=key" },
    { title: "Plant Inventories", href: "/projects/index.php" },
    { title: "OSU Herbarium", href: "/collections/harvestparams.php?db[]=5,8,10,7,238,239,240,241" },
    { title: "Gardening with Natives", href: "/garden/index.php" },
    { title: "Image Search", href: "/imagelib/search.php" },
    { title: "Taxanomic Tree", href: "/taxa/admin/taxonomydisplay.php" },
  ],
  "Resources": [
    { title: "What's New", href: "/pages/whats-new.php" },
    { title: "Archived Newsletters", href: "/newsletters/index.php" },
    { title: "Links", href: "/pages/links.php" }
  ],
  "About": [
    { title: "Mission and History", href: "/pages/mission.php" },
    { title: "Contact Info", href: "/pages/contact.php" },
    { title: "Project Participants", href: "/pages/project-participants.php" },
  ],
  "Contribute": [
    { title: "Donate", href: "/pages/donate.php" },
    { title: "Volunteer", href: "/pages/volunteer.php" },
    { title: "Merchandise", href: "/pages/merchandise.php" },
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
    <div className="row mr-3" style={ props.style }>
      { props.children }
    </div>
  );
}

function getScollPos() {
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
      isCollapsed: getScollPos() > 80,
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

  onSearch(searchObj) {
    this.setState({ isLoading: true });
    let targetUrl = `${this.props.clientRoot}/taxa/`;
    if (searchObj.value !== -1) {
      targetUrl += `index.php?taxon=${searchObj.value}`;
    } else {
      targetUrl += `search.php?search=${ encodeURIComponent(searchObj.text) }`;
    }
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
        this.setState({isCollapsed: getScollPos() > (80 + siteHeader.offsetHeight)});
      }
    });
  }

  getLoginButtons() {
    if (this.props.userName !== "") {
      return (
        <HeaderButtonBar style={{ display: this.state.isCollapsed ? 'none' : 'flex' }}>
          <div className="col header-button mx-auto my-auto">Hello, { this.props.userName }!</div>
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
    return (
      <nav
        id="site-header"
        style={{ backgroundImage: `url(${this.props.clientRoot}/images/header/OF-Header_May8.png)` }}
        className={ `navbar navbar-expand-lg navbar-dark bg-dark site-header ${this.state.isCollapsed ? "site-header-scroll" : ''}` }>

        <a className="navbar-brand" href={ `${this.props.clientRoot}/` }>
          <img id="site-header-logo"
               src={
                 this.state.isCollapsed
                   ? `${this.props.clientRoot}/images/header/oregonflora-logo-sm.png`
                   : `${this.props.clientRoot}/images/header/oregonflora-logo.png`
               }
               alt="OregonFlora"/>
        </a>

        <div id="site-header-dropdowns" className="collapse navbar-collapse">
          <ul className="navbar-nav">
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

        <div className={ "ml-auto mr-4" + (this.state.isCollapsed ? " my-auto" : "") }>
          { this.getLoginButtons() }

          <div className="row">
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
    );
  }
}

const domContainer = document.getElementById("react-header");
const dataProps = JSON.parse(domContainer.getAttribute("data-props"));
ReactDOM.render(<HeaderApp clientRoot={ dataProps["clientRoot"] } userName={ dataProps["userName"] } />, domContainer);

      {/*
      <!-- Holds dropdowns on mobile -->
      <button
        id="site-header-navbar-toggler"
        className="navbar-toggler ml-auto"
        type="button"
        data-toggle="collapse"
        data-target="#site-header-dropdowns"
        aria-controls="navbarSupportedContent"
        aria-expanded="false"
        aria-label="Toggle navigation">

        <span className="navbar-toggler-icon"></span>
      </button>

      <!-- Dropdowns -->
      <div id="site-header-dropdowns" className="collapse navbar-collapse">
        <ul className="navbar-nav">
          <!-- Explore site -->
          <li className="nav-item dropdown">
            <a id="explore" className="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown"
               aria-haspopup="true" aria-expanded="false">
              Explore Our Site
            </a>
            <div className="dropdown-menu" aria-labelledby="explore">
              <a className="dropdown-item" href="<?php echo $clientRoot; ?>/spatial/index.php">Mapping</a>
              <a className="dropdown-item" href="<?php echo $clientRoot; ?>/checklists/dynamicmap.php?interface=key">Interactive
                Key</a>
              <a className="dropdown-item" href="<?php echo $clientRoot; ?>/projects/index.php">Plant Inventories</a>
              <a className="dropdown-item"
                 href="<?php echo $clientRoot; ?>/collections/harvestparams.php?db[]=5,8,10,7,238,239,240,241">OSU
                Herbarium</a>
              <a className="dropdown-item" href="<?php echo $clientRoot; ?>/garden/index.php">Gardening with Natives</a>
              <a className="dropdown-item" href="<?php echo $clientRoot; ?>/imagelib/search.php">Image Search</a>
              <a className="dropdown-item" href="<?php echo $clientRoot; ?>/taxa/admin/taxonomydisplay.php">Taxonomic
                Tree</a>
            </div>
          </li>

          <!-- Resources -->
          <li className="nav-item dropdown">
            <a id="resources" className="nav-link dropdown-toggle wht-txt" href="#" role="button" data-toggle="dropdown"
               aria-haspopup="true" aria-expanded="false">
              Resources
            </a>
            <div className="dropdown-menu" aria-labelledby="resources">
              <a className="dropdown-item" href="<?php echo $clientRoot; ?>/pages/whats-new.php">What's New</a>
              <a className="dropdown-item" href="<?php echo $clientRoot; ?>/newsletters/index.php">Archived
                Newsletter</a>
              <a className="dropdown-item" href="<?php echo $clientRoot; ?>/pages/links.php">Links</a>
            </div>
          </li>

          <!-- About -->
          <li className="nav-item dropdown">
            <a id="about" className="nav-link dropdown-toggle wht-txt" href="#" role="button" data-toggle="dropdown"
               aria-haspopup="true" aria-expanded="false">
              About
            </a>
            <div className="dropdown-menu" aria-labelledby="about">
              <a className="dropdown-item" href="<?php echo $clientRoot; ?>/pages/mission.php">Mission and History</a>
              <a className="dropdown-item" href="<?php echo $clientRoot; ?>/pages/contact.php">Contact Info</a>
              <a className="dropdown-item" href="<?php echo $clientRoot; ?>/pages/project-participants.php">Project
                Participants</a>
            </div>
          </li>

          <!-- Contribute -->
          <li className="nav-item dropdown">
            <a id="contribute" className="nav-link dropdown-toggle wht-txt" href="#" role="button"
               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              Contribute
            </a>
            <div className="dropdown-menu" aria-labelledby="contribute">
              <a className="dropdown-item" href="<?php echo $clientRoot; ?>/pages/donate.php">Donate</a>
              <a className="dropdown-item" href="<?php echo $clientRoot; ?>/pages/volunteer.php">Volunteer</a>
              <a className="dropdown-item" href="<?php echo $clientRoot; ?>/pages/merchandise.php">Merchandise</a>
            </div>
          </li>
        </ul>
      </div>
      <!-- Dropdowns end -->

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