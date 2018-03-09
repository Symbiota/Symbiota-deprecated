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
                    var imgArr = res.split("-||-");
                    var resId = imgArr[0];
                    var resData = imgArr[1];
                    imgDataArr[resId] = resData;
                    var index = imgProcArr.indexOf(resId);
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
    contentArr.push({
        toc: {
            title: {text: 'INDEX', alignment: 'left', style: 'TOCHeader'}
        },
        pageBreak: 'after'
    });
    var familyKeys = Object.keys(dataArr);
    familyKeys.sort();
    //contentArr.push({toc: {title: 'INDEX'}});
    for(i in familyKeys){
        var familyName = familyKeys[i];
        var famArr = dataArr[familyName];
        if(famArr){
            var scinameKeys = Object.keys(famArr);
            scinameKeys.sort();
            for(s in scinameKeys){
                var sciname = scinameKeys[s];
                if(typeof sciname === "string"){
                    leftColContent = [];
                    rightColContent = [];
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
                    //console.log(typeof descArr);
                    if(Object.keys(descArr).length !== 0){
                        var source = descArr.source;
                        delete descArr.source;
                        for(d in descArr){
                            if(descArr[d]['heading']){
                                leftColContent.push({text: descArr[d]['heading']+':', style: 'descheadtext'});
                                leftColContent.push(' ');
                            }
                            leftColContent.push({text: descArr[d]['statement'], style: 'descstattext'});
                            leftColContent.push(' ');
                        }
                        if(source){
                            leftColContent.push('\n');
                            leftColContent.push({text: source, style: 'descsourcetext', alignment: 'right'});
                        }
                    }
                    else{
                        leftColContent.push({text: 'No Description Available', style: 'nodesctext'});
                    }
                    if(imgArr.length > 0){
                        for(p in imgArr){
                            var imgid = imgArr[p]['id'];
                            var owner = imgArr[p]['owner'];
                            var photographer = imgArr[p]['photographer'];
                            if(imgDataArr[imgid]){
                                var tempArr = [];
                                var creditStr = '';
                                tempArr.push({image: imgDataArr[imgid], width: 150, alignment: 'right'});
                                rightColContent.push(tempArr);
                                if(photographer){
                                    creditStr = 'Photograph by: '+photographer;
                                }
                                if(owner){
                                    creditStr += (photographer?'\n':'')+owner;
                                }
                                if(creditStr){
                                    tempArr = [];
                                    tempArr.push({text: creditStr, style: 'imageCredit', alignment: 'right'});
                                    rightColContent.push(tempArr);
                                }
                            }
                        }
                    }

                    var leftColArr = {
                        width: 340,
                        text: leftColContent
                    };
                    if(rightColContent.length > 1){
                        var rightColArr = {
                            table: {
                                widths: [160],
                                body: rightColContent
                            },
                            layout: 'noBorders'

                        };
                    }
                    else{
                        var rightColArr = {
                            table: {
                                widths: [160],
                                body: [rightColContent]
                            },
                            layout: 'noBorders'

                        };
                    }
                    var pageArr = {
                        columns: [leftColArr, rightColArr],
                        pageBreak: 'after'
                    };
                    var TOCString = familyName+': '+sciname;
                    contentArr.push({text: TOCString, tocItem: true, alignment: 'left', margin: [-500, 0, 0, 0]});
                    contentArr.push(pageArr);
                }
            }
        }
    }
    var docDefinition = {
        content: contentArr,
        footer: function(page){
            return [
                {canvas: [{ type: 'line', x1: 20, y1: 0, x2: 595-20, y2: 0, lineWidth: 1 }]},
                {
                    columns: [
                        {
                            width: 400,
                            text: checklistName, alignment: 'right', style: 'checkListName', margin: [20, 10, 20, 10]
                        },
                        {
                            width: 200,
                            columns: [
                                {
                                    width: 30,
                                    text: page, alignment: 'left', style: 'pageNumber', margin: [20, 10, 20, 10]
                                },
                                {
                                    width: 170,
                                    text: 'Back to Contents', alignment: 'right', style: 'TOCLink', margin: [0, 10, 40, 10],
                                    linkToPage: 1
                                }


                            ]
                        }
                    ]
                }
            ];
        },
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
                fontSize: 11,
                bold: true
            },
            nodesctext: {
                fontSize: 15,
                bold: true
            },
            descstattext: {
                fontSize: 10.5
            },
            descsourcetext: {
                fontSize: 8
            },
            checkListName: {
                fontSize: 9,
                bold: true
            },
            pageNumber: {
                fontSize: 11,
                bold: true
            },
            TOCLink: {
                fontSize: 10,
                bold: true
            },
            imageCredit: {
                fontSize: 6,
                margin: [ 0, 0, 0, 13 ]
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