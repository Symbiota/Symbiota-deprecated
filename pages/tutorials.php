<?php
//error_reporting(E_ALL);
include_once( "../config/symbini.php" );
header( "Content-Type: text/html; charset=" . $charset );
?>
<html>
<head>
    <title><?php echo $defaultTitle ?> How to get the most our of our site</title>
    <meta charset="UTF-8">
    <link href="../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet"/>
    <link href="../css/main.css?<?php echo $CSS_VERSION_LOCAL; ?>" type="text/css" rel="stylesheet"/>
    <meta name='keywords' content=''/>
    <script type="text/javascript">
        <?php include_once( $serverRoot . '/config/googleanalytics.php' ); ?>
    </script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script type="text/javascript" src="video_modal.js"></script>
    <script src="https://kit.fontawesome.com/a01aa82192.js" crossorigin="anonymous"></script>
</head>
<body>
<?php
include( $serverRoot . "/header.php" );
?>

<div class="info-page tutorials">
    <section id="titlebackground" class="title-blueberry">
        <div class="inner-content">
            <h1>How to get the most out of our site</h1>
        </div>
    </section>
    <section>
        <!-- if you need a full width column, just put it outside of .inner-content -->
        <!-- .inner-content makes a column max width 1100px, centered in the viewport -->
        <div class="inner-content" id="tutorials-content">
            <!-- place static page content here. -->
            <h2>Tutorials and tips – in both video and textual form – to help unlock the power of OregonFlora.</h2>
            <p>OregonFlora is made for land managers, gardeners, scientists, restorationists, and plant lovers of all ages. You’ll find information about all the native and exotic plants of the state—ferns, conifers, grasses, herbs, and trees—that grow in the wild.</p>
            <div id="video-tutorial-top"></div>
            <p>Here are a series of tutorials and tips to help you get the most out of our site.</p>

						<!-- Modal -->
						<div class="modal fade" id="videoModal" tabindex="-1" aria-hidden="true">
								<div class="modal-dialog" role="document">
										<div class="modal-content">

												<div class="modal-body">

														<button type="button" class="close" data-dismiss="modal" aria-label="Close">
																<span aria-hidden="true">&times;</span>
														</button>
														<!-- 16:9 aspect ratio -->
														<div class="embed-responsive embed-responsive-16by9">
																<iframe class="embed-responsive-item" src="" id="video" allowscriptaccess="always" allow="autoplay"></iframe>
														</div>

												</div>

										</div>
								</div>
						</div>
						
						<section id="intro">
							<div class="intro">
								<div class="video">
									<a href="#" type="button" class="btn video-modal-btn" data-toggle="modal" data-src="https://www.youtube.com/embed/qQpy107PKbE" data-target="#videoModal">
										<img 
											src="<?php echo $CLIENT_ROOT; ?>/pages/images/youtube_thumbs/intro.jpg" 
											srcset="
												<?php echo $CLIENT_ROOT; ?>/pages/images/youtube_thumbs/intro_sm.jpg 1x, 
												<?php echo $CLIENT_ROOT; ?>/pages/images/youtube_thumbs/intro.jpg 2x
											"
											alt="intro video
										"/>
									</a>
								</div>
								<div class="info">
									<h3>An Introduction to OregonFlora</h3>
                	<p>The OregonFlora program is based at Oregon State University. 
                	For over 25 years, we have been gathering and sharing information about the plants of the state to help scientists, land managers, and the public understand the plant diversity that surrounds us. 
                	The information in this website is a digital presentation of the floristic research of the <a href="<?php echo $CLIENT_ROOT; ?>/pages/store.php">Flora of Oregon</a> books; 
                	each format supports the other to bring plant knowledge to a diverse audience.
                	</p>
								</div>
							</div>
						</section>
								
						<section id="plant-profile">
							<div class="intro">
								<div class="video">
									<a href="#" type="button" class="btn video-modal-btn" data-toggle="modal" data-src="https://www.youtube.com/embed/fAiJa7HxyV8" data-target="#videoModal">
										<img 
											src="<?php echo $CLIENT_ROOT; ?>/pages/images/youtube_thumbs/plant_profile.jpg"  
											srcset="
												<?php echo $CLIENT_ROOT; ?>/pages/images/youtube_thumbs/plant_profile_sm.jpg 1x, 
												<?php echo $CLIENT_ROOT; ?>/pages/images/youtube_thumbs/plant_profile.jpg 2x
											"
											alt="plant profile video"
										/>
									</a>
								</div>
								<div class="info">
									<h3>Plant profile pages</h3>
									<p>Comprehensive information—gathered in one location—for each of the ~4,700 vascular plant in the state!</p>
                  <div class="more-link" data-toggle="collapse" data-target="#collapseProfile" role="button" aria-expanded="false" aria-controls="collapseProfile">
                  	Text-based tutorial 
                    <div class="arrow fa-down"><i class="fas fa-chevron-down 2x"></i></div>
                    <div class="arrow fa-up"><i class="fas fa-chevron-up 2x"></i></div>
                  </div>
								</div>
							
							</div>
							<div class="reveal collapse" id="collapseProfile">
							<p>Begin typing a plant scientific or common name in the search box in the header or on the home page to access a dropdown list of all the vascular plants addressed in the website. 
                	Select a name, then click/tap to open that plant’s profile page. 
                	Here you’ll find photos, a detailed description, distribution map, nomenclature, and its native/naturalized status. 
                	Clicking on the map or an image opens to details about that selection. 
              </p>
							<p><strong>Tips:</strong></p>
							<ul>
								<li>The plant name featured reflects the scientific name accepted in the OregonFlora taxonomic thesaurus. For example, if you entered “Mahonia nervosa” in the search box, the page returned is titled “Berberis nervosa.” Synonyms, along with common names, are listed under the “Context” section in the page’s right column. </li>
								<li>Click or tap on the green up/down arrows to view all the RELATED taxa in the next higher or lower taxonomic grouping.</li>
								<li>The dot map is a link to an interactive distribution map, where selecting any dot reveals details about that plant occurrence.</li>
								<li>To see all the plant names listed within a taxonomic group, go to the Taxonomic Tree viewer found under the Tools dropdown in the website header.</li>
							</ul>
							</div>
						</section>			
								
						<section id="mapping">
							<div class="intro">
								<div class="video">
									<a href="#" type="button" class="btn video-modal-btn" data-toggle="modal" data-src="https://www.youtube.com/embed/stlBt_e7yds" data-target="#videoModal">
										<img 
											src="<?php echo $CLIENT_ROOT; ?>/pages/images/youtube_thumbs/mapping.jpg" 
											srcset="
												<?php echo $CLIENT_ROOT; ?>/pages/images/youtube_thumbs/mapping_sm.jpg 1x, 
												<?php echo $CLIENT_ROOT; ?>/pages/images/youtube_thumbs/mapping.jpg 2x
											"
											alt="mapping video"
										/>
									</a>
								</div>
								<div class="info">
									<h3>Mapping</h3>
									<p>This is a GIS-based tool. 
									You can type in a plant name to see its distribution in Oregon and beyond, or draw shapes on the map to explore the diversity of an area. 
									Shapefiles can be both imported and exported, making it possible to map other datasets together with OregonFlora plant distribution data.</p>
                  <div class="more-link" data-toggle="collapse" data-target="#collapseMapping" role="button" aria-expanded="false" aria-controls="collapseMapping">
                  	Text-based tutorial 
                    <div class="arrow fa-down"><i class="fas fa-chevron-down 2x"></i></div>
                    <div class="arrow fa-up"><i class="fas fa-chevron-up 2x"></i></div>
                  </div>
								</div>
							
							</div>
							<div class="reveal collapse" id="collapseMapping">
								<p><strong>Distribution:</strong> discover where a plant has been found</p>
								<ul>
								<li>In the panel to the left of the map, enter the criteria for your search by typing in the appropriate box. The Taxon box has a dropdown list to aid in entering names. More than one name can be entered--separate names with a comma.</li>
								<li>Click/tap the green “Load Records” button to map your results.</li>
								</ul>

								<p><strong>Diversity:</strong>  discover what plants occur in a defined area</p>
								<ul>
								<li>In the blue “Define an area” box overlaying the map, choose a shape option from the ‘draw’ dropdown to define your area of interest.</li>
								<li>Click on the map to start creating your shape.</li>
								<li>Highlight the shape by clicking inside it to make its blue outline thick.</li>
								<li>Click/tap on the green “Load Records” button in the left column to map your results.</li>
								</ul>
								
								<p><strong>Viewing your results</strong></p>
								<ul>
									<li>Pan and zoom on the map to see the dots indicating where plants have been recorded to occur. Zoom into resolve dots representing more than one record. </li>
									<li>Press the &lt;Alt&gt; key (&lt;option&gt; key on a Mac) while clicking on any dot on the map to see a popup with record details.</li>
									<li>The Records tab in the left text column lists all the plant records that are mapped.
										<ul>
											<li>Click/tap on a name in the Collector column to view the details of that record.</li>
											<li>Select particular records to be copied to the Selections tab by using the checkboxes to the left of each record.</li>
										</ul>
									</li>
								
									<li>The Taxa tab lists all plant names returned in your search. 
										<ul>
											<li>Each vascular plant name is a link to the profile page of that plant.</li>
											<li>Taxa that are not in the OregonFlora thesaurus (fungi, algae, bryophytes, vascular plant names that are not accepted) are included in the list, but these do not have a linked profile page.</li>
										</ul>
									</li>
									<li>The Selections tab is like your shopping basket; it is created as you select dots on the map (the map dot(s) will also be highlighted in blue) or check records in the Records tab. </li>
									<li>To map something new, return to the Search Criteria tab at the top of the left column and click/tap the green “Reset” button. </li>
								</ul>
								
								<p><strong>Rare plant information</strong></p>
								<ul>
									<li>Currently, access to rare plant (those state and federally listed, and on ORBIC List 1) locality data is restricted to authorized users. OregonFlora is working with these agencies to establish data access policies. All non-listed plant records are displayed in any searches.</li>
									<li>To request an account granting access to restricted information, click the “Log In” link on the top banner, complete and submit the profile form. Please explain in the Biography section why you need access.</li>
								</ul>
								<p><strong>Tips:</strong></p>
								<ul>
									<li>When defining an area with a circle or polygon, remember to select the shape to allow the results to load. </li>
									<li>Download the information in the Records, Taxa, or Selections tabs using the green “Download” button.</li>
									<li>Save an image of your map by going to the Records tab, selecting the download type of “Map PNG Image,” and clicking the green “Download” button.</li>
									<li>Save and download your mapped results as a .kml or .geoJSON file to use in other GIS applications. </li>
									<li>Import spatial files by dragging and dropping them onto the map. </li>
									<li>Change the base map using the “Base Layer” dropdown found in the blue Define an Area box.</li>
									<li>Learn more in the OregonFlora video tutorial and also in Symbiota webinars covering basic and advanced (including vector tools) features. </li>
								</ul>
							</div>
						</section>		
                
            <section id="identify">
							<div class="intro">
								<div class="video">
									<a href="#" type="button" class="btn video-modal-btn" data-toggle="modal" data-src="https://www.youtube.com/embed/op0LVJRBHto" data-target="#videoModal">
										<img 
											src="<?php echo $CLIENT_ROOT; ?>/pages/images/youtube_thumbs/identify.jpg" 
											srcset="
												<?php echo $CLIENT_ROOT; ?>/pages/images/youtube_thumbs/identify_sm.jpg 1x, 
												<?php echo $CLIENT_ROOT; ?>/pages/images/youtube_thumbs/identify.jpg 2x
											"
											alt="identify video"
										/>
									</a>
								</div>
								<div class="info">
									<h3>Identify Plants</h3>
									<p>Use the plant features you recognize! Mark your location on a map to get a list of species found there, then narrow the possibilities.</p>
                  <div class="more-link" data-toggle="collapse" data-target="#collapseIdentify" role="button" aria-expanded="false" aria-controls="collapseIdentify">
                  	Text-based tutorial 
                    <div class="arrow fa-down"><i class="fas fa-chevron-down 2x"></i></div>
                    <div class="arrow fa-up"><i class="fas fa-chevron-up 2x"></i></div>
                  </div>
								</div>
							
							</div>
							<div class="reveal collapse" id="collapseIdentify">
							
								<p>Click/tap on the map to drop a pin in the approximate location of your unknown plant. 
								Click the green “Search” button to create a list of plants known to occur in the area of your unknown. 
								The list created is drawn from &gt;878,000 georeferenced records (herbarium specimens, observations, field photos) in the OregonFlora database. 
								Select any number of features from the list of characteristics that you recognize in your plant; 
								each selection narrows the number of possible matches. 
								View the profile pages of the remaining plants on the list by clicking on the plant name. 
								Compare them to your unknown to make your final identification. 
								</p>
								<p><strong>Tips:</strong></p>
								<ul>
									<li>In the results screen, you can filter on any number of characters selected in any order. </li>
									<li>You can optionally filter your results before clicking the “Search” button in two ways:
										<ul>
											<li>Limit results to a single plant family by typing in the family name in the designated box.</li>
											<li>Define the radius from your point on the map to create a species list exclusively from that defined area. If no radius is entered, the program determines the radius that gives the best representation of local species diversity. In other words, poorly collected areas will have a larger radius using the default settings. </li>
										</ul>
									</li>
								</ul>
							</div>
						</section>	
                
            <section id="inventories">
							<div class="intro">
								<div class="video">
									<a href="#" type="button" class="btn video-modal-btn" data-toggle="modal" data-src="https://www.youtube.com/embed/aAuE3nz_kVk" data-target="#videoModal">
										<img 
											src="<?php echo $CLIENT_ROOT; ?>/pages/images/youtube_thumbs/inventories.jpg" 
											srcset="
												<?php echo $CLIENT_ROOT; ?>/pages/images/youtube_thumbs/inventories_sm.jpg 1x, 
												<?php echo $CLIENT_ROOT; ?>/pages/images/youtube_thumbs/inventories.jpg 2x
											" 
											alt="inventories video"
										/>
									</a>
								</div>
								<div class="info">
									<h3>Inventories</h3>
									<p>Plant inventories tell the story of Oregon places and the plants that are found there. </p>
                  <div class="more-link" data-toggle="collapse" data-target="#collapseInventories" role="button" aria-expanded="false" aria-controls="collapseInventories">
                  	Text-based tutorial 
                    <div class="arrow fa-down"><i class="fas fa-chevron-down 2x"></i></div>
                    <div class="arrow fa-up"><i class="fas fa-chevron-up 2x"></i></div>
                  </div>
								</div>
							
							</div>
							<div class="reveal collapse" id="collapseInventories">
							
							<p>
								We present several projects, each with a theme. 
								Every collection contains a number of checklists, each of which documents the species found at the defined place. 
								A checklist can be viewed in two ways: 
								the EXPLORE view presents the information as a plant list to sort or view as thumbnails; 
								the IDENTIFY view opens our identification tool using exclusively the plants within the checklist.
							</p>
							<p><strong>Tips:</strong></p>
							<ul>
								<li>From the map on a collection’s landing page, click on a dot to open that checklist. </li>
								<li>Filter the lists of a collection or select one using the search box; you can organize the lists either geographically (East to West or vice versa) or alphabetically. </li>
								<li>Save and download a plant list as a Word document or a .csv file using the EXPORT options in the left column. </li>
							</ul>

							</div>
						</section>	
            
            <section id="herbarium">
							<div class="intro">
								<div class="video">
									<a href="#" type="button" class="btn video-modal-btn" data-toggle="modal" data-src="https://www.youtube.com/embed/OIJs0W9_aUo" data-target="#videoModal">
										<img 
											src="<?php echo $CLIENT_ROOT; ?>/pages/images/youtube_thumbs/herbarium.jpg" 
											srcset="
												<?php echo $CLIENT_ROOT; ?>/pages/images/youtube_thumbs/herbarium_sm.jpg 1x, 
												<?php echo $CLIENT_ROOT; ?>/pages/images/youtube_thumbs/herbarium.jpg 2x
											" 
											alt="herbarium video"
										/>
									</a>
								</div>
								<div class="info">
									<h3>Herbarium</h3>
									<p>This feature gives access to all the digitized collections housed in the Herbarium at Oregon State University in Corvallis:  
							vascular plants, algae, bryophytes, fungi, and lichens from Oregon and beyond. </p>
                  <div class="more-link" data-toggle="collapse" data-target="#collapseHerbarium" role="button" aria-expanded="false" aria-controls="collapseHerbarium">
                  	Text-based tutorial 
                    <div class="arrow fa-down"><i class="fas fa-chevron-down 2x"></i></div>
                    <div class="arrow fa-up"><i class="fas fa-chevron-up 2x"></i></div>
                  </div>
								</div>
							
							</div>
							<div class="reveal collapse" id="collapseHerbarium">
							
							<p>
							Select from a number of filtering options to narrow your results. 
							Results are shown in three tabs: you can view them organized by Occurrence records, as a Species list, or as dots on a distribution Map.
							</p>
							<p><strong>Tips:</strong></p>
							<ul>
								<li>Currently only vascular plant names will appear in a dropdown, but you may type in the scientific names of organisms from any group, and all available records will be returned.</li>
								<li>To see results in in a table format, select the checkbox “show results in table view” at the top of the search page or from the top of the results page.</li>
								<li>Do a map-based search in the “Latitude and Longitude” section by clicking on an earth icon or entering latitude/longitude values.</li>
								<li>Download your results by clicking/tapping on the icon in the upper right corner of the results page. </li>
							</ul>

							</div>
						</section>	
    
            
            <section id="natives">
							<div class="intro">
								<div class="video">
									<a href="#" type="button" class="btn video-modal-btn" data-toggle="modal" data-src="https://www.youtube.com/embed/aeuFT55jfr0" data-target="#videoModal">
										<img 
											src="<?php echo $CLIENT_ROOT; ?>/pages/images/youtube_thumbs/natives.jpg" 
											srcset="
												<?php echo $CLIENT_ROOT; ?>/pages/images/youtube_thumbs/natives_sm.jpg 1x, 
												<?php echo $CLIENT_ROOT; ?>/pages/images/youtube_thumbs/natives.jpg 2x
											" 
											alt="natives video"
										/>
									</a>
								</div>
								<div class="info">
									<h3>Grow Natives</h3>
									<p>This section has information on almost 200 species of native plants that are ideal for your garden or landscape conditions. </p>
                  <div class="more-link" data-toggle="collapse" data-target="#collapseNatives" role="button" aria-expanded="false" aria-controls="collapseNatives">
                  	Text-based tutorial 
                    <div class="arrow fa-down"><i class="fas fa-chevron-down 2x"></i></div>
                    <div class="arrow fa-up"><i class="fas fa-chevron-up 2x"></i></div>
                  </div>
								</div>
							
							</div>
							<div class="reveal collapse" id="collapseNatives">
							
							<p>Browse through thumbnail photos of all the featured plants or enter a plant’s scientific or common name. 
							Find those with features you want by filtering on any combination of 17 characters listed in the sidebar on the left. 
							As you make selections, the displayed results will dynamically adjust. 
							Click or tap on a thumbnail in the results section to open a profile page for that plant. 
							Profile pages have photos of the plant and all its garden characteristics. 
							A link to the species’ core profile page presents information from other tools in our website. </p>
							<p>There are five featured plant combinations; 
							select one to learn more about that garden type and which plants are well-suited for its conditions. </p>

							</p>
							<p><strong>Tips:</strong></p>
							<ul>
								<li>As you select filtering options, your choices are listed above the results. You can remove any or all your selections here as well. </li>
								<li>View results as a grid or a list, and have them sort alphabetically by common or scientific name by tapping or clicking on the selected option at the top of the results section. </li>
								<li>Plants featured in the Grow Natives section currently emphasize species appropriate for the Willamette Valley and the Cascades ecoregions. We will be adding species adapted to eastern Oregon soon!</li>
							</ul>

							</div>
						</section>	
                                        
            
        </div> <!-- .inner-content -->
    </section>
</div>
<?php
include( $serverRoot . "/footer.php" );
?>

</body>
</html>