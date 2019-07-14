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

    // -----
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

/**
 * Sync handler.
 */
jQuery(document).ready(function () {
    var elProgress = jQuery( '.codeable-sync-progress' );

    if ( ! elProgress.length ) {
        return;
    }

    function processNext() {
        window.setTimeout(function () {
            jQuery.post(
                window.ajaxurl,
                {
                    action: 'wpcable_sync_process'
                },
                checkSyncStatus
            );
        }, 1);
    }

    function showProgressBar( step ) {
        var label = 'Fetching data';

        if ( step && step.label ) {
            label = step.label;
        }
        if ( step && ! isNaN( step.page ) && step.paged ) {
            label += ', page ' + step.page;
        }

        elProgress.show();
        elProgress.find( '.msg' ).text( label );
    }

    function hideProgressBar() {
        if ( elProgress.is(':visible') ) {
            elProgress.hide();

            // Reload the window, if user consents.
            if ( confirm('Sync completed. Do you want to reload this page now?') ) {
                window.location.reload();
            }
        }
    }

    function checkSyncStatus( res ) {
        var state = false;
        if ( res && res.data ) {
            state = res.data.state
        }

        if ( state === 'RUNNING' || state === 'READY' ) {
            showProgressBar( res.data.step );
            processNext();
        } else {
            hideProgressBar();
        }
    }

    function startSync( ev ) {
        jQuery.post(
            window.ajaxurl,
            {
                action: 'wpcable_sync_start'
            },
            checkSyncStatus
        );

        ev.preventDefault();
        return false;
    }

    jQuery( '.sync-start' ).on( 'click', startSync );

    // Check, if a sync process is active from previous page load.
    processNext();

    // Check for new sync process every 5 minutes.
    window.setInterval( processNext, 300000 );
});

/**
 * Task list functions.
 */
