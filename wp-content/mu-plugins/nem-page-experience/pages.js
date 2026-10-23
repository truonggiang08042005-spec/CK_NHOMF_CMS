(() => {
  'use strict';
  const ready = () => {
    const $ = (selector, root=document) => root.querySelector(selector);
    const $$ = (selector, root=document) => [...root.querySelectorAll(selector)];
    const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const read = (key) => { try { const data=JSON.parse(localStorage.getItem(key)); return Array.isArray(data)?data:[]; } catch { return []; } };
    const write = (key,value) => { try { localStorage.setItem(key,JSON.stringify(value)); } catch {} };
    let toastTimer;
    const toast = text => { const el=$('.npe-toast'); if(!el)return; el.textContent=text; el.classList.add('is-visible'); clearTimeout(toastTimer); toastTimer=setTimeout(()=>el.classList.remove('is-visible'),2600); };
    const menu=$('.npe-menu-toggle'), nav=$('#npe-navigation'), search=$('#npe-search'), searchToggle=$('.npe-search-toggle');
    const closeMenu=()=>{nav?.classList.remove('is-open');menu?.setAttribute('aria-expanded','false');};
    menu?.addEventListener('click',()=>{const open=menu.getAttribute('aria-expanded')!=='true';nav?.classList.toggle('is-open',open);menu.setAttribute('aria-expanded',String(open));});
    searchToggle?.addEventListener('click',()=>{search.hidden=!search.hidden;searchToggle.setAttribute('aria-expanded',String(!search.hidden));if(!search.hidden)$('input[type=search]',search)?.focus();});
    document.addEventListener('keydown',event=>{if(event.key==='Escape'){closeMenu();if(search&&!search.hidden){search.hidden=true;searchToggle?.setAttribute('aria-expanded','false');searchToggle?.focus();}}});
    document.addEventListener('click',event=>{if(!event.target.closest('.npe-header'))closeMenu();});
    const header=$('.npe-header'),top=$('.npe-top');
    const onScroll=()=>{header?.classList.toggle('is-scrolled',scrollY>25);if(top)top.hidden=scrollY<500;};
    addEventListener('scroll',onScroll,{passive:true});onScroll();top?.addEventListener('click',()=>scrollTo({top:0,behavior:reduced?'instant':'smooth'}));
    const hero=$('.npe-hero'),slides=$$('.npe-slide'),dots=$$('.npe-dots button');
    if(hero&&slides.length>1){
      let current=0,timer,paused=reduced,hover=false,focused=false;
      const show=index=>{current=(index+slides.length)%slides.length;slides.forEach((slide,i)=>{slide.classList.toggle('is-active',i===current);slide.inert=i!==current;slide.setAttribute('aria-hidden',String(i!==current));});dots.forEach((dot,i)=>dot.setAttribute('aria-pressed',String(i===current)));};
      const schedule=()=>{clearInterval(timer);if(!paused&&!hover&&!focused&&!document.hidden)timer=setInterval(()=>show(current+1),5500);};
      $('.npe-hero-prev')?.addEventListener('click',()=>{show(current-1);schedule();});$('.npe-hero-next')?.addEventListener('click',()=>{show(current+1);schedule();});
      dots.forEach((dot,i)=>dot.addEventListener('click',()=>{show(i);schedule();}));
      const pause=$('.npe-pause');if(pause){pause.textContent=paused?'▶':'Ⅱ';pause.setAttribute('aria-label',paused?'Bật tự chuyển banner':'Tạm dừng tự chuyển banner');pause.addEventListener('click',()=>{paused=!paused;pause.textContent=paused?'▶':'Ⅱ';pause.setAttribute('aria-label',paused?'Bật tự chuyển banner':'Tạm dừng tự chuyển banner');schedule();});}
      hero.addEventListener('mouseenter',()=>{hover=true;schedule();});hero.addEventListener('mouseleave',()=>{hover=false;schedule();});
      hero.addEventListener('focusin',()=>{focused=true;schedule();});hero.addEventListener('focusout',event=>{if(!hero.contains(event.relatedTarget)){focused=false;schedule();}});
      hero.addEventListener('keydown',event=>{if(event.key==='ArrowRight'||event.key==='ArrowLeft'){event.preventDefault();show(current+(event.key==='ArrowRight'?1:-1));schedule();}});
      let start=0;hero.addEventListener('touchstart',event=>{start=event.changedTouches[0].clientX;},{passive:true});hero.addEventListener('touchend',event=>{const distance=start-event.changedTouches[0].clientX;if(Math.abs(distance)>45){show(current+(distance>0?1:-1));schedule();}},{passive:true});
      document.addEventListener('visibilitychange',schedule);show(0);schedule();
    }
    $$('.npe-slider-wrap').forEach(wrap=>{const track=$('.npe-product-track',wrap);const move=direction=>track?.scrollBy({left:direction*track.clientWidth*.8,behavior:reduced?'instant':'smooth'});$('.npe-track-prev',wrap)?.addEventListener('click',()=>move(-1));$('.npe-track-next',wrap)?.addEventListener('click',()=>move(1));});
    $$('.npe-home-slider').forEach(wrap=>{
      const track=$('.npe-home-track',wrap),next=$('.npe-home-next',wrap);
      if(!track||!next)return;
      next.disabled=track.children.length<2;
      next.addEventListener('click',()=>{
        if(track.scrollWidth>track.clientWidth+2){
          const atEnd=track.scrollLeft+track.clientWidth>=track.scrollWidth-2;
          track.scrollTo({left:atEnd?0:track.scrollLeft+track.firstElementChild.getBoundingClientRect().width+parseFloat(getComputedStyle(track).gap||0),behavior:reduced?'instant':'smooth'});
        }else{
          track.append(track.firstElementChild);
        }
      });
    });
    const thumbs=$$('.npe-thumb'),main=$('.npe-main-photo img'),lightbox=$('.npe-lightbox');let photo=0;
    const setPhoto=(src,index)=>{if(!main||!src)return;main.removeAttribute('srcset');main.removeAttribute('sizes');main.src=src;if(Number.isInteger(index)){photo=index;thumbs.forEach((thumb,i)=>{thumb.classList.toggle('is-active',i===index);thumb.setAttribute('aria-pressed',String(i===index));});}if(lightbox?.open)$('img',lightbox).src=src;};
    thumbs.forEach((thumb,index)=>thumb.addEventListener('click',()=>setPhoto(thumb.dataset.full,index)));
    const thumbTrack=$('.npe-thumb-track');const scrollThumbs=direction=>thumbTrack?.scrollBy({top:direction*thumbTrack.clientHeight*.7,behavior:reduced?'instant':'smooth'});
    $('.npe-thumbs-up')?.addEventListener('click',()=>scrollThumbs(-1));$('.npe-thumbs-down')?.addEventListener('click',()=>scrollThumbs(1));
    const fitThumbnails=()=>{if(thumbTrack&&main)thumbTrack.style.maxHeight=innerWidth>760?`${main.getBoundingClientRect().height}px`:'';};
    main?.addEventListener('load',fitThumbnails);addEventListener('resize',fitThumbnails);fitThumbnails();
    const photoButton=$('.npe-main-photo'),finePointer=matchMedia('(hover: hover) and (pointer: fine)');
    const lens=document.createElement('span'),zoom=document.createElement('div');
    lens.className='npe-zoom-lens';zoom.className='npe-zoom-window';lens.hidden=zoom.hidden=true;
    lens.setAttribute('aria-hidden','true');zoom.setAttribute('aria-hidden','true');
    if(photoButton&&main){photoButton.append(lens);document.body.append(zoom);}
    const hideZoom=()=>{lens.hidden=zoom.hidden=true;};
    photoButton?.addEventListener('pointermove',event=>{
      if(event.pointerType!=='mouse'||!finePointer.matches||innerWidth<1000||!main?.naturalWidth||lightbox?.open){hideZoom();return;}
      const r=main.getBoundingClientRect(),left=r.right+10,width=Math.min(360,innerWidth-left-20),height=Math.min(360,innerHeight-20);
      if(width<220){hideZoom();return;}
      const scale=2,lw=width/scale,lh=height/scale;
      const x=Math.max(0,Math.min(r.width-lw,event.clientX-r.left-lw/2));
      const y=Math.max(0,Math.min(r.height-lh,event.clientY-r.top-lh/2));
      const b=photoButton.getBoundingClientRect();
      Object.assign(lens.style,{width:`${lw}px`,height:`${lh}px`,left:`${r.left-b.left+x}px`,top:`${r.top-b.top+y}px`});
      Object.assign(zoom.style,{left:`${left}px`,top:`${Math.max(10,Math.min(r.top,innerHeight-height-10))}px`,width:`${width}px`,height:`${height}px`,backgroundImage:`url(${JSON.stringify(main.currentSrc||main.src)})`,backgroundSize:`${r.width*scale}px ${r.height*scale}px`,backgroundPosition:`${-x*scale}px ${-y*scale}px`});
      lens.hidden=zoom.hidden=false;
    });
    photoButton?.addEventListener('pointerleave',hideZoom);
    photoButton?.addEventListener('click',hideZoom);
    main?.addEventListener('load',hideZoom);
    addEventListener('scroll',hideZoom,{passive:true});addEventListener('resize',hideZoom);
    document.addEventListener('keydown',event=>{if(event.key==='Escape')hideZoom();});
    thumbs.forEach((thumb,index)=>thumb.addEventListener('pointerenter',event=>{if(event.pointerType==='mouse'&&finePointer.matches){hideZoom();setPhoto(thumb.dataset.full,index);}}));
    $('.npe-main-photo')?.addEventListener('click',()=>{if(!main||!lightbox)return;$('img',lightbox).src=main.src;lightbox.showModal();});
    const changePhoto=direction=>{if(!thumbs.length)return;photo=(photo+direction+thumbs.length)%thumbs.length;setPhoto(thumbs[photo].dataset.full,photo);};
    $('.npe-lightbox-prev')?.addEventListener('click',()=>changePhoto(-1));$('.npe-lightbox-next')?.addEventListener('click',()=>changePhoto(1));
    lightbox?.addEventListener('keydown',event=>{if(['ArrowLeft','ArrowRight'].includes(event.key)){event.preventDefault();changePhoto(event.key==='ArrowRight'?1:-1);}});
    $$('dialog').forEach(dialog=>{ $('.npe-dialog-close',dialog)?.addEventListener('click',()=>dialog.close());dialog.addEventListener('click',event=>{if(event.target===dialog){const r=dialog.getBoundingClientRect();if(event.clientX<r.left||event.clientX>r.right||event.clientY<r.top||event.clientY>r.bottom)dialog.close();}});});
    $('[data-guide]')?.addEventListener('click',event=>{const dialog=$('.npe-guide-dialog');if(dialog){event.preventDefault();dialog.showModal();}});
    const simpleTable=$('.npe-simple-options'),simpleForm=$('.npe-purchase form.cart:not(.variations_form)');if(simpleTable&&simpleForm)simpleForm.prepend(simpleTable);
    const guideLink=$('[data-guide]'),variationButtons=$('.woocommerce-variation-add-to-cart'),simpleQuantity=$('.npe-purchase form.cart:not(.variations_form) .quantity');if(guideLink&&variationButtons)variationButtons.before(guideLink);else if(guideLink&&simpleQuantity)simpleQuantity.before(guideLink);
    const qty=$('.npe-purchase input.qty');if(qty){const row=document.createElement('div');row.className='npe-quantity-row';const label=document.createElement('label');label.htmlFor=qty.id;label.textContent='Số lượng';qty.parentElement.before(row);row.append(label,qty.parentElement);}
    const reviewForm=$('#review_form_wrapper');if(reviewForm){reviewForm.hidden=true;const toggle=document.createElement('button');toggle.type='button';toggle.className='npe-review-toggle';toggle.innerHTML='<svg aria-hidden="true" width="13" height="13" viewBox="0 0 20 20" fill="none" stroke="currentColor"><path d="M16 12a7 7 0 1 0-4 4l5 2-1-6Z"/></svg> Viết đánh giá';toggle.setAttribute('aria-controls','review_form_wrapper');toggle.setAttribute('aria-expanded','false');toggle.addEventListener('click',()=>{reviewForm.hidden=!reviewForm.hidden;toggle.setAttribute('aria-expanded',String(!reviewForm.hidden));if(!reviewForm.hidden)$('textarea',reviewForm)?.focus();});$('.npe-reviews>h2')?.after(toggle);}
    const favorite=$('.npe-wishlist');if(favorite){const id=Number(favorite.dataset.id);favorite.setAttribute('aria-pressed',String(read('npe_favorites').includes(id)));favorite.addEventListener('click',()=>{let saved=read('npe_favorites');const exists=saved.includes(id);saved=exists?saved.filter(value=>value!==id):[...saved,id];write('npe_favorites',saved);favorite.setAttribute('aria-pressed',String(!exists));toast(exists?'Đã bỏ lưu sản phẩm':'Đã lưu sản phẩm yêu thích');});}
    $$('.npe-purchase .quantity').forEach(quantity=>{const input=$('input.qty',quantity);if(!input)return;for(const [sign,label,step] of [['−','Giảm số lượng',-1],['+','Tăng số lượng',1]]){const button=document.createElement('button');button.type='button';button.className='npe-quantity-button';button.disabled=input.disabled;button.textContent=sign;button.setAttribute('aria-label',label);button.addEventListener('click',()=>{const unit=Number(input.step)||1,min=Number(input.min)||1,max=input.max?Number(input.max):Infinity;input.value=String(Math.max(min,Math.min(max,(Number(input.value)||min)+step*unit)));input.dispatchEvent(new Event('change',{bubbles:true}));});if(step<0)quantity.prepend(button);else quantity.append(button);}});
    const colorNames={'đen':'#191919','black':'#191919','trắng':'#f5f3eb','white':'#f5f3eb','nâu':'#85722e','brown':'#85722e','vàng':'#d4bd53','yellow':'#d4bd53','xanh':'#465d61','be':'#cbb79b','đỏ':'#a23237','hồng':'#d79ca5'};
    $$('.npe-purchase .variations select').forEach(select=>{
      const group=document.createElement('div');group.className='npe-options';group.setAttribute('role','group');group.setAttribute('aria-label',select.closest('tr')?.querySelector('label')?.textContent||'Chọn biến thể');
      const color=select.dataset.npeColor==='1'||/color|mau|màu/i.test(select.name);[...select.options].filter(option=>option.value).forEach(option=>{const button=document.createElement('button');button.type='button';button.dataset.value=option.value;button.setAttribute('aria-pressed','false');button.title=option.textContent;const label=document.createElement('span');label.textContent=option.textContent;button.append(label);if(color){button.className='npe-color';const key=Object.keys(colorNames).find(key=>option.textContent.toLowerCase().includes(key));button.style.setProperty('--npe-color',option.textContent.toLowerCase()==='xanh'?'#a3cedf':colorNames[key]||'#8c7c31');button.setAttribute('aria-label',option.textContent);}button.addEventListener('click',()=>{select.value=option.value;if(window.jQuery)window.jQuery(select).trigger('change');select.dispatchEvent(new Event('change',{bubbles:true}));});group.append(button);});
      select.classList.add('npe-variation-select');select.tabIndex=-1;select.after(group);
      const update=()=>{$$('button',group).forEach(button=>{const available=[...select.options].find(option=>option.value===button.dataset.value);button.disabled=!available||available.disabled;button.classList.toggle('is-selected',select.value===button.dataset.value);button.setAttribute('aria-pressed',String(select.value===button.dataset.value));});};
      select.addEventListener('change',update);if(window.jQuery)window.jQuery(select.closest('form')).on('woocommerce_update_variation_values found_variation reset_data',update);update();
    });
    // Auto-select initial variation options if none selected
    $$('.npe-purchase .variations select').forEach(select => {
      if (!select.value && select.options.length > 1) {
        const first = [...select.options].find(opt => opt.value);
        if (first) {
          select.value = first.value;
          if (window.jQuery) window.jQuery(select).trigger('change');
          select.dispatchEvent(new Event('change', { bubbles: true }));
        }
      }
    });
    if(window.jQuery){const form=window.jQuery('.npe-purchase form.variations_form');form.on('show_variation',(_event,variation,purchasable)=>{const buy=$('.npe-buy-now');if(buy)buy.disabled=!purchasable||!variation.is_in_stock;if(variation.image?.full_src)setPhoto(variation.image.full_src);});form.on('hide_variation',()=>{const buy=$('.npe-buy-now');if(buy)buy.disabled=true;});if(form.length){const buy=$('.npe-buy-now');if(buy)buy.disabled=!form.find('input.variation_id').val();}}
    document.addEventListener('click', event => {
      const buyBtn = event.target.closest('.npe-buy-now');
      if (!buyBtn) return;
      const form = buyBtn.closest('form.cart');
      if (!form) return;
      let hidden = form.querySelector('input[name="npe_buy_now"]');
      if (!hidden) {
        hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'npe_buy_now';
        hidden.value = '1';
        form.appendChild(hidden);
      } else {
        hidden.value = '1';
      }
    });
    const normalizeRecentPrice=product=>{
      const text=typeof product.price==='string'?product.price:'';
      const regular=text.match(/Original price was:\s*([\d.,]+\s*[₫đ])/i);
      const sale=text.match(/Current price is:\s*([\d.,]+\s*[₫đ])/i);
      if(regular&&sale){product.regularPrice=regular[1];product.salePrice=sale[1];product.price=sale[1];}
      else if(/Original price was:|Current price is:/i.test(text)){
        product.price=text.split(/Original price was:|Current price is:/i)[0].trim();
      }
      return product;
    };
    const record=$('#npe-current-product');let recent=read('npe_recent').filter(item=>item&&typeof item.name==='string'&&typeof item.url==='string'&&Number.isInteger(item.id)).map(normalizeRecentPrice);
    if(record){try{const product=JSON.parse(record.textContent);recent=[product,...recent.filter(item=>item.id!==product.id)].slice(0,12);write('npe_recent',recent);}catch{}}
    write('npe_recent',recent);
    const recentSection=$('.npe-recent'),recentTrack=$('.npe-product-track',recentSection||document);
    if(recentSection&&recentTrack){let count=0;for(const product of recent){try{if(new URL(product.url,location.href).origin!==location.origin)continue;const article=document.createElement('article');article.className='npe-card';const link=document.createElement('a');link.href=product.url;const photo=document.createElement('div');photo.className='npe-card-photo';if(typeof product.image==='string'&&/^https?:/.test(product.image)){const image=document.createElement('img');image.src=product.image;image.alt=product.name;image.loading='lazy';image.className='npe-primary';photo.append(image);if(typeof product.secondary==='string'&&/^https?:/.test(product.secondary)){const alternate=document.createElement('img');alternate.src=product.secondary;alternate.alt=product.name+' — góc chụp khác';alternate.loading='lazy';alternate.className='npe-secondary';photo.append(alternate);}}const overlay=document.createElement('span');overlay.className='npe-card-hover';overlay.setAttribute('aria-hidden','true');const more=document.createElement('span');more.textContent='XEM THÊM';overlay.append(more);photo.append(overlay);const name=document.createElement('h3');name.textContent=product.name;const price=document.createElement('div');price.className='npe-card-price';if(typeof product.regularPrice==='string'&&typeof product.salePrice==='string'&&product.regularPrice&&product.salePrice){const sale=document.createElement('ins'),regular=document.createElement('del');sale.textContent=product.salePrice;regular.textContent=product.regularPrice;price.append(sale,document.createTextNode(' '),regular);}else{price.textContent=product.price||'';}link.append(photo,name);article.append(link,price);recentTrack.append(article);count++;}catch{}}recentSection.hidden=count===0;}
  };
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',ready);else ready();
})();
