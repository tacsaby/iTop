function updateProvider(elem){

	let oColor1 = $(elem).prev('[name="provider"]').attr('data-color1');
	let oColor2 = $(elem).prev('[name="provider"]').attr('data-color2');
	let oColor4 = $(elem).prev('[name="provider"]').attr('data-color3');
	let oColor3 = $(elem).prev('[name="provider"]').attr('data-color4');
	
	$('#fcef55fc-4968-45b2-93bb-1a1080c85fc7').attr('fill', oColor1);
	$('#e8fa0310-b872-4adf-aedd-0c6eda09f3b8').attr('fill', oColor1);
	$('#a4813fcf-056e-4514-bb8b-e6506f49341f').attr('fill', oColor1);
	$('#e73810fe-4cf4-40cc-8c7c-ca544ce30bd4-108').attr('fill', oColor2);
	$('#e12ee00d-aa4a-4413-a013-11d20b7f97f7').attr('fill', oColor2);
	$('#b4d4939a-c6e6-4f4d-ba6c-e8b05485017d').attr('fill', oColor2);
	$('#b06d66ec-6c84-45dd-8c27-1263a6253192-107').attr('fill', oColor3);
	$('#f58f497e-6949-45c8-be5f-eee2aa0f6586').attr('fill', oColor3);
	$('#aff120b1-519b-4e96-ac87-836aa55663de').attr('fill', oColor3);
	$('#aff120b1-519b-4e96-ac87-836aa55663de').attr('fill', oColor3);
	$('#ae7af94f-88d7-4204-9f07-e3651de85c05-111').attr('fill', oColor4);
	$('#a6768b0e-63d0-4b31-8462-9b2e0b00f0fd-112').attr('fill', oColor4);
}
$('body').on('click', '.ui-checkboxradio-radio-label', function(){
	updateProvider(this)
})
updateProvider($('[name="provider"]:checked').next());
