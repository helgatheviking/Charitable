CHARITABLE = window.CHARITABLE || {};

/**
 * Set up Donation_Form object.
 */
( function( exports, $ ) {

    /**
     * Donation_Form expects a jQuery this.form object.
     */
    var Donation_Form = function( form ) {

        /**
         * Array of errors.
         *
         * @access public
         */
        this.errors = [];

        /**
         * Pending processes array.
         *
         * @access public
         */
        this.pending_processes = [];

        /**
         * Form object.
         *
         * @access public
         */
        this.form = form;

        /**
         * Flag to allow processing to be paused (i.e. while something like Stripe is processing).
         *
         * @access public
         */
        this.pause_processing = false;

        /**
         * Flag to prevent the on_submit handler from sending multiple concurrent AJAX requests
         *
         * @access public
         */
        this.submit_processing = false;

        /**
         * The donation amount.
         *
         * @access public
         */
        this.total = 0;

        /**
         * Reference to this object.
         *
         * @access private
         */
        var self = this;

        /**
         * Body element reference.
         *
         * @access private
         */
        var $body = $( 'body' );

        /**
         * Handle click of terms link.
         *
         * @access private
         */
        var on_click_terms = function() {
            self.form.find( '.charitable-terms-text' ).addClass( 'active' );
            return false;
        };

        /**
         * Focus event handler on custom donation amount input field.
         *
         * @access private
         */
        var on_focus_custom_amount = function() {
            $( this ).closest( 'li' ).trigger( 'click' ).find( 'input[name=donation_amount]' ).prop( 'checked', true ).trigger( 'change' );

            self.form.off( 'focus', 'input.custom-donation-input', on_focus_custom_amount );

            $( this ).focus();

            self.form.on( 'focus', 'input.custom-donation-input', on_focus_custom_amount );
        };

        /**
         * Trigger jQuery events to broadcast the change in donation amount.
         *
         * @since  1.6.19
         *
         * @access private
         */
        var trigger_amount_change_events = function() {
            /* The chosen donation amount has changed. */
            $body.trigger( 'charitable:form:amount:changed', self );

            /* The overall donation amount has changed. */
            $body.trigger( 'charitable:form:total:changed', self );
        };

        /**
         * Focus event handler for changes to custom donation amount input field.
         *
         * @access private
         */
        var on_change_custom_donation_amount = function() {
            var unformatted = self.unformat_amount( $( this ).val() );

            if ( $.trim( unformatted ) > 0 ) {
                $( this ).val( self.format_amount( unformatted ) );
            }

            trigger_amount_change_events();
        };

        /**
         * Select a donation amount.
         *
         * @return  void
         */
        var on_select_donation_amount = function() {
            var $li = $( this ).closest( 'li' );

            // Already selected, quit early to prevent focus/change loop
            if ( $li.hasClass( 'selected' ) ) {
                return;
            }

            $li.parents( '.charitable-donation-form' ).find( '.donation-amount.selected' ).removeClass( 'selected' );

            $li.addClass( 'selected' );

            if ( $li.hasClass( 'custom-donation-amount' ) ) {
                $li.find( 'input.custom-donation-input' ).focus();
            }

            trigger_amount_change_events();
        };

        /**
         * Change event handler for payment gateway selector.
         *
         * @access private
         */
        var on_change_payment_gateway = function() {
            self.hide_inactive_payment_methods();
            self.show_active_payment_methods( $(this).val() );
        };

        /**
         * Event handler when the "Change" link is clicked for pre-filled donations.
         */
        var on_click_change_amount_link = function() {
            $(this).parent().addClass( 'charitable-hidden' );
        };

        /**
         * Submit event handler for donation form.
         *
         * @access private
         */
        var on_submit = function( event ) {
            var helper = event.data.helper;

            if ( helper.submit_processing ) {
                return false;
            }

            helper.submit_processing = true;

            /* Display the processing spinner and hide the button */
            helper.show_processing();

            /* Validate the form submission before going further. */
            if ( false === helper.validate() ) {
                helper.hide_processing();
                helper.print_errors();
                helper.scroll_to_top();
                return false;
            }

            /* If processing has been paused, return false now. */
            if ( false !== helper.pause_processing ) {
                return false;
            }

            /* If we're not using AJAX to process the donation further, return now. */
            if ( 1 !== helper.form.data( 'use-ajax' ) ) {
                return true;
            }

            /* Continue on to process the donation. */
            maybe_process( helper, function() {
                $body.trigger( 'charitable:form:process', helper );
            } );

            return false;
        };

        /**
         * Check whether there are any asynchronous threads we need to wait for.
         *
         * If not, trigger processing. If there are, check again in 500ms.
         */
        var maybe_process = function( helper, callback ) {
            if ( ! helper.waiting() ) {
                /* Double-check that there are still no errors. */
                if ( helper.get_errors().length > 0 ) {
                    helper.hide_processing();

                    helper.print_errors();

                    helper.scroll_to_top();
                } else {
                    callback();
                }

                return;
            }

            setTimeout( maybe_process, 500, helper, callback );
        }

        /**
         * Process the donation.
         *
         * This is a callback for the 'charitable:form:process' event. It's called
         * after validation has taken place.
         *
         * @param   object Event
         * @param   object Donation_Form
         */
        var process_donation = function( event, helper ) {
            var data = helper.get_data();
            var form = helper.form;

            /* Cancel the default Charitable action, but pass it along as the form_action variable */
            data.action = 'make_donation';
            data.form_action = data.charitable_action;
            delete data.charitable_action;

            $.ajax({
                type: "POST",
                data: data,
                dataType: "json",
                url: CHARITABLE_VARS.ajaxurl,
                timeout: 0,
                xhrFields: {
                    withCredentials: true
                },
                success: function (response) {
                    $body.trigger( 'charitable:form:processed', [ response, helper ] );

                    if ( response.success ) {
                        maybe_process( helper, function() {
                            window.location.href = response.redirect_to;
                        } );
                    }
                    else {
                        helper.hide_processing();
                        helper.print_errors( response.errors );
                        helper.scroll_to_top();

                        if ( response.donation_id ) {
                            helper.set_donation_id( response.donation_id );
                        }
                    }
                }
            }).fail(function (response, textStatus, errorThrown) {

                if ( window.console && window.console.log ) {
                    console.log( response );
                }

                helper.hide_processing();

                helper.print_errors( [ CHARITABLE_VARS.error_unknown ] );

                helper.scroll_to_top();

            });

            return false;
        }

        /**
         * Set up event handlers for donation forms.
         *
         * @return  void
         */
        var init = function() {
            // Init donation amount selection
            self.form.on( 'click', '.donation-amount', on_select_donation_amount );
            self.form.on( 'focus', 'input.custom-donation-input', on_focus_custom_amount );
            self.form.on( 'click', '.charitable-terms-link', on_click_terms );

            // Init currency formatting
            self.form.on( 'blur', '.custom-donation-input', on_change_custom_donation_amount );

            self.form.find( '.donation-amount input:checked' ).each( function() {
                $( this ).closest( 'li' ).addClass( 'selected' );
            });

            if ( self.get_all_payment_methods().length ) {
                self.hide_inactive_payment_methods();
                self.form.on( 'change', '#charitable-gateway-selector input[name=gateway]', on_change_payment_gateway );
            }

            self.form.on( 'click', '.change-donation', on_click_change_amount_link );

            // Handle donation form submission
            self.form.on( 'submit', {
                helper : self,
            }, on_submit );

            // This only fires after the first form instance is initialized.
            if ( false === CHARITABLE.forms_initialized ) {

                // Process the donation on the 'charitable:form:process' event.
                $body.on( 'charitable:form:process', process_donation );

                $body.trigger( 'charitable:form:initialize', self );

                CHARITABLE.forms_initialized = true;
            }

            $body.trigger( 'charitable:form:loaded', self );
        }

        init();
    };

    /**
     * Return a particular input field.
     *
     * @param   string The field name to retrieve.
     * @return  object
     */
    Donation_Form.prototype.get_input = function( field ) {
        return this.form.find( '[name=' + field + ']' );
    }

    /**
     * Return the submitted email address.
     *
     * @return  string
     */
    Donation_Form.prototype.get_email = function() {
        return this.form.find( '[name=email]' ).val();
    };

    /**
     * Returns whether this is a recurring donation.
     *
     * @since  1.4.21
     *
     * @return boolean
     */
    Donation_Form.prototype.is_recurring_donation = function() {
        var recurring = this.form.find( '[name=recurring_donation]:checked' );

        return recurring.length && 'once' !== recurring.val();
    };

    /**
     * Get the chosen suggested amount.
     *
     * Note that when no option has been selected, or a custom donation amount has been added,
     * this will return 0.
     *
     * @since   1.4.19
     *
     * @return  float
     */
    Donation_Form.prototype.get_suggested_amount = function() {
        return accounting.unformat(
            this.form.find( '[name=donation_amount]:checked, input[type=hidden][name=donation_amount]' ).val(),
            CHARITABLE_VARS.currency_format_decimal_sep
        );
    }

    /**
     * Get the custom amount.
     *
     * Note that this will return 0 when no custom amount has been entered or a 0 has been entered.
     *
     * @since   1.4.19
     *
     * @return  float
     */
    Donation_Form.prototype.get_custom_amount = function() {
        var input = this.form.find( '.charitable-donation-options.active input.custom-donation-input,.charitable-donation-options.active input.custom-donation-amount' );

        if ( 0 === input.length ) {
            input = this.form.find( 'input.custom-donation-input' );
        }

        return accounting.unformat(
            input.val(),
            CHARITABLE_VARS.currency_format_decimal_sep
        );
    }

    /**
     * Get the donation subtotal, which is the amount passed by the donor.
     *
     * Notably, this does not include any additional amounts that may be added onto the donation
     * total, such as processing fees that the donor agrees to pay.
     *
     * @since  1.6.7
     *
     * @return float
     */
    Donation_Form.prototype.get_subtotal = function() {
        return this.get_suggested_amount() || this.get_custom_amount();
    };

    /**
     * Get the submitted amount, taking into account both the custom & suggested donation fields.
     *
     * @return  float
     */
    Donation_Form.prototype.get_amount = function() {
        this.total = this.get_subtotal();

        this.form.trigger( 'charitable:form:get_amount', this );

        return this.total;
    };

    /**
     * Get the submitted amount, taking into account both the custom & suggested donation fields.
     *
     * @since  1.6.8
     *
     * @param  float add
     * @return void
     */
    Donation_Form.prototype.add_amount = function( add ) {
        this.total += add;
    };

    /**
     * Get the submitted amount, taking into account both the custom & suggested donation fields.
     *
     * @since  1.6.8
     *
     * @param  float remove Amount to be removed
     * @return void
     */
    Donation_Form.prototype.remove_amount = function( remove ) {
        this.total -= remove;
    };

    /**
     * Get a description of the donation.
     *
     * @return  string
     */
    Donation_Form.prototype.get_description = function() {
        return this.form.find( '[name=description]' ).val() || '';
    };

    /**
     * Get credit card number.
     *
     * @return  string
     */
    Donation_Form.prototype.get_cc_number = function() {
        return this.form.find( '#charitable_field_cc_number input' ).val() || '';
    };

    /**
     * Get credit card CVC number.
     *
     * @return  string
     */
    Donation_Form.prototype.get_cc_cvc = function() {
        return this.form.find( '#charitable_field_cc_cvc input' ).val() || '';
    };

    /**
     * Get credit card expiry month.
     *
     * @return  string
     */
    Donation_Form.prototype.get_cc_expiry_month = function() {
        return this.form.find( '#charitable_field_cc_expiration select.month' ).val() || '';
    };

    /**
     * Get credit card expiry year.
     *
     * @return  string
     */
    Donation_Form.prototype.get_cc_expiry_year = function() {
        return this.form.find( '#charitable_field_cc_expiration select.year' ).val() || '';
    };

    /**
     * Clear credit card fields.

     *
     * This is used by gateways that create tokens through Javascript (such as Stripe), to
     * avoid credit card details hitting the server.
     *
     * @return  void
     */
    Donation_Form.prototype.clear_cc_fields = function() {
        this.form.find( '#charitable_field_cc_number input, #charitable_field_cc_name input, #charitable_field_cc_cvc input, #charitable_field_cc_expiration select' ).removeAttr( 'name' );
    };

    /**
     * Return the selected payment method.
     *
     * @return  string
     */
    Donation_Form.prototype.get_payment_method = function() {
        return this.form.find( '[type=hidden][name=gateway], [name=gateway]:checked' ).val() || '';
    };

    /**
     * Return all payment methods.
     *
     * @return  object
     */
    Donation_Form.prototype.get_all_payment_methods = function() {
        return this.form.find( '#charitable-gateway-selector input[name=gateway]' );
    }

    /**
     * Hide inactive payment methods.
     *
     * @return  void
     */
    Donation_Form.prototype.hide_inactive_payment_methods = function() {
        var active = this.get_payment_method();
        var fields = this.form.find( '.charitable-gateway-fields[data-gateway!=' + active + ']' );

        fields.hide();
        fields.find( 'input[required],select[required],textarea[required]' ).attr( 'data-required', 1 ).attr( 'required', false );
    };

    /**
     * Show active payment methods.
     *
     * @return  void
     */
    Donation_Form.prototype.show_active_payment_methods = function( active ) {
        var active = active || this.get_payment_method();
        var fields = this.form.find( '.charitable-gateway-fields[data-gateway=' + active + ']' );

        fields.show();
        fields.find( '[data-required]' ).attr( 'required', true );
    };

    /**
     * Select a donation amount.
     *
     * @param   int price
     * @param   string symbol
     * @return  string
     */
    Donation_Form.prototype.format_amount = function( price, symbol ){
        if ( typeof symbol === 'undefined' )
            symbol = '';

        return accounting.formatMoney( price, {
                symbol : symbol,
                decimal : CHARITABLE_VARS.currency_format_decimal_sep,
                thousand: CHARITABLE_VARS.currency_format_thousand_sep,
                precision : CHARITABLE_VARS.currency_format_num_decimals,
                format: CHARITABLE_VARS.currency_format
        }).trim();
    };

    /**
     * Select a donation amount.
     *
     * @param   int price
     * @return  string
     */
    Donation_Form.prototype.unformat_amount = function( price ) {
        return Math.abs( parseFloat( accounting.unformat( price, CHARITABLE_VARS.currency_format_decimal_sep ) ) );
    };

    /**
     * Add an error message.
     *
     * @param   string message
     * @return  void
     */
    Donation_Form.prototype.add_error = function( message ) {
        this.errors.push( message );
    };

    /**
     * Return the errors.
     *
     * @return  []
     */
    Donation_Form.prototype.get_errors = function() {
        return this.errors;
    };

    /**
     * Prints out a nice string describing the errors.
     *
     * @return  string
     */
    Donation_Form.prototype.get_error_message = function() {
        return this.errors.join( ' ' );
    };

    /**
     * Print the errors at the top of the form.
     *
     * @param   array
     */
    Donation_Form.prototype.print_errors = function( errors ) {
        var e = errors || this.errors,
            i = 0,
            count = e.length,
            output = '';

        if ( 0 === count ) {
            return;
        }

        if ( this.form.find( '.charitable-form-errors' ).length ) {
            this.form.find( '.charitable-form-errors' ).remove();
        }

        output += '<div class="charitable-form-errors charitable-notice"><ul class="errors"><li>';
        output += e.join( '</li><li>' );
        output += '</li></ul></div>';

        this.form.prepend( output );
    }

    /**
     * Clear the errors and remove the printed errors.
     */
    Donation_Form.prototype.clear_errors = function() {
        this.errors = [];

        if ( this.form.find( '.charitable-form-errors' ).length ) {
            this.form.find( '.charitable-form-errors' ).remove();
        }
    }

    /**
     * Return whether we are waiting for an asynchronous process to finish.
     *
     * @since  1.6.9
     *
     * @return boolean
     */
    Donation_Form.prototype.waiting = function() {
        return this.pending_processes.length > 0;
    }

    /**
     * Add a pending process.
     *
     * @since  1.6.9
     *
     * @param  string Process identifier.
     * @return int Index of the process.
     */
    Donation_Form.prototype.add_pending_process = function( process ) {
        var index = this.pending_processes.indexOf( process );
        return -1 === index ? ( this.pending_processes.push( process ) - 1 ) : index;
    }

    /**
     * Remove a pending process by index.
     *
     * @since  1.6.9
     *
     * @param  int Index of the process.
     * @return void
     */
    Donation_Form.prototype.remove_pending_process = function( index ) {
        this.pending_processes.splice( index, 1 );
    }

    /**
     * Remove a pending process by process name.
     *
     * @since  1.6.17
     *
     * @param  string Name of the process.
     * @return void
     */
    Donation_Form.prototype.remove_pending_process_by_name = function( process ) {
        var index = this.pending_processes.indexOf( process );
        return -1 !== index && this.remove_pending_process( index );
    }

    /**
     * Show that the donation form is processing.
     */
    Donation_Form.prototype.show_processing = function() {
        this.form.find( '.charitable-form-processing' ).show();
        this.form.find( 'button[name="donate"]' ).hide();
    }

    /**
     * Hide the processing spinner and show the donate button.
     */
    Donation_Form.prototype.hide_processing = function() {
        this.form.find( '.charitable-form-processing' ).hide();
        this.form.find( 'button[name="donate"]' ).show();

        this.submit_processing = false;
        this.pause_processing = false;
    }

    /**
     * Scroll to the top of the form.
     */
    Donation_Form.prototype.scroll_to_top = function() {
        var $modal = this.form.parents( '.charitable-modal' );

        if ( $modal.length ) {
            $modal.scrollTop( 0 );
        }
        else {
            window.scrollTo( this.form.position().left, this.form.position().top );
        }
    };

    /**
     * Set the donation ID.
     */
    Donation_Form.prototype.set_donation_id = function( donation_id ) {
        this.form.find( '[name=ID]' ).val( donation_id );
    }

    /**
     * Returns all of the submitted data as key=>value object.
     *
     * @return  object
     */
    Donation_Form.prototype.get_data = function() {

        return this.form.serializeArray().reduce( function( obj, item ) {
            if ( '[]' === item.name.slice( -2 ) ) {
                var name = item.name.slice( 0, -2 );

                if ( ! obj.hasOwnProperty( name ) ) {
                    obj[name] = [];
                }

                obj[name].push( item.value );
            } else {
                obj[ item.name ] = item.value;
            }

            return obj;
        }, {} );

    };

    /**
     * Returns all of the required fields.
     *
     * @return  object
     */
    Donation_Form.prototype.get_required_fields = function() {
        var fields = this.form.find( '.charitable-fieldset .required-field' ).not( '#charitable-gateway-fields .required-field' ),
            method = this.get_payment_method();

        if ( '' !== method ) {

            var gateway_fields = this.form.find( '[data-gateway=' + method + '] .required-field' );

            if ( gateway_fields.length ) {
                fields = $.merge( fields, gateway_fields );
            }

        }

        return fields;
    };

    /**
     * Make sure that the submitted amount is valid.
     *
     * @return  boolean
     */
    Donation_Form.prototype.is_valid_amount = function() {
        var minimum = parseFloat( CHARITABLE_VARS.minimum_donation );

        return minimum > 0 || CHARITABLE_VARS.permit_0_donation
            ? this.get_subtotal() >= minimum
            : this.get_subtotal() > minimum;
    };

    /**
     * Validate the amount. If not valid, set an error.
     *
     * @return  boolean
     */
    Donation_Form.prototype.validate_amount = function() {
        if ( false === this.is_valid_amount() ) {
            this.add_error( CHARITABLE_VARS.error_invalid_amount );
            return false;
        }

        return true;
    };

    /**
     * Verify that all required fields are filled out.
     *
     * @return  boolean
     */
    Donation_Form.prototype.validate_required_fields = function() {
        var has_all_required_fields = true;

        this.get_required_fields().each( function() {
            if ( '' === $( this ).find( 'input, select, textarea' ).val() ) {
                has_all_required_fields = false;
            }
        });

        if ( ! has_all_required_fields ) {
            this.add_error( CHARITABLE_VARS.error_required_fields );
        }

        return has_all_required_fields;
    };

    /**
     * Verifies the submission and returns true if it all looks ok.
     *
     * @param   this.form The submitted form.
     * @return  boolean
     */
    Donation_Form.prototype.validate = function() {
        /* First clear out the errors. */
        this.clear_errors();

        this.validate_amount();

        this.validate_required_fields();

        this.form.trigger( 'charitable:form:validate', this );

        return this.errors.length === 0;
    };

    exports.Donation_Form = Donation_Form;

})( CHARITABLE, jQuery );

