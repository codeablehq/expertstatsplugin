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
    var dtable = jQuery('#clients_table').DataTable({
        "order": [[7, "desc"]]
    });


});