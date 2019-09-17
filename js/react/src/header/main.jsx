function getScollPos() {
  return document.body.scrollTop || document.documentElement.scrollTop;
}

function ImageLink(props) {
  return (
    <a href={ props.href }>
      <img src={ props.src } alt={ props.src } />
    </a>
  );
}

class HeaderApp extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      isCollapsed: getScollPos() > 80,
      scrollLock: false
    };
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

  render() {
    return (
      <nav
        id="site-header"
        style={{ backgroundImage: `url(${this.props.clientRoot}/images/header/OF-Header_May8.png)` }}
        className={ `navbar navbar-expand-lg navbar-dark bg-dark site-header ${this.state.isCollapsed ? "site-header-scroll" : ''}` }>

        <a className="navbar-brand" href={ `${this.props.clientRoot}/` }>
          <img id="site-header-logo"
               src={ this.state.isCollapsed ? "/images/header/oregonflora-logo-sm.png" : "/images/header/oregonflora-logo.png" }
               alt="OregonFlora"/>
        </a>


      </nav>
    );
  }
}

module.exports = {
  "renderHeader": (props, targetNode) => {
    const component = React.createElement(HeaderApp, props, null);
    ReactDOM.render(component, targetNode);
    return component;
  }
};

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