CHARITABLE_ADMIN = window.CHARITABLE_ADMIN || {};

/**
 * Datepicker
 */
( function( exports, $ ){

	if ( ! $.fn.datepicker ) {
		return;
	}

	var Datepicker = function( $el ) {
		this.$el = $el;
		var options = {
			dateFormat 	: this.$el.data('format') || 'MM d, yy',
			minDate 	: this.$el.data('min-date') || '',
			beforeShow	: function( input, inst ) {
				$('#ui-datepicker-div').addClass('charitable-datepicker-table');
			}
		}

		this.$el.datepicker( options );

		if ( this.$el.data('date') ) {
			this.$el.datepicker( 'setDate', this.$el.data('date') );
		}

		if ( this.$el.data('min-date') ) {
			this.$el.datepicker( 'option', 'minDate', this.$el.data('min-date') );
		}
	}

	exports.Datepicker = Datepicker;

})( CHARITABLE_ADMIN, jQuery );

/**
 * Conditional settings.
 */
( function( exports, $ ){

	var Settings = function( $el ) {
		var triggers = [];

		show_setting = function(value, $trigger) {
			var not = '!' === value[0],
				compare = not ? value.slice(1) : value,
				show;

			if ('checked' === compare) {
				show = $trigger.is(':checked');
			} else if ('selected' === compare) {
				show = $trigger.selected();
			} else {
				show = $trigger.val() === compare;
			}

			return not ? !show : show;
		};

		toggle_setting = function($setting, $trigger) {
			var $el = (function($setting){
					var $tr = $setting.parents('tr');
					return $tr.length ? $tr.first() : $setting;
				})($setting),
				value = get_setting_value( $setting ),
				show = show_setting(value, $trigger);

			$el.toggle(show);
		};

		get_setting_value = function($setting) {
			var value = $setting.data( 'trigger-value' );

			/* Backwards compatibility for pre 1.5 */
			if ( 'undefined' === typeof value ) {
				value = $setting.data( 'show-only-if-value' );
			}

			return value;
		};

		toggle_options = function($setting, $trigger) {
			var value = $trigger.val(),
				options = $setting.data( 'trigger-value' ),
				available;

			/* If it's a radio input that isn't checked, ignore the event. */
			if ( 'radio' === $trigger.attr( 'type' ) && ! $trigger.prop( 'checked' ) ) {
				return;
			}

			if ( ! options.hasOwnProperty( value ) ) {
				return;
			}

			available = options[value];

			$setting.find( 'input' ).each( function(){
				var $option = $(this),
					disabled = ! ( $option.val() in available );

				if ( disabled ) {
					$option.prop( 'checked', false );
				}

				$option.prop( 'disabled', disabled ).trigger( 'change' );
			});
		};

		get_trigger_id = function($setting) {
			var id = $setting.data( 'trigger-key' );

			/* Backwards compatibility for pre 1.5 */
			if ( 'undefined' === typeof id ) {
				id = '#' + $setting.data( 'show-only-if-key' );
			}

			return id;
		};

		get_trigger = function(id) {
			if ( '#' === id[0] ) {
				return $( id );
			}

			return $( '[name=' + id + ']' );
		};

		get_change_type = function($setting) {
			var type = $setting.data( 'trigger-change-type' );

			if ( 'undefined' === typeof type ) {
				type = 'visibility';
			}

			return type;
		};

		on_change = function() {
			var $trigger = $( this ),
				trigger_idx = $trigger.data( 'trigger_idx' ),
				settings,
				change;

			trigger_idx.forEach( function( id ) {
				settings = triggers[id]['settings'];

				settings.forEach( function( $setting ) {
					var change = get_change_type( $setting );

					if ( 'visibility' === change ) {
						toggle_setting( $setting, $trigger );
					} else if ( 'options' === change ) {
						toggle_options( $setting, $trigger );
					}
				} );
			} );
		};

		this.$el = $el;

		var i = 0;

		this.$el.find( '[data-trigger-key],[data-show-only-if-key]' ).each( function(){
			var $this      = $(this),
				trigger_id = get_trigger_id( $this ),
				element    = triggers[trigger_id];

			if ( 'undefined' === typeof triggers[trigger_id] ) {
				triggers[i] = {
					trigger_id : trigger_id,
					settings : []
				};
			}

			triggers[i].settings.push( $this );

			i += 1;
		});

		triggers.forEach( function( trigger, i ) {
			var $trigger = get_trigger( trigger['trigger_id'] ),
				trigger_idx = $trigger.data( 'trigger_idx' );

			if ( 'undefined' === typeof( trigger_idx ) ) {
				trigger_idx = [];
			}

			trigger_idx.push( i );

			$trigger.data( 'trigger_idx', trigger_idx );

			$trigger.on( 'change', on_change );

			$trigger.trigger( 'change' );
		} );
	};

	exports.Settings = Settings;

})( CHARITABLE_ADMIN, jQuery );

