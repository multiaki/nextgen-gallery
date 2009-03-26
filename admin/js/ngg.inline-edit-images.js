
(function($) {
inlineEditImages = {

	init : function() {
		var t = this, qeRow = $('#inline-edit'), bulkRow = $('#bulk-edit');

		// get all editable rows
		t.rows = $('tr.iedit');

		// prepare the edit rows
		qeRow.keyup(function(e) { if(e.which == 27) return inlineEditImages.revert(); });
		bulkRow.keyup(function(e) { if (e.which == 27) return inlineEditImages.revert(); });

		$('a.cancel', qeRow).click(function() { return inlineEditImages.revert(); });
		$('a.save', qeRow).click(function() { return inlineEditImages.save(this); });
		$('input, select', qeRow).keydown(function(e) { if(e.which == 13) return inlineEditImages.save(this); });

		$('a.cancel', bulkRow).click(function() { return inlineEditImages.revert(); });

		// add edit event
		t.addEvents(t.rows);

	},

	toggle : function(el) {
		var t = this;

		$(t.what+t.getId(el)).css('display') == 'none' ? t.revert() : t.edit(el);
	},

	addEvents : function(r) {
		r.each(function() {
			var row = $(this);
			$('a.editinline', row).click(function() { inlineEditImages.edit(this); return false; });
		});
	},

	edit : function(id) {
		var t = this;

		if ( typeof(id) == 'object' )
			id = t.getId(id);
		
		// hide the action div
		$('#picture-'+id+' .row-actions').addClass('hidden');
		
		$('.inline_text_'+id).hide();
		$('.inline_edit_'+id).show();
		
		// focus on the first input field
		$('.inline_edit_'+id+':first input').focus();

		return false;
	},

	save : function(id) {
		if( typeof(id) == 'object' )
			id = this.getId(id);

		$('table.widefat .inline-edit-save .waiting').show();

		var params = {
			action: 'inline-save',
			post_type: this.type,
			post_ID: id,
			edit_date: 'true'
		};

		var fields = $('#edit-'+id+' :input').fieldSerialize();
		params = fields + '&' + $.param(params);

		// make ajax request
		$.post('admin-ajax.php', params,
			function(r) {
				$('table.widefat .inline-edit-save .waiting').hide();

				if (r) {
					if ( -1 != r.indexOf('<tr') ) {
						$(inlineEditImages.what+id).remove();
						$('#edit-'+id).before(r).remove();

						var row = $(inlineEditImages.what+id);
						row.hide();

						if ( 'draft' == $('input[name="post_status"]').val() )
							row.find('td.column-comments').hide();

						row.find('.hide-if-no-js').removeClass('hide-if-no-js');
						inlineEditImages.addEvents(row);
						row.fadeIn();
					} else {
						r = r.replace( /<.[^<>]*?>/g, '' );
						$('#edit-'+id+' .inline-edit-save').append('<span class="error">'+r+'</span>');
					}
				} else {
					$('#edit-'+id+' .inline-edit-save').append('<span class="error">'+inlineEditL10n.error+'</span>');
				}
			}
		, 'html');
		return false;
	},

	revert : function() {
		var id;

		if ( id = $('table.widefat tr.inline-editor').attr('id') ) {
			$('table.widefat .inline-edit-save .waiting').hide();

			if ( 'bulk-edit' == id ) {
				$('table.widefat #bulk-edit').removeClass('inline-editor').hide();
				$('#bulk-titles').html('');
				$('#inlineedit').append( $('#bulk-edit') );
			} else  {
				$('#'+id).remove();
				id = id.substr( id.lastIndexOf('-') + 1 );
				$(this.what+id).show();
			}
		}

		return false;
	},

	getId : function(o) {
		var id = o.tagName == 'TR' ? o.id : $(o).parents('tr').attr('id');
		var parts = id.split('-');
		return parts[parts.length - 1];
	}
};

$(document).ready(function(){inlineEditImages.init();});
})(jQuery);
