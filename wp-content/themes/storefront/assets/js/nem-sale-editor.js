/**
 * Ultra-Smooth Canvas Drag-and-Drop for NEM Gutenberg Editor (SALE & PLUS)
 * - Dragging the Entire Category Box (.nem-categories-box / .nem-new-sidebar):
 *   Grab the top blue bar, bottom bar, "Danh mục" title, or any border/padding of the sidebar.
 *   Moving Left <-> Right swaps the Danh mục column between Left and Right LIVE in real time!
 *   Moving Up <-> Down drops the entire Danh mục block above/below any section on the page!
 * - Dragging Category Groups (.nem-new-category-group: Đầm, Áo, Quần...):
 *   Grab the top "⋮⋮ Kéo cả nhóm" bar or the parent title ("Đầm", "Áo") to drag the whole group
 *   anywhere — inside the sidebar, over to the right side (catalog), or anywhere on the page!
 * - Dragging Individual Category Items (.nem-new-category-child, .nem-new-category-all...):
 *   Grab any element (Đầm suông, Đầm dáng A, Tất cả sản phẩm...) to drop it wherever you want —
 *   inside groups, across groups, into the right side catalog, between products, or on any page section!
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
		return null;
	}

	function getClosestBlockElement( node ) {
		if ( ! node || node.nodeType !== 1 ) {
			node = node ? node.parentElement : null;
		}
		if ( ! node || ! node.closest ) {
			return null;
		}
		return node.closest( '[data-block], [id^="block-"]' );
	}

	function getClientId( el ) {
		if ( ! el ) {
			return null;
		}
		var blockEl = getClosestBlockElement( el );
		if ( blockEl ) {
			var dataBlock = blockEl.getAttribute( 'data-block' );
			if ( dataBlock ) {
				return dataBlock;
			}
			if ( blockEl.id && blockEl.id.indexOf( 'block-' ) === 0 ) {
				return blockEl.id.replace( /^block-/, '' );
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
		var blockEl = getClosestBlockElement( matched );
		return blockEl || matched;
	}

	/**
	 * Strictly find an individual category item block element without jumping up to its parent group container.
	 */
	function findCategoryItemBlock( node ) {
		if ( ! node || node.nodeType !== 1 ) {
			node = node ? node.parentElement : null;
		}
		if ( ! node || ! node.closest ) {
			return null;
		}
		var matched = node.closest(
			'.nem-new-category-child, .nem-new-category-parent, .nem-new-category-all, [class*="nem-cat-slug-"]'
		);
		if ( ! matched ) {
			return null;
		}
		if ( matched.hasAttribute( 'data-block' ) || ( matched.id && matched.id.indexOf( 'block-' ) === 0 ) ) {
			return matched;
		}
		var curr = matched.parentElement;
		while ( curr && ! curr.classList.contains( 'nem-new-category-group' ) && ! curr.classList.contains( 'nem-categories-box' ) ) {
			if ( curr.hasAttribute( 'data-block' ) || ( curr.id && curr.id.indexOf( 'block-' ) === 0 ) ) {
				return curr;
			}
			curr = curr.parentElement;
		}
		return matched;
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
			return {
				el: catBox,
				type: 'categories-box',
				label: '✥ Đang di chuyển: Cả khối Danh mục',
				immediate: true,
			};
		}

		// 2. Parent item "Đầm", "Áo" (.nem-new-category-parent) -> drags the whole category group!
		// When the user drags "Đầm", they want to move the entire group "Đầm"
		var catParent = targetEl.closest( '.nem-new-category-parent' );
		var catGroup  = findBlockByClass( targetEl, '.nem-new-category-group' );
		if ( catParent && catGroup ) {
			var groupTitleEl = catGroup.querySelector( '.nem-new-category-parent' );
			var groupName = groupTitleEl ? groupTitleEl.textContent.trim() : 'Nhóm danh mục';
			return {
				el: catGroup,
				type: 'category-group',
				label: '⋮⋮ Đang kéo nhóm: ' + groupName + ' (toàn bộ nhóm)',
				immediate: true,
			};
		}

		// 3. Category Group top bar / handle / border ("⋮⋮ Kéo cả nhóm")
		if ( catGroup ) {
			var groupRect = catGroup.getBoundingClientRect();
			var isGroupTopBar = ( event.clientY - groupRect.top <= 28 );
			var isGroupBorder = ( targetEl === catGroup );
			var isGroupLeftHandle = ( event.clientX - groupRect.left <= 24 && event.clientX - groupRect.left >= -35 && event.clientY - groupRect.top <= 40 );

			if ( isGroupTopBar || isGroupBorder || isGroupLeftHandle ) {
				var gTitleEl = catGroup.querySelector( '.nem-new-category-parent' );
				var gName = gTitleEl ? gTitleEl.textContent.trim() : 'Nhóm danh mục';
				return {
					el: catGroup,
					type: 'category-group',
					label: '⋮⋮ Đang kéo nhóm: ' + gName + ' (toàn bộ nhóm)',
					immediate: true,
				};
			}
		}

		// 4. Individual category item ("Đầm suông", "Đầm dáng A", "Tất cả sản phẩm"...)
		var catItem = findCategoryItemBlock( targetEl );
		if ( catItem ) {
			var itemName = ( catItem.textContent || '' ).trim();
			if ( itemName.length > 25 ) {
				itemName = itemName.substring( 0, 25 ) + '...';
			}

			return {
				el: catItem,
				type: 'category-item',
				label: '⋮⋮ Đang kéo phần tử: ' + ( itemName || 'Phần tử danh mục' ),
				immediate: true,
			};
		}

		// 5. Entire Category Box (.nem-categories-box) or Sidebar column (.nem-new-sidebar)
		// Matches top 38px bar, bottom 30px bar, or empty space
		if ( catBox ) {
			var boxRect = catBox.getBoundingClientRect();
			var isBoxBar = ( event.clientY - boxRect.top <= 38 ) || ( boxRect.bottom - event.clientY <= 32 ) || ( targetEl === catBox );
			if ( isBoxBar ) {
				return {
					el: catBox,
					type: 'categories-box',
					label: '✥ Đang di chuyển: Cả khối Danh mục (Qua Trái / Phải / Lên / Xuống)',
					immediate: true,
				};
			}
		}

		var sidebarCol = findBlockByClass( targetEl, '.nem-new-sidebar' );
		if ( sidebarCol && targetEl === sidebarCol ) {
			var innerBox = sidebarCol.querySelector( '.nem-categories-box' ) || sidebarCol;
			return {
				el: innerBox,
				type: 'categories-box',
				label: '✥ Đang di chuyển: Cả khối Danh mục',
				immediate: true,
			};
		}

		// 6. Product Card top bar ("⋮⋮ Kéo đổi vị trí sản phẩm")
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
		var layoutEl   = doc.querySelector( '.nem-new-layout[data-block]' ) || doc.querySelector( '.nem-new-layout' );
		var sidebarCol = doc.querySelector( '.nem-new-sidebar[data-block]' ) || doc.querySelector( '.nem-new-sidebar' );
		var catalogCol = doc.querySelector( '.nem-new-catalog[data-block]' ) || doc.querySelector( '.nem-new-catalog' );
		if ( ! layoutEl || ! sidebarCol || ! catalogCol ) {
			return false;
		}

		var layoutRect = layoutEl.getBoundingClientRect();
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

		if ( idxSide < idxCat && clientX > midX + 40 ) {
			dispatch.moveBlocksToPosition( [ sideId ], rootId, rootId, idxCat );
			return true;
		}

		if ( idxSide > idxCat && clientX < midX - 40 ) {
			dispatch.moveBlocksToPosition( [ sideId ], rootId, rootId, 0 );
			return true;
		}

		return true;
	}

	/**
	 * Universal Drop Target Finder:
	 * Supports dropping anywhere: inside category groups, in the sidebar,
	 * over to the right side (catalog column, toolbar, grid, product rows, products),
	 * or around major page sections!
	 */
	function findDropTarget( doc, clientX, clientY, dragState ) {
		var hoverEl = doc.elementFromPoint( clientX, clientY );
		if ( ! hoverEl ) {
			return null;
		}

		var sourceEl = dragState.el;
		var type     = dragState.type;

		// 1. Product card swap
		if ( type === 'product-card' ) {
			var targetProd = findBlockByClass( hoverEl, '.nem-collection-product' );
			if ( targetProd && targetProd !== sourceEl ) {
				return { el: targetProd, position: 'swap' };
			}
			return null;
		}

		// 2. Entire Category Box
		if ( type === 'categories-box' ) {
			var handledHorizontal = tryLiveSwapSidebarHorizontal( doc, clientX, clientY, dragState );
			if ( handledHorizontal ) {
				return null;
			}
			var majorSec = findBlockByClass(
				hoverEl,
				'.nem-new-banner, .nem-new-layout, .nem-collection-newsletter, .nem-footer-wrapper, .nem-new-breadcrumb'
			);
			if ( majorSec && majorSec !== sourceEl && ! majorSec.contains( sourceEl ) ) {
				var msRect = majorSec.getBoundingClientRect();
				var msPos  = clientY < ( msRect.top + msRect.height / 2 ) ? 'top' : 'bottom';
				return { el: majorSec, position: msPos };
			}
			return null;
		}

		// 3. For BOTH 'category-group' and 'category-item': UNIVERSAL DROP EVERYWHERE!

		// Target A: An individual category item
		var targetItem = findCategoryItemBlock( hoverEl );
		if ( targetItem && targetItem !== sourceEl && ! sourceEl.contains( targetItem ) ) {
			var iRect = targetItem.getBoundingClientRect();
			var iPos  = clientY < ( iRect.top + iRect.height / 2 ) ? 'top' : 'bottom';
			return { el: targetItem, position: iPos };
		}

		// Target B: A category group container
		var targetGrp = hoverEl.closest ? hoverEl.closest( '.nem-new-category-group' ) : null;
		if ( targetGrp && targetGrp !== sourceEl && ! sourceEl.contains( targetGrp ) ) {
			var gBlock = findBlockByClass( targetGrp, '.nem-new-category-group' ) || targetGrp;
			var gRect  = gBlock.getBoundingClientRect();
			if ( type === 'category-item' ) {
				if ( clientY < gRect.top + 14 ) {
					return { el: gBlock, position: 'top' };
				}
				if ( clientY > gRect.bottom - 14 ) {
					return { el: gBlock, position: 'bottom' };
				}
				return { el: gBlock, position: 'inside' };
			}
			var gPos = clientY < ( gRect.top + gRect.height / 2 ) ? 'top' : 'bottom';
			return { el: gBlock, position: gPos };
		}

		// Target C: Title "Danh mục"
		var catTitleEl = hoverEl.closest ? hoverEl.closest( '.nem-new-categories-title' ) : null;
		if ( catTitleEl && ! sourceEl.contains( catTitleEl ) ) {
			return { el: catTitleEl, position: 'bottom' };
		}

		// Target D: ON THE RIGHT SIDE (Catalog, Products, Toolbar, Grid)
		// D1: A product card
		var hoverProd = findBlockByClass( hoverEl, '.nem-collection-product' );
		if ( hoverProd && hoverProd !== sourceEl && ! sourceEl.contains( hoverProd ) ) {
			var hpRect = hoverProd.getBoundingClientRect();
			var hpPos  = ( clientX < hpRect.left + hpRect.width / 2 ) ? 'top' : 'bottom';
			return { el: hoverProd, position: hpPos };
		}

		// D2: A product row
		var hoverRow = findBlockByClass(
			hoverEl,
			'.nem-new-products-row, .nem-sale-products-row, .nem-plus-products-row'
		);
		if ( hoverRow && hoverRow !== sourceEl && ! sourceEl.contains( hoverRow ) ) {
			var hrRect = hoverRow.getBoundingClientRect();
			var hrPos  = clientY < ( hrRect.top + hrRect.height / 2 ) ? 'top' : 'bottom';
			return { el: hoverRow, position: hrPos };
		}

		// D3: Products grid
		var hoverGrid = findBlockByClass( hoverEl, '.nem-new-products-grid' );
		if ( hoverGrid && hoverGrid !== sourceEl && ! sourceEl.contains( hoverGrid ) ) {
			var hgRect = hoverGrid.getBoundingClientRect();
			var hgPos  = clientY < ( hgRect.top + hgRect.height / 2 ) ? 'top' : 'bottom';
			return { el: hoverGrid, position: hgPos };
		}

		// D4: Toolbar in catalog
		var hoverToolbar = findBlockByClass( hoverEl, '.nem-new-toolbar' );
		if ( hoverToolbar && hoverToolbar !== sourceEl && ! sourceEl.contains( hoverToolbar ) ) {
			var htRect = hoverToolbar.getBoundingClientRect();
			var htPos  = clientY < ( htRect.top + htRect.height / 2 ) ? 'top' : 'bottom';
			return { el: hoverToolbar, position: htPos };
		}

		// D5: Pagination
		var hoverPagination = findBlockByClass( hoverEl, '.nem-new-pagination' );
		if ( hoverPagination && hoverPagination !== sourceEl && ! sourceEl.contains( hoverPagination ) ) {
			var hpgRect = hoverPagination.getBoundingClientRect();
			var hpgPos  = clientY < ( hpgRect.top + hpgRect.height / 2 ) ? 'top' : 'bottom';
			return { el: hoverPagination, position: hpgPos };
		}

		// D6: Catalog column container (.nem-new-catalog)
		var hoverCatalog = findBlockByClass( hoverEl, '.nem-new-catalog' );
		if ( hoverCatalog && hoverCatalog !== sourceEl && ! sourceEl.contains( hoverCatalog ) ) {
			var hcRect = hoverCatalog.getBoundingClientRect();
			if ( clientY < hcRect.top + 60 ) {
				return { el: hoverCatalog, position: 'top' };
			}
			if ( clientY > hcRect.bottom - 60 ) {
				return { el: hoverCatalog, position: 'bottom' };
			}
			return { el: hoverCatalog, position: 'inside' };
		}

		// Target E: Major page sections (Banner, Newsletter, Footer, Layout, Breadcrumb)
		var hoverMajor = findBlockByClass(
			hoverEl,
			'.nem-new-banner, .nem-new-breadcrumb, .nem-collection-newsletter, .nem-footer-wrapper, .nem-new-layout'
		);
		if ( hoverMajor && hoverMajor !== sourceEl && ! hoverMajor.contains( sourceEl ) ) {
			var hmRect = hoverMajor.getBoundingClientRect();
			var hmPos  = clientY < ( hmRect.top + hmRect.height / 2 ) ? 'top' : 'bottom';
			return { el: hoverMajor, position: hmPos };
		}

		// Target F: Any generic block nearby
		var anyBlock = getClosestBlockElement( hoverEl );
		if ( anyBlock && anyBlock !== sourceEl && ! sourceEl.contains( anyBlock ) ) {
			var abRect = anyBlock.getBoundingClientRect();
			var abPos  = clientY < ( abRect.top + abRect.height / 2 ) ? 'top' : 'bottom';
			return { el: anyBlock, position: abPos };
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

		// Case 1: Drop 'inside' a container (e.g. into .nem-new-category-group or .nem-new-catalog)
		if ( dropTarget.position === 'inside' ) {
			var order = select.getBlockOrder( targetId );
			var targetIndex = order ? order.length : 0;
			dispatch.moveBlocksToPosition( [ sourceId ], fromRoot, targetId, targetIndex );
			dispatch.selectBlock( sourceId );
			return;
		}

		// Case 2: Product card swap
		if ( dragState.type === 'product-card' && dropTarget.position === 'swap' ) {
			var toRootSwap  = select.getBlockRootClientId( targetId ) || '';
			var toIndexSwap = select.getBlockIndex( targetId );
			if ( fromRoot === toRootSwap ) {
				dispatch.moveBlocksToPosition( [ sourceId ], fromRoot, toRootSwap, toIndexSwap );
			} else {
				dispatch.moveBlocksToPosition( [ sourceId ], fromRoot, toRootSwap, toIndexSwap );
				dispatch.moveBlocksToPosition( [ targetId ], toRootSwap, fromRoot, fromIndex );
			}
			dispatch.selectBlock( sourceId );
			return;
		}

		// Case 3: Drop 'top' (before) or 'bottom' (after) targetId
		var toRoot  = select.getBlockRootClientId( targetId ) || '';
		var toIndex = select.getBlockIndex( targetId );

		if ( fromIndex === -1 || toIndex === -1 ) {
			return;
		}

		var insertIndex = toIndex;
		if ( fromRoot === toRoot ) {
			if ( fromIndex < toIndex ) {
				insertIndex = ( dropTarget.position === 'bottom' ) ? toIndex : ( toIndex - 1 );
			} else {
				insertIndex = ( dropTarget.position === 'bottom' ) ? ( toIndex + 1 ) : toIndex;
			}
		} else {
			insertIndex = ( dropTarget.position === 'bottom' ) ? ( toIndex + 1 ) : toIndex;
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
					var threshold = dragState.immediate ? 3 : 5;
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
