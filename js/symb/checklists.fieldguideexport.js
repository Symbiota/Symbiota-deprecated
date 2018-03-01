$(document).ready(function() {
    $('#fieldguideexport').popup({
        transition: 'all 0.3s',
        scrolllock: true
    });
});
var loadingComplete = true;
var lazyLoadCnt = 200;
var loadIndex = 0;
var procIndex = 0;
var lastLoad = 0;
var dataArr = [];
var imgProcArr = [];
var imgDataArr = [];
var leftColContent = [];
var rightColContent = [];

function openFieldGuideExporter(){
    var taxonFilter = document.getElementById("thesfilter").value;
    //document.getElementById("exporteriframe").src = "fieldguideexporter.php?thesfilter="+taxonFilter+"&cl=<?php echo $clValue; ?>";
    $("#fieldguideexport").popup("show");
}

function prepareFieldGuideExport(taxCnt){
    //showWorking();
    var processed = 0;
    lastLoad = Math.ceil(taxCnt/lazyLoadCnt);
    do{
        lazyLoadData(loadIndex,function(res){
            loadingComplete = true;
            processDataResponse(res);
        });
        processed = processed + lazyLoadCnt;
        loadIndex++;
    }
    while(processed < taxCnt);
}

function lazyLoadData(index,callback){
    var startindex = 0;
    loadingComplete = false;
    if(index > 0) startindex = (index*lazyLoadCnt) + 1;
    var http = new XMLHttpRequest();
    var url = "rpc/fieldguideexporter.php";
    var params = 'rows='+lazyLoadCnt+'&start='+startindex+'&cl=<?php echo $clValue."&pid=".$pid."&dynclid=".$dynClid."&thesfilter=".($thesFilter?$thesFilter:1); ?>';
    //console.log(url+'?'+params);
    http.open("POST", url, true);
    http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    http.onreadystatechange = function() {
        if(http.readyState == 4 && http.status == 200) {
            callback(http.responseText);
        }
    };
    http.send(params);
}

function processDataResponse(res){
    var tempArr = JSON.parse(res);
    for(i in tempArr) {
        var family = tempArr[i]['family'];
        if(!family) family = "Family Undefined";
        var sciname = tempArr[i]['sciname'];
        if(!dataArr[family]) dataArr[family] = [];
        if(!dataArr[family][sciname]) dataArr[family][sciname] = [];
        dataArr[family][sciname]['author'] = tempArr[i]['author'];
        dataArr[family][sciname]['order'] = tempArr[i]['order'];
        if(tempArr[i]['vern']) dataArr[family][sciname]['common'] = tempArr[i]['vern'][0];
        if(tempArr[i]['desc']){
            if(tempArr[i]['desc']['Field Guide']){
                dataArr[family][sciname]['desc'] = tempArr[i]['desc']['Field Guide'];
            }
            else{
                var x = 0;
                do{
                    for(de in tempArr[i]['desc']){
                        dataArr[family][sciname]['desc'] = tempArr[i]['desc'][de];
                        x++;
                    }
                }
                while(x < 1);
            }
        }
        dataArr[family][sciname]['images'] = [];
        if(tempArr[i]['img']){
            for(im in tempArr[i]['img']){
                var imgId = tempArr[i]['img'][im]['id'];
                dataArr[family][sciname]['images'].push(tempArr[i]['img'][im]);
                imgProcArr.push(imgId);
                loadImageDataUri(imgId,function(res){
                    imgDataArr[imgId] = res;
                    var index = imgProcArr.indexOf(imgId);
                    imgProcArr.splice(index, 1);
                    if(loadingComplete && (procIndex == lastLoad) && (imgProcArr.length == 0)) createPDFGuide();
                });
            }
        }
    }
    procIndex++;
    if(loadingComplete && (procIndex == lastLoad) && (imgProcArr.length == 0)) createPDFGuide();
}