/**
 * Set up Toggle object.
 */
( function( exports, $ ){
    var Toggle = function() {

        /**
         * Hide toggle target.
         */
        var hide_target = function() {
            get_target( $(this) ).addClass( 'charitable-hidden' );
        };

        /**
         * Get the target element.
         */
        var get_target = function( $el ) {
            var target = $el.data('charitable-toggle');

            if ( target[0] !== '.' && target[0] !== '#' ) {
                target = '#' + target;
            }

            return $( target );
        }

        /**
         * Toggle event handler for any fields with the [data-charitable-toggle] attribute.
         *
         * @access private
         */
        var on_toggle = function() {
            var $this   = $( this ),
                $target = get_target( $this );

            $target.toggleClass( 'charitable-hidden', ( function(){
                    if ( $this.is( ':checkbox' ) ) {
                        return ! $this.is( ':checked' );
                    }
                    return false === $target.hasClass( 'charitable-hidden' );
                })()
            );

            return false;
        };

        /**
         * Initializing function.
         */
        var init = function() {
            $( '[data-charitable-toggle]' ).each( hide_target );
        };

        // Initialization only required once.
        $( 'body' ).on( 'click', '[data-charitable-toggle]', on_toggle );

        // Check whether content is being loaded from the session.
        if ( exports.content_loading ) {
            var timer = setInterval(function(){
                if ( false === exports.content_loading ) {
                    init();
                    clearInterval(timer);
                }
            }, 500);
        }

        // Initialization that will be performed everytime
        return init;
    }

    exports.Toggle = Toggle();

})( CHARITABLE, jQuery );

