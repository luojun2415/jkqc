/**
 * 自定义alert
 * @param detail:需要显示的内容
 */
function goMsg(detail){
	$("#msg").text("");
	$("#msg").text(detail);
	$("#msg").removeClass("showoff");
	$("#msg").addClass("showon"); 
	 
    setTimeout(function(){
    	$("#msg").removeClass("showon");
		$("#msg").addClass("showoff");               	
    },2000);            
}
