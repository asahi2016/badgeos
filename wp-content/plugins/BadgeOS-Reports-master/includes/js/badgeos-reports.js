jQuery(document).ready(function($) {

	/*
	 * Natural Sort algorithm for Javascript - Version 0.7 - Released under MIT license
	 * Author: Jim Palmer (based on chunking idea from Dave Koelle)
	 * Contributors: Mike Grier (mgrier.com), Clint Priest, Kyle Adams, guillermo
	 * See: http://js-naturalsort.googlecode.com/svn/trunk/naturalSort.js
	 */
	function naturalSort (a, b) {
		var re = /(^-?[0-9]+(\.?[0-9]*)[df]?e?[0-9]?$|^0x[0-9a-f]+$|[0-9]+)/gi,
			sre = /(^[ ]*|[ ]*$)/g,
			dre = /(^([\w ]+,?[\w ]+)?[\w ]+,?[\w ]+\d+:\d+(:\d+)?[\w ]?|^\d{1,4}[\/\-]\d{1,4}[\/\-]\d{1,4}|^\w+, \w+ \d+, \d{4})/,
			hre = /^0x[0-9a-f]+$/i,
			ore = /^0/,
			// convert all to strings and trim()
			x = a.toString().replace(sre, '') || '',
			y = b.toString().replace(sre, '') || '',
			// chunk/tokenize
			xN = x.replace(re, '\0$1\0').replace(/\0$/,'').replace(/^\0/,'').split('\0'),
			yN = y.replace(re, '\0$1\0').replace(/\0$/,'').replace(/^\0/,'').split('\0'),
			// numeric, hex or date detection
			xD = parseInt(x.match(hre)) || (xN.length != 1 && x.match(dre) && Date.parse(x)),
			yD = parseInt(y.match(hre)) || xD && y.match(dre) && Date.parse(y) || null;
		// first try and sort Hex codes or Dates
		if (yD)
			if ( xD < yD ) return -1;
			else if ( xD > yD )  return 1;
		// natural sorting through split numeric strings and default strings
		for(var cLoc=0, numS=Math.max(xN.length, yN.length); cLoc < numS; cLoc++) {
			// find floats not starting with '0', string or 0 if not defined (Clint Priest)
			var oFxNcL = !(xN[cLoc] || '').match(ore) && parseFloat(xN[cLoc]) || xN[cLoc] || 0;
			var oFyNcL = !(yN[cLoc] || '').match(ore) && parseFloat(yN[cLoc]) || yN[cLoc] || 0;
			// handle numeric vs string comparison - number < string - (Kyle Adams)
			if (isNaN(oFxNcL) !== isNaN(oFyNcL)) return (isNaN(oFxNcL)) ? 1 : -1;
			// rely on string comparison if different types - i.e. '02' < 2 != '02' < '2'
			else if (typeof oFxNcL !== typeof oFyNcL) {
				oFxNcL += '';
				oFyNcL += '';
			}
			if (oFxNcL < oFyNcL) return -1;
			if (oFxNcL > oFyNcL) return 1;
		}
		return 0;
	}

	jQuery.extend( jQuery.fn.dataTableExt.oSort, {
		"natural-pre": function ( a ) {
			var x = a.match(/data-value="(.+)"/)[1];
			return x;
		},

		"natural-asc": function ( a, b ) {
			return naturalSort(a,b);
		},

		"natural-desc": function ( a, b ) {
			return naturalSort(a,b) * -1;
		}
	} );

	// Initialize sorting on report tables
    var reportTable = $('.badgeos-report-table').dataTable({
		'aLengthMenu' : [
			[25, 50, 100, -1],
			[25, 50, 100, "All"]
		],
		'iDisplayLength' : 50,
		'sPaginationType' : 'full_numbers',
		'aoColumnDefs' : [
			{
				'sType' : 'natural',
				'aTargets' : [ '_all' ]
			}
		]
	});

	$('.datepicker').datepicker({
		dateFormat : 'MM d, yy'
	});

    /* 8 is groups column index value
     *  apply filter for groups column
     */
    $('select.user-reports').change( function() {
       if($(".badgeos-report-table").hasClass('badgeos-report-earnings-report-table')){
           var fieldValue = 6;
       }else{
           var fieldValue = 8;
       }
        groupFilter = $(this).find('option:selected').text();
        groupFilter = (groupFilter=="All")?"":groupFilter;
        reportTable.fnFilter(groupFilter,fieldValue);
    });
});