/**
 * Set up Charitable helper functions.
 */
( function( exports, $ ) {
    exports.Helpers = {
        /**
         * Sanitize URLs.
         */
        sanitize_url : function( input ) {
            var url = input.value.toLowerCase();

            if ( !/^https?:\/\//i.test( url ) && url.length > 0 ) {
                url = 'http://' + url;
                input.value = url;
            }
        },

        /**
         * Disable forms after submission.
         */
        disable_forms_after_submission : function( forms ) {
            forms.forEach( function(form) {
                var $form = $(form);

                if ( $form.length ) {
                    $form.append( '<input type="hidden" id="charitable-submit-button-value" />' );

                    $form.find( '[type=submit]' ).on( 'click', function( event ) {
                        var name = event.currentTarget.name,
                            value = event.currentTarget.value;

                        $form.find( '#charitable-submit-button-value' )
                            .attr( 'name', name )
                            .attr( 'value', value );

                        $form.find( '[type=submit]' )
                            .attr( 'disabled', 'disabled' );

                        return $form.submit();
                    } );
                }
            } );
        }
    };
})( CHARITABLE, jQuery );

/**
 * URL sanitization.
 *
 * This is provided for backwards compatibility.
 */
CHARITABLE.SanitizeURL = function( input ) {
    CHARITABLE.Helpers.sanitize_url( input );
};