jQuery(document).ready(function () {
    if (!jQuery('.wrap.wpcable_wrap.tasks').length) {
        return;
    }
    if (!window.wpcable || ! window.wpcable.tasks) {
        return;
    }

    var list = jQuery( '.wrap .task-list' );
    var listTitle = jQuery( '.wrap .list-title' );
    var itemTpl = wp.template( 'list-item' );
    var notesForm = jQuery( '.wrap .notes-editor-layer' );
    var filterCb = jQuery( '.wrap [data-filter]' );
    var flagCb = jQuery( '.wrap [data-flag]' );
    var filterTxt = jQuery( '.wrap #post-search-input' );
    var notesMde = false;
    var filterTxtVal = '';

    var filterState = 'all';
    var currFilters = {};
    var currFlags = {};

    function refreshList() {
        list.empty();

        for ( var i = 0; i < wpcable.tasks.length; i++ ) {
            var task = wpcable.tasks[i];

            if ( filterState === 'all' ) {
                // Display all tasks.
            } else if ( filterState === 'lost' && (task.state === 'lost' || task.flag === 'lost')) {
                // Display lost tasks list.
            } else if ( filterState === task.state && task.flag !== 'lost' ) {
                // Display single task state list.
            } else {
                continue;
            }
            if ( false === task._visible ) {
                continue;
            }

            function _refresh() {
                var task = this;
                var prev = list.find( '#task-' + task.task_id );

                task.notes_html = SimpleMDE.prototype.markdown( task.notes );

                task.$el = jQuery( itemTpl( task ) );
                task.$el.data( 'task', task );

                if ( prev.length ) {
                    prev.off( 'task:refresh', _refresh );
                    prev.replaceWith( task.$el );
                } else {
                    list.append( task.$el );
                }

                task.$el.on( 'task:refresh', _refresh );
            }
            _refresh = _refresh.bind( task )

            _refresh();
        }

        updateTitle();
    }

    function updateTitle() {
        listTitle.empty();

        var count = list.find( '.list-item:visible' ).length;
        if ( ! count ) {
            listTitle.text( listTitle.data('none') );
        } else if ( 1 === count ) {
            listTitle.text( listTitle.data('one') );
        } else {
            listTitle.text( listTitle.data('many').replace( '[NUM]', count ) );
        }
    }

    function updateFilters( ev ) {
        var hash = window.location.hash.substr( 1 ).split( '=' );
        var filterRe = false;

        // Update filters based on current #hash value.
        if ( hash && 2 === hash.length ) {
            if ( 'state' === hash[0] ) {
                filterState = hash[1];
            }
        }

        filterCb.each(function() {
            var el = jQuery(this);
            var key = el.data('filter');

            if ( ! el.prop('checked') ) {
                currFilters[key] = false;
            } else {
                currFilters[key] = true;
            }
        });

        currFlags = [];
        flagCb.each(function() {
            var el = jQuery(this);
            var key = el.data('flag');

            if ( el.prop('checked') ) {
                currFlags.push( key );
            }
        });

        filterTxtVal = filterTxt.val();

        if ( filterTxtVal.length ) {
            filterRe = new RegExp( filterTxtVal, 'i' );
        }

        // Mark tasks as visible/hidden.
        for ( var i = 0; i < wpcable.tasks.length; i++ ) {
            var task = wpcable.tasks[i];
            task._visible = true;

            if ( task.hidden && currFilters.no_hidden ) {
                task._visible = false;
            }
            if ( !task.favored && currFilters.favored ) {
                task._visible = false;
            }
            if ( !task.promoted && currFilters.promoted ) {
                task._visible = false;
            }
            if ( !task.subscribed && currFilters.subscribed ) {
                task._visible = false;
            }
            if ( !task.preferred && currFilters.preferred ) {
                task._visible = false;
            }

            if ( currFlags.length ) {
                if ( -1 !== currFlags.indexOf( task.flag ) ) {
                    task._visible = false;
                }
            }

            if (filterRe) {
                if (
                    -1 === task.title.search( filterRe ) &&
                    -1 === task.task_id.search( filterRe ) &&
                    -1 === task.client_name.search( filterRe ) &&
                    -1 === task.notes.search( filterRe )
                ) {
                    task._visible = false;
                }
            }
        }

        // Update the UI to reflect active filters.
        var currState = jQuery( '.subsubsub li.' + filterState ).filter( ':visible' );

        jQuery( '.subsubsub .current' ).removeClass( 'current' )
        if ( 1 !== currState.length ) {
            filterState = 'all';
            currState = jQuery( '.subsubsub li.all' );
        }

        currState.find( 'a' ).addClass('current');
        refreshList();

        if ( ev ) {
            ev.preventDefault();
        }
        return false;
    }

    function initFilters() {
        var totals = {};

        totals.all = 0;
        for ( var i = 0; i < wpcable.tasks.length; i++ ) {
            var task = wpcable.tasks[i];
            var state = task.state;

            if ('lost' === task.flag) {
                state = 'lost';
            }

            if ( false === task._visible ) {
                continue;
            }

            if ( undefined === totals[state] ) {
                totals[state] = 0;
            }
            totals[state]++;
            totals.all++;
        }

        jQuery( '.subsubsub li' ).hide();

        for ( var i in totals ) {
            var item = jQuery( '.subsubsub li.' + i );
            item.show();
            item.find( '.count' ).text( totals[i] );
        }

        filterState = 'all';
    }

    function startEditor( ev ) {
        if ( ! list.isClick ) {
            return;
        }
        if ( notesMde ) {
            closeEditor();
        }

        var row = jQuery( this ).closest( 'tr' );
        var task = row.data( 'task' );

        notesForm.show();
        notesForm.find( '.task-title' ).text( task.title );
        notesForm.data( 'task', task );

        notesMde = new SimpleMDE({
            element: notesForm.find( 'textarea' ).val( task.notes )[0],
            status: false
        });
        notesMde.codemirror.focus();
        notesMde.codemirror.setCursor(notesMde.codemirror.lineCount(), 0);

        notesForm.on( 'click', '.btn-save', closeEditorSave );
        notesForm.on( 'click', '.btn-cancel', closeEditor );

        list.isClick = false;
    }

    function closeEditor() {
        notesForm.find( '.task-title' ).text('');

        notesForm.off( 'click', '.btn-save', closeEditorSave );
        notesForm.off( 'click', '.btn-cancel', closeEditor );

        notesMde.toTextArea();
        notesMde = null;

        notesForm.hide();
    }

    function editorMouseDown( ev ) {
        list.isClick = true
        list.downPos = {
            sum: ev.offsetX + ev.offsetY,
            x: ev.offsetX,
            y: ev.offsetY
        };
    }

    function editorMouseMove( ev ) {
        if ( ! list.isClick ) {
            return;
        }

        var moved = Math.abs(ev.offsetX + ev.offsetY - list.downPos.sum)

        if (moved > 5) {
            list.isClick = false;
        }
    }

    function closeEditorSave() {
        var task = notesForm.data( 'task' );
        task.notes = notesMde.value();

        updateTask( task, closeEditor );
    }

    function setColorFlag() {
        var flag = jQuery( this );
        var row = flag.closest( 'tr' );
        var task = row.data( 'task' );

        task.flag = flag.data( 'flag' );
        updateTask( task );
    }

    function updateTask( task, onDone ) {
        var ajaxTask = _.clone( task );
        delete ajaxTask.$el;
        delete ajaxTask.notes_html;
        delete ajaxTask.client_name;
        delete ajaxTask.avatar;

        jQuery.post(
            window.ajaxurl,
            {
                action: 'wpcable_update_task',
                _wpnonce: window.wpcable.update_task_nonce,
                task: ajaxTask
            },
            function done( res ) {
                task.$el.trigger( 'task:refresh' );

                updateFilters();
                initFilters();

                if ( 'function' === typeof onDone ) {
                    onDone();
                }
            }
        );
    }

    notesForm.hide();

    initFilters();
    updateFilters();
    refreshList();

    notesMde = new SimpleMDE({
        element: notesForm.find( 'textarea' )[0],
        status: false
    });

    jQuery( window ).on( 'hashchange', updateFilters );
    filterCb.on( 'click', function() { updateFilters(); initFilters(); } );
    flagCb.on( 'click', function() { updateFilters(); initFilters(); } );

    filterTxt.on( 'change keyup search', function() {
        if ( filterTxtVal !== filterTxt.val() ) {
            updateFilters();
            initFilters();
        }
    } );

    list.on( 'click', '.color-flag [data-flag]', setColorFlag );
    list.on( 'click', '.notes-body', startEditor )
        .on( 'mousedown', '.notes-body', editorMouseDown)
        .on( 'mousemove', '.notes-body', editorMouseMove);
});
