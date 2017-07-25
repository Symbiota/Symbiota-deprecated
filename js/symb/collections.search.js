function addVoucherToCl(occidIn,clidIn,tidIn){
    $.ajax({
        type: "POST",
        url: "rpc/addvoucher.php",
        data: { occid: occidIn, clid: clidIn, tid: tidIn }
    }).done(function( msg ) {
        if(msg == "1"){
            alert("Success! Voucher added to checklist.");
        }
        else{
            alert(msg);
        }
    });
}

function openIndPU(occId,clid){
    var wWidth = 900;
    if(document.getElementById('maintable')){
        wWidth = document.getElementById('maintable').offsetWidth*1.05;
    }
    else if(document.body.offsetWidth){
        wWidth = document.body.offsetWidth*0.9;
    }
    if(wWidth > 1000) wWidth = 1000;
    newWindow = window.open('individual/index.php?occid='+occId+'&clid='+clid,'indspec' + occId,'scrollbars=1,toolbar=1,resizable=1,width='+(wWidth)+',height=700,left=20,top=20');
    if (newWindow.opener == null) newWindow.opener = self;
    return false;
}

function toggleFieldBox(target){
    var objDiv = document.getElementById(target);
    if(objDiv){
        if(objDiv.style.display=="none"){
            objDiv.style.display = "block";
        }
        else{
            objDiv.style.display = "none";
        }
    }
    else{
        var divs = document.getElementsByTagName("div");
        for (var h = 0; h < divs.length; h++) {
            var divObj = divs[h];
            if(divObj.className == target){
                if(divObj.style.display=="none"){
                    divObj.style.display="block";
                }
                else {
                    divObj.style.display="none";
                }
            }
        }
    }
}

function openMapPU(){
    var url = '../map/googlemap.php?starr='+starrJson+'&jsoncollstarr='+collJson+'&maptype=occquery';
    window.open(url,'gmap','toolbar=0,location=0,directories=0,status=0,menubar=0,scrollbars=1,resizable=1,width=1150,height=900,left=20,top=20');
}