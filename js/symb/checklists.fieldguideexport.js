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
var imagesExist = false;
var tempImgArr = [];
var imgDataArr = [];
var contentArr = [];
var leftColContent = [];
var rightColContent = [];
var priDescSource = '';
var secDescSource = '';
var anyDescSource = 0;
var photog = [];
var photoNum = 0;
var zipFile = '';
var zipFolder = '';
var pdfFileNum = 0;
var pdfFileTot = 0;
var projFileName = '';
var savedPDFs = 0;
var t0 = 0;
var t1 = 0;

function hideWorking(){
    $('#loadingOverlay').popup('hide');
    $("#fieldguideexport").popup("show");
    //t1 = performance.now();
    //console.log("Total process took " + ((t1 - t0)/1000) + " seconds.");
}

function showWorking(){
    $("#fieldguideexport").popup("hide");
    $('#loadingOverlay').popup('show');
    //t0 = performance.now();
}

function openFieldGuideExporter(){
    var taxonFilter = document.getElementById("thesfilter").value;
    $("#fieldguideexport").popup("show");
}

function prepareFieldGuideExport(taxCnt){
    showWorking();
    processSettings();
    var processed = 0;
    lastLoad = Math.ceil(taxCnt/lazyLoadCnt);
    pdfFileTot = Math.ceil(taxCnt/100);
    projFileName = checklistName.replace(/ /g,"_");
    do{
        lazyLoadData(loadIndex,function(res){
            loadingComplete = true;
            //console.log('load '+loadIndex+' loaded');
            processDataResponse(res);
        });
        processed = processed + lazyLoadCnt;
        loadIndex++;
    }
    while(processed < taxCnt);
}

function processSettings(){
    priDescSource = document.getElementById("fgPriDescSource").value;
    secDescSource = document.getElementById("fgSecDescSource").value;
    anyDescSource = document.getElementById("fgUseAltDesc").checked;
    if(document.getElementById("fgUseAllPhotog").checked == true){
        photog = 'all';
    }
    else{
        photog = [];
        var dbElements = document.getElementsByName("photog[]");
        for(i = 0; i < dbElements.length; i++){
            if(dbElements[i].checked){
                photog.push(dbElements[i].value);
            }
        }
    }
    photoNum = $("input[name=fgMaxImages]:checked").val();
}

function processDataResponse(res){
    var tempArr = JSON.parse(res);
    //var imagesExist = false;
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
            if(tempArr[i]['desc'][priDescSource]){
                dataArr[family][sciname]['desc'] = tempArr[i]['desc'][priDescSource];
            }
            else if(tempArr[i]['desc'][secDescSource]){
                dataArr[family][sciname]['desc'] = tempArr[i]['desc'][secDescSource];
            }
            else if(anyDescSource){
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
            imagesExist = true;
            for(im in tempArr[i]['img']){
                var imgId = tempArr[i]['img'][im]['id'];
                var imgUrl = tempArr[i]['img'][im]['url'];
                if(imgId && imgUrl){
                    dataArr[family][sciname]['images'].push(tempArr[i]['img'][im]);
                    tempImgArr.push(imgId);
                }
            }
        }
    }
    procIndex++;
    if(loadingComplete && (procIndex == lastLoad)) prepImageResponse();
}

function splitArray(arr,size){
    var index = 0;
    var arrLength = arr.length;
    var tempArr = [];
    for (index = 0; index < arrLength; index += size) {
        var subArr = arr.slice(index,(index+size));
        tempArr.push(subArr);
    }

    return tempArr;
}

function prepImageResponse(){
    if(imagesExist){
        tempImgArr = splitArray(tempImgArr,200);
        processImageResponse();
    }
    else{
        createPDFGuides();
    }
}

