
(function($){

    $(document).ready(function(){
        var $body = $( 'body' );

        //Our main achievement list AJAX call
        function badgeos_ajax_progress_map_list() {

            $.ajax( {
                url : badgeos_interactive_progress_map.ajax_url,
                data : {
                    'action' : 'get_progress_map',
                    'type' : badgeos_interactive_progress_map.type,
                    'limit' : badgeos_interactive_progress_map.limit,
                    'status' : badgeos_interactive_progress_map.status,
                    'show_parent' : badgeos_interactive_progress_map.show_parent,
                    'show_child' : badgeos_interactive_progress_map.show_child,
                    'group_id' : badgeos_interactive_progress_map.group_id,
                    'user_id' : badgeos_interactive_progress_map.user_id,
                    'wpms' : badgeos_interactive_progress_map.wpms,
                    'offset' : $( '#badgeos_progress_map_offset' ).val(),
                    'count' : $( '#badgeos_progress_map_count' ).val(),
                    'filter' : $( '#progress_map_list_filter' ).val(),
                    'search' : $( '#progress_map_list_search' ).val(),
                    'orderby' : badgeos_interactive_progress_map.orderby,
                    'order' : badgeos_interactive_progress_map.order,
                    'include' : badgeos_interactive_progress_map.include,
                    'exclude' : badgeos_interactive_progress_map.exclude,
                    'meta_key' : badgeos_interactive_progress_map.meta_key,
                    'meta_value' : badgeos_interactive_progress_map.meta_value
                },
                dataType : 'json',
                success : function( response ) {
                    $( '.badgeos-spinner' ).hide();
                    if ( response.data.message === null ) {
                        //alert("That's all folks!");
                    }
                    else {
                        $( '#badgeos-progress-map-container' ).append( response.data.message );
                        $( '#badgeos_progress_map_offset' ).val( response.data.offset );
                        $( '#badgeos_progress_map_count' ).val( response.data.badge_count );

                        setQuestionContainer();
                        //hide/show load more button
                        if ( response.data.query_count <= response.data.badge_count ) {
                            $( '#progress_map_list_load_more' ).hide();
                        }
                        else {
                            $( '#progress_map_list_load_more' ).show();
                        }
                    }
                }
            } );

        }
        //badgeos_ajax_progress_map_list();

        // Reset all our base query vars and run an AJAX call
        function badgeos_ajax_progress_map_list_reset() {

         $( '#badgeos_progress_map_offset' ).val( 0 );
         $( '#badgeos_progress_map_count' ).val( 0 );

         $( '#badgeos-progress-map-container' ).html( '' );
         $( '#progress_map_list_load_more' ).hide();

            badgeos_ajax_progress_map_list();

         }

        // Listen for changes to the achievement filter
        $( '#progress_map_list_filter' ).change(function() {

         badgeos_ajax_progress_map_list_reset();

         } ).change();


        // Listen for users clicking the "Load More" button
        /*$( '#progress_map_list_load_more' ).click( function() {

         $( '.badgeos-spinner' ).show();

         badgeos_ajax_progress_map_list();

         } );*/

        function setQuestionContainer(){
            //setting question containers width
            var questionContainer = parseInt($(".outerContainer").innerWidth())-parseInt($(".achievement").outerWidth(true))-
                parseInt($(".buttonContainer").outerWidth(true))-10;
           // $(".questionContainer").css("width",questionContainer+'px');

            //setting scroll container width
            $(".outerContainer").each(function(index,container){
                var scrollContainer = parseInt($(container).find(".scroll").find('.ques').size()) * 80;
               // $(container).find(".scroll").css("width",scrollContainer+'px');
                if(scrollContainer < questionContainer){
                    $(container).find('.buttonContainer').hide();
                } else {
                    $(container).find('.buttonContainer').show();
                }
            });
        }

        $( window ).resize(function() {
            setQuestionContainer();
        });
    });

})(jQuery);