jQuery(document).ready(function($) {
	
	$(document).on('click', '.qa-migration .qa_dw2qa_start_migration', function() {		
		
		var paged 		= parseInt( $(this).attr('paged') );
		var total_page 	= $(this).attr('total_page');
		var ppp 		= $(this).attr('ppp');
		
		if( paged > total_page ) {
			
			$(this).text('Completed !');
			return;
		}
		
		$(this).text('Please wait...');
		$('#myProgress').fadeIn();
			
		$.ajax(
			{
		type: 'POST',
		context: this,
		url:qa_dwqa_ajax.qa_dwqa_ajaxurl,
		data: {
			"action" : "qa_dwqa_ajax_migration", 
			"paged"  : paged,
			"ppp"    : ppp,
		},
		success: function(data) {
			
			width = paged * 100 / total_page;
			$('#myBar').css( 'width', width + '%' );
			$('#myBar').text( width.toFixed(2) + '%' );				
				
			paged++;
				
			$(this).attr( 'paged',  paged );
				
			$(this).trigger('click');
			
		}
			});
			
			
			
			
			
		
	})	

	
});	