/**
 * Do a version check.
 *
 * @since 1.6.19
 *
 * @param string version The version we are comparing with.
 * @param string compare If compare is left empty, use the Charitable version.
 * @return integer|boolean
 */
CHARITABLE.VersionCompare = function( version, compare ) {
    compare = compare || CHARITABLE_VARS.version;

    if ( typeof version + typeof compare != 'stringstring')
        return false;

    var a = version.split( '.' ),
        b = compare.split( '.' ),
        i = 0,
        len = Math.max( a.length, b.length );

    for ( ; i < len; i++ ) {
        if ((a[i] && !b[i] && parseInt(a[i]) > 0) || (parseInt(a[i]) > parseInt(b[i]))) {
            return 1;
        } else if ((b[i] && !a[i] && parseInt(b[i]) > 0) || (parseInt(a[i]) < parseInt(b[i]))) {
            return -1;
        }
    }

    return 0;
};

/***
 * Finally queue up all the scripts.
 */
( function( $ ) {

    /**
     * Prevent re-initializing form handlers.
     */
    CHARITABLE.forms_initialized = false;

    $( document ).ready( function() {

        $( 'html' ).addClass( 'js' );

        $( '.charitable-donation-form' ).each( function() {
            new CHARITABLE.Donation_Form( $( this ) );
        });

        CHARITABLE.Toggle();

        CHARITABLE.Helpers.disable_forms_after_submission(
            [
                '#charitable-registration-form',
                '#charitable-profile-form',
                '#charitable-campaign-submission-form'
            ]
        );

    });

})( jQuery );