( function($){

	var setup_campaign_end_date_field = function() {
		$( '#campaign_end_date' ).on( 'change', function() {
			var $field = $( this ),
				date   = $field.val(),
				$span  = $field.siblings( '.charitable-end-time' ),
				$input = $field.siblings( '#campaign_end_time' );

			if ( '' === date || ! date ) {
				$span.hide();
				$input.val( 0 );
			} else {
				$span.text( '@ 23:59 PM' ).show();
				$input.val( '23:59:59' );
			}
		});
	};

	var setup_charitable_ajax = function() {
		$('[data-charitable-action]').on( 'click', function( e ){
			var data 	= $(this).data( 'charitable-args' ) || {},
				action 	= 'charitable-' + $(this).data( 'charitable-action' );

			$.post( ajaxurl,
				{
					'action'	: action,
					'data'		: data
				},
				function( response ) {
					console.log( "Response: " + response );
				}
			);

			return false;
		} );
	};

	var setup_charitable_toggle = function() {
		$( '[data-charitable-toggle]' ).on( 'click', function( e ){
			var toggle_id = $(this).data( 'charitable-toggle' ),
				toggle_text = $(this).attr( 'data-charitable-toggle-text' );

			if ( toggle_text && toggle_text.length ) {
				$(this).attr( 'data-charitable-toggle-text', $(this).text() );
				$(this).text( toggle_text );
			}

			$('#' + toggle_id).toggle();

			return false;
		} );
	};

	var setup_advanced_meta_box = function() {
		var $meta_box = $('#charitable-campaign-advanced-metabox');

		$meta_box.tabs();

		var min_height = $meta_box.find('.charitable-tabs').height();

		$meta_box.find('.ui-tabs-panel').each( function(){
			$(this).css( 'min-height', min_height );
		});
	};

	var toggle_custom_donations_checkbox = function() {
		var $custom = $('#campaign_allow_custom_donations'),
			$suggestions = $('.charitable-campaign-suggested-donations tbody tr:not(.to-copy)'),
			has_suggestions = $suggestions.length > 1 || false === $suggestions.first().hasClass('no-suggested-amounts');

		$custom.prop( 'disabled', ! has_suggestions );

		if ( ! has_suggestions ) {
			$custom.prop( 'checked', true );
		}
	};

	var setup_sortable_suggested_donations = function(){
		$('.charitable-campaign-suggested-donations tbody').sortable({
			items: "tr:not(.to-copy)",
			handle: ".handle",
			stop: function( event, ui ) {
				reindex_rows();
			}

	    });
	};

	var add_suggested_amount_row = function( $button ) {
		var $table = $button.closest( '.charitable-campaign-suggested-donations' ).find('tbody');
		var $clone = $table.find('tr.to-copy').clone().removeClass('to-copy hidden');
		$table.find( '.no-suggested-amounts' ).hide();
		$table.append( $clone );
		reindex_rows();
		toggle_custom_donations_checkbox();
	};

	var delete_suggested_amount_row = function($button) {
		console.log($button);
		$button.closest( 'tr' ).remove();
		var $table = $button.closest('.charitable-campaign-suggested-donations').find('tbody');
		if( $table.find( 'tr:not(.to-copy)' ).length == 1 ){
			$table.find( '.no-suggested-amounts' ).removeClass('hidden').show();
		}
		reindex_rows();
		toggle_custom_donations_checkbox();
	};

	var reindex_rows = function(){
		$('.charitable-campaign-suggested-donations tbody').each(function(){
			$(this).children('tr').not('.no-suggested-amounts .to-copy').each(function(index) {
				$(this).data('index', index );
				$(this).find('input').each(function(i) {
					this.name = this.name.replace(/(\[\d\])/, '[' + index + ']');
				});
			});
		});
	};

	var setup_dashboard_widgets = function() {
		var $widget = $( '#charitable_dashboard_donations' );

		if ( $widget.length ) {
			$.ajax({
				type: "GET",
				data: {
					action: 'charitable_load_dashboard_donations_widget'
				},
				url: ajaxurl,
				success: function (response) {
					$widget.find( '.inside' ).html( response );
				}
			});
		}
	};

	var setup_actions_form = function() {
		var $form = $( '.charitable-actions-form-wrapper' ),
			$select,
			$submit,
			$button;

		if ( ! $form.length ) {
			return;
		}

		$select = $form.find( '.charitable-action-select' );
		$submit = $form.find( '.charitable-actions-submit' );
		$button = $submit.find( 'button' );

		$submit.hide();

		$select.on( 'change', function() {
			var action = $select.val(),
				text = $select.find( 'option:selected' ).data( 'button-text');

			if ( '' === action ) {
				$submit.hide();
				return;
			}

			$form.find( '.charitable-action-fields' ).each( function() {
				var $el = $( this );
				$el.toggle( action === $el.data( 'type' ) );
			});

			if ( text ) {
				$button.text( text );
			} else {
				$button.text( $button.prop( 'title' ) );
			}

			$submit.show();
		});
	}

	var setup_select2 = function() {
		if ( ! $.fn.select2 ) {
			return;
		}

		$( '.select2 select, select.select2' ).select2();
	}

	var setup_donor_select = function() {
		var user_fields;

		var get_user_fields = function() {
			if ( 'object' === typeof user_fields ) {
				return user_fields;
			}

			user_fields = [];

			$( '#charitable-user-fields-wrap' ).find( 'select,input,textarea' ).each( function() {
				user_fields.push(this.name);
			});

			return user_fields;
		};

		$( '.charitable-admin-donation-form #donor-id' ).on( 'change', function() {
			var donor_id = $(this).val(),
				$user_fields,
				field;

			if ( '' === donor_id || 'new' === donor_id ) {
				return;
			}

			$user_fields = $( '#charitable-user-fields-wrap' );
			$user_fields.addClass( 'loading-data' );

			data = {
				action   : 'charitable_get_donor_data',
				donor_id : donor_id,
				fields   : get_user_fields(),
				nonce    : $(this).data( 'nonce' )
			};

			$.ajax({
				type: 'POST',
				data: data,
				dataType: 'json',
				url: ajaxurl,
				xhrFields: {
					withCredentials: true
				},
				success: function (response) {
					if ( response.success ) {
						for ( field in response.data ) {
							$user_fields.find( "[name=" + field + "]" ).val( response.data[field] );
						}
					}
					$user_fields.removeClass( 'loading-data' );
				}
			}).fail(function (data) {
				if ( window.console && window.console.log ) {
					console.log( data );
					$user_fields.removeClass( 'loading-data' );
				}
			});
		});
	}

	var setup_currency_inputs = function() {
		if ( 'undefined' === typeof accounting ) {
			return;
		}

		var unformat = function( amount ) {
			return Math.abs( parseFloat( accounting.unformat( amount, CHARITABLE.currency_format_decimal_sep ) ) );
		}

		var format = function( amount ) {
			return accounting.formatMoney( amount, {
				symbol : '',
				decimal : CHARITABLE.currency_format_decimal_sep,
				thousand : CHARITABLE.currency_format_thousand_sep,
				precision : CHARITABLE.currency_format_num_decimals,
				format : CHARITABLE.currency_format
			}).trim();
		}

		$( 'body' ).on( 'change', '.currency-input', function() {
			var amount = unformat( $( this ).val() );

			if ( $.trim( amount ) > 0 ) {
				$( this ).val( format( amount ) );
			}
		} );

		$( '.currency-input' ).trigger( 'change' );
	};

	$(document).ready( function(){

		if ( CHARITABLE_ADMIN.Datepicker ) {
			$( '.charitable-datepicker' ).each( function() {
				CHARITABLE_ADMIN.Datepicker( $(this ) );
			});
		}

		$( '#charitable-settings, body.post-type-campaign form#post, body.post-type-donation form#post' ).each( function(){
			CHARITABLE_ADMIN.Settings( $(this) );
		});

		// $('body.post-type-campaign .handlediv, body.post-type-donation .handlediv').remove();
		// $('body.post-type-campaign .hndle, body.post-type-donation .hndle').removeClass( 'hndle ui-sortable-handle' ).addClass( 'postbox-title' );

		setup_advanced_meta_box();
		setup_sortable_suggested_donations();
		toggle_custom_donations_checkbox();
		setup_charitable_ajax();
		setup_charitable_toggle();
		setup_dashboard_widgets();
		setup_campaign_end_date_field();
		setup_actions_form();
		setup_select2();
		setup_donor_select();
		setup_currency_inputs();

		$('[data-charitable-add-row]').on( 'click', function() {
			var type = $( this ).data( 'charitable-add-row' );

			if ( 'suggested-amount' === type ) {
				add_suggested_amount_row($(this));
			}

			return false;
		});

		$('.charitable-campaign-suggested-donations').on( 'click', '.charitable-delete-row', function() { console.log('clicked');
			delete_suggested_amount_row( $(this) );
			return false;
		});

		$('body').on( 'click', '[data-campaign-benefactor-delete]', function() {
			var $block = $( this ).parents( '.charitable-benefactor' ),
				data = {
					action 			: 'charitable_delete_benefactor',
					benefactor_id 	: $(this).data( 'campaign-benefactor-delete' ),
					nonce 			: $(this).data( 'nonce' )
				};

			$.ajax({
				type: "POST",
				data: data,
				dataType: "json",
				url: ajaxurl,
				xhrFields: {
					withCredentials: true
				},
				success: function (response) {
					if ( response.deleted ) {
						$block.remove();
					}
				}
			}).fail(function (data) {
				if ( window.console && window.console.log ) {
					console.log( 'failture' );
					console.log( data );
				}
			});

			return false;
		});

		$('#change-donation-status').on( 'change', function() {
			$(this).parents( 'form' ).submit();
		});
	});

})( jQuery );