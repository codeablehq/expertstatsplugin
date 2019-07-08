jQuery(document).ready(function () {

    jQuery('.compareicon').click(function() {
      jQuery('.compareto').toggle();
      jQuery('#compare_date_from, #compare_date_to').val('');
    });

    jQuery('div[data-score]').raty({
        cancel: false,
        readOnly: true,
        half: true,
        starType: 'i',
        score: function () {
            return jQuery(this).attr('data-score');
        }
    });

    jQuery(function () {
        jQuery('.row.match_height .column_inner').matchHeight();
    });

    var start_year = jQuery('#date_form').attr('data-start-year');
    var end_year = jQuery('#date_form').attr('data-end-year');

    jQuery('.datepicker').datepicker({
        buttonImage: jQuery('.datepicker').attr('data-icon'),
        buttonImageOnly: true,
        showOn: "button",
        buttonText: "Select date",
        maxDate: '0',
        changeMonth: true,
        changeYear: true,
        showButtonPanel: true,
        dateFormat: 'yy-mm',
        yearRange: '' + start_year + ':' + end_year + '',
        onClose: function (dateText, inst) {
            jQuery(this).datepicker('setDate', new Date(inst.selectedYear, inst.selectedMonth, 1));
        }
    });

    jQuery('.datatable_inner').each(function () {
        jQuery(this).DataTable({
            "order": [[1, "desc"]]
        });
    });
    jQuery('#clients_table').DataTable({
        "order": [[7, "desc"]]
    });

    // Remove the "error" and "success" params from settings URL after page load.
    var url = window.location.href;
    url = setUrlParameter(url, 'error', '');
    url = setUrlParameter(url, 'success', '');
    window.history.replaceState({}, window.document.title, url);

    function setUrlParameter(url, key, value) {
        var key = encodeURIComponent(key),
            value = encodeURIComponent(value);

        var baseUrl = url.split('?')[0],
            newParam = key + '=' + value,
            params = '?' + newParam;

        // if there are no query strings, make urlQueryString empty
        if (url.split('?')[1] === undefined){
            urlQueryString = '';
        } else {
            urlQueryString = '?' + url.split('?')[1];
        }

        // If the "search" string exists, then build params from it
        if (urlQueryString) {
            var updateRegex = new RegExp('([\?&])' + key + '[^&]*');
            var removeRegex = new RegExp('([\?&])' + key + '=[^&;]+[&;]?');

            if (typeof value === 'undefined' || value === null || value === '') {
                // Remove param if value is empty
                params = urlQueryString.replace(removeRegex, "$1");
                params = params.replace(/[&;]$/, "");

            } else if (urlQueryString.match(updateRegex) !== null) {
                // If param exists already, update it
                params = urlQueryString.replace(updateRegex, "$1" + newParam);

            } else if (urlQueryString === '') {
                // If there are no query strings
                params = '?' + newParam;
            } else {
                // Otherwise, add it to end of query string
                params = urlQueryString + '&' + newParam;
            }
        }

        // no parameter was set so we don't need the question mark
        params = params === '?' ? '' : params;

        return baseUrl + params;
    }

});
