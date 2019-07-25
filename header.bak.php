<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

<!-- Compat stuff -->
<link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,300i,400,400i,600,600i,700,700i" rel="stylesheet" type="text/css">
<link rel='stylesheet' href='//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' type='text/css' media='all' />

<!-- Bootstrap Deps -->
<script
  src="https://code.jquery.com/jquery-3.4.1.min.js"
  integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
  crossorigin="anonymous">
</script>
<script
  src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"
  integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1"
  crossorigin="anonymous">
</script>

<!-- Bootstrap -->
<link
  rel="stylesheet"
  href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
  integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T"
  crossorigin="anonymous">
<script
  src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"
  integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM"
  crossorigin="anonymous">
</script>

<style>
  #main-nav {
    font-family: 'Source Sans Pro', sans-serif;
    font-weight: bold;
  }

  #navbar-background {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 75%;
    z-index: -1;

    background-image: url(<?php echo $clientRoot; ?>/images/layout/whois-bg.jpg);
    background-size: 100% auto;
  }

  /* Display dropdown on hover */
  .hover-dropdown:hover .dropdown-menu {
    display: block;
  }

  .wht-txt {
    text-transform: uppercase;
    color: white !important;
  }

  /* Get rid of caret */
  .nav-link.dropdown-toggle::after {
    display: none;
  }

  .drk-grn {
    background-color: rgba(0, 100, 0, 0.5) !important;
  }

  #autocomplete-results .dropdown-item {
    text-transform: capitalize;
  }
</style>

<link href="../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
<link href="../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />

