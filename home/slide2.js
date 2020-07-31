
const headerContainer = document.getElementById("react-header");
const dataProps = JSON.parse(headerContainer.getAttribute("data-props"));
let clientRoot= dataProps["clientRoot"];

module.exports = `
<div class="row slide-wrapper slide-2">
	<div class="col-sm-6 slide-col-1">
        <h1>How to get the most out of our site</h1>
        <p>OregonFlora makes information about Oregon plants accessible to diverse audiences: scientists, restorationists, gardeners, land managers, and plant enthusiasts of all ages. We focus on the vascular plants of the state—ferns, conifers, grasses, herbs, and trees—that grow in the wild.</p>
        <p>Now, all that information is even more accessible through our collaboration with Symbiota and its powerful database! 
        Watch our overview below, explore top areas at right, or <a href="` + clientRoot + `/pages/tutorials.php">browse our full set of tutorials – including text-based – here</a>.</p>
        <div class="row video-card">
            <div class="col-auto video-img">
                <a href="` + clientRoot + `/pages/tutorials.php"><img src="` + clientRoot + `/images/YouTube-tutorial-Intro.png"></a>
            </div>
            <div class="col video-text">
                <h3>An Introduction to Oregon Flora</h3>
                <p>All databased specimen records of OSU Herbarium’s vascular plants, mosses, lichens, fungi, and algae in a searchable, downloadable format.</p>
            </div>
        </div>
    </div>
	<div class="col-sm-6 slide-col-2">
        <div class="row video-card">
            <div class="col-auto video-img">
                <a href="` + clientRoot + `/pages/tutorials.php"><img src="` + clientRoot + `/images/YouTube-tutorial-Taxon.png"></a>
            </div>
            <div class="col video-text">
                <h3>Taxon profile pages</h3>
                <p>Comprehensive information, gathered in one location—for each of the ~4,700 vascular plants in the state!</p>
            </div>
        </div>
        <div class="row video-card">
            <div class="col-auto video-img">
                <a href="` + clientRoot + `/pages/tutorials.php"><img src="` + clientRoot + `/images/YouTube-tutorial-Map.png"></a>
            </div>
            <div class="col video-text">
                <h3>Mapping</h3>
                <p>Draw a shape on the interactive map to learn what plant diversity is found there, or enter plant names to view their distribution.</p>
            </div>
        </div>
        <div class="row video-card">
            <div class="col-auto video-img">
                <a href="` + clientRoot + `/pages/tutorials.php"><img src="` + clientRoot + `/images/YouTube-tutorial-InterKey.png"></a>
            </div>
            <div class="col video-text">
                <h3>Interactive Key</h3>
                <p>An identification tool based on the plant features you recognize! Start with a list of species, then narrow the possibilities.</p>
            </div>
        </div>        
        <div class="row video-card">
            <div class="col-auto video-img">
                <a href="` + clientRoot + `/pages/tutorials.php"><img src="` + clientRoot + `/images/YouTube-tutorial-Inventory.png"></a>
            </div>
            <div class="col video-text">
                <h3>Plant Inventories</h3>
                <p>Species lists for defined places, presented as a checklist and an interactive key.</p>
            </div>
        </div>                
        <p><a href="` + clientRoot + `/pages/tutorials.php"><button class="btn btn-primary">See the rest of our tutorials here</button></a></p>
	</div>
</div>
`;       
        