$(window).load(function() {
	// these formatting functions are located in $(window).load instead
	// of $(document).ready because with the former, the images are not
	// yet loaded and it is not possible to know their width and height.
	console.log('embedded.js');
	// setup the facebook-like album icons for the main fotobook page
	$('.fotobook-album-entry img').each( function() {
		var innerFrame = $(this).parent();
		var outerFrame = $(this).parent().parent();
		outerFrame.width(innerFrame.width());
		outerFrame.height(innerFrame.height());
	});
	$('#fotobook-album-list').css({
		position: 'relative',
		top: 0,
		left: 0,
	});

	// if this is an album display page, vertically align all the images.
	if ( $('#fotobook-album').length ) {
		$('#fotobook-album .photo img').vAlign();
	}
	
});