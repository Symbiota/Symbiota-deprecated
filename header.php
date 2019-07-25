<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

<link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700" rel="stylesheet" type="text/css">
<link
  rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
  integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T"
  crossorigin="anonymous">
<link rel='stylesheet' href='<?php echo "$clientRoot/css/header.css"?>' type='text/css'>

<script
  src="https://code.jquery.com/jquery-3.4.1.min.js"
  integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
  crossorigin="anonymous"></script>
</script>
<script
  src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"
  integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1"
  crossorigin="anonymous">
</script>
<script
  src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"
  integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM"
  crossorigin="anonymous">
</script>

<script src='<?php echo "$clientRoot/js/header.js" ?>' type="text/javascript"></script>

<!-- Header start -->

<nav id="site-header" style="background-image: url(<?php echo $clientRoot ?>/images/header/OF-Header_May8.png);" class="navbar navbar-expand-lg navbar-dark bg-dark site-header">

  <!-- Logo -->
  <a class="navbar-brand" href="<?php echo $clientRoot ?>">
    <img id="site-header-logo" src="<?php echo "$clientRoot/images/header/oregonflora-logo.png" ?>" alt="OregonFlora">
  </a>

  <!-- Holds dropdowns on mobile -->
  <button
    id="site-header-navbar-toggler"
    class="navbar-toggler ml-auto"
    type="button"
    data-toggle="collapse"
    data-target="#site-header-dropdowns"
    aria-controls="navbarSupportedContent"
    aria-expanded="false"
    aria-label="Toggle navigation">

    <span class="navbar-toggler-icon"></span>
  </button>

  <!-- Dropdowns -->
  <div id="site-header-dropdowns" class="collapse navbar-collapse">
    <ul class="navbar-nav">
      <!-- Explore site -->
      <li class="nav-item dropdown">
        <a id="explore" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
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
      <li class="nav-item dropdown">
        <a id="resources" class="nav-link dropdown-toggle wht-txt" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          Resources
        </a>
        <div class="dropdown-menu" aria-labelledby="resources">
          <a class="dropdown-item" href="<?php echo $clientRoot; ?>/pages/whats-new.php">What's New</a>
          <a class="dropdown-item" href="<?php echo $clientRoot; ?>/newsletters/index.php">Archived Newsletter</a>
          <a class="dropdown-item" href="<?php echo $clientRoot; ?>/pages/links.php">Links</a>
        </div>
      </li>

      <!-- About -->
      <li class="nav-item dropdown">
        <a id="about" class="nav-link dropdown-toggle wht-txt" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          About
        </a>
        <div class="dropdown-menu" aria-labelledby="about">
          <a class="dropdown-item" href="<?php echo $clientRoot; ?>/pages/mission.php">Mission and History</a>
          <a class="dropdown-item" href="<?php echo $clientRoot; ?>/pages/contact.php">Contact Info</a>
          <a class="dropdown-item" href="<?php echo $clientRoot; ?>/pages/project-participants.php">Project Participants</a>
        </div>
      </li>

      <!-- Contribute -->
      <li class="nav-item dropdown">
        <a id="contribute" class="nav-link dropdown-toggle wht-txt" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          Contribute
        </a>
        <div class="dropdown-menu" aria-labelledby="contribute">
          <a class="dropdown-item" href="<?php echo $clientRoot; ?>/pages/donate.php">Donate</a>
          <a class="dropdown-item" href="<?php echo $clientRoot; ?>/pages/volunteer.php">Volunteer</a>
          <a class="dropdown-item" href="<?php echo $clientRoot; ?>/pages/merchandise.php">Merchandise</a>
        </div>
      </li>
    </ul>
  </div>
  <!-- Dropdowns end -->

  <!-- Search -->
  <form
    class="form-inline ml-auto"
    name="quick-search"
    id="quick-search"
    autocomplete="off"
    action="<?php echo $clientRoot . '/taxa/index.php'?>">
    <div class="input-group">
      <div class="dropdown">
        <input id="search-term" name="taxon" type="text" class="form-control dropdown-toggle" data-toggle="dropdown" placeholder="Search all plants">
        <div id="autocomplete-results" class="dropdown-menu" aria-labelledby="search-term">
          <a class="dropdown-item" onclick="document.getElementById('search-term').value = this.innerHTML;" href="#"></a>
          <a class="dropdown-item" onclick="document.getElementById('search-term').value = this.innerHTML;" href="#"></a>
          <a class="dropdown-item" onclick="document.getElementById('search-term').value = this.innerHTML;" href="#"></a>
          <a class="dropdown-item" onclick="document.getElementById('search-term').value = this.innerHTML;" href="#"></a>
          <a class="dropdown-item" onclick="document.getElementById('search-term').value = this.innerHTML;" href="#"></a>
        </div>
      </div>
      <input
        id="search-btn"
        src="<?php echo $clientRoot; ?>/images/header/search-white.png"
        class="mt-auto mb-auto"
        type="image">
    </div>
  </form>
  <!-- Search end -->

</nav>
<!-- Header end -->

<div id="site-content">
