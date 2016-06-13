;(function($) {
$(document).ready(function(){

	// Update CPT rank options dropdown
	function badgeos_leaderboard_update_cpt_rank_metrics() {

		// Get page selectors
		var input = 'select[name="_badgeos_leaderboard_sort_metric"]';
		var selected = $(input).val();
		var metrics = $( 'input[name="_badgeos_leaderboard_metrics[]"]' ).map( function() { if ( $(this).is(':checked') ) return $(this).val(); } ).get();

		// Update rank output
		badgeos_leaderboard_update_rank_metrics( input, selected, metrics );
	}

	// Update rank options dropdown
	function badgeos_leaderboard_update_rank_metrics( input, selected, metrics ) {

		// Initialize empty select option
		var output = '<option></option>';

		// Loop through all selected metrics
		$.each( metrics, function( index, metric_slug ) {
			output += badgeos_leaderboard_rank_render_option( metric_slug, selected );
		});

		// change select html to use new markup
		$(input).html( output );
	}

	// Helper for rendering a single option
	function badgeos_leaderboard_rank_render_option( slug, selected ) {

		// check if slug matches selected
		selected_html = ( slug == selected ) ? ' selected="selected"' : '';

		// Render option (leaderboard.metricLabels is set via wp_enqueue_script())
		var option = '<option value="' + slug + '"' + selected_html + '>' + leaderboard.metricLabels[slug] + '</option>';

		// Return option
		return option;
	}

	// Update CPT rank options every time a metric changes
	$('input[name="_badgeos_leaderboard_metrics[]"]').change( function() {
		badgeos_leaderboard_update_cpt_rank_metrics();
	} );

	// Update rank options on page-load
	badgeos_leaderboard_update_cpt_rank_metrics();

	// Update widget rank when the leaderboard slect changes
	$('select.leaderboard-select').change( function() {
		// Grab the current leaderboard ID
		var leaderboard_id = $(this).val();

		// Run our AJAX call
		$.ajax({
			type : 'post',
			url : ajaxurl,
			data : {
				'action' : 'get-leaderboard-metrics',
				'leaderboard_id' : leaderboard_id
			},
			success : function( response ) {
				badgeos_leaderboard_update_rank_metrics( 'select.leaderboard-rank-metric', response.data.sort_metric, response.data.metrics );
			}
		});
	} ).change();

});
})(jQuery);