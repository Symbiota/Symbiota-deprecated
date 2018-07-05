function ImageExplorer(options) {
    ImageExplorer.options = options;
    ImageExplorer.filterCriteria = {};
}


ImageExplorer.prototype.search = function(query, searchCollection) {
    if (searchCollection != undefined) {
        // reset start and filterCriteria
        ImageExplorer.currStart = 0;
        ImageExplorer.filterCriteria = {};

        for (var i in searchCollection.models) {

            var modelAttrs = searchCollection.models[i].attributes;

            if (ImageExplorer.filterCriteria[modelAttrs.category] === undefined) {
                ImageExplorer.filterCriteria[modelAttrs.category] = []; // init empty array
            }

            if (modelAttrs.category == 'text') {
                ImageExplorer.filterCriteria['text'].push(modelAttrs.value);
            } else {
                var value = ImageExplorer.lookupValue(modelAttrs.category, modelAttrs.value);
                ImageExplorer.filterCriteria[modelAttrs.category].push(value);
            }
        }
        console.log(ImageExplorer.filterCriteria);
    }

    ImageExplorer.filterCriteria.idNeeded = $("#id_needed").prop("checked")?1:0;
    ImageExplorer.filterCriteria.idPoor = $("#id_poor").prop("checked")?1:0;
    ImageExplorer.filterCriteria.countPerCategory = $("#count_per_category").val();
    ImageExplorer.filterCriteria.idToSpecies = $("#id_to_species").prop("checked")?1:0;

    $('body').addClass("loading");

    $.post(ImageExplorer.options.displayUrl, ImageExplorer.filterCriteria, function(result) {
        $("#images").html(result);
        $('body').removeClass("loading");

        var toValue = ImageExplorer.currStart+ImageExplorer.currLimit;
        var page = toValue / ImageExplorer.options.limit;
        var totalPages = Math.ceil($('#imgCnt').val() / ImageExplorer.options.limit);

        if($('#imgCnt').val() < toValue) toValue = $('#imgCnt').val();
        $('#count').html(ImageExplorer.currStart + " - " + toValue + " of " + $('#imgCnt').val() + " images"); 
        $('#count_bottom').html(ImageExplorer.currStart + " - " + toValue + " of " + $('#imgCnt').val() + " images");

        $('#pages').html(" page " + page + " of " + totalPages); 
        $('#pages_bottom').html(" page " + page + " of " + totalPages); 

        if (parseFloat($('#imgCnt').val()) <= ImageExplorer.currLimit) {
            // only one page, so don't display page navigation links
            $("#previousPage").css("display","none");
            $("#nextPage").css("display","none");
            $("#previousPage_bottom").css("display","none");
            $("#nextPage_bottom").css("display","none");
            $("#firstPage").css("display","none");
            $("#lastPage").css("display","none");
            $("#firstPage_bottom").css("display","none");
            $("#lastPage_bottom").css("display","none");
        } else {
            $("#previousPage").css("display","inline");
            $("#nextPage").css("display","inline");
            $("#previousPage_bottom").css("display","inline");
            $("#nextPage_bottom").css("display","inline");
            $("#firstPage").css("display","inline");
            $("#lastPage").css("display","inline");
            $("#firstPage_bottom").css("display","inline");
            $("#lastPage_bottom").css("display","inline");
        }

        // hack to get autocomplete to close after a search
        var $focused = $(':focus');
        $focused.blur();
    });
}


ImageExplorer.prototype.firstPage = function() {
    var start = 0;

    if (start < parseFloat($('#imgCnt').val())) {
        ImageExplorer.filterCriteria.start = start;
        this.search();

        ImageExplorer.currStart = start;
    }

}
ImageExplorer.prototype.lastPage = function() {
    var totalPages = Math.ceil(parseFloat($('#imgCnt').val()) / ImageExplorer.options.limit);
    var start = (totalPages -1) * ImageExplorer.options.limit;
    if (start < 0) { 
       start = 0;
    }
    if (start < parseFloat($('#imgCnt').val())) {
        ImageExplorer.filterCriteria.start = start;
        this.search();

        ImageExplorer.currStart = start;
    }

}
ImageExplorer.prototype.nextPage = function() {
    var start = ImageExplorer.currStart + ImageExplorer.options.limit;

    if (start < parseFloat($('#imgCnt').val())) {
        ImageExplorer.filterCriteria.start = start;
        this.search();

        ImageExplorer.currStart = start;
    }

}

ImageExplorer.prototype.previousPage = function() {
    var start = ImageExplorer.currStart - ImageExplorer.options.limit;
    if (start >= 0) {
        ImageExplorer.filterCriteria.start = start;
        this.search();

        ImageExplorer.currStart = start;
    }
}

ImageExplorer.lookupValue = function(facet, label) {
            var lookups = ImageExplorer.lookupTable[facet];
            for (var i = 0; i < lookups.length; i++) {
                if (lookups[i].label == label) {
                    return lookups[i].value;
                }
            }
}

