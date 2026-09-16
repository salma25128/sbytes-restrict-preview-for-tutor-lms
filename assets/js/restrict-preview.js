/**
 * Preview Gate for Tutor LMS - front-end behaviour.
 *
 * Everything here is presentation and convenience. Access itself is
 * enforced server-side, so a visitor with JavaScript disabled still cannot
 * reach gated preview content.
 */
( function () {
	'use strict';

	if ( typeof rptlData === 'undefined' ) {
		return;
	}

	var watcherAttached = false;

	document.addEventListener( 'DOMContentLoaded', function () {
		initModal();
		initPreviewClickIntercept();
		initCurriculumBadges();
		initAuthTabs();
		initInlineRedirect();
		initFieldLabels();
		initSwitchLinks();
	} );

	/* ---------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------- */

	function stripHash( url ) {
		try {
			var u = new URL( url, window.location.origin );
			u.hash = '';
			return u.href.replace( /\/$/, '' );
		} catch ( err ) {
			return url;
		}
	}

	function sprintf( template, value ) {
		return String( template ).replace( '%d', value );
	}

	/* ---------------------------------------------------------------
	 * Popup
	 * ------------------------------------------------------------- */

	function initModal() {
		var overlay = document.getElementById( 'rptl-overlay' );
		if ( ! overlay ) {
			return;
		}

		var closeBtn = document.getElementById( 'rptl-close' );
		if ( closeBtn ) {
			closeBtn.addEventListener( 'click', closeModal );
		}

		overlay.addEventListener( 'click', function ( e ) {
			if ( e.target === overlay ) {
				closeModal();
			}
		} );

		document.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key ) {
				closeModal();
			}
		} );
	}

	function openModal( targetUrl ) {
		var overlay = document.getElementById( 'rptl-overlay' );
		if ( ! overlay ) {
			return;
		}

		targetUrl = targetUrl || window.location.href;

		// Point both forms at the lesson that was actually clicked. That is
		// only known at this moment, so it cannot be rendered server-side.
		applyRedirectTo( overlay.querySelector( '[data-rptl-auth]' ), targetUrl );
		setReturnCookie( targetUrl );

		overlay.classList.add( 'is-open' );
		overlay.setAttribute( 'aria-hidden', 'false' );
		document.body.style.overflow = 'hidden';

		var first = overlay.querySelector( '.rptl-panel:not([hidden]) input:not([type=hidden])' );
		if ( first ) {
			first.focus();
		}
	}

	function closeModal() {
		var overlay = document.getElementById( 'rptl-overlay' );
		if ( ! overlay ) {
			return;
		}
		overlay.classList.remove( 'is-open' );
		overlay.setAttribute( 'aria-hidden', 'true' );
		document.body.style.overflow = '';
	}

	/* ---------------------------------------------------------------
	 * Intercept preview lesson links for guests
	 * ------------------------------------------------------------- */

	function initPreviewClickIntercept() {
		if ( rptlData.isLoggedIn || watcherAttached ) {
			return;
		}

		var previewUrls = ( rptlData.previewUrls || [] ).map( stripHash );
		if ( ! previewUrls.length ) {
			return;
		}

		watcherAttached = true;

		document.addEventListener( 'click', function ( e ) {
			var link = e.target.closest ? e.target.closest( 'a[href]' ) : null;
			if ( ! link ) {
				return;
			}
			if ( -1 === previewUrls.indexOf( stripHash( link.href ) ) ) {
				return;
			}
			e.preventDefault();
			openModal( link.href );
		}, true );
	}

	/* ---------------------------------------------------------------
	 * Redirect after login or signup
	 *
	 * Tutor honours a redirect_to field natively: its login handler ends
	 * with wp_safe_redirect( $_POST['redirect_to'] ) and its student
	 * registration handler reads $_REQUEST['redirect_to']. Writing that
	 * field into each form is all that is required.
	 * ------------------------------------------------------------- */

	function applyRedirectTo( root, url ) {
		if ( ! root || ! url ) {
			return;
		}

		var store = root.querySelector( '[data-rptl-redirect]' );
		if ( store ) {
			store.value = url;
		}

		root.querySelectorAll( 'form' ).forEach( function ( form ) {
			var field = form.querySelector( 'input[name="redirect_to"]' );
			if ( ! field ) {
				field = document.createElement( 'input' );
				field.type = 'hidden';
				field.name = 'redirect_to';
				form.appendChild( field );
			}
			field.value = url;
		} );
	}

	// Secondary safety net for flows that drop redirect_to, read by
	// RPTL_Access::maybe_redirect_after_auth().
	function setReturnCookie( url ) {
		if ( ! url || ! rptlData.cookieName ) {
			return;
		}
		document.cookie = rptlData.cookieName + '=' + encodeURIComponent( url ) +
			';path=/;max-age=1800;SameSite=Lax';
	}

	function initInlineRedirect() {
		var root = document.querySelector( '.rptl-card [data-rptl-auth]' );
		if ( ! root ) {
			return;
		}
		var store = root.querySelector( '[data-rptl-redirect]' );
		var url = ( store && store.value ) || window.location.href;
		applyRedirectTo( root, url );
		setReturnCookie( url );
	}

	/* ---------------------------------------------------------------
	 * Tabs
	 * ------------------------------------------------------------- */

	function initAuthTabs() {
		document.querySelectorAll( '[data-rptl-auth]' ).forEach( function ( root ) {
			var tabs = root.querySelectorAll( '[data-rptl-tab]' );
			if ( ! tabs.length ) {
				return;
			}
			var tablist = root.querySelector( '.rptl-tabs' );

			tabs.forEach( function ( tab ) {
				tab.addEventListener( 'click', function () {
					var name = tab.getAttribute( 'data-rptl-tab' );

					tabs.forEach( function ( t ) {
						var on = t === tab;
						t.classList.toggle( 'is-active', on );
						t.setAttribute( 'aria-selected', on ? 'true' : 'false' );
					} );

					root.querySelectorAll( '[data-rptl-panel]' ).forEach( function ( panel ) {
						panel.hidden = panel.getAttribute( 'data-rptl-panel' ) !== name;
						panel.classList.toggle( 'is-active', ! panel.hidden );
					} );

					if ( tablist ) {
						tablist.setAttribute( 'data-active', name );
					}
				} );
			} );

			// Arrow-key navigation, expected for role="tablist".
			root.addEventListener( 'keydown', function ( e ) {
				if ( 'ArrowLeft' !== e.key && 'ArrowRight' !== e.key ) {
					return;
				}
				var active = root.querySelector( '[data-rptl-tab].is-active' );
				if ( ! active || document.activeElement !== active ) {
					return;
				}
				var list = Array.prototype.slice.call( tabs );
				var next = list[ list.indexOf( active ) + ( 'ArrowRight' === e.key ? 1 : -1 ) ];
				if ( next ) {
					next.focus();
					next.click();
				}
			} );
		} );
	}

	/* ---------------------------------------------------------------
	 * Rewire Tutor's own switch-form links to the tabs
	 *
	 * Tutor's login form ends with "Don't have an account? Register Now"
	 * and its signup form with "Already have an account? Login". Left
	 * alone those navigate away and drop the visitor out of the popup.
	 * ------------------------------------------------------------- */

	function initSwitchLinks() {
		document.querySelectorAll( '[data-rptl-auth]' ).forEach( function ( root ) {
			var loginTab = root.querySelector( '[data-rptl-tab="login"]' );
			var regTab = root.querySelector( '[data-rptl-tab="register"]' );
			if ( ! loginTab || ! regTab ) {
				return;
			}

			root.querySelectorAll( '[data-rptl-panel] a' ).forEach( function ( a ) {
				var text = ( a.textContent || '' ).toLowerCase();
				var inLogin = !! a.closest( '[data-rptl-panel="login"]' );
				var toRegister = inLogin && /register|sign\s*up|create/.test( text );
				var toLogin = ! inLogin && /login|log\s*in|sign\s*in/.test( text );

				if ( ! toRegister && ! toLogin ) {
					return;
				}

				a.addEventListener( 'click', function ( e ) {
					e.preventDefault();
					( toRegister ? regTab : loginTab ).click();
					var panel = root.querySelector( '[data-rptl-panel]:not([hidden])' );
					if ( panel ) {
						panel.scrollTop = 0;
					}
				} );
			} );
		} );
	}

	/* ---------------------------------------------------------------
	 * Give every field a visible title
	 *
	 * Tutor labels the fields it renders itself, but other plugins can
	 * inject extra fields into the registration form through the
	 * register_form hook, and those often arrive with no label at all.
	 * ------------------------------------------------------------- */

	function phoneLabels() {
		var phone = ( rptlData.i18n && rptlData.i18n.phoneLabel ) || 'Phone Number';
		var mobile = ( rptlData.i18n && rptlData.i18n.mobileLabel ) || 'Mobile Number';
		return {
			phone: phone,
			phone_number: phone,
			billing_phone: phone,
			user_phone: phone,
			tutor_profile_phone: phone,
			mobile: mobile
		};
	}

	function prettifyName( name ) {
		return name
			.replace( /[[\]]/g, ' ' )
			.replace( /[_-]+/g, ' ' )
			.trim()
			.replace( /\s+/g, ' ' )
			.replace( /\b\w/g, function ( c ) {
				return c.toUpperCase();
			} );
	}

	/**
	 * Whether a field already has a label of its own.
	 *
	 * Walks up while each wrapper still contains exactly one real input.
	 * Tutor nests its password fields one level deeper than the rest
	 * (.tutor-password-field > label + .tutor-input-field > input), so
	 * checking only the input's own wrapper misses that label and produces
	 * a duplicate. The single-input guard is what stops the walk at the
	 * form, which holds every field's label and would otherwise make every
	 * field look already labelled.
	 */
	function hasLabel( input ) {
		if ( input.id && document.querySelector( 'label[for="' + CSS.escape( input.id ) + '"]' ) ) {
			return true;
		}
		if ( input.closest( 'label' ) ) {
			return true;
		}

		var wrap = input.closest( '.tutor-input-field, .tutor-form-group, .rptl-field' ) || input.parentElement;

		for ( var i = 0; i < 4 && wrap; i++ ) {
			if ( 1 !== wrap.querySelectorAll( 'input:not([type=hidden])' ).length ) {
				return false;
			}
			if ( wrap.querySelector( 'label' ) ) {
				return true;
			}
			if ( 'FORM' === wrap.tagName || wrap.hasAttribute( 'data-rptl-panel' ) ) {
				return false;
			}
			wrap = wrap.parentElement;
		}

		return false;
	}

	function initFieldLabels() {
		var known = phoneLabels();

		document.querySelectorAll( '[data-rptl-panel]' ).forEach( function ( panel ) {
			panel.querySelectorAll( 'input' ).forEach( function ( input ) {
				var type = ( input.type || '' ).toLowerCase();

				if ( -1 !== [ 'hidden', 'submit', 'checkbox', 'radio', 'button' ].indexOf( type ) ) {
					return;
				}
				if ( input.getAttribute( 'data-rptl-labelled' ) || hasLabel( input ) ) {
					return;
				}

				var name = ( input.getAttribute( 'name' ) || '' ).toLowerCase();
				var text = known[ name ] ||
					input.getAttribute( 'aria-label' ) ||
					input.getAttribute( 'placeholder' ) ||
					( name ? prettifyName( name ) : '' );

				if ( ! text && 'tel' === type ) {
					text = known.phone;
				}
				if ( ! text ) {
					return;
				}

				if ( ! input.id ) {
					input.id = 'rptl-field-' + Math.random().toString( 36 ).slice( 2, 9 );
				}

				var label = document.createElement( 'label' );
				label.className = 'rptl-generated-label';
				label.setAttribute( 'for', input.id );
				label.textContent = text;

				var anchor = input.closest( '.tutor-input-field' ) || input;
				anchor.parentNode.insertBefore( label, anchor );

				// Mark the input, not its parent: several fields often share
				// one parent, and marking the parent would skip every field
				// after the first.
				input.setAttribute( 'data-rptl-labelled', '1' );

				if ( ! input.getAttribute( 'placeholder' ) ) {
					input.setAttribute( 'placeholder', text );
				}
			} );
		} );
	}

	/* ---------------------------------------------------------------
	 * Curriculum badges
	 *
	 * Matched against the real preview data computed server-side, by exact
	 * lesson URL. Matching on rendered text such as "Free" finds nothing on
	 * the many courses that never print that word.
	 * ------------------------------------------------------------- */

	function initCurriculumBadges() {
		if ( ! rptlData.showBadges ) {
			return;
		}

		var units = rptlData.courseUnits || [];
		if ( ! units.length ) {
			return;
		}

		// Flat lookup of every preview lesson URL on this course.
		// Badges are placed by finding the links themselves rather than by
		// locating unit containers first: container class names differ
		// between Tutor versions, themes and page builders, so guessing them
		// meant the badges silently did nothing on some perfectly normal
		// course layouts.
		var previewUrls = {};
		units.forEach( function ( unit ) {
			( unit.urls || [] ).forEach( function ( url ) {
				previewUrls[ stripHash( url ) ] = true;
			} );
		} );

		var badgeText = rptlLessonBadge() || 'Free Preview';
		var matched = [];

		document.querySelectorAll( 'a[href]' ).forEach( function ( a ) {
			if ( ! previewUrls[ stripHash( a.href ) ] ) {
				return;
			}
			if ( a.closest( '.rptl-auth, .rptl-overlay' ) ) {
				return; // never badge links inside our own markup
			}

			matched.push( a );

			var item = a.closest( 'li' ) || a.parentElement;
			if ( ! item || item.querySelector( '.rptl-preview-badge' ) ) {
				return;
			}

			var badge = document.createElement( 'span' );
			badge.className = 'rptl-preview-badge';
			badge.textContent = badgeText;

			// Sit the badge next to the lesson title text, falling back to
			// the end of the row when there is no recognisable title element.
			var title = item.querySelector( 'h2, h3, h4, h5, h6, .tutor-course-topic-item-title' );
			( title || item ).appendChild( badge );
		} );

		if ( ! matched.length ) {
			return;
		}

		addUnitBadges( matched );
	}

	function rptlLessonBadge() {
		return rptlData.i18n && rptlData.i18n.lessonBadge;
	}

	/**
	 * Add a free-lesson count to each curriculum section.
	 *
	 * The section is discovered from the matched links: walk up until an
	 * ancestor contains a heading, and treat that as the section. This works
	 * regardless of the markup Tutor or the theme produces, where matching on
	 * specific class names does not.
	 */
	function addUnitBadges( matchedLinks ) {
		var sections = [];
		var counts = [];
		var headingFor = new Map();

		matchedLinks.forEach( function ( a ) {
			var node = a.parentElement;
			var section = null;
			var sectionHeading = null;

			for ( var i = 0; i < 8 && node && node !== document.body; i++ ) {
				// A unit heading sits outside the lesson list, whereas lesson
				// rows are list items and are frequently headings themselves
				// (<li><h5><a>...). So require a heading that is neither
				// inside an <li> nor an ancestor of the link. Without both
				// conditions the count lands on a lesson title, or worse on a
				// neighbouring lesson's title.
				var headings = node.querySelectorAll( 'h2, h3, h4, h5, h6' );

				for ( var h = 0; h < headings.length; h++ ) {
					if ( headings[ h ].contains( a ) || headings[ h ].closest( 'li' ) ) {
						continue;
					}
					sectionHeading = headings[ h ];
					break;
				}

				if ( sectionHeading ) {
					section = node;
					break;
				}

				node = node.parentElement;
			}

			if ( ! section ) {
				return;
			}

			headingFor.set( section, sectionHeading );

			var idx = sections.indexOf( section );
			if ( -1 === idx ) {
				sections.push( section );
				counts.push( 1 );
			} else {
				counts[ idx ]++;
			}
		} );

		sections.forEach( function ( section, i ) {
			var heading = headingFor.get( section );
			if ( ! heading || heading.querySelector( '.rptl-unit-badge' ) ) {
				return;
			}

			var count = counts[ i ];
			var template = 1 === count ?
				( rptlData.i18n && rptlData.i18n.unitBadgeOne ) || '%d Free Lesson' :
				( rptlData.i18n && rptlData.i18n.unitBadgeMany ) || '%d Free Lessons';

			var badge = document.createElement( 'span' );
			badge.className = 'rptl-unit-badge';
			badge.textContent = sprintf( template, count );
			heading.appendChild( badge );
			section.classList.add( 'rptl-unit-has-preview' );
		} );
	}
}() );
