
function switchState(ctrl, obj){  
  var ctrlList = document.getElementsByName(ctrl);
  var chk = obj.checked ;
  for ( var i = 0 , l = ctrlList.length ; i < l ; i++ ) {
    ctrlList[i].checked = chk;
  }
}


function editEmail(id){
  var frm = document.getElementById("frmbmemain");
  frm.act.value = 'edit';
  frm.emailID.value = id;
  frm.submit();
}