ImageExplorer.prototype.init = function(containerId) {
    var controlsHtml="";
    controlsHtml += "<a id=\"displayOptions\" href=\"#\"><span id=\"displayOptionsText\">Show</span> options...</a>";
    controlsHtml += "<div id=\"options\" style=\"margin-top: 20px\">";
    controlsHtml += "    <table>";
    controlsHtml += "        <tr>";
    controlsHtml += "            <td>Images displayed per group: <\/td>";
    controlsHtml += "            <td>";
    controlsHtml += "                <select id=\"count_per_category\" name=\"count_per_category\">";
    controlsHtml += "                    <option value=\"taxon\" >One per taxon<\/option>";
    controlsHtml += "                    <option value=\"specimen\">One per specimen<\/option>";
    controlsHtml += "                    <option value=\"all\" selected=\"selected\">All images<\/option>";
    controlsHtml += "                <\/select>";
    controlsHtml += "            <\/td>";
    controlsHtml += "        <\/tr>";
    controlsHtml += "        <tr>";
    controlsHtml += "            <td>Display images needing identification: <\/td>";
    controlsHtml += "            <td><input type=\"checkbox\" id=\"id_needed\" name=\"id_needed\" \/><\/td>";
    controlsHtml += "        <\/tr>";
    controlsHtml += "        <tr>";
    controlsHtml += "            <td>Display images with low quality identifications: <\/td>";
    controlsHtml += "            <td><input type=\"checkbox\" id=\"id_poor\" name=\"id_poor\" \/><\/td>";
    controlsHtml += "        <\/tr>";
    controlsHtml += "        <tr>";
    controlsHtml += "            <td>Display images identified to species: <\/td>";
    controlsHtml += "            <td><input type=\"checkbox\" id=\"id_to_species\" name=\"id_to_species\" checked=\"true\" \/><\/td>";
    controlsHtml += "        <\/tr>";
    controlsHtml += "    <\/table>";
    controlsHtml += "<\/div>";
    controlsHtml += "<div style=\"margin: 20px 0px 40px 0px\">"
    controlsHtml += "    <span><em>Try searching for: taxon, owner, country, state, photographer or tag.</em></span>";
    controlsHtml += "    <div id=\"searchbox\"></div>";
    controlsHtml += "</div>";
    controlsHtml += "<div id=\"pagination\">";
    controlsHtml += "    <div style=\"float:right;\">";
    controlsHtml += "        <span class=\"link\" id=\"firstPage\">&lt;&lt; First</span>&nbsp;<span class=\"link\" id=\"previousPage\"> Previous Page</span>&nbsp;<span id=\"pages\"></span>&nbsp;<span class=\"link\" id=\"nextPage\">Next Page</span>&nbsp;<span class=\"link\" id=\"lastPage\">Last &gt;&gt;</span>";
    controlsHtml += "    <\/div>";
    controlsHtml += "    <div id=\"count\" style=\"font-weight:bold;\">Images: 0 - 100 of 136<\/div>";
    controlsHtml += "</div>";
    controlsHtml += "<hr />";
    controlsHtml += "<div id=\"images\"><\/div>";
    controlsHtml += "<div id=\"pagination_bottom\" style=\"clear:both;margin-top:40px\">";
    controlsHtml += "<hr />";
    controlsHtml += "    <div style=\"float:right;\">";
    controlsHtml += "        <span class=\"link\" id=\"firstPage_bottom\">&lt;&lt; First</span>&nbsp;<span class=\"link\" id=\"previousPage_bottom\"> Previous Page</span>&nbsp;<span id=\"pages_bottom\"></span>&nbsp;<span class=\"link\" id=\"nextPage_bottom\">Next Page</span>&nbsp;<span class=\"link\" id=\"lastPage_bottom\">Last &gt;&gt;</span>";
    controlsHtml += "    <\/div>";
    controlsHtml += "    <div id=\"count_bottom\" style=\"font-weight:bold;\">Images: 0 - 100 of 136<\/div>";
    controlsHtml += "</div>";


    $("#"+containerId).html(controlsHtml);

    $("#id_needed").change(this.search);
    $("#id_poor").change(this.search);
    $("#count_per_category").change(this.search);
    $("#id_to_species").change(this.search);

    var facets = ImageExplorer.options.facets;

    var facetMatches = function(callback) {
        var facetNames = [];

        for (var i = 0; i < facets.length; i++) {
            facetNames.push(facets[i].name);
        }

        callback(facetNames);
    };

    ImageExplorer.lookupTable = {}; // init the lookup table

    var valueMatches = function(facet, searchTerm, callback) {
        for (var i = 0; i < facets.length; i++) {
            if (facets[i].name == facet) {

                facets[i].source(searchTerm, function(source) {
                    var lookups = [];
                    var labels = [];

                    for (var j = 0; j < source.length; j++) {
                        if (source[j]['label'] !== undefined) {
                            labels.push(source[j]['label']);
                            lookups.push(source[j]);
                        } else {
                            labels.push(source[j]);
                            lookups.push({ value: source[j], label : source[j] });
                        }
                    }

                    callback(labels);
                    ImageExplorer.lookupTable[facet] = lookups;
                });
            }
        }
    };

    var searchFunc = this.search;

    // initialize the visual search component
    ImageExplorer.visualSearch = VS.init({
        container : $('#searchbox'),
        query     : '',
        callbacks : {
            search       : searchFunc,
            facetMatches : facetMatches,
            valueMatches : valueMatches
        }
    });

    ImageExplorer.currStart = ImageExplorer.options.start;
    ImageExplorer.currLimit = ImageExplorer.options.limit;

    // set up initial criteria
    if (ImageExplorer.options.initialCriteria) {
        ImageExplorer.filterCriteria = ImageExplorer.options.initialCriteria;

        if (ImageExplorer.options.initialCriteria.idNeeded) {
            $("#id_needed").prop("checked", ImageExplorer.filterCriteria.idNeeded);
        }

        if (ImageExplorer.options.initialCriteria.countPerCategory) {
            $("#count_per_category").val(ImageExplorer.filterCriteria.countPerCategory);
        }

        if (ImageExplorer.options.initialCriteria.countPerCategory) {
            $("#id_to_species").val(ImageExplorer.filterCriteria.countPerCategory);
        }

        if (ImageExplorer.options.initialCriteria.idPoor) {
            $("#id_poor").prop("checked", ImageExplorer.filterCriteria.idPoor);
        }
    }

    this.search();

    $("#options").toggle();
}
