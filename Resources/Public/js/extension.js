function preview(img, selection) {
	$('#x1').val(selection.x1);
	$('#y1').val(selection.y1);
	$('#x2').val(selection.x2);
	$('#y2').val(selection.y2);
	$('#w').val(selection.width);
	$('#h').val(selection.height);
}


$(function () {
	$('#edit #aspectRatio').val(crop['ar']);

	var cropbox = $('img#cropbox').imgAreaSelect({
		x1: crop['x1'],
		y1: crop['y1'],
		x2: crop['x2'],
		y2: crop['y2'],
		aspectRatio: crop['ar'],
		handles: true,
		imageWidth: crop['width'],
		imageHeight: crop['height'],
		fadeSpeed: 200,
		onInit: preview,
		onSelectChange: preview,
		instance: true,
		persistent: true
	});

	$("#setAR").click(function () {
		cropbox.setOptions({
			aspectRatio: $("#aspectRatio").val()
		});
		cropbox.update();
	});


	$('#controller').on('click', '#save', function () {
		alert('13');
	});
});