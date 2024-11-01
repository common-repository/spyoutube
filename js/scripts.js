function changeForEdit(ident, reverse) {
	if (reverse == false) {
		$('#spYouTubeTitleText_' + ident).hide();
		$('#spYouTubeTitleInput_' + ident).show();

		$('#spYouTubeCategoryText_' + ident).hide();
		$('#spYouTubeCategoryInput_' + ident).show();

		$('#spYouTubeDescriptionText_' + ident).hide();
		$('#spYouTubeDescriptionInput_' + ident).show();

		$('#spYouTubeTagsText_' + ident).hide();
		$('#spYouTubeTagsInput_' + ident).show();

		$('#spYouTubeManageLinks_' + ident).hide();
		$('#spYouTubeManageButtons_' + ident).show();
	}else{
		$('#spYouTubeTitleText_' + ident).show();
		$('#spYouTubeTitleInput_' + ident).hide();

		$('#spYouTubeCategoryText_' + ident).show();
		$('#spYouTubeCategoryInput_' + ident).hide();

		$('#spYouTubeDescriptionText_' + ident).show();
		$('#spYouTubeDescriptionInput_' + ident).hide();

		$('#spYouTubeTagsText_' + ident).show();
		$('#spYouTubeTagsInput_' + ident).hide();

		$('#spYouTubeManageLinks_' + ident).show();
		$('#spYouTubeManageButtons_' + ident).hide();
	}
}

function stopUploadButton(){
	$('#spYouTubeUploadText').show();
	$('#spYouTubeUploadButton').hide();
}

function insertIntoPost(html){
	var win = window.dialogArguments || opener || parent || top;
	win.send_to_editor(html);
}