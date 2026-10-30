/**
 * Ultra-Smooth Canvas Drag-and-Drop for NEM SALE Gutenberg Editor
 * - Dragging the Entire Category Box (.nem-categories-box / .nem-new-sidebar):
 *   Grab the top blue bar, bottom bar, "Danh mục" title, or any border/padding of the sidebar.
 *   Moving the mouse Left <-> Right swaps the Danh mục column between Left and Right LIVE in real time!
 *   Moving the mouse Up <-> Down drops the entire Danh mục block above/below any section on the page!
 * - Dragging Individual Category Groups (.nem-new-category-group: Đầm, Áo, Quần...):
 *   Grab the top bar of the group to reorder groups smoothly.
 * - Dragging Individual Category Lines:
 *   Grab the left "⋮⋮" icon to reorder lines, while clicking on the text itself edits text natively.
 */
( function () {
	'use strict';

	function getWPData() {
		if ( window.wp && window.wp.data ) {
			return window.wp.data;
		}
		if ( window.parent && window.parent.wp && window.parent.wp.data ) {
			return window.parent.wp.data;
		}
				// 6. Auth Column (.nem-auth__col--login, .nem-auth__col--register)
		var authCol = findBlockByClass( targetEl, '.nem-auth__col' );
		if ( authCol ) {
			var authRect = authCol.getBoundingClientRect();
			var isLogin  = authCol.classList.contains( 'nem-auth__col--login' );
			if ( event.clientY - authRect.top <= 44 || targetEl === authCol ) {
				return {
					el: authCol,
					type: 'auth-column',
					label: isLogin ? '✥ Đang kéo cột: Đăng nhập (Trái ⇄ Phải)' : '✥ Đang kéo cột: Đăng ký (Trái ⇄ Phải)',
					immediate: true,
				};
			}
		}

		// 7. Auth Field (.nem-auth__field)
		var authField = findBlockByClass( targetEl, '.nem-auth__field' );
		if ( authField ) {
			var fieldRect = authField.getBoundingClientRect();
			if ( event.clientX - fieldRect.left <= 28 || event.clientY - fieldRect.top <= 20 ) {
				var labelEl  = authField.querySelector( '.nem-auth__label' );
				var labelTxt = labelEl ? labelEl.textContent.trim() : 'trường dữ liệu';
				return {
					el: authField,
					type: 'auth-field',
					label: '⋮⋮ Đang kéo trường: ' + labelTxt,
					immediate: true,
				};
			}
		}

		// 8. Auth Layout (.nem-auth-layout)
		var authLayout = findBlockByClass( targetEl, '.nem-auth-layout' );
		if ( authLayout ) {
			var layoutR = authLayout.getBoundingClientRect();
			if ( event.clientY - layoutR.top <= 34 || targetEl === authLayout ) {
				return {
					el: authLayout,
					type: 'auth-layout',
					label: '✥ Đang di chuyển: Cả khối Đăng nhập / Đăng ký',
					immediate: true,
				};
			}
		}

		return null;
	}

	function findBlockByClass( node, selector ) {
		if ( ! node || node.nodeType !== 1 ) {
			node = node ? node.parentElement : null;
		}
		if ( ! node || ! node.closest ) {
			return null;
		}
		var matched = node.closest( selector );
		if ( ! matched ) {
			return null;
		}
		return matched.closest( '[data-block]' ) || matched;
	}

	function getClientId( el ) {
		if ( ! el ) {
			return null;
		}
		var blockNode = el.closest ? el.closest( '[data-block]' ) : null;
		if ( blockNode ) {
			return blockNode.getAttribute( 'data-block' );
		}
		var idNode = el.closest ? el.closest( '[id^="block-"]' ) : null;
		if ( idNode ) {
			return idNode.id.replace( /^block-/, '' );
		}
				// 6. Auth Column (.nem-auth__col--login, .nem-auth__col--register)
		var authCol = findBlockByClass( targetEl, '.nem-auth__col' );
		if ( authCol ) {
			var authRect = authCol.getBoundingClientRect();
			var isLogin  = authCol.classList.contains( 'nem-auth__col--login' );
			if ( event.clientY - authRect.top <= 44 || targetEl === authCol ) {
				return {
					el: authCol,
					type: 'auth-column',
					label: isLogin ? '✥ Đang kéo cột: Đăng nhập (Trái ⇄ Phải)' : '✥ Đang kéo cột: Đăng ký (Trái ⇄ Phải)',
					immediate: true,
				};
			}
		}

		// 7. Auth Field (.nem-auth__field)
		var authField = findBlockByClass( targetEl, '.nem-auth__field' );
		if ( authField ) {
			var fieldRect = authField.getBoundingClientRect();
			if ( event.clientX - fieldRect.left <= 28 || event.clientY - fieldRect.top <= 20 ) {
				var labelEl  = authField.querySelector( '.nem-auth__label' );
				var labelTxt = labelEl ? labelEl.textContent.trim() : 'trường dữ liệu';
				return {
					el: authField,
					type: 'auth-field',
					label: '⋮⋮ Đang kéo trường: ' + labelTxt,
					immediate: true,
				};
			}
		}

		// 8. Auth Layout (.nem-auth-layout)
		var authLayout = findBlockByClass( targetEl, '.nem-auth-layout' );
		if ( authLayout ) {
			var layoutR = authLayout.getBoundingClientRect();
			if ( event.clientY - layoutR.top <= 34 || targetEl === authLayout ) {
				return {
					el: authLayout,
					type: 'auth-layout',
					label: '✥ Đang di chuyển: Cả khối Đăng nhập / Đăng ký',
					immediate: true,
				};
			}
		}

		return null;
	}

	function determineDraggableHandle( targetEl, event ) {
		if ( ! targetEl || targetEl.nodeType !== 1 ) {
			targetEl = targetEl ? targetEl.parentElement : null;
		}
		if ( ! targetEl || ! targetEl.closest ) {
			return null;
		}

		var catBox = findBlockByClass( targetEl, '.nem-categories-box' );

		// 1. Heading "Danh mục" (.nem-new-categories-title) -> drags the ENTIRE Category Box!
		var catTitle = targetEl.closest( '.nem-new-categories-title' );
		if ( catTitle && catBox ) {
			var titleRect = catTitle.getBoundingClientRect();
			var isTitleIcon = event.clientX - titleRect.left <= 28;
			return {
				el: catBox,
				type: 'categories-box',
				label: '✥ Đang di chuyển: Cả khối Danh mục',
				immediate: isTitleIcon,
			};
		}

		// 2. Individual category item ("Đầm", "Đầm suông", "Tất cả sản phẩm"...)
		var catItem = findBlockByClass(
			targetEl,
			'.nem-new-category-child, .nem-new-category-parent, .nem-new-category-all'
		);
		if ( catItem ) {
			var itemRect = catItem.getBoundingClientRect();
			// Left 26px "⋮⋮" handle drags the item (or parent group if shift/parent)
			if ( event.clientX - itemRect.left <= 26 ) {
				return {
					el: catItem,
					type: 'category-item',
					label: '⋮⋮ Đang kéo mục: ' + ( catItem.textContent || '' ).trim(),
					immediate: true,
				};
			}
			// Clicking on the text itself -> let native Gutenberg text editing work 100% smoothly!
			return null;
		}

		// 3. Category Group box (.nem-new-category-group: Đầm, Áo, Quần...)
		var catGroup = findBlockByClass( targetEl, '.nem-new-category-group' );
		if ( catGroup ) {
			var groupRect = catGroup.getBoundingClientRect();
			// Top 28px bar OR clicking directly on the group's padding/border
			if ( event.clientY - groupRect.top <= 28 || targetEl === catGroup ) {
				var groupTitleEl = catGroup.querySelector( '.nem-new-category-parent' );
				var groupName    = groupTitleEl ? groupTitleEl.textContent.trim() : 'Nhóm danh mục';
				return {
					el: catGroup,
					type: 'category-group',
					label: '⋮⋮ Đang kéo nhóm: ' + groupName,
					immediate: true,
				};
			}
		}

		// 4. Entire Category Box (.nem-categories-box) or Sidebar column (.nem-new-sidebar)
		// Matches top 38px bar, bottom 30px bar, left/right borders, or any empty space in the box/sidebar!
		if ( catBox ) {
			return {
				el: catBox,
				type: 'categories-box',
				label: '✥ Đang di chuyển: Cả khối Danh mục (Qua Trái / Phải / Lên / Xuống)',
				immediate: true,
			};
		}

		var sidebarCol = findBlockByClass( targetEl, '.nem-new-sidebar' );
		if ( sidebarCol && targetEl === sidebarCol ) {
			var innerBox = sidebarCol.querySelector( '.nem-categories-box[data-block]' ) || sidebarCol;
			return {
				el: innerBox,
				type: 'categories-box',
				label: '✥ Đang di chuyển: Cả khối Danh mục',
				immediate: true,
			};
		}

		// 5. Product Card top bar ("⋮⋮ Kéo đổi vị trí sản phẩm")
		var prodCol = findBlockByClass( targetEl, '.nem-collection-product' );
		if ( prodCol ) {
			var prodRect = prodCol.getBoundingClientRect();
			if ( event.clientY - prodRect.top <= 28 ) {
				return {
					el: prodCol,
					type: 'product-card',
					label: '⋮⋮ Đang kéo đổi vị trí Sản phẩm',
					immediate: true,
				};
			}
		}

				// 6. Auth Column (.nem-auth__col--login, .nem-auth__col--register)
		var authCol = findBlockByClass( targetEl, '.nem-auth__col' );
		if ( authCol ) {
			var authRect = authCol.getBoundingClientRect();
			var isLogin  = authCol.classList.contains( 'nem-auth__col--login' );
			if ( event.clientY - authRect.top <= 44 || targetEl === authCol ) {
				return {
					el: authCol,
					type: 'auth-column',
					label: isLogin ? '✥ Đang kéo cột: Đăng nhập (Trái ⇄ Phải)' : '✥ Đang kéo cột: Đăng ký (Trái ⇄ Phải)',
					immediate: true,
				};
			}
		}

		// 7. Auth Field (.nem-auth__field)
		var authField = findBlockByClass( targetEl, '.nem-auth__field' );
		if ( authField ) {
			var fieldRect = authField.getBoundingClientRect();
			if ( event.clientX - fieldRect.left <= 28 || event.clientY - fieldRect.top <= 20 ) {
				var labelEl  = authField.querySelector( '.nem-auth__label' );
				var labelTxt = labelEl ? labelEl.textContent.trim() : 'trường dữ liệu';
				return {
					el: authField,
					type: 'auth-field',
					label: '⋮⋮ Đang kéo trường: ' + labelTxt,
					immediate: true,
				};
			}
		}

		// 8. Auth Layout (.nem-auth-layout)
		var authLayout = findBlockByClass( targetEl, '.nem-auth-layout' );
		if ( authLayout ) {
			var layoutR = authLayout.getBoundingClientRect();
			if ( event.clientY - layoutR.top <= 34 || targetEl === authLayout ) {
				return {
					el: authLayout,
					type: 'auth-layout',
					label: '✥ Đang di chuyển: Cả khối Đăng nhập / Đăng ký',
					immediate: true,
				};
			}
		}

		return null;
	}

	function clearDragHighlights( doc ) {
		var highlighted = doc.querySelectorAll(
			'.nem-dragging, .nem-drag-over-top, .nem-drag-over-bottom, .nem-drag-over-swap'
		);
		for ( var i = 0; i < highlighted.length; i++ ) {
			highlighted[ i ].classList.remove(
				'nem-dragging',
				'nem-drag-over-top',
				'nem-drag-over-bottom',
				'nem-drag-over-swap'
			);
		}
	}

	/**
	 * Live Left <-> Right swapping of Sidebar (.nem-new-sidebar) and Catalog (.nem-new-catalog)
	 * while the user is dragging the Category Box horizontally across the 2-column layout!
	 */
	function tryLiveSwapSidebarHorizontal( doc, clientX, clientY, dragState ) {
		var wpData = getWPData();
		if ( ! wpData ) {
			return false;
		}
		var layoutEl   = doc.querySelector( '.nem-new-layout[data-block]' );
		var sidebarCol = doc.querySelector( '.nem-new-sidebar[data-block]' );
		var catalogCol = doc.querySelector( '.nem-new-catalog[data-block]' );
		if ( ! layoutEl || ! sidebarCol || ! catalogCol ) {
			return false;
		}

		var layoutRect = layoutEl.getBoundingClientRect();
		// Only live-swap left/right when cursor is vertically within the 2-column layout region
		if ( clientY < layoutRect.top - 30 || clientY > layoutRect.bottom + 30 ) {
			return false;
		}

		var select   = wpData.select( 'core/block-editor' );
		var dispatch = wpData.dispatch( 'core/block-editor' );
		var sideId   = getClientId( sidebarCol );
		var catId    = getClientId( catalogCol );
		var boxId    = getClientId( dragState.el );
		if ( ! sideId || ! catId || ! boxId ) {
			return false;
		}

		// If .nem-categories-box was previously moved outside .nem-new-sidebar, put it back inside .nem-new-sidebar first
		var boxRoot = select.getBlockRootClientId( boxId ) || '';
		if ( boxRoot !== sideId && dragState.el.classList.contains( 'nem-categories-box' ) ) {
			dispatch.moveBlocksToPosition( [ boxId ], boxRoot, sideId, 0 );
		}

		var rootId  = select.getBlockRootClientId( sideId ) || '';
		var idxSide = select.getBlockIndex( sideId );
		var idxCat  = select.getBlockIndex( catId );
		if ( idxSide === -1 || idxCat === -1 ) {
			return false;
		}

		var midX = layoutRect.left + layoutRect.width / 2;

		// If sidebar is currently on the Left (idxSide < idxCat) and cursor moves to the Right side
		if ( idxSide < idxCat && clientX > midX + 40 ) {
			dispatch.moveBlocksToPosition( [ sideId ], rootId, rootId, idxCat );
			return true;
		}

		// If sidebar is currently on the Right (idxSide > idxCat) and cursor moves to the Left side
		if ( idxSide > idxCat && clientX < midX - 40 ) {
			dispatch.moveBlocksToPosition( [ sideId ], rootId, rootId, 0 );
			return true;
		}

		return true;
	}

	function tryLiveSwapAuthHorizontal( doc, clientX, clientY, dragState ) {
		var wpData = getWPData();
		if ( ! wpData ) {
			return false;
		}
		var layoutEl = doc.querySelector( '.nem-auth-layout[data-block]' );
		var loginCol = doc.querySelector( '.nem-auth__col--login[data-block]' );
		var regCol   = doc.querySelector( '.nem-auth__col--register[data-block]' );
		if ( ! layoutEl || ! loginCol || ! regCol ) {
			return false;
		}

		var layoutRect = layoutEl.getBoundingClientRect();
		if ( clientY < layoutRect.top - 40 || clientY > layoutRect.bottom + 40 ) {
			return false;
		}

		var select   = wpData.select( 'core/block-editor' );
		var dispatch = wpData.dispatch( 'core/block-editor' );
		var loginId  = getClientId( loginCol );
		var regId    = getClientId( regCol );
		if ( ! loginId || ! regId ) {
			return false;
		}

		var rootId   = select.getBlockRootClientId( loginId ) || '';
		var idxLogin = select.getBlockIndex( loginId );
		var idxReg   = select.getBlockIndex( regId );
		if ( idxLogin === -1 || idxReg === -1 ) {
			return false;
		}

		var midX = layoutRect.left + layoutRect.width / 2;

		if ( dragState.el === loginCol ) {
			if ( idxLogin < idxReg && clientX > midX + 30 ) {
				dispatch.moveBlocksToPosition( [ loginId ], rootId, rootId, idxReg );
				return true;
			}
			if ( idxLogin > idxReg && clientX < midX - 30 ) {
				dispatch.moveBlocksToPosition( [ loginId ], rootId, rootId, 0 );
				return true;
			}
		}

		if ( dragState.el === regCol ) {
			if ( idxReg < idxLogin && clientX > midX + 30 ) {
				dispatch.moveBlocksToPosition( [ regId ], rootId, rootId, idxLogin );
				return true;
			}
			if ( idxReg > idxLogin && clientX < midX - 30 ) {
				dispatch.moveBlocksToPosition( [ regId ], rootId, rootId, 0 );
				return true;
			}
		}

		return true;
	}

	function findDropTarget( doc, clientX, clientY, dragState ) {
		var hoverEl = doc.elementFromPoint( clientX, clientY );
		if ( ! hoverEl ) {
			return null;
		}

		var sourceEl = dragState.el;
		var type     = dragState.type;

		if ( type === 'auth-column' ) {
			var handledSwap = tryLiveSwapAuthHorizontal( doc, clientX, clientY, dragState );
			if ( handledSwap ) {
				return null;
			}
			var targetCol = findBlockByClass( hoverEl, '.nem-auth__col' );
			if ( targetCol && targetCol !== sourceEl ) {
				return { el: targetCol, position: 'swap' };
			}
			return null;
		}

		if ( type === 'auth-field' ) {
			var targetField = findBlockByClass( hoverEl, '.nem-auth__field' );
			if ( targetField && targetField !== sourceEl ) {
				var fRect = targetField.getBoundingClientRect();
				var fPos  = clientY < fRect.top + fRect.height / 2 ? 'top' : 'bottom';
				return { el: targetField, position: fPos };
			}
			return null;
		}

		if ( type === 'product-card' ) {
			var targetProd = findBlockByClass( hoverEl, '.nem-collection-product' );
			if ( targetProd && targetProd !== sourceEl ) {
				return { el: targetProd, position: 'swap' };
			}
			return null;
		}

		if ( type === 'category-group' ) {
			var targetGroup = findBlockByClass( hoverEl, '.nem-new-category-group' );
			if ( targetGroup && targetGroup !== sourceEl ) {
				var rect = targetGroup.getBoundingClientRect();
				var pos  = clientY < rect.top + rect.height / 2 ? 'top' : 'bottom';
				return { el: targetGroup, position: pos };
			}
			var targetAll = findBlockByClass( hoverEl, '.nem-new-category-all, .nem-new-categories-title' );
			if ( targetAll && targetAll !== sourceEl ) {
				return { el: targetAll, position: 'bottom' };
			}
			var allGroups = doc.querySelectorAll( '.nem-new-category-group[data-block]' );
			var bestGroup = null;
			var bestDist  = Infinity;
			for ( var i = 0; i < allGroups.length; i++ ) {
				var g = allGroups[ i ];
				if ( g === sourceEl ) {
					continue;
				}
				var r   = g.getBoundingClientRect();
				var mid = r.top + r.height / 2;
				var d   = Math.abs( clientY - mid );
				if ( d < bestDist && d < 180 ) {
					bestDist  = d;
					bestGroup = g;
				}
			}
			if ( bestGroup ) {
				var bRect = bestGroup.getBoundingClientRect();
				return {
					el: bestGroup,
					position: clientY < bRect.top + bRect.height / 2 ? 'top' : 'bottom',
				};
			}
			return null;
		}

		if ( type === 'category-item' ) {
			var targetItem = findBlockByClass(
				hoverEl,
				'.nem-new-category-child, .nem-new-category-parent, .nem-new-category-all, .nem-new-categories-title'
			);
			if ( targetItem && targetItem !== sourceEl ) {
				var iRect = targetItem.getBoundingClientRect();
				var iPos  = clientY < iRect.top + iRect.height / 2 ? 'top' : 'bottom';
				if ( targetItem.classList.contains( 'nem-new-category-parent' ) ) {
					iPos = 'bottom';
				}
				return { el: targetItem, position: iPos };
			}
			var intoGroup = findBlockByClass( hoverEl, '.nem-new-category-group' );
			if ( intoGroup && ! intoGroup.contains( sourceEl ) ) {
				return { el: intoGroup, position: 'inside' };
			}
			return null;
		}

		if ( type === 'categories-box' ) {
			// First, check if cursor is inside the 2-column layout -> live swap Left <-> Right!
			var handledHorizontal = tryLiveSwapSidebarHorizontal( doc, clientX, clientY, dragState );
			if ( handledHorizontal ) {
				return null;
			}

			// Otherwise, allow dropping the entire Category Box above/below any major section on the page
			var majorSection = findBlockByClass(
				hoverEl,
				'.nem-new-banner, .nem-new-layout, .nem-collection-newsletter, .nem-footer-wrapper, .nem-new-breadcrumb'
			);
			if ( majorSection && majorSection !== sourceEl && ! majorSection.contains( sourceEl ) ) {
				var mRect = majorSection.getBoundingClientRect();
				var mPos  = clientY < mRect.top + mRect.height / 2 ? 'top' : 'bottom';
				return { el: majorSection, position: mPos };
			}
			return null;
		}

				// 6. Auth Column (.nem-auth__col--login, .nem-auth__col--register)
		var authCol = findBlockByClass( targetEl, '.nem-auth__col' );
		if ( authCol ) {
			var authRect = authCol.getBoundingClientRect();
			var isLogin  = authCol.classList.contains( 'nem-auth__col--login' );
			if ( event.clientY - authRect.top <= 44 || targetEl === authCol ) {
				return {
					el: authCol,
					type: 'auth-column',
					label: isLogin ? '✥ Đang kéo cột: Đăng nhập (Trái ⇄ Phải)' : '✥ Đang kéo cột: Đăng ký (Trái ⇄ Phải)',
					immediate: true,
				};
			}
		}

		// 7. Auth Field (.nem-auth__field)
		var authField = findBlockByClass( targetEl, '.nem-auth__field' );
		if ( authField ) {
			var fieldRect = authField.getBoundingClientRect();
			if ( event.clientX - fieldRect.left <= 28 || event.clientY - fieldRect.top <= 20 ) {
				var labelEl  = authField.querySelector( '.nem-auth__label' );
				var labelTxt = labelEl ? labelEl.textContent.trim() : 'trường dữ liệu';
				return {
					el: authField,
					type: 'auth-field',
					label: '⋮⋮ Đang kéo trường: ' + labelTxt,
					immediate: true,
				};
			}
		}

		// 8. Auth Layout (.nem-auth-layout)
		var authLayout = findBlockByClass( targetEl, '.nem-auth-layout' );
		if ( authLayout ) {
			var layoutR = authLayout.getBoundingClientRect();
			if ( event.clientY - layoutR.top <= 34 || targetEl === authLayout ) {
				return {
					el: authLayout,
					type: 'auth-layout',
					label: '✥ Đang di chuyển: Cả khối Đăng nhập / Đăng ký',
					immediate: true,
				};
			}
		}

		return null;
	}

	function executeDrop( dragState, dropTarget ) {
		var wpData = getWPData();
		if ( ! wpData || ! dropTarget || ! dropTarget.el ) {
			return;
		}

		var select   = wpData.select( 'core/block-editor' );
		var dispatch = wpData.dispatch( 'core/block-editor' );
		if ( ! select || ! dispatch ) {
			return;
		}

		var sourceId = getClientId( dragState.el );
		var targetId = getClientId( dropTarget.el );
		if ( ! sourceId || ! targetId || sourceId === targetId ) {
			return;
		}

		var fromRoot  = select.getBlockRootClientId( sourceId ) || '';
		var fromIndex = select.getBlockIndex( sourceId );

		if ( dropTarget.position === 'inside' ) {
			var order = select.getBlockOrder( targetId );
			dispatch.moveBlocksToPosition( [ sourceId ], fromRoot, targetId, order ? order.length : 0 );
			dispatch.selectBlock( sourceId );
			return;
		}

		var toRoot  = select.getBlockRootClientId( targetId ) || '';
		var toIndex = select.getBlockIndex( targetId );

		if ( fromIndex === -1 || toIndex === -1 ) {
			return;
		}

		if ( dragState.type === 'product-card' && dropTarget.position === 'swap' ) {
			if ( fromRoot === toRoot ) {
				dispatch.moveBlocksToPosition( [ sourceId ], fromRoot, toRoot, toIndex );
			} else {
				dispatch.moveBlocksToPosition( [ sourceId ], fromRoot, toRoot, toIndex );
				dispatch.moveBlocksToPosition( [ targetId ], toRoot, fromRoot, fromIndex );
			}
			dispatch.selectBlock( sourceId );
			return;
		}

		var insertIndex = toIndex;
		if ( fromRoot === toRoot ) {
			if ( fromIndex < toIndex ) {
				insertIndex = dropTarget.position === 'bottom' ? toIndex : toIndex - 1;
			} else {
				insertIndex = dropTarget.position === 'bottom' ? toIndex + 1 : toIndex;
			}
		} else {
			insertIndex = dropTarget.position === 'bottom' ? toIndex + 1 : toIndex;
		}

		if ( insertIndex < 0 ) {
			insertIndex = 0;
		}

		dispatch.moveBlocksToPosition( [ sourceId ], fromRoot, toRoot, insertIndex );
		dispatch.selectBlock( sourceId );
	}

	function createGhostElement( doc, label ) {
		var ghost = doc.createElement( 'div' );
		ghost.className   = 'nem-floating-drag-ghost';
		ghost.textContent = label || '✥ Đang di chuyển...';
		doc.body.appendChild( ghost );
		return ghost;
	}

	function attachToDocument( doc ) {
		if ( ! doc || doc.__nemSaleDragV4Attached ) {
			return;
		}
		doc.__nemSaleDragV4Attached = true;

		var dragState       = null;
		var currentDropInfo = null;
		var ghostEl         = null;

		doc.addEventListener(
			'dragstart',
			function ( e ) {
				if ( dragState ) {
					e.preventDefault();
					e.stopPropagation();
				}
			},
			true
		);

		doc.addEventListener(
			'mousedown',
			function ( e ) {
				if ( e.button !== 0 ) {
					return;
				}
				var candidate = determineDraggableHandle( e.target, e );
				if ( ! candidate || ! candidate.el ) {
					return;
				}

				dragState = {
					el: candidate.el,
					type: candidate.type,
					label: candidate.label,
					immediate: candidate.immediate,
					startX: e.clientX,
					startY: e.clientY,
					isDragging: false,
				};

				if ( candidate.immediate ) {
					e.preventDefault();
					var wpData   = getWPData();
					var clientId = getClientId( candidate.el );
					if ( wpData && clientId ) {
						wpData.dispatch( 'core/block-editor' ).selectBlock( clientId );
					}
				}
			},
			true
		);

		doc.addEventListener(
			'mousemove',
			function ( e ) {
				if ( ! dragState ) {
					return;
				}

				var dx = e.clientX - dragState.startX;
				var dy = e.clientY - dragState.startY;

				if ( ! dragState.isDragging ) {
					var threshold = dragState.immediate ? 3 : 6;
					if ( Math.hypot( dx, dy ) < threshold ) {
						return;
					}
					dragState.isDragging = true;
					if ( doc.getSelection ) {
						var sel = doc.getSelection();
						if ( sel && sel.removeAllRanges ) {
							sel.removeAllRanges();
						}
					}
					ghostEl = createGhostElement( doc, dragState.label );
				}

				e.preventDefault();
				clearDragHighlights( doc );

				if ( dragState.el ) {
					dragState.el.classList.add( 'nem-dragging' );
				}

				if ( ghostEl ) {
					ghostEl.style.left = ( e.clientX + 14 ) + 'px';
					ghostEl.style.top  = ( e.clientY + 14 ) + 'px';
				}

				currentDropInfo = findDropTarget( doc, e.clientX, e.clientY, dragState );
				if ( currentDropInfo && currentDropInfo.el ) {
					if ( currentDropInfo.position === 'top' ) {
						currentDropInfo.el.classList.add( 'nem-drag-over-top' );
					} else if ( currentDropInfo.position === 'bottom' ) {
						currentDropInfo.el.classList.add( 'nem-drag-over-bottom' );
					} else {
						currentDropInfo.el.classList.add( 'nem-drag-over-swap' );
					}
				}
			},
			true
		);

		var finishDrag = function ( e ) {
			if ( ! dragState ) {
				return;
			}
			var wasDragging = dragState.isDragging;
			var stateCopy   = dragState;
			var dropCopy    = currentDropInfo;

			dragState       = null;
			currentDropInfo = null;

			if ( ghostEl && ghostEl.parentNode ) {
				ghostEl.parentNode.removeChild( ghostEl );
			}
			ghostEl = null;

			clearDragHighlights( doc );

			if ( wasDragging && dropCopy ) {
				if ( e ) {
					e.preventDefault();
					e.stopPropagation();
				}
				executeDrop( stateCopy, dropCopy );
			}
		};

		doc.addEventListener( 'mouseup', finishDrag, true );
		if ( doc.defaultView ) {
			doc.defaultView.addEventListener( 'mouseup', finishDrag, true );
		}
	}

	function scanAndAttach() {
		attachToDocument( document );
		var iframes = document.querySelectorAll( 'iframe[name="editor-canvas"], iframe.edit-site-visual-editor__editor-canvas, iframe' );
		for ( var i = 0; i < iframes.length; i++ ) {
			try {
				if ( iframes[ i ].contentDocument && iframes[ i ].contentDocument.body ) {
					attachToDocument( iframes[ i ].contentDocument );
				}
			} catch ( err ) {}
		}
	}

	scanAndAttach();
	setInterval( scanAndAttach, 400 );
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', scanAndAttach );
	}
} )();