function processImageResponse(){
    //console.log(tempImgArr.length);
    var reqArrStr = JSON.stringify(tempImgArr[0]);
    loadImageDataUri(reqArrStr,function(res){
        if(res){
            var tempDataArr = res.split("-****-");
            for(d in tempDataArr){
                if(tempDataArr[d]){
                    var imgArr = tempDataArr[d].toString().split("-||-");
                    var resId = imgArr[0];
                    var resData = imgArr[1];
                    if(resData) imgDataArr[resId] = resData;
                    //t1 = performance.now();
                    //console.log(resId+" processed at " + ((t1 - t0)/1000) + " seconds.");
                }
            }
        }
        tempImgArr.splice(0,1);
        if(tempImgArr.length > 0){
            processImageResponse();
        }
        else{
            createPDFGuides();
        }
    });
}

function createPDFGuides(){
    pdfFileNum = 1;
    var taxonNum = 0;
    zipFile = new JSZip();
    zipFolder = zipFile.folder("files");
    contentArr = [];
    contentArr.push({
        toc: {
            title: {text: 'INDEX', alignment: 'left', style: 'TOCHeader'}
        },
        pageBreak: 'after'
    });
    var familyKeys = Object.keys(dataArr);
    familyKeys.sort();
    for(i in familyKeys){
        var familyName = familyKeys[i];
        if(typeof familyName === "string"){
            var scinameKeys = Object.keys(dataArr[familyName]);
            scinameKeys.sort();
            for(s in scinameKeys){
                if(taxonNum == 100){
                    savePDFFile(contentArr,pdfFileNum);
                    taxonNum = 0;
                    contentArr = [];
                    contentArr.push({
                        toc: {
                            title: {text: 'INDEX', alignment: 'left', style: 'TOCHeader'}
                        },
                        pageBreak: 'after'
                    });
                }
                var sciname = scinameKeys[s];
                if(typeof sciname === "string"){
                    createPDFPage(familyName,sciname);
                    taxonNum++;
                }
            }
        }
    }
    savePDFFile(contentArr,pdfFileNum);
}

function createPDFPage(familyName,sciname){
    //console.log(sciname);
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
    if(Object.keys(descArr).length !== 0){
        var source = descArr.source;
        delete descArr.source;
        for(d in descArr){
            if(descArr[d]['heading']){
                leftColContent.push({text: descArr[d]['heading']+':', style: 'descheadtext'});
                leftColContent.push(' ');
            }
            if(descArr[d]['statement']){
                leftColContent.push({text: descArr[d]['statement'], style: 'descstattext'});
                leftColContent.push(' ');
            }
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

function savePDFFile(content,fileNum){
    var docDefinition = {
        content: content,
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
                                    width: 60,
                                    text: page, alignment: 'left', style: 'pageNumber', margin: [20, 10, 20, 10]
                                },
                                {
                                    width: 140,
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
    pdfFileNum++;
    const pdfDocGenerator = pdfMake.createPdf(docDefinition);
    pdfDocGenerator.getBase64((data) => {
        var filename = projFileName+'-'+fileNum+'.pdf';
        zipFolder.file(filename, data.substr(data.indexOf(',')+1), {base64: true});
        savedPDFs++;
        if(savedPDFs == pdfFileTot){
            zipFile.generateAsync({type:"blob"}).then(function(content) {
                var zipfilename = projFileName+'.zip';
                saveAs(content,zipfilename);
                hideWorking();
            });
        }
    });
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

function selectAllPhotog(){
    var boxesChecked = true;
    var selectAll = document.getElementById("fgUseAllPhotog");
    if(!selectAll.checked){
        boxesChecked = false;
    }
    var dbElements = document.getElementsByName("photog[]");
    for(i = 0; i < dbElements.length; i++){
        dbElements[i].checked = boxesChecked;
    }
}

function checkPhotogSelections(){
    var boxesChecked = true;
    var dbElements = document.getElementsByName("photog[]");
    for(i = 0; i < dbElements.length; i++){
        if(!dbElements[i].checked) boxesChecked = false;
    }
    document.getElementById("fgUseAllPhotog").checked = boxesChecked;
}