<!-- Navbar -->
<nav id="main-nav" class="navbar navbar-expand-lg sticky-top">
  <div id="navbar-background" class="shadow"></div>
  <a class="navbar-brand" href="<?php echo $clientRoot; ?>/index.php">
    <img id="logo" src="<?php echo $clientRoot; ?>/images/layout/new-logo.png" alt="Oregon Flora">
  </a>

  <ul class="navbar-nav">
    <!-- Explore our Site -->
    <li class="nav-item dropdown hover-dropdown">
      <a class="nav-link dropdown-toggle wht-txt" href="#" id="explore">
        Explore Our Site
      </a>
      <div class="dropdown-menu" aria-labelledby="explore">
        <a class="dropdown-item" href="<?php echo $clientRoot; ?>/spatial/index.php">Mapping</a>
        <a class="dropdown-item" href="<?php echo $clientRoot; ?>/checklists/dynamicmap.php?interface=key">Interactive Key</a>
        <a class="dropdown-item" href="<?php echo $clientRoot; ?>/projects/index.php">Plant Inventories</a>
        <a class="dropdown-item" href="<?php echo $clientRoot; ?>/collections/harvestparams.php?db[]=5,8,10,7,238,239,240,241">OSU Herbarium</a>
        <a class="dropdown-item" href="<?php echo $clientRoot; ?>/garden/index.php">Gardening with Natives</a>
        <a class="dropdown-item" href="<?php echo $clientRoot; ?>/imagelib/search.php">Image Search</a>
        <a class="dropdown-item" href="<?php echo $clientRoot; ?>/taxa/admin/taxonomydisplay.php">Taxonomic Tree</a>
      </div>
    </li>

    <!-- Resources -->
    <li class="nav-item dropdown hover-dropdown">
      <a class="nav-link dropdown-toggle wht-txt" href="#" id="resources">
        Resources
      </a>
      <div class="dropdown-menu" aria-labelledby="resources">
        <a class="dropdown-item" href="<?php echo $clientRoot; ?>/pages/whats-new.php">What's New</a>
        <a class="dropdown-item" href="<?php echo $clientRoot; ?>/newsletters/index.php">Archived Newsletter</a>
        <a class="dropdown-item" href="<?php echo $clientRoot; ?>/pages/links.php">Links</a>
      </div>
    </li>

    <!-- About -->
    <li class="nav-item dropdown hover-dropdown">
      <a class="nav-link dropdown-toggle wht-txt" href="#" id="about">
        About
      </a>
      <div class="dropdown-menu" aria-labelledby="about">
        <a class="dropdown-item" href="<?php echo $clientRoot; ?>/pages/mission.php">Mission and History</a>
        <a class="dropdown-item" href="<?php echo $clientRoot; ?>/pages/contact.php">Contact Info</a>
        <a class="dropdown-item" href="<?php echo $clientRoot; ?>/pages/project-participants.php">Project Participants</a>
      </div>
    </li>

    <!-- Support -->
    <li class="nav-item dropdown hover-dropdown">
      <a class="nav-link dropdown-toggle wht-txt" href="#" id="support">
        Contribute
      </a>
      <div class="dropdown-menu" aria-labelledby="support">
        <a class="dropdown-item" href="<?php echo $clientRoot; ?>/pages/donate.php">Donate</a>
        <a class="dropdown-item" href="<?php echo $clientRoot; ?>/pages/volunteer.php">Volunteer</a>
        <a class="dropdown-item" href="<?php echo $clientRoot; ?>/pages/merchandise.php">Merchandise</a>
      </div>
    </li>
  </ul> <!-- Dropdowns -->

  <!-- login-search -->
  <div class="my-4 ml-auto mr-1 p-0" id="login-search">
      <?php
      if($userDisplayName) {
      ?>
        <nav id="login-nav" class="navbar navbar-expand-sm mb-4 p-0" style="font-size: 0.8em; text-align: center;">
          <div class="nav-item wht-txt">Welcome <b><?php echo $userDisplayName; ?></b>!</div>
          <div class="nav-link"><a class="wht-txt" href="<?php echo $clientRoot; ?>/profile/viewprofile.php">My Profile</a></div>
          <div class="nav-link"><a class="wht-txt" href="<?php echo $clientRoot; ?>/profile/index.php?submit=logout">Logout</a></div>
        </nav>
      <?php
      }
      else{
      ?>
        <div class="container-fluid mb-4" style="font-size: 0.8em; text-align: right;">
          <a class="wht-txt" href="<?php echo $clientRoot."/profile/index.php?refurl=".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']; ?>">
            Log In
          </a>
        </div>
      <?php
      }
      ?>
  </div> <!-- #login-search -->

  <!-- Search -->
  <form
    class="form-inline ml-auto mr-0"
    name="quick-search"
    id="quick-search"
    autocomplete="off"
    action="<?php echo $clientRoot . '/taxa/index.php'?>">
    <div class="input-group">
      <div class="dropdown">
        <input id="search-term" name="taxon" type="text" class="form-control dropdown-toggle" data-toggle="dropdown">
        <div id="autocomplete-results" class="dropdown-menu" aria-labelledby="search-term">
          <a class="dropdown-item" onclick="document.getElementById('search-term').value = this.innerHTML;" href="#"></a>
          <a class="dropdown-item" onclick="document.getElementById('search-term').value = this.innerHTML;" href="#"></a>
          <a class="dropdown-item" onclick="document.getElementById('search-term').value = this.innerHTML;" href="#"></a>
          <a class="dropdown-item" onclick="document.getElementById('search-term').value = this.innerHTML;" href="#"></a>
          <a class="dropdown-item" onclick="document.getElementById('search-term').value = this.innerHTML;" href="#"></a>
        </div>
      </div>
      <div class="input-group-append m-0">
        <button id="search-btn" class="btn dropdown-toggle drk-grn m-0" data-toggle="dropdown" type="button">Search</button>
        <div class="dropdown-menu" aria-labelledby="search-btn">
          <a id="search-type-cn" class="dropdown-item" href="#">Common Name Search</a>
          <a id="search-type-tx" class="dropdown-item" href="#">Taxon Search</a>
        </div>
      </div>
    </div>
  </form>

  <script>
    window.onload = () => {
      const commonSearchId = 3;
      const taxonSearchId = 5;
      const autocompleteTerms = "<?php echo $clientRoot . '/collections/rpc/taxalist.php?l=5&term=' ?>";

      function getAutocompleteTerms(url, searchTerm, searchType) {
        return new Promise((resolve, reject) => {
          try {
            $.getJSON(url + searchTerm + "&t=" + searchType, (data) => {
              resolve(data);
            });
          } catch(err) {
            reject(err);
          }
        });
      }

      function onSearchTypeSelected(searchType) {
        const form = $("#quick-search");
        let action;
        let searchTermName;
        if (searchType === "cn") {
          action = "<?php echo $clientRoot . '/taxa/common.php'; ?>";
          searchTermName = "common";
        } else if (searchType === "tx") {
          action = "<?php echo $clientRoot . '/taxa/index.php'; ?>";
          searchTermName = "taxon";
        }
        form.attr("action", action);
        $("#search-term").attr("name", searchTermName);

        if ($("#search-term").val() === '') {
          alert("Please enter a search term");
        } else {
          form.submit();
        }
      }

      $("#search-term").bind("keyup", "blur", () => {
        if ($("#search-term").val() === "") {
          $("#autocomplete-results").children().text("");
        }
      });

      function collapseNavBar() {
        // $("#logo").attr("src", "<?php echo $clientRoot; ?>/images/layout/new-logo-sm.png");
        // $("#login-nav").hide();
        // $("#login-search").removeClass("my-4").addClass("my-2");
      }

      function expandNavBar() {
        // $("#logo").attr("src", "<?php echo $clientRoot; ?>/images/layout/new-logo.png");
        // $("#login-nav").show();
        // $("#login-search").removeClass("my-2").addClass("my-4");
      }

      $("#search-term").bind("keydown", () => {
        if ($("#search-term").val() !== "") {
          Promise.all([
            getAutocompleteTerms(autocompleteTerms, $("#search-term").val(), taxonSearchId),
            getAutocompleteTerms(autocompleteTerms, $("#search-term").val(), commonSearchId),
          ])
          .then(([taxonSuggestions, commonSuggestions]) => {
            if (taxonSuggestions instanceof Array) {
              for (let i = 0; i < 5; i++) {
                // Fill the odd slots with common names, if any exist
                if (i % 2 == 1 && commonSuggestions instanceof Array && commonSuggestions.length > i) {
                  $("#autocomplete-results").children().eq(i).text(commonSuggestions[i]);
                } else {
                  $("#autocomplete-results").children().eq(i).text(taxonSuggestions[i]);
                }
              }
            }
          })
          .catch((err) => { console.error(err); });
        }
      });

      $("#search-type-cn").click(() => { onSearchTypeSelected("cn"); });
      $("#search-type-tx").click(() => { onSearchTypeSelected("tx"); });

      $(document).scroll((e) => {
        if (e.pageY > 170) {
          collapseNavBar();
        } else {
          expandNavBar();
        }
      });
    };
  </script>

</nav>

<div id="site-content">