function createPDFGuide(){
    var contentArr = [];
    var familyKeys = Object.keys(dataArr);
    familyKeys.sort();
    for(i in familyKeys){
        var familyName = familyKeys[i];
        var famArr = dataArr[familyName];
        if(famArr){
            var scinameKeys = Object.keys(famArr);
            scinameKeys.sort();
            for(s in scinameKeys){
                var sciname = scinameKeys[s];
                if(typeof sciname === "string"){
                    var taxonOrder = dataArr[familyName][sciname]['order'];
                    var scinameAuthor = dataArr[familyName][sciname]['author'];
                    var commonName = '';
                    var descArr = [];
                    var imgArr = [];
                    var imgBodyArr = [];
                    if(dataArr[familyName][sciname]['common']) commonName = dataArr[familyName][sciname]['common'];
                    if(dataArr[familyName][sciname]['desc']) descArr = dataArr[familyName][sciname]['desc'];
                    if(dataArr[familyName][sciname]['images']) imgArr = dataArr[familyName][sciname]['images'];
                    leftColContent.push({text: taxonOrder, style: 'ordertext'});
                    leftColContent.push('\n');
                    leftColContent.push({text: familyName, style: 'familytext'});
                    leftColContent.push('\n');
                    leftColContent.push({text: sciname, style: 'scinametext'});
                    leftColContent.push(' ');
                    leftColContent.push({text: scinameAuthor, style: 'authortext'});
                    if(commonName){
                        leftColContent.push('\n');
                        leftColContent.push({text: commonName, style: 'commontext'});
                    }
                    leftColContent.push('\n\n');
                    for(d in descArr){
                        if(descArr[d]['heading']){
                            leftColContent.push({text: descArr[d]['heading']+':', style: 'descheadtext'});
                            leftColContent.push(' ');
                        }
                        leftColContent.push({text: descArr[d]['statement'], style: 'descstattext'});
                        leftColContent.push(' ');
                    }

                    var rightColText = [];
                    for(p in imgArr){
                        var imgid = imgArr[p]['id'];
                        if(imgDataArr[imgid]){
                            var tempArr = [];
                            tempArr.push({image: imgDataArr[imgid], width: 150, alignment: 'right'});
                            rightColContent.push(tempArr);
                        }
                    }

                    //if(imgArr[0]['url']) testImg2 = imgArr[0]['url'];

                    var leftColArr = {
                        width: 340,
                        text: leftColContent
                    };
                    var rightColArr = {
                        /*table: {
                            widths: [160],
                            body: [{image: testImg2, width: 150, alignment: 'right'}]

                            body: [
                                [{image: testImg2, width: 150, alignment: 'right'}],
                                [{image: testImageDataUrl, width: 150, alignment: 'right'}],
                                [{image: testImageDataUrl, width: 150, alignment: 'right'}]
                            ]
                        },
                        layout: 'noBorders'*/

                        table: {
                            widths: [160],
                            body: rightColContent
                        },
                        layout: 'noBorders'

                    };
                    var pageArr = {
                        columns: [leftColArr, rightColArr],
                        pageBreak: 'after'
                    };
                    contentArr.push(pageArr);
                }
            }
        }
    }
    var docDefinition = {
        content: contentArr,
        styles: {
            ordertext: {
                fontSize: 11.5,
                bold: true
            },
            familytext: {
                fontSize: 16,
                bold: true
            },
            scinametext: {
                fontSize: 18,
                italics: true
            },
            authortext: {
                fontSize: 10
            },
            commontext: {
                fontSize: 10.5
            },
            descheadtext: {
                fontSize: 11.5,
                bold: true
            },
            descstattext: {
                fontSize: 11
            }
        }
    };
    pdfMake.createPdf(docDefinition).download('optionalName.pdf');
}

function loadImageDataUri(imgid,callback){
    var http = new XMLHttpRequest();
    var url = "rpc/fieldguideimageprocessor.php";
    var params = 'imgid='+imgid;
    //console.log(url+'?'+params);
    http.open("POST", url, true);
    http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    http.onreadystatechange = function() {
        if(http.readyState == 4 && http.status == 200) {
            callback(http.responseText);
        }
    };
    http.send(params);